<?php

namespace Database\Factories;

use App\Models\Gallery;
use App\Models\Photo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Photo>
 */
class PhotoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $width = fake()->randomElement([1920, 2560, 3840, 4096]);
        $height = fake()->randomElement([1080, 1440, 2160, 2732]);
        $filename = fake()->uuid() . '.jpg';
        $storagePath = 'photos/' . date('Y/m') . '/' . $filename;

        return [
            'gallery_id' => Gallery::factory(),
            'filename' => $filename,
            'storage_path' => $storagePath,
            'storage_disk' => fake()->randomElement(['local', 's3', 'public']),
            'original_width' => $width,
            'original_height' => $height,
            'file_size' => fake()->numberBetween(1000000, 10000000), // 1MB to 10MB
            'metadata' => [
                'camera' => fake()->randomElement(['Canon EOS R5', 'Nikon D850', 'Sony A7R IV', 'Fujifilm X-T4']),
                'iso' => fake()->randomElement([100, 200, 400, 800, 1600, 3200]),
                'aperture' => fake()->randomElement(['f/1.4', 'f/2.8', 'f/4', 'f/5.6', 'f/8', 'f/11']),
                'shutter_speed' => fake()->randomElement(['1/125', '1/250', '1/500', '1/1000', '1/2000']),
                'focal_length' => fake()->numberBetween(14, 200) . 'mm',
                'quality_score' => fake()->randomFloat(2, 70, 100),
                'categories' => fake()->randomElements(['portrait', 'landscape', 'wedding', 'event', 'nature'], fake()->numberBetween(1, 3)),
            ],
            'is_featured' => fake()->boolean(20),
            'sort_order' => fake()->numberBetween(0, 1000),
        ];
    }

    /**
     * Indicate that the photo is featured.
     */
    public function featured(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_featured' => true,
        ]);
    }

    /**
     * Indicate that the photo has AI metadata.
     */
    public function withAiMetadata(): static
    {
        return $this->state(function (array $attributes) {
            $metadata = $attributes['metadata'] ?? [];
            $metadata['ai_tags'] = fake()->words(5);
            $metadata['face_data'] = [
                'detected' => fake()->boolean(70),
                'count' => fake()->numberBetween(0, 10),
            ];

            return [
                'metadata' => $metadata,
            ];
        });
    }
}

