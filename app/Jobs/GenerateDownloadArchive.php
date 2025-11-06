<?php

namespace App\Jobs;

use App\Models\Order;
use App\Mail\DownloadReadyMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class GenerateDownloadArchive implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 600; // 10 minutes for large archives

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Order $order
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $zipPath = "downloads/batch_{$this->order->id}_{$this->order->user_id}.zip";
        $zipDisk = Storage::disk('local');

        // Check if ZIP already exists
        if ($zipDisk->exists($zipPath)) {
            Log::info('ZIP archive already exists', [
                'order_id' => $this->order->id,
                'zip_path' => $zipPath,
            ]);
            $this->sendDownloadEmail($zipPath);
            return;
        }

        // Create ZIP archive
        $tempZipPath = storage_path('app/temp_' . uniqid() . '.zip');
        $zip = new ZipArchive();

        if ($zip->open($tempZipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \Exception('Failed to create ZIP archive');
        }

        try {
            $this->order->load(['items.photo']);

            $tempDir = storage_path('app/tmp');
            if (!is_dir($tempDir)) {
                mkdir($tempDir, 0775, true);
            }

            $temporaryFiles = [];

            foreach ($this->order->items as $item) {
                if (!$item->photo) {
                    continue;
                }

                $photo = $item->photo;
                $photoDisk = Storage::disk($photo->storage_disk);

                if (!$photoDisk->exists($photo->storage_path)) {
                    Log::warning('Photo file not found for ZIP', [
                        'photo_id' => $photo->id,
                        'storage_path' => $photo->storage_path,
                    ]);
                    continue;
                }

                $stream = $photoDisk->readStream($photo->storage_path);

                if (!$stream) {
                    Log::warning('Unable to open photo stream for ZIP', [
                        'photo_id' => $photo->id,
                        'storage_path' => $photo->storage_path,
                    ]);
                    continue;
                }

                $tempFile = tempnam($tempDir, 'zip');
                $temporaryFiles[] = $tempFile;

                $localHandle = fopen($tempFile, 'w+b');
                if (!$localHandle) {
                    fclose($stream);
                    Log::warning('Unable to create temporary file for ZIP', [
                        'photo_id' => $photo->id,
                    ]);
                    @unlink($tempFile);
                    array_pop($temporaryFiles);
                    continue;
                }

                $bytesCopied = stream_copy_to_stream($stream, $localHandle);

                fclose($stream);
                fflush($localHandle);
                fclose($localHandle);

                if ($bytesCopied === false) {
                    Log::warning('Failed to copy photo stream into temporary file for ZIP', [
                        'photo_id' => $photo->id,
                    ]);
                    @unlink($tempFile);
                    array_pop($temporaryFiles);
                    continue;
                }

                // Add to ZIP with organized naming
                $zipFilename = "{$this->order->order_number}/{$photo->filename}";
                $zip->addFile($tempFile, $zipFilename);
            }

            $zip->close();

            foreach ($temporaryFiles as $tempFile) {
                @unlink($tempFile);
            }

            // Move to final location
            $destinationPath = $zipDisk->path($zipPath);
            $destinationDir = dirname($destinationPath);

            if (!is_dir($destinationDir)) {
                mkdir($destinationDir, 0775, true);
            }

            if (!rename($tempZipPath, $destinationPath)) {
                throw new \RuntimeException('Failed to move generated ZIP archive into place.');
            }

            // Delete temporary file
            @unlink($tempZipPath);

            Log::info('ZIP archive generated successfully', [
                'order_id' => $this->order->id,
                'zip_path' => $zipPath,
                'photos_count' => $this->order->items->count(),
            ]);

            // Send email with download link
            $this->sendDownloadEmail($zipPath);
        } catch (\Exception $e) {
            // Cleanup on error
            @unlink($tempZipPath);
            if ($zipDisk->exists($zipPath)) {
                $zipDisk->delete($zipPath);
            }

            if (isset($temporaryFiles)) {
                foreach ($temporaryFiles as $tempFile) {
                    @unlink($tempFile);
                }
            }

            Log::error('Failed to generate ZIP archive', [
                'order_id' => $this->order->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Send email with download link.
     */
    private function sendDownloadEmail(string $zipPath): void
    {
        if (!$this->order->user || $this->order->user->email_unsubscribed) {
            return;
        }

        $downloadService = app(\App\Services\DownloadService::class);
        $downloadUrl = $downloadService->generateBatchDownloadLink($this->order);

        // Create a simple download ready mail (can be enhanced later)
        Mail::to($this->order->user->email)->send(
            new \App\Mail\DownloadReadyMail($this->order, $downloadUrl)
        );
    }
}
