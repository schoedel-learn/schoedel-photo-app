<?php

namespace App\Http\Controllers;

use App\Models\Download;
use App\Models\Order;
use App\Models\Photo;
use App\Services\DownloadService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class DownloadController extends Controller
{
    public function __construct(
        private DownloadService $downloadService
    ) {}

    /**
     * Show download history for authenticated user.
     */
    public function index()
    {
        $user = Auth::guard('client')->user();

        if (!$user) {
            return redirect()->route('login');
        }

        $downloads = Download::where('user_id', $user->id)
            ->with(['photo', 'order'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('downloads.index', compact('downloads'));
    }

    /**
     * Download a single photo.
     */
    public function download(Request $request, string $token)
    {
        try {
            // Validate token and get download record
            $download = $this->downloadService->validateDownloadFromUrl($token);

            if (!$download) {
                return view('downloads.expired', [
                    'message' => 'Invalid download link. Please contact support if you believe this is an error.',
                ]);
            }

            // Check expiration
            if ($download->isExpired()) {
                return view('downloads.expired', [
                    'message' => 'This download link has expired.',
                    'order' => $download->order,
                ]);
            }

            // Check attempt limit
            if (!$download->hasAttemptsRemaining()) {
                return view('downloads.expired', [
                    'message' => 'You have reached the maximum number of download attempts for this file.',
                    'order' => $download->order,
                ]);
            }

            // Load photo
            $photo = $download->photo;
            if (!$photo) {
                return view('downloads.expired', [
                    'message' => 'Photo not found.',
                ]);
            }

            // Verify user has access to this download (optional check - token is already validated)
            // For guest downloads, we rely on token security
            $user = Auth::guard('client')->user();
            if ($user && $download->user_id !== $user->id) {
                abort(403, 'You do not have permission to download this file.');
            }

            // Get file from storage
            $disk = Storage::disk($photo->storage_disk);
            if (!$disk->exists($photo->storage_path)) {
                Log::error('Photo file not found', [
                    'photo_id' => $photo->id,
                    'storage_path' => $photo->storage_path,
                    'storage_disk' => $photo->storage_disk,
                ]);
                return view('downloads.expired', [
                    'message' => 'File not found. Please contact support.',
                ]);
            }

            // Record download attempt
            $this->downloadService->recordDownload(
                $download,
                $request->ip(),
                $request->userAgent()
            );

            // Stream file
            return response()->streamDownload(function () use ($disk, $photo) {
                $stream = $disk->readStream($photo->storage_path);
                if ($stream) {
                    while (!feof($stream)) {
                        echo fread($stream, 8192); // 8KB chunks
                        flush();
                    }
                    fclose($stream);
                }
            }, $photo->filename, [
                'Content-Type' => $this->getContentType($photo->filename),
                'Content-Length' => $photo->file_size ?? $disk->size($photo->storage_path),
            ]);
        } catch (\Exception $e) {
            Log::error('Download error', [
                'token' => substr($token, 0, 20) . '...',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return view('downloads.expired', [
                'message' => 'An error occurred while processing your download. Please try again or contact support.',
            ]);
        }
    }

    /**
     * Download batch ZIP archive.
     */
    public function batch(Request $request, string $token)
    {
        try {
            // Validate batch token
            $order = $this->downloadService->validateBatchToken($token);

            if (!$order) {
                return view('downloads.expired', [
                    'message' => 'Invalid download link.',
                ]);
            }

            // Verify user access
            $user = Auth::guard('client')->user();
            if ($user && $order->user_id !== $user->id) {
                abort(403, 'You do not have permission to download this archive.');
            }

            // Dispatch job to generate ZIP if not already generated
            $zipPath = "downloads/batch_{$order->id}_{$order->user_id}.zip";
            $zipDisk = Storage::disk('local');

            if (!$zipDisk->exists($zipPath)) {
                // Queue ZIP generation
                \App\Jobs\GenerateDownloadArchive::dispatch($order);

                return view('downloads.preparing', [
                    'order' => $order,
                    'message' => 'Your download is being prepared. You will receive an email when it\'s ready.',
                ]);
            }

            // Serve ZIP file
            return response()->download(
                $zipDisk->path($zipPath),
                "order_{$order->order_number}_photos.zip",
                [
                    'Content-Type' => 'application/zip',
                ]
            );
        } catch (\Exception $e) {
            Log::error('Batch download error', [
                'token' => substr($token, 0, 20) . '...',
                'error' => $e->getMessage(),
            ]);

            return view('downloads.expired', [
                'message' => 'An error occurred while preparing your download.',
            ]);
        }
    }

    /**
     * Get content type for file.
     */
    private function getContentType(string $filename): string
    {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        return match ($extension) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'heic' => 'image/heic',
            default => 'application/octet-stream',
        };
    }
}
