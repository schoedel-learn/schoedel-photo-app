<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Gallery;
use App\Models\Photo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class GalleryViewController extends Controller
{
    /**
     * Display a gallery for clients.
     */
    public function show(Request $request, string $slug)
    {
        $gallery = Gallery::where('slug', $slug)->firstOrFail();

        // Check if gallery is published
        if (!$gallery->published_at) {
            abort(404, 'Gallery not found.');
        }

        // Check if gallery has expired
        if ($gallery->is_expired) {
            return view('client.gallery-expired', compact('gallery'));
        }

        // Check access control
        if ($gallery->access_type === 'private') {
            // Private galleries require authentication and ownership check
            if (!Auth::guard('client')->check() || Auth::guard('client')->id() !== $gallery->user_id) {
                abort(403, 'This gallery is private.');
            }
        } elseif ($gallery->access_type === 'password_protected') {
            // Check if password is already verified in session
            $passwordKey = "gallery_{$gallery->id}_verified";
            if (!$request->session()->has($passwordKey)) {
                return view('client.gallery-password', compact('gallery'));
            }
        }

        // Load photos with pagination
        $photos = $gallery->photos()
            ->orderBy('sort_order')
            ->orderBy('created_at')
            ->paginate(30);

        // Generate signed URLs for photos if using cloud storage
        // For now, use thumbnails or full images based on availability
        foreach ($photos as $photo) {
            // Try to get thumbnail first, fallback to original
            $thumbnailPath = $this->getThumbnailPath($photo);
            
            if ($thumbnailPath && Storage::disk('thumbnails')->exists($thumbnailPath)) {
                // Use thumbnail for grid view
                $photo->signed_url = Storage::disk('thumbnails')->url($thumbnailPath);
            } elseif ($photo->storage_disk !== 'local' && $photo->storage_disk !== 'public') {
                // Generate temporary signed URL for secure access (15 minutes)
                $photo->signed_url = Storage::disk($photo->storage_disk)
                    ->temporaryUrl($photo->storage_path, now()->addMinutes(15));
            } else {
                $photo->signed_url = Storage::disk($photo->storage_disk)->url($photo->storage_path);
            }
            
            // Store full URL for lightbox
            if ($photo->storage_disk !== 'local' && $photo->storage_disk !== 'public') {
                $photo->full_url = Storage::disk($photo->storage_disk)
                    ->temporaryUrl($photo->storage_path, now()->addMinutes(15));
            } else {
                $photo->full_url = Storage::disk($photo->storage_disk)->url($photo->storage_path);
            }
        }

        return view('client.gallery-view', compact('gallery', 'photos'));
    }

    /**
     * Verify password for password-protected gallery.
     */
    public function verifyPassword(Request $request, string $slug)
    {
        $gallery = Gallery::where('slug', $slug)->firstOrFail();

        $request->validate([
            'password' => ['required', 'string'],
        ]);

        // Verify password
        if (!$gallery->password_hash || !Hash::check($request->password, $gallery->password_hash)) {
            return back()->withErrors([
                'password' => 'Invalid password. Please try again.',
            ]);
        }

        // Store verification in session
        $passwordKey = "gallery_{$gallery->id}_verified";
        $request->session()->put($passwordKey, true);

        return redirect()->route('client.gallery.show', $gallery->slug)
            ->with('success', 'Password verified. You now have access to this gallery.');
    }

    /**
     * Get thumbnail path for a photo (medium size).
     */
    private function getThumbnailPath(Photo $photo): ?string
    {
        $filename = pathinfo($photo->filename, PATHINFO_FILENAME);
        $year = date('Y', strtotime($photo->created_at));
        $month = date('m', strtotime($photo->created_at));
        $thumbnailPath = "galleries/{$photo->gallery_id}/{$year}/{$month}/thumbnails/{$filename}_medium.jpg";
        
        return $thumbnailPath;
    }
}

