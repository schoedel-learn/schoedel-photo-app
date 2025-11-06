<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subtotal = fake()->randomFloat(2, 10, 1000);
        $tax = $subtotal * 0.08; // 8% tax
        $total = $subtotal + $tax;

        return [
            'user_id' => User::factory(),
            'photographer_id' => User::factory(),
            'order_type' => fake()->randomElement([
                'photo_purchase',
                'print_order',
                'digital_download',
                'session_fee',
                'subscription',
                'swag',
            ]),
            'status' => fake()->randomElement(['pending', 'processing', 'completed', 'cancelled', 'refunded']),
            'subtotal' => $subtotal,
            'tax' => $tax,
            'total' => $total,
            'currency' => 'USD',
            'billing_address' => [
                'name' => fake()->name(),
                'street' => fake()->streetAddress(),
                'city' => fake()->city(),
                'state' => fake()->stateAbbr(),
                'zip' => fake()->postcode(),
                'country' => 'US',
            ],
            'shipping_address' => fake()->boolean(70) ? [
                'name' => fake()->name(),
                'street' => fake()->streetAddress(),
                'city' => fake()->city(),
                'state' => fake()->stateAbbr(),
                'zip' => fake()->postcode(),
                'country' => 'US',
            ] : null,
            'notes' => fake()->boolean(30) ? fake()->sentence() : null,
        ];
    }

    /**
     * Indicate that the order is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
        ]);
    }

    /**
     * Indicate that the order is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
        ]);
    }

    /**
     * Indicate that the order is a photo purchase.
     */
    public function photoPurchase(): static
    {
        return $this->state(fn (array $attributes) => [
            'order_type' => 'photo_purchase',
        ]);
    }

    /**
     * Indicate that the order is a print order.
     */
    public function printOrder(): static
    {
        return $this->state(fn (array $attributes) => [
            'order_type' => 'print_order',
        ]);
    }

    /**
     * Indicate that the order is a digital download.
     */
    public function digitalDownload(): static
    {
        return $this->state(fn (array $attributes) => [
            'order_type' => 'digital_download',
        ]);
    }
}

