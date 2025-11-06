<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Transaction;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\ApiErrorException;
use Stripe\PaymentIntent;
use Stripe\Refund;
use Stripe\Stripe;
use Stripe\Webhook;

class PaymentService
{
    public function __construct()
    {
        Stripe::setApiKey(config('payment.gateways.stripe.secret'));
    }

    /**
     * Create a Stripe Payment Intent for an order.
     */
    public function createPaymentIntent(Order $order, array $metadata = []): PaymentIntent
    {
        $idempotencyKey = 'pi_' . $order->id . '_' . time();

        try {
            $paymentIntent = PaymentIntent::create([
                'amount' => (int) ($order->total * 100), // Convert to cents
                'currency' => strtolower($order->currency ?? 'usd'),
                'metadata' => array_merge([
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'user_id' => $order->user_id,
                    'photographer_id' => $order->photographer_id,
                ], $metadata),
                'automatic_payment_methods' => [
                    'enabled' => true,
                ],
                'confirmation_method' => 'manual',
                'confirm' => false,
            ], [
                'idempotency_key' => $idempotencyKey,
            ]);

            Log::info('Payment Intent created', [
                'order_id' => $order->id,
                'payment_intent_id' => $paymentIntent->id,
                'amount' => $order->total,
            ]);

            return $paymentIntent;
        } catch (ApiErrorException $e) {
            Log::error('Failed to create Payment Intent', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Confirm a Payment Intent with payment method.
     */
    public function confirmPaymentIntent(string $paymentIntentId, string $paymentMethodId): PaymentIntent
    {
        try {
            $paymentIntent = PaymentIntent::retrieve($paymentIntentId);
            
            $paymentIntent->confirm([
                'payment_method' => $paymentMethodId,
                'return_url' => route('orders.show', ['order' => $paymentIntent->metadata->order_id ?? null]),
            ]);

            return $paymentIntent;
        } catch (ApiErrorException $e) {
            Log::error('Failed to confirm Payment Intent', [
                'payment_intent_id' => $paymentIntentId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Retrieve a Payment Intent by ID.
     */
    public function retrievePaymentIntent(string $paymentIntentId): PaymentIntent
    {
        try {
            return PaymentIntent::retrieve($paymentIntentId);
        } catch (ApiErrorException $e) {
            Log::error('Failed to retrieve Payment Intent', [
                'payment_intent_id' => $paymentIntentId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Create a transaction record from a successful payment.
     */
    public function createTransaction(Order $order, PaymentIntent $paymentIntent): Transaction
    {
        $charge = $paymentIntent->charges->data[0] ?? null;

        return Transaction::create([
            'order_id' => $order->id,
            'payment_gateway' => 'stripe',
            'gateway_transaction_id' => $paymentIntent->id,
            'amount' => $paymentIntent->amount / 100, // Convert from cents
            'currency' => strtoupper($paymentIntent->currency),
            'status' => $this->mapStripeStatus($paymentIntent->status),
            'payment_method' => $charge->payment_method_details->type ?? 'card',
            'metadata' => [
                'payment_intent_id' => $paymentIntent->id,
                'charge_id' => $charge->id ?? null,
                'payment_method_id' => $paymentIntent->payment_method ?? null,
                'status' => $paymentIntent->status,
                'receipt_url' => $charge->receipt_url ?? null,
            ],
        ]);
    }

    /**
     * Process a refund for an order.
     */
    public function refundPayment(Transaction $transaction, ?float $amount = null): Refund
    {
        $chargeId = $transaction->metadata['charge_id'] ?? null;

        if (!$chargeId) {
            throw new \InvalidArgumentException('Charge ID not found in transaction metadata');
        }

        $refundAmount = $amount ?? $transaction->amount;
        $idempotencyKey = 'refund_' . $transaction->id . '_' . time();

        try {
            $refund = Refund::create([
                'charge' => $chargeId,
                'amount' => (int) ($refundAmount * 100), // Convert to cents
                'metadata' => [
                    'transaction_id' => $transaction->id,
                    'order_id' => $transaction->order_id,
                ],
            ], [
                'idempotency_key' => $idempotencyKey,
            ]);

            // Update transaction status
            $transaction->update([
                'status' => $amount && $amount < $transaction->amount ? 'processing' : 'refunded',
                'metadata' => array_merge($transaction->metadata ?? [], [
                    'refund_id' => $refund->id,
                    'refund_amount' => $refundAmount,
                    'refunded_at' => now()->toIso8601String(),
                ]),
            ]);

            Log::info('Refund processed', [
                'transaction_id' => $transaction->id,
                'refund_id' => $refund->id,
                'amount' => $refundAmount,
            ]);

            return $refund;
        } catch (ApiErrorException $e) {
            Log::error('Failed to process refund', [
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Verify webhook signature.
     */
    public function verifyWebhookSignature(string $payload, string $signature): bool
    {
        $webhookSecret = config('payment.gateways.stripe.webhook_secret');

        if (!$webhookSecret) {
            Log::warning('Webhook secret not configured');
            return false;
        }

        try {
            Webhook::constructEvent($payload, $signature, $webhookSecret);
            return true;
        } catch (\Exception $e) {
            Log::error('Webhook signature verification failed', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Map Stripe payment intent status to transaction status.
     */
    private function mapStripeStatus(string $stripeStatus): string
    {
        return match ($stripeStatus) {
            'succeeded' => 'completed',
            'processing' => 'processing',
            'requires_payment_method', 'requires_confirmation', 'requires_action' => 'pending',
            'canceled' => 'cancelled',
            default => 'failed',
        };
    }

    /**
     * Get Stripe publishable key for frontend.
     */
    public function getPublishableKey(): string
    {
        return config('payment.gateways.stripe.key');
    }

    /**
     * Ensure a payment intent belongs to the supplied order and matches the expected totals.
     */
    public function assertIntentMatchesOrder(Order $order, PaymentIntent $paymentIntent): void
    {
        $metadataOrderId = $paymentIntent->metadata['order_id'] ?? null;
        if ((int) $metadataOrderId !== (int) $order->id) {
            throw new \RuntimeException('Payment Intent does not belong to this order.');
        }

        $intentCurrency = strtolower((string) ($paymentIntent->currency ?? ''));
        $orderCurrency = strtolower((string) ($order->currency ?? 'usd'));
        if ($intentCurrency !== $orderCurrency) {
            throw new \RuntimeException('Payment Intent currency mismatch.');
        }

        $expectedAmount = (int) round($order->total * 100);
        $intentAmount = (int) ($paymentIntent->amount ?? 0);
        $amountReceived = (int) ($paymentIntent->amount_received ?? 0);
        
        // For succeeded payments, verify amount_received (what Stripe actually captured)
        // For pending/processing, verify intent amount (what we expect to receive)
        if ($paymentIntent->status === 'succeeded') {
            if ($amountReceived !== $expectedAmount) {
                throw new \RuntimeException(
                    "Payment amount received ({$amountReceived}) does not match order total ({$expectedAmount})."
                );
            }
        } else {
            if ($intentAmount !== $expectedAmount) {
                throw new \RuntimeException(
                    "Payment Intent amount ({$intentAmount}) does not match order total ({$expectedAmount})."
                );
            }
        }

        $metadataUserId = $paymentIntent->metadata['user_id'] ?? null;
        if ($metadataUserId !== null && (int) $metadataUserId !== (int) ($order->user_id ?? 0)) {
            throw new \RuntimeException('Payment Intent user mismatch.');
        }

        $metadataPhotographerId = $paymentIntent->metadata['photographer_id'] ?? null;
        if ($metadataPhotographerId !== null && (int) $metadataPhotographerId !== (int) ($order->photographer_id ?? 0)) {
            throw new \RuntimeException('Payment Intent photographer mismatch.');
        }
    }
}

