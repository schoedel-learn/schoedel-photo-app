<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\CartService;
use App\Services\OrderService;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(
        private CartService $cartService,
        private OrderService $orderService
    ) {}

    /**
     * Show checkout page.
     */
    public function checkout()
    {
        $cart = $this->cartService->getSummary();

        if (empty($cart['items'])) {
            return redirect()->route('cart.index')
                ->with('error', 'Your cart is empty.');
        }

        // Load photo details for display
        $photoIds = array_column($cart['items'], 'photo_id');
        $photos = \App\Models\Photo::whereIn('id', $photoIds)
            ->with('gallery')
            ->get()
            ->keyBy('id');

        foreach ($cart['items'] as &$item) {
            $photo = $photos->get($item['photo_id']);
            if ($photo) {
                $item['photo'] = $photo;
            }
        }

        return view('orders.checkout', compact('cart'));
    }

    /**
     * Create order from cart.
     */
    public function store(Request $request)
    {
        $cart = $this->cartService->getSummary();

        if (empty($cart['items'])) {
            return back()->withErrors(['error' => 'Your cart is empty.']);
        }

        $request->validate([
            'billing_address' => ['required', 'array'],
            'billing_address.name' => ['required', 'string', 'max:255'],
            'billing_address.email' => ['required', 'email', 'max:255'],
            'billing_address.street' => ['required', 'string', 'max:255'],
            'billing_address.city' => ['required', 'string', 'max:255'],
            'billing_address.state' => ['required', 'string', 'max:255'],
            'billing_address.zip' => ['required', 'string', 'max:20'],
            'billing_address.country' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'terms' => ['required', 'accepted'],
        ]);

        try {
            $order = $this->orderService->createFromCart(
                $request->billing_address,
                $request->notes
            );

            // Redirect to payment page
            return redirect()->route('payments.show', $order)
                ->with('success', 'Order created! Please complete payment.');
        } catch (\Exception $e) {
            \Log::error('Failed to create order', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->withErrors(['error' => 'Failed to create order. Please try again.']);
        }
    }

    /**
     * Show order confirmation.
     */
    public function show(Order $order)
    {
        // Check authorization
        $isOwner = false;
        if (\Illuminate\Support\Facades\Auth::guard('client')->check()) {
            $isOwner = $order->user_id === \Illuminate\Support\Facades\Auth::guard('client')->id();
        }

        if (!$isOwner) {
            abort(403, 'You do not have permission to view this order.');
        }

        $order->load(['items.photo', 'photographer', 'transactions']);

        // Check if this is from a redirect after order creation
        $fromCheckout = request()->query('from_checkout') === '1';

        if ($fromCheckout) {
            return view('orders.confirmation', compact('order'));
        }

        return view('orders.show', compact('order'));
    }
}
