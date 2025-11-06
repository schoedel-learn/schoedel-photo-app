<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\PaymentService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessPayment implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $orderId,
        public string $paymentIntentId
    ) {}

    /**
     * Execute the job.
     */
    public function handle(PaymentService $paymentService): void
    {
        try {
            /** @var Order|null $order */
            $order = Order::with(['transactions', 'user'])
                ->find($this->orderId);

            if (!$order) {
                Log::warning('Order not found for payment processing job', [
                    'order_id' => $this->orderId,
                    'payment_intent_id' => $this->paymentIntentId,
                ]);
                return;
            }

            // Verify payment intent status
            $paymentIntent = $paymentService->retrievePaymentIntent($this->paymentIntentId);
            $paymentService->assertIntentMatchesOrder($order, $paymentIntent);

            if ($paymentIntent->status !== 'succeeded') {
                Log::warning('Payment Intent not succeeded', [
                    'order_id' => $order->id,
                    'payment_intent_id' => $this->paymentIntentId,
                    'status' => $paymentIntent->status,
                ]);
                return;
            }

            $transaction = null;

            DB::transaction(function () use (&$order, &$transaction, $paymentService, $paymentIntent) {
                $lockedOrder = Order::with('transactions')
                    ->whereKey($order->id)
                    ->lockForUpdate()
                    ->first();

                if (!$lockedOrder) {
                    throw new \RuntimeException('Unable to acquire order lock.');
                }

                $transaction = $lockedOrder->transactions()
                    ->where('gateway_transaction_id', $paymentIntent->id)
                    ->first();

                if ($transaction && $transaction->status === 'completed') {
                    $order = $lockedOrder;
                    return;
                }

                if (!$transaction) {
                    $transaction = $paymentService->createTransaction($lockedOrder, $paymentIntent);
                } else {
                    $charge = $paymentIntent->charges->data[0] ?? null;
                    $transaction->update([
                        'status' => 'completed',
                        'payment_method' => $charge->payment_method_details->type ?? 'card',
                        'metadata' => array_merge($transaction->metadata ?? [], [
                            'charge_id' => $charge->id ?? null,
                            'receipt_url' => $charge->receipt_url ?? null,
                            'processed_at' => now()->toIso8601String(),
                        ]),
                    ]);
                }

                // Update order status based on current state and order type
                if ($lockedOrder->status === 'pending') {
                    // Regular pending order becomes processing after payment
                    if ($lockedOrder->order_type === 'pre_order') {
                        // This shouldn't happen - pre-orders should use 'pre_order_pending'
                        Log::warning('Pre-order has status=pending instead of pre_order_pending', [
                            'order_id' => $lockedOrder->id,
                            'order_type' => $lockedOrder->order_type,
                        ]);
                        $lockedOrder->update(['status' => 'pre_order_paid']);
                    } else {
                        $lockedOrder->update(['status' => 'processing']);
                    }
                } elseif ($lockedOrder->status === 'pre_order_pending') {
                    // Pre-order pending becomes pre_order_paid after payment
                    if ($lockedOrder->order_type !== 'pre_order') {
                        // This shouldn't happen - only pre-orders should use this status
                        Log::warning('Non pre-order has status=pre_order_pending', [
                            'order_id' => $lockedOrder->id,
                            'order_type' => $lockedOrder->order_type,
                        ]);
                        $lockedOrder->update(['status' => 'processing']);
                    } else {
                        $lockedOrder->update(['status' => 'pre_order_paid']);
                    }
                }

                $order = $lockedOrder->fresh(['transactions', 'user']);
            });

            if (!$transaction) {
                Log::warning('Payment processing job found no transaction to update', [
                    'order_id' => $order->id,
                    'payment_intent_id' => $this->paymentIntentId,
                ]);
                return;
            }

            $order->loadMissing(['items.photo']);

            // Generate download links for purchased photos
            // Only generate if no downloads exist yet (idempotency)
            $existingDownloads = \App\Models\Download::where('order_id', $order->id)->exists();
            
            if (!$existingDownloads) {
                $downloadService = app(\App\Services\DownloadService::class);
                $downloadService->generateDownloadLinksForOrder($order);
                
                Log::info('Download links generated for order', [
                    'order_id' => $order->id,
                ]);
            } else {
                Log::info('Download links already exist for order, skipping generation', [
                    'order_id' => $order->id,
                ]);
            }

            // Send payment receipt email with download links
            if ($order->user && !$order->user->email_unsubscribed) {
                \Illuminate\Support\Facades\Mail::to($order->user->email)
                    ->send(new \App\Mail\PaymentReceiptMail($order, $transaction));
            }

            Log::info('Payment processed successfully', [
                'order_id' => $order->id,
                'transaction_id' => $transaction->id,
                'payment_intent_id' => $this->paymentIntentId,
            ]);
        } catch (\Exception $e) {
            Log::error('Payment processing job failed', [
                'order_id' => $this->orderId,
                'payment_intent_id' => $this->paymentIntentId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Payment processing job failed permanently', [
            'order_id' => $this->orderId,
            'payment_intent_id' => $this->paymentIntentId,
            'error' => $exception->getMessage(),
        ]);
    }
}
