<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePackageRequest;
use App\Http\Requests\UpdatePackageRequest;
use App\Models\Package;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PackageController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $this->authorize('viewAny', Package::class);

        $packages = Package::where('photographer_id', Auth::guard('staff')->id())
            ->orderBy('sort_order')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('packages.index', compact('packages'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $this->authorize('create', Package::class);

        return view('packages.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePackageRequest $request)
    {
        $this->authorize('create', Package::class);

        $data = $request->validated();

        $package = Package::create([
            'photographer_id' => Auth::guard('staff')->id(),
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'price' => $data['price'],
            'photo_count' => $data['photo_count'],
            'includes_digital' => $data['includes_digital'] ?? true,
            'includes_prints' => $data['includes_prints'] ?? false,
            'features' => $data['features'] ?? [],
            'is_active' => $data['is_active'] ?? true,
            'sort_order' => $data['sort_order'] ?? 0,
        ]);

        return redirect()->route('packages.index')
            ->with('success', 'Package created successfully!');
    }

    /**
     * Display the specified resource.
     */
    public function show(Package $package)
    {
        $this->authorize('view', $package);

        return view('packages.show', compact('package'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Package $package)
    {
        $this->authorize('update', $package);

        return view('packages.edit', compact('package'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePackageRequest $request, Package $package)
    {
        $this->authorize('update', $package);

        $data = $request->validated();
        $package->update($data);

        return redirect()->route('packages.index')
            ->with('success', 'Package updated successfully!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Package $package)
    {
        $this->authorize('delete', $package);

        $package->delete();

        return redirect()->route('packages.index')
            ->with('success', 'Package deleted successfully!');
    }
}
