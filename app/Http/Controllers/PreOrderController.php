<?php

namespace App\Http\Controllers;

use App\Models\Gallery;
use App\Models\Order;
use App\Models\Package;
use App\Services\PreOrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PreOrderController extends Controller
{
    public function __construct(
        private PreOrderService $preOrderService
    ) {}

    /**
     * Show public packages page for clients.
     */
    public function browsePackages(Request $request, ?int $photographerId = null)
    {
        $query = Package::where('is_active', true)
            ->with('photographer');

        if ($photographerId) {
            $query->where('photographer_id', $photographerId);
        }

        $packages = $query->orderBy('sort_order')
            ->orderBy('price')
            ->get()
            ->groupBy('photographer_id');

        return view('packages.browse', compact('packages'));
    }

    /**
     * Show pre-order creation form.
     */
    public function create(Request $request, Package $package)
    {
        if (!$package->is_active) {
            abort(404, 'Package not available');
        }

        // Check if user is authenticated
        if (!Auth::guard('client')->check()) {
            return redirect()->route('login')
                ->with('error', 'Please log in to purchase a package.')
                ->with('intended', route('pre-orders.create', $package));
        }

        return view('pre-orders.create', compact('package'));
    }

    /**
     * Store a new pre-order.
     */
    public function store(Request $request, Package $package)
    {
        if (!$package->is_active) {
            abort(404, 'Package not available');
        }

        $user = Auth::guard('client')->user();
        if (!$user) {
            abort(403, 'Authentication required');
        }

        $request->validate([
            'billing_address' => ['required', 'array'],
            'billing_address.name' => ['required', 'string'],
            'billing_address.street' => ['required', 'string'],
            'billing_address.city' => ['required', 'string'],
            'billing_address.state' => ['required', 'string'],
            'billing_address.zip' => ['required', 'string'],
            'billing_address.country' => ['required', 'string'],
        ]);

        DB::beginTransaction();

        try {
            // Create order
            $subtotal = $package->price;
            $tax = $subtotal * 0.08; // 8% tax
            $total = $subtotal + $tax;

            $order = Order::create([
                'user_id' => $user->id,
                'photographer_id' => $package->photographer_id,
                'package_id' => $package->id,
                'order_type' => 'pre_order',
                'status' => 'pre_order_pending',
                'subtotal' => $subtotal,
                'tax' => $tax,
                'total' => $total,
                'currency' => 'USD',
                'billing_address' => $request->billing_address,
                'shipping_address' => $request->shipping_address ?? null,
            ]);

            // Create initial order item for package
            $order->items()->create([
                'photo_id' => null,
                'product_type' => 'package',
                'product_details' => [
                    'package_id' => $package->id,
                    'package_name' => $package->name,
                    'photo_count' => $package->photo_count,
                    'includes_digital' => $package->includes_digital,
                    'includes_prints' => $package->includes_prints,
                ],
                'quantity' => 1,
                'unit_price' => $package->price,
                'total_price' => $package->price,
            ]);

            DB::commit();

            // Send confirmation email
            $order->user->notify(new \App\Notifications\PreOrderCreatedNotification($order));

            // Redirect to payment page
            return redirect()->route('payments.show', $order)
                ->with('success', 'Pre-order created! Please complete payment.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create pre-order', [
                'package_id' => $package->id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return back()->withErrors(['error' => 'Failed to create pre-order. Please try again.']);
        }
    }

    /**
     * Show pre-order details.
     */
    public function show(Order $order)
    {
        // Check authorization
        if (Auth::guard('client')->check()) {
            if ($order->user_id !== Auth::guard('client')->id()) {
                abort(403);
            }
        } elseif (Auth::guard('staff')->check()) {
            if ($order->photographer_id !== Auth::guard('staff')->id()) {
                abort(403);
            }
        } else {
            abort(403);
        }

        $order->load(['package', 'user', 'photographer', 'gallery', 'items.photo']);
        
        // Load galleries for photographer if needed
        $galleries = null;
        if (Auth::guard('staff')->check() && $order->photographer_id === Auth::guard('staff')->id()) {
            $galleries = $order->photographer->galleries()->get();
        }

        return view('pre-orders.show', compact('order', 'galleries'));
    }

    /**
     * Initialize finalization (photographer starts selection process).
     */
    public function startFinalization(Request $request, Order $order)
    {
        if (!Auth::guard('staff')->check()) {
            abort(403);
        }

        if ($order->photographer_id !== Auth::guard('staff')->id()) {
            abort(403);
        }

        $request->validate([
            'gallery_id' => ['required', 'exists:galleries,id'],
        ]);

        try {
            $this->preOrderService->initializeFinalization($order, $request->gallery_id);

            return redirect()->route('staff.pre-orders.finalize', $order)
                ->with('success', 'Finalization started. Client can now select photos.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Show finalization interface.
     */
    public function finalize(Request $request, Order $order)
    {
        if ($order->status !== 'pre_order_selecting') {
            return redirect()->route('pre-orders.show', $order)
                ->with('error', 'This pre-order is not ready for finalization.');
        }

        // Check authorization
        $isClient = Auth::guard('client')->check() && $order->user_id === Auth::guard('client')->id();
        $isPhotographer = Auth::guard('staff')->check() && $order->photographer_id === Auth::guard('staff')->id();

        if (!$isClient && !$isPhotographer) {
            abort(403);
        }

        $gallery = $order->gallery;
        $photos = $this->preOrderService->getAvailablePhotos($order);
        $package = $order->package;

        // Generate signed URLs for photos
        foreach ($photos as $photo) {
            if ($photo->storage_disk !== 'local' && $photo->storage_disk !== 'public') {
                $photo->signed_url = \Illuminate\Support\Facades\Storage::disk($photo->storage_disk)
                    ->temporaryUrl($photo->storage_path, now()->addMinutes(15));
            } else {
                $photo->signed_url = \Illuminate\Support\Facades\Storage::disk($photo->storage_disk)
                    ->url($photo->storage_path);
            }
        }

        return view('pre-orders.finalize', compact('order', 'gallery', 'photos', 'package'));
    }

    /**
     * Complete finalization with selected photos.
     */
    public function completeFinalization(Request $request, Order $order)
    {
        if ($order->status !== 'pre_order_selecting') {
            return back()->withErrors(['error' => 'Order is not in selecting status']);
        }

        // Check authorization
        $isClient = Auth::guard('client')->check() && $order->user_id === Auth::guard('client')->id();
        $isPhotographer = Auth::guard('staff')->check() && $order->photographer_id === Auth::guard('staff')->id();

        if (!$isClient && !$isPhotographer) {
            abort(403);
        }

        $request->validate([
            'photo_ids' => ['required', 'array', 'min:' . $order->package->photo_count],
            'photo_ids.*' => ['required', 'exists:photos,id'],
        ]);

        try {
            $this->preOrderService->finalizePreOrder($order, $request->photo_ids);

            return redirect()->route('pre-orders.show', $order)
                ->with('success', 'Pre-order finalized successfully!');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }
}
