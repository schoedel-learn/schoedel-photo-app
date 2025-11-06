<?php

namespace App\Services;

use App\Models\Gallery;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class PhotoStorageService
{
    /**
     * Store the uploaded photo to cloud storage.
     *
     * @param UploadedFile $file
     * @param Gallery $gallery
     * @return array
     */
    public function store(UploadedFile $file, Gallery $gallery): array
    {
        $filename = $this->generateFilename($file);
        $storagePath = $this->getStoragePath($gallery, $filename);
        $disk = env('FILESYSTEM_DISK', config('filesystems.default', 's3'));

        // Extract metadata before storing (file might be moved)
        $metadata = $this->extractMetadata($file);

        // Store the original file
        // For S3/cloud storage, use putFileAs with public visibility
        // For local storage, the path structure will be maintained
        $storedPath = Storage::disk($disk)->putFileAs(
            dirname($storagePath),
            $file,
            basename($storagePath),
            'public'
        );

        // Use the stored path (might differ from expected path on some drivers)
        $finalPath = $storedPath ?: $storagePath;

        return [
            'filename' => $filename,
            'storage_path' => $finalPath,
            'storage_disk' => $disk,
            'original_width' => $metadata['width'],
            'original_height' => $metadata['height'],
            'file_size' => $metadata['file_size'],
            'metadata' => $metadata,
        ];
    }

    /**
     * Generate a unique filename using UUID.
     *
     * @param UploadedFile $file
     * @return string
     */
    public function generateFilename(UploadedFile $file): string
    {
        $extension = $file->getClientOriginalExtension();
        $uuid = Str::uuid();
        return "{$uuid}.{$extension}";
    }

    /**
     * Get the storage path for a photo in a gallery.
     *
     * @param Gallery $gallery
     * @param string $filename
     * @return string
     */
    public function getStoragePath(Gallery $gallery, string $filename): string
    {
        $year = date('Y');
        $month = date('m');
        return "galleries/{$gallery->id}/{$year}/{$month}/{$filename}";
    }

    /**
     * Extract metadata from uploaded file.
     *
     * @param UploadedFile $file
     * @return array
     */
    public function extractMetadata(UploadedFile $file): array
    {
        $metadata = [
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'original_name' => $file->getClientOriginalName(),
        ];

        // Get image dimensions
        try {
            $manager = new ImageManager(new Driver());
            $image = $manager->read($file->getRealPath());
            $metadata['width'] = $image->width();
            $metadata['height'] = $image->height();
        } catch (\Exception $e) {
            // If image reading fails, set defaults
            $metadata['width'] = null;
            $metadata['height'] = null;
        }

        // Extract EXIF data if available (only for JPEG/HEIC)
        $exifData = $this->extractExifData($file);
        if (!empty($exifData)) {
            $metadata['exif'] = $exifData;
        }

        return $metadata;
    }

    /**
     * Extract EXIF data from image file.
     *
     * @param UploadedFile $file
     * @return array
     */
    private function extractExifData(UploadedFile $file): array
    {
        $exifData = [];

        // Only extract EXIF for supported formats
        $mimeType = $file->getMimeType();
        if (!in_array($mimeType, ['image/jpeg', 'image/jpg'])) {
            return $exifData;
        }

        try {
            $realPath = $file->getRealPath();
            if (function_exists('exif_read_data') && file_exists($realPath)) {
                $exif = @exif_read_data($realPath);
                if ($exif !== false) {
                    // Extract useful EXIF data
                    $exifData = [
                        'camera' => $exif['Make'] ?? null,
                        'model' => $exif['Model'] ?? null,
                        'iso' => $exif['ISOSpeedRatings'] ?? null,
                        'aperture' => isset($exif['COMPUTED']['ApertureFNumber']) ? $exif['COMPUTED']['ApertureFNumber'] : null,
                        'shutter_speed' => isset($exif['ExposureTime']) ? $exif['ExposureTime'] : null,
                        'focal_length' => isset($exif['FocalLength']) ? $exif['FocalLength'] : null,
                        'date_taken' => isset($exif['DateTimeOriginal']) ? $exif['DateTimeOriginal'] : null,
                        'orientation' => $exif['Orientation'] ?? null,
                    ];

                    // Clean up null values
                    $exifData = array_filter($exifData, fn($value) => $value !== null);
                }
            }
        } catch (\Exception $e) {
            // Silently fail if EXIF reading fails
        }

        return $exifData;
    }
}

