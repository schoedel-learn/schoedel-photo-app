<?php

namespace App\Jobs;

use App\Models\Gallery;
use App\Models\Photo;
use App\Services\PhotoStorageService;
use App\Services\ThumbnailService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class ProcessPhotoUpload implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public int $backoff = 60;

    /**
     * File data that can be serialized.
     */
    public string $tempFilePath;
    public string $originalName;
    public string $mimeType;
    public int $size;

    /**
     * Create a new job instance.
     */
    public function __construct(
        UploadedFile $file,
        public Gallery $gallery,
        public int $sortOrder = 0
    ) {
        // Store file temporarily for job processing
        $this->tempFilePath = $file->store('temp', 'local');
        $this->originalName = $file->getClientOriginalName();
        $this->mimeType = $file->getMimeType();
        $this->size = $file->getSize();
    }

    /**
     * Execute the job.
     */
    public function handle(
        PhotoStorageService $storageService,
        ThumbnailService $thumbnailService
    ): void {
        try {
            // Get the temp file path
            $tempFullPath = Storage::disk('local')->path($this->tempFilePath);
            
            // Reconstruct UploadedFile from temp path
            // Using test mode (5th param = true) to prevent file from being moved
            $file = new UploadedFile(
                $tempFullPath,
                $this->originalName,
                $this->mimeType,
                null,
                true // Test mode to avoid moving file
            );

            // Store the original photo
            $storageData = $storageService->store($file, $this->gallery);

            // Create photo record
            $photo = Photo::create([
                'gallery_id' => $this->gallery->id,
                'filename' => $storageData['filename'],
                'storage_path' => $storageData['storage_path'],
                'storage_disk' => $storageData['storage_disk'],
                'original_width' => $storageData['original_width'],
                'original_height' => $storageData['original_height'],
                'file_size' => $storageData['file_size'],
                'metadata' => $storageData['metadata'],
                'sort_order' => $this->sortOrder,
            ]);

            // Generate thumbnails
            $thumbnailService->generate($photo);

            // Clean up temp file
            Storage::disk('local')->delete($this->tempFilePath);

            Log::info('Photo uploaded and processed successfully', [
                'photo_id' => $photo->id,
                'gallery_id' => $this->gallery->id,
            ]);
        } catch (\Exception $e) {
            // Clean up temp file on error
            if (isset($this->tempFilePath)) {
                Storage::disk('local')->delete($this->tempFilePath);
            }

            Log::error('Failed to process photo upload', [
                'gallery_id' => $this->gallery->id,
                'filename' => $this->originalName,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e; // Re-throw to trigger retry mechanism
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        // Clean up temp file
        if (isset($this->tempFilePath)) {
            Storage::disk('local')->delete($this->tempFilePath);
        }

        Log::error('Photo upload job failed permanently', [
            'gallery_id' => $this->gallery->id,
            'filename' => $this->originalName,
            'error' => $exception->getMessage(),
        ]);
    }
}

