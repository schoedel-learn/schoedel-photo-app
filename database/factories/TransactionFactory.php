<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Transaction>
 */
class TransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $paymentGateway = fake()->randomElement(['stripe', 'paypal', 'square']);

        return [
            'order_id' => Order::factory(),
            'payment_gateway' => $paymentGateway,
            'gateway_transaction_id' => $this->generateGatewayTransactionId($paymentGateway),
            'amount' => fake()->randomFloat(2, 10, 1000),
            'currency' => 'USD',
            'status' => fake()->randomElement(['pending', 'processing', 'completed', 'failed', 'cancelled', 'refunded']),
            'payment_method' => fake()->randomElement(['card', 'paypal', 'bank_transfer', 'apple_pay', 'google_pay']),
            'metadata' => [
                'ip_address' => fake()->ipv4(),
                'user_agent' => fake()->userAgent(),
                'processed_at' => fake()->dateTimeBetween('-1 week', 'now')->format('Y-m-d H:i:s'),
            ],
        ];
    }

    /**
     * Generate a realistic gateway transaction ID based on the gateway.
     */
    private function generateGatewayTransactionId(string $gateway): string
    {
        return match ($gateway) {
            'stripe' => 'ch_' . fake()->bothify('####################'),
            'paypal' => fake()->bothify('PAY-####################'),
            'square' => fake()->bothify('sq_####################'),
            default => fake()->uuid(),
        };
    }

    /**
     * Indicate that the transaction is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
        ]);
    }

    /**
     * Indicate that the transaction is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
        ]);
    }

    /**
     * Indicate that the transaction failed.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
        ]);
    }

    /**
     * Indicate that the transaction uses Stripe.
     */
    public function stripe(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_gateway' => 'stripe',
            'gateway_transaction_id' => 'ch_' . fake()->bothify('####################'),
            'payment_method' => 'card',
        ]);
    }

    /**
     * Indicate that the transaction uses PayPal.
     */
    public function paypal(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_gateway' => 'paypal',
            'gateway_transaction_id' => fake()->bothify('PAY-####################'),
            'payment_method' => 'paypal',
        ]);
    }
}

