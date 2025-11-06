<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessPayment;
use App\Models\Order;
use App\Models\Transaction;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    public function __construct(
        private PaymentService $paymentService
    ) {}

    /**
     * Show payment form for an order.
     */
    public function show(Order $order)
    {
        // Check authorization
        $isOwner = false;
        if (Auth::guard('client')->check()) {
            $isOwner = $order->user_id === Auth::guard('client')->id();
        }

        if (!$isOwner) {
            // For staff, verify they are the photographer for this order
            if (!Auth::guard('staff')->check()) {
                abort(403, 'You do not have permission to view this payment page.');
            }
            
            if ($order->photographer_id !== Auth::guard('staff')->id()) {
                abort(403, 'You can only view payment pages for your own orders.');
            }
        }

        // Check if order is already paid
        if ($order->status !== 'pending' && $order->status !== 'pre_order_pending') {
            return redirect()->route('orders.show', $order)
                ->with('info', 'This order has already been processed.');
        }

        // Create or retrieve payment intent
        $paymentIntent = null;
        $existingTransaction = $order->transactions()
            ->where('payment_gateway', 'stripe')
            ->where('status', 'pending')
            ->first();

        if ($existingTransaction && isset($existingTransaction->metadata['payment_intent_id'])) {
            try {
                $paymentIntent = $this->paymentService->retrievePaymentIntent(
                    $existingTransaction->metadata['payment_intent_id']
                );
            } catch (\Exception $e) {
                Log::warning('Failed to retrieve existing payment intent', [
                    'transaction_id' => $existingTransaction->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if (!$paymentIntent) {
            $paymentIntent = $this->paymentService->createPaymentIntent($order);
            
            // Create pending transaction
            Transaction::create([
                'order_id' => $order->id,
                'payment_gateway' => 'stripe',
                'gateway_transaction_id' => $paymentIntent->id,
                'amount' => $order->total,
                'currency' => $order->currency ?? 'USD',
                'status' => 'pending',
                'payment_method' => 'card',
                'metadata' => [
                    'payment_intent_id' => $paymentIntent->id,
                    'client_secret' => $paymentIntent->client_secret,
                ],
            ]);
        }

        $order->load(['items.photo', 'photographer']);

        return view('payments.form', [
            'order' => $order,
            'paymentIntent' => $paymentIntent,
            'stripeKey' => $this->paymentService->getPublishableKey(),
        ]);
    }

    /**
     * Process payment submission.
     */
    public function process(Request $request, Order $order)
    {
        // Check authorization
        $isOwner = false;
        if (Auth::guard('client')->check()) {
            $isOwner = $order->user_id === Auth::guard('client')->id();
        }

        if (!$isOwner) {
            abort(403, 'You do not have permission to process this payment.');
        }

        $request->validate([
            'payment_intent_id' => ['required', 'string'],
            'payment_method_id' => ['required', 'string'],
        ]);

        try {
            $paymentIntent = $this->paymentService->confirmPaymentIntent(
                $request->payment_intent_id,
                $request->payment_method_id
            );

            $this->paymentService->assertIntentMatchesOrder($order, $paymentIntent);

            // Handle 3D Secure authentication
            if ($paymentIntent->status === 'requires_action') {
                return response()->json([
                    'requires_action' => true,
                    'client_secret' => $paymentIntent->client_secret,
                    'payment_intent_id' => $paymentIntent->id,
                ]);
            }

            // If payment succeeded, process it
            if ($paymentIntent->status === 'succeeded') {
                // Dispatch job to process payment
                ProcessPayment::dispatch($order->id, $paymentIntent->id);

                return response()->json([
                    'success' => true,
                    'message' => 'Payment processed successfully!',
                    'redirect' => route('orders.show', $order) . '?from_checkout=1',
                ]);
            }

            // Handle payment failure
            return response()->json([
                'success' => false,
                'error' => $paymentIntent->last_payment_error->message ?? 'Payment failed. Please try again.',
            ], 400);
        } catch (\RuntimeException $e) {
            Log::warning('Payment verification failed', [
                'order_id' => $order->id,
                'payment_intent_id' => $request->payment_intent_id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Payment verification failed. Please refresh and try again.',
            ], 422);
        } catch (\Exception $e) {
            Log::error('Payment processing failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'An error occurred while processing your payment. Please try again.',
            ], 500);
        }
    }

    /**
     * Handle Stripe webhooks.
     */
    public function webhook(Request $request)
    {
        $payload = $request->getContent();
        $signature = $request->header('Stripe-Signature');

        // Verify webhook signature
        if (!$this->paymentService->verifyWebhookSignature($payload, $signature)) {
            Log::warning('Webhook signature verification failed');
            abort(400, 'Invalid webhook signature');
        }

        $event = json_decode($payload, true);

        Log::info('Stripe webhook received', [
            'type' => $event['type'],
            'id' => $event['id'],
        ]);

        try {
            switch ($event['type']) {
                case 'payment_intent.succeeded':
                    $this->handlePaymentSucceeded($event['data']['object']);
                    break;

                case 'payment_intent.payment_failed':
                    $this->handlePaymentFailed($event['data']['object']);
                    break;

                case 'charge.refunded':
                    $this->handleRefunded($event['data']['object']);
                    break;

                default:
                    Log::info('Unhandled webhook event type', ['type' => $event['type']]);
            }

            return response()->json(['received' => true]);
        } catch (\Exception $e) {
            Log::error('Webhook processing failed', [
                'event_type' => $event['type'],
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(['error' => 'Webhook processing failed'], 500);
        }
    }

    /**
     * Process refund request (photographer only).
     */
    public function refund(Request $request, Order $order)
    {
        if (!Auth::guard('staff')->check()) {
            abort(403, 'Only photographers can process refunds.');
        }

        if ($order->photographer_id !== Auth::guard('staff')->id()) {
            abort(403, 'You can only refund orders for your own clients.');
        }

        // Find the most recent completed or processing transaction (processing = partial refund)
        $transaction = $order->transactions()
            ->where('payment_gateway', 'stripe')
            ->whereIn('status', ['completed', 'processing'])
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$transaction) {
            return back()->withErrors(['error' => 'No completed transaction found for this order.']);
        }

        // Check if already fully refunded
        if ($transaction->status === 'refunded') {
            return back()->withErrors(['error' => 'This transaction has already been fully refunded.']);
        }

        $request->validate([
            'amount' => ['nullable', 'numeric', 'min:0.01', 'max:' . $transaction->amount],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $refundAmount = $request->input('amount', $transaction->amount);
            $refund = $this->paymentService->refundPayment($transaction, $refundAmount);

            // Update order status
            if ($refundAmount >= $transaction->amount) {
                $order->update(['status' => 'refunded']);
            }

            // TODO: Send refund notification email

            return redirect()->route('orders.show', $order)
                ->with('success', 'Refund processed successfully.');
        } catch (\Exception $e) {
            Log::error('Refund processing failed', [
                'order_id' => $order->id,
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage(),
            ]);

            return back()->withErrors(['error' => 'Failed to process refund. Please try again.']);
        }
    }

    /**
     * Handle payment_intent.succeeded webhook.
     */
    private function handlePaymentSucceeded(array $paymentIntent): void
    {
        // Idempotency: Check if this webhook event was already processed
        // Stripe retries webhooks, so we need to prevent duplicate processing
        $eventId = request()->input('id'); // Webhook event ID from Stripe
        if ($eventId) {
            $cacheKey = "webhook_processed:{$eventId}";
            
            // Check if already processed
            if (Cache::has($cacheKey)) {
                Log::info('Webhook already processed (idempotency check)', [
                    'event_id' => $eventId,
                    'payment_intent_id' => $paymentIntent['id'],
                ]);
                return;
            }
            
            // Mark as processed for 48 hours (Stripe retries for 24h)
            Cache::put($cacheKey, true, now()->addHours(48));
        }

        $orderId = $paymentIntent['metadata']['order_id'] ?? null;

        if (!$orderId) {
            Log::warning('Payment Intent succeeded but no order_id in metadata', [
                'payment_intent_id' => $paymentIntent['id'],
            ]);
            return;
        }

        $order = Order::find($orderId);

        if (!$order) {
            Log::warning('Order not found for successful payment', [
                'order_id' => $orderId,
                'payment_intent_id' => $paymentIntent['id'],
            ]);
            return;
        }

        // Process payment asynchronously via job
        ProcessPayment::dispatch($order->id, $paymentIntent['id']);
    }

    /**
     * Handle payment_intent.payment_failed webhook.
     */
    private function handlePaymentFailed(array $paymentIntent): void
    {
        // Idempotency check
        $eventId = request()->input('id');
        if ($eventId) {
            $cacheKey = "webhook_processed:{$eventId}";
            if (Cache::has($cacheKey)) {
                Log::info('Webhook already processed (idempotency check)', [
                    'event_id' => $eventId,
                    'payment_intent_id' => $paymentIntent['id'],
                ]);
                return;
            }
            Cache::put($cacheKey, true, now()->addHours(48));
        }

        $orderId = $paymentIntent['metadata']['order_id'] ?? null;

        if (!$orderId) {
            return;
        }

        $order = Order::find($orderId);
        $transaction = Transaction::where('gateway_transaction_id', $paymentIntent['id'])->first();

        if ($transaction) {
            $transaction->update([
                'status' => 'failed',
                'metadata' => array_merge($transaction->metadata ?? [], [
                    'failure_reason' => $paymentIntent['last_payment_error']['message'] ?? 'Unknown error',
                    'failed_at' => now()->toIso8601String(),
                ]),
            ]);
        }

        Log::info('Payment failed', [
            'order_id' => $orderId,
            'payment_intent_id' => $paymentIntent['id'],
            'error' => $paymentIntent['last_payment_error']['message'] ?? 'Unknown error',
        ]);
    }

    /**
     * Handle charge.refunded webhook.
     */
    private function handleRefunded(array $charge): void
    {
        // Idempotency check
        $eventId = request()->input('id');
        if ($eventId) {
            $cacheKey = "webhook_processed:{$eventId}";
            if (Cache::has($cacheKey)) {
                Log::info('Webhook already processed (idempotency check)', [
                    'event_id' => $eventId,
                    'charge_id' => $charge['id'],
                ]);
                return;
            }
            Cache::put($cacheKey, true, now()->addHours(48));
        }

        // Find transaction by charge ID
        $transactions = Transaction::where('payment_gateway', 'stripe')
            ->whereJsonContains('metadata->charge_id', $charge['id'])
            ->get();

        foreach ($transactions as $transaction) {
            $transaction->update([
                'status' => 'refunded',
                'metadata' => array_merge($transaction->metadata ?? [], [
                    'refund_id' => $charge['refunds']['data'][0]['id'] ?? null,
                    'refund_amount' => $charge['amount_refunded'] / 100,
                    'refunded_at' => now()->toIso8601String(),
                ]),
            ]);

            // Update order status if fully refunded
            if ($charge['amount_refunded'] >= $charge['amount']) {
                $transaction->order->update(['status' => 'refunded']);
            }
        }
    }
}
