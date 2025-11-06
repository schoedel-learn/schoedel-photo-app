<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreGalleryRequest;
use App\Http\Requests\UpdateGalleryRequest;
use App\Models\Gallery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class GalleryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $this->authorize('viewAny', Gallery::class);

        $galleries = Gallery::where('user_id', Auth::guard('staff')->id())
            ->withCount('photos')
            ->orderBy('created_at', 'desc')
            ->paginate(12);

        return view('galleries.index', compact('galleries'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $this->authorize('create', Gallery::class);

        return view('galleries.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreGalleryRequest $request)
    {
        $this->authorize('create', Gallery::class);

        $data = $request->validated();

        // Generate slug from name
        $slug = Str::slug($data['name']);

        // Hash password if provided
        if ($data['access_type'] === 'password_protected' && !empty($data['password'])) {
            $passwordHash = Hash::make($data['password']);
        } else {
            $passwordHash = null;
        }

        // Create gallery
        $gallery = Gallery::create([
            'user_id' => Auth::guard('staff')->id(),
            'name' => $data['name'],
            'slug' => $slug,
            'description' => $data['description'] ?? null,
            'access_type' => $data['access_type'],
            'password_hash' => $passwordHash,
            'expires_at' => $data['expires_at'] ?? null,
            'published_at' => now(),
        ]);

        // Send gallery published emails to clients
        // Find clients who have orders/pre-orders associated with this photographer
        $photographerId = Auth::guard('staff')->id();
        $clientIds = \App\Models\Order::where('photographer_id', $photographerId)
            ->whereNotNull('user_id')
            ->distinct()
            ->pluck('user_id');

        $clients = \App\Models\User::whereIn('id', $clientIds)
            ->where('role', 'client')
            ->where('email_unsubscribed', false)
            ->get();

        foreach ($clients as $client) {
            \Illuminate\Support\Facades\Mail::to($client->email)
                ->send(new \App\Mail\GalleryPublishedMail($gallery, $client));
        }

        return redirect()->route('galleries.show', $gallery)
            ->with('success', 'Gallery created successfully!');
    }

    /**
     * Display the specified resource.
     */
    public function show(Gallery $gallery)
    {
        $this->authorize('view', $gallery);

        $gallery->load(['photos' => function ($query) {
            $query->orderBy('sort_order')->orderBy('created_at');
        }]);

        return view('galleries.show', compact('gallery'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Gallery $gallery)
    {
        $this->authorize('update', $gallery);

        return view('galleries.edit', compact('gallery'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateGalleryRequest $request, Gallery $gallery)
    {
        $this->authorize('update', $gallery);

        $data = $request->validated();

        // Update slug if name changed
        if (isset($data['name']) && $data['name'] !== $gallery->name) {
            $data['slug'] = Str::slug($data['name']);
        }

        // Handle password update
        if ($data['access_type'] === 'password_protected' && !empty($data['password'])) {
            $data['password_hash'] = Hash::make($data['password']);
        } elseif ($data['access_type'] !== 'password_protected') {
            // Clear password if access type changed away from password_protected
            $data['password_hash'] = null;
        } else {
            // Don't update password if not provided and still password_protected
            unset($data['password']);
        }

        // Remove password from data array as it's not a fillable field
        unset($data['password']);

        $gallery->update($data);

        return redirect()->route('galleries.show', $gallery)
            ->with('success', 'Gallery updated successfully!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Gallery $gallery)
    {
        $this->authorize('delete', $gallery);

        $gallery->delete(); // Soft delete

        return redirect()->route('galleries.index')
            ->with('success', 'Gallery archived successfully!');
    }
}
