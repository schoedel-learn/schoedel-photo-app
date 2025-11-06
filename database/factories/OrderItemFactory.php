<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Photo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\OrderItem>
 */
class OrderItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $quantity = fake()->numberBetween(1, 5);
        $unitPrice = fake()->randomFloat(2, 5, 100);
        $totalPrice = $quantity * $unitPrice;

        return [
            'order_id' => Order::factory(),
            'photo_id' => fake()->boolean(70) ? Photo::factory() : null,
            'product_type' => fake()->randomElement(['photo', 'print', 'digital_download', 'session_fee']),
            'product_details' => [
                'format' => fake()->randomElement(['digital', 'print_4x6', 'print_8x10', 'print_11x14', 'canvas']),
                'size' => fake()->randomElement(['small', 'medium', 'large', 'original']),
            ],
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'total_price' => $totalPrice,
        ];
    }

    /**
     * Indicate that the order item is for a photo.
     */
    public function photo(): static
    {
        return $this->state(fn (array $attributes) => [
            'product_type' => 'photo',
            'photo_id' => Photo::factory(),
        ]);
    }

    /**
     * Indicate that the order item is for a print.
     */
    public function print(): static
    {
        return $this->state(fn (array $attributes) => [
            'product_type' => 'print',
            'product_details' => [
                'format' => fake()->randomElement(['print_4x6', 'print_8x10', 'print_11x14', 'canvas']),
                'size' => fake()->randomElement(['small', 'medium', 'large']),
            ],
        ]);
    }

    /**
     * Indicate that the order item is for a digital download.
     */
    public function digitalDownload(): static
    {
        return $this->state(fn (array $attributes) => [
            'product_type' => 'digital_download',
            'product_details' => [
                'format' => 'digital',
                'size' => fake()->randomElement(['small', 'medium', 'large', 'original']),
            ],
        ]);
    }
}

