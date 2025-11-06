<?php

namespace Database\Factories;

use App\Models\Gallery;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Gallery>
 */
class GalleryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->words(3, true);

        return [
            'user_id' => User::factory(),
            'name' => ucwords($name),
            'slug' => Str::slug($name),
            'description' => fake()->paragraph(),
            'access_type' => fake()->randomElement(['public', 'private', 'password_protected']),
            'password_hash' => fake()->boolean(30) ? bcrypt('password') : null,
            'expires_at' => fake()->boolean(40) ? fake()->dateTimeBetween('+1 week', '+1 year') : null,
            'published_at' => fake()->boolean(70) ? fake()->dateTimeBetween('-1 month', 'now') : null,
            'settings' => [
                'watermark_enabled' => fake()->boolean(30),
                'watermark_position' => fake()->randomElement(['bottom-right', 'bottom-left', 'top-right', 'top-left', 'center']),
                'download_allowed' => fake()->boolean(50),
                'share_enabled' => fake()->boolean(60),
            ],
        ];
    }

    /**
     * Indicate that the gallery is public.
     */
    public function public(): static
    {
        return $this->state(fn (array $attributes) => [
            'access_type' => 'public',
            'password_hash' => null,
        ]);
    }

    /**
     * Indicate that the gallery is private.
     */
    public function private(): static
    {
        return $this->state(fn (array $attributes) => [
            'access_type' => 'private',
            'password_hash' => null,
        ]);
    }

    /**
     * Indicate that the gallery is password protected.
     */
    public function passwordProtected(): static
    {
        return $this->state(fn (array $attributes) => [
            'access_type' => 'password_protected',
            'password_hash' => bcrypt('password'),
        ]);
    }

    /**
     * Indicate that the gallery is published.
     */
    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'published_at' => now(),
        ]);
    }

    /**
     * Indicate that the gallery has expired.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => fake()->dateTimeBetween('-1 year', '-1 day'),
        ]);
    }
}

