<?php

namespace App\Http\Controllers;

use App\Models\Photo;
use App\Services\CartService;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function __construct(
        private CartService $cartService
    ) {}

    /**
     * Display cart contents.
     */
    public function index()
    {
        $cart = $this->cartService->getSummary();

        // Load photo details for display
        $photoIds = array_column($cart['items'], 'photo_id');
        $photos = Photo::whereIn('id', $photoIds)
            ->with('gallery')
            ->get()
            ->keyBy('id');

        // Add photo URLs to cart items
        foreach ($cart['items'] as &$item) {
            $photo = $photos->get($item['photo_id']);
            if ($photo) {
                // Generate signed URL if needed
                if ($photo->storage_disk !== 'local' && $photo->storage_disk !== 'public') {
                    $item['photo_url'] = \Illuminate\Support\Facades\Storage::disk($photo->storage_disk)
                        ->temporaryUrl($photo->storage_path, now()->addMinutes(15));
                } else {
                    $item['photo_url'] = \Illuminate\Support\Facades\Storage::disk($photo->storage_disk)
                        ->url($photo->storage_path);
                }
                $item['photo'] = $photo;
            }
        }

        return view('cart.index', compact('cart'));
    }

    /**
     * Add item to cart.
     */
    public function add(Request $request)
    {
        $request->validate([
            'photo_id' => ['required', 'exists:photos,id'],
            'product_type' => ['sometimes', 'string', 'in:digital_download'],
            'quantity' => ['sometimes', 'integer', 'min:1', 'max:10'],
        ]);

        $photo = Photo::findOrFail($request->photo_id);
        $productType = $request->input('product_type', 'digital_download');
        $quantity = $request->input('quantity', 1);

        $result = $this->cartService->add($photo, $productType, $quantity);

        if ($request->expectsJson()) {
            return response()->json($result);
        }

        return redirect()->route('cart.index')
            ->with('success', 'Item added to cart!');
    }

    /**
     * Update item quantity.
     */
    public function update(Request $request, string $itemId)
    {
        $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:10'],
        ]);

        $success = $this->cartService->update($itemId, $request->quantity);

        if ($request->expectsJson()) {
            return response()->json(['success' => $success]);
        }

        return redirect()->route('cart.index')
            ->with($success ? 'success' : 'error', $success ? 'Cart updated!' : 'Item not found.');
    }

    /**
     * Remove item from cart.
     */
    public function remove(string $itemId)
    {
        $success = $this->cartService->remove($itemId);

        return redirect()->route('cart.index')
            ->with($success ? 'success' : 'error', $success ? 'Item removed from cart!' : 'Item not found.');
    }

    /**
     * Clear cart.
     */
    public function clear()
    {
        $this->cartService->clear();

        return redirect()->route('cart.index')
            ->with('success', 'Cart cleared!');
    }

    /**
     * Apply discount code.
     */
    public function applyDiscount(Request $request)
    {
        $request->validate([
            'code' => ['required', 'string', 'max:50'],
        ]);

        $result = $this->cartService->applyDiscount($request->code);

        return redirect()->route('cart.index')
            ->with($result['success'] ? 'success' : 'error', $result['message'] ?? 'Discount code applied!');
    }

    /**
     * Remove discount code.
     */
    public function removeDiscount()
    {
        $this->cartService->removeDiscount();

        return redirect()->route('cart.index')
            ->with('success', 'Discount code removed.');
    }

    /**
     * Add multiple photos to cart at once.
     */
    public function addBulk(Request $request)
    {
        $request->validate([
            'photo_ids' => ['required', 'array', 'min:1'],
            'photo_ids.*' => ['required', 'exists:photos,id'],
            'product_type' => ['sometimes', 'string', 'in:digital_download'],
        ]);

        $productType = $request->input('product_type', 'digital_download');
        $added = 0;

        foreach ($request->photo_ids as $photoId) {
            $photo = Photo::findOrFail($photoId);
            $result = $this->cartService->add($photo, $productType, 1);
            if ($result['success']) {
                $added++;
            }
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => "{$added} photo(s) added to cart",
                'cart_count' => $this->cartService->getItemCount(),
            ]);
        }

        return redirect()->route('cart.index')
            ->with('success', "{$added} photo(s) added to cart!");
    }
}
