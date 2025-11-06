<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Gallery;
use App\Models\Photo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PhotoSelectionController extends Controller
{
    /**
     * Toggle photo selection (add/remove from favorites).
     */
    public function toggle(Request $request, Photo $photo)
    {
        $gallery = $photo->gallery;

        // Check access to gallery
        $this->checkGalleryAccess($request, $gallery);

        $selectionKey = $this->getSelectionKey($gallery);
        $selections = $request->session()->get($selectionKey, []);

        if (in_array($photo->id, $selections)) {
            // Remove from selections
            $selections = array_values(array_diff($selections, [$photo->id]));
            $selected = false;
        } else {
            // Add to selections
            $selections[] = $photo->id;
            $selected = true;
        }

        // Store selections in session
        $request->session()->put($selectionKey, array_unique($selections));

        // If user is logged in, also store in database (future: use database table)
        if (Auth::guard('client')->check()) {
            // TODO: Store in database for logged-in users
        }

        return response()->json([
            'success' => true,
            'selected' => $selected,
            'count' => count($selections),
        ]);
    }

    /**
     * Get all selected photos for a gallery.
     */
    public function list(Request $request, Gallery $gallery)
    {
        $this->checkGalleryAccess($request, $gallery);

        $selectionKey = $this->getSelectionKey($gallery);
        $selectedIds = $request->session()->get($selectionKey, []);

        if (empty($selectedIds)) {
            return response()->json([
                'success' => true,
                'photos' => [],
                'count' => 0,
            ]);
        }

        $photos = Photo::whereIn('id', $selectedIds)
            ->where('gallery_id', $gallery->id)
            ->get();

        return response()->json([
            'success' => true,
            'photos' => $photos->map(function ($photo) {
                return [
                    'id' => $photo->id,
                    'filename' => $photo->filename,
                ];
            }),
            'count' => count($selectedIds),
        ]);
    }

    /**
     * Clear all selections for a gallery.
     */
    public function clear(Request $request, Gallery $gallery)
    {
        $this->checkGalleryAccess($request, $gallery);

        $selectionKey = $this->getSelectionKey($gallery);
        $request->session()->forget($selectionKey);

        return response()->json([
            'success' => true,
            'message' => 'All selections cleared.',
        ]);
    }

    /**
     * Check if user has access to gallery.
     */
    private function checkGalleryAccess(Request $request, Gallery $gallery): void
    {
        if ($gallery->access_type === 'private') {
            if (!Auth::guard('client')->check() || Auth::guard('client')->id() !== $gallery->user_id) {
                abort(403, 'Access denied.');
            }
        } elseif ($gallery->access_type === 'password_protected') {
            $passwordKey = "gallery_{$gallery->id}_verified";
            if (!$request->session()->has($passwordKey)) {
                abort(403, 'Password verification required.');
            }
        }
    }

    /**
     * Get the session key for storing selections.
     */
    private function getSelectionKey(Gallery $gallery): string
    {
        $userId = Auth::guard('client')->id() ?? 'guest';
        return "gallery_{$gallery->id}_selections_{$userId}";
    }
}

