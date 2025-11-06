<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessPhotoUpload;
use App\Models\Gallery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PhotoController extends Controller
{
    /**
     * Show the form for uploading photos to a gallery.
     */
    public function create(Gallery $gallery)
    {
        // Ensure user owns the gallery
        if (Auth::guard('staff')->id() !== $gallery->user_id) {
            abort(403, 'You do not have permission to upload photos to this gallery.');
        }

        return view('photos.upload', compact('gallery'));
    }

    /**
     * Store uploaded photos.
     */
    public function store(Request $request, Gallery $gallery)
    {
        // Ensure user owns the gallery
        if (Auth::guard('staff')->id() !== $gallery->user_id) {
            abort(403, 'You do not have permission to upload photos to this gallery.');
        }

        $request->validate([
            'photos' => ['required', 'array', 'max:100'],
            'photos.*' => [
                'required',
                'image',
                'mimes:jpeg,jpg,png,heic,heif',
                'max:51200', // 50MB
            ],
        ]);

        $files = $request->file('photos');
        $jobs = [];
        $sortOrder = $gallery->photos()->max('sort_order') ?? 0;

        // Create jobs for each uploaded file
        foreach ($files as $index => $file) {
            $jobs[] = new ProcessPhotoUpload(
                $file,
                $gallery,
                $sortOrder + $index + 1
            );
        }

        // Dispatch all jobs as a batch for progress tracking
        $batch = Bus::batch($jobs)->dispatch();

        return response()->json([
            'success' => true,
            'message' => count($files) . ' photos queued for upload.',
            'batch_id' => $batch->id,
            'total_jobs' => count($jobs),
        ]);
    }

    /**
     * Get upload progress for a batch.
     */
    public function progress(string $batchId)
    {
        $batch = Bus::findBatch($batchId);

        if (!$batch) {
            return response()->json([
                'error' => 'Batch not found',
            ], 404);
        }

        return response()->json([
            'batch_id' => $batch->id,
            'total_jobs' => $batch->totalJobs,
            'pending_jobs' => $batch->pendingJobs,
            'processed_jobs' => $batch->processedJobs(),
            'failed_jobs' => $batch->failedJobs,
            'finished' => $batch->finished(),
            'cancelled' => $batch->cancelled(),
            'progress' => $batch->progress(),
        ]);
    }
}
