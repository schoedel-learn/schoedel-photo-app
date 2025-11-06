<?php

namespace App\Services;

use App\Models\Photo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class ThumbnailService
{
    /**
     * Default thumbnail sizes.
     */
    private const THUMBNAIL_SIZES = [
        'thumb' => 150,
        'medium' => 400,
        'large' => 1200,
    ];

    /**
     * Generate thumbnails for a photo.
     *
     * @param Photo $photo
     * @param array $sizes Optional custom sizes
     * @return array Paths of generated thumbnails
     */
    public function generate(Photo $photo, array $sizes = null): array
    {
        $sizes = $sizes ?? self::THUMBNAIL_SIZES;
        $thumbnailPaths = [];

        // Get the original image from storage
        $tempPath = null;
        
        // If original is in cloud, download temporarily
        if ($photo->storage_disk !== 'local' && $photo->storage_disk !== 'public') {
            $tempPath = storage_path('app/temp/' . Str::uuid() . '_' . basename($photo->storage_path));
            $originalContent = Storage::disk($photo->storage_disk)->get($photo->storage_path);
            file_put_contents($tempPath, $originalContent);
            $originalPath = $tempPath;
        } else {
            $originalPath = Storage::disk($photo->storage_disk)->path($photo->storage_path);
        }

        try {
            $manager = new ImageManager(new Driver());
            $image = $manager->read($originalPath);

            foreach ($sizes as $sizeName => $maxDimension) {
                $thumbnailPath = $this->generateThumbnail($image, $photo, $sizeName, $maxDimension);
                if ($thumbnailPath) {
                    $thumbnailPaths[$sizeName] = $thumbnailPath;
                }
            }
        } catch (\Exception $e) {
            // Log error but don't fail the entire process
            \Log::error('Thumbnail generation failed', [
                'photo_id' => $photo->id,
                'error' => $e->getMessage(),
            ]);
        } finally {
            // Clean up temporary file if created
            if ($tempPath && file_exists($tempPath)) {
                @unlink($tempPath);
            }
        }

        return $thumbnailPaths;
    }

    /**
     * Generate a single thumbnail.
     *
     * @param \Intervention\Image\Image $image
     * @param Photo $photo
     * @param string $sizeName
     * @param int $maxDimension
     * @return string|null Path to thumbnail
     */
    private function generateThumbnail($image, Photo $photo, string $sizeName, int $maxDimension): ?string
    {
        try {
            // Calculate dimensions maintaining aspect ratio
            $width = $photo->original_width;
            $height = $photo->original_height;

            if (!$width || !$height) {
                return null;
            }

            // Calculate new dimensions
            if ($width > $height) {
                $newWidth = min($maxDimension, $width);
                $newHeight = (int) ($height * ($newWidth / $width));
            } else {
                $newHeight = min($maxDimension, $height);
                $newWidth = (int) ($width * ($newHeight / $height));
            }

            // Resize image maintaining aspect ratio
            $image->scale($newWidth, $newHeight);

            // Generate thumbnail path
            $thumbnailFilename = $this->getThumbnailFilename($photo->filename, $sizeName);
            $thumbnailPath = $this->getThumbnailPath($photo, $thumbnailFilename);

            // Save thumbnail
            $thumbnailDisk = 'thumbnails';
            $quality = $sizeName === 'thumb' ? 85 : 90;

            // Ensure directory exists
            $directory = dirname($thumbnailPath);
            if (!Storage::disk($thumbnailDisk)->exists($directory)) {
                Storage::disk($thumbnailDisk)->makeDirectory($directory);
            }

            // Convert to JPEG and save
            $thumbnailContent = (string) $image->toJpeg($quality);
            Storage::disk($thumbnailDisk)->put($thumbnailPath, $thumbnailContent);

            return $thumbnailPath;
        } catch (\Exception $e) {
            \Log::error("Failed to generate thumbnail {$sizeName}", [
                'photo_id' => $photo->id,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Get thumbnail filename.
     *
     * @param string $originalFilename
     * @param string $sizeName
     * @return string
     */
    private function getThumbnailFilename(string $originalFilename, string $sizeName): string
    {
        $extension = pathinfo($originalFilename, PATHINFO_EXTENSION);
        $nameWithoutExt = pathinfo($originalFilename, PATHINFO_FILENAME);
        return "{$nameWithoutExt}_{$sizeName}.jpg";
    }

    /**
     * Get thumbnail storage path.
     *
     * @param Photo $photo
     * @param string $thumbnailFilename
     * @return string
     */
    private function getThumbnailPath(Photo $photo, string $thumbnailFilename): string
    {
        $year = date('Y', strtotime($photo->created_at));
        $month = date('m', strtotime($photo->created_at));
        return "galleries/{$photo->gallery_id}/{$year}/{$month}/thumbnails/{$thumbnailFilename}";
    }
}

