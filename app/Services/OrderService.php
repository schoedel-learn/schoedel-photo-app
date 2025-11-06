<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Photo;
use App\Models\DiscountCode;
use App\Services\CartService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderService
{
    public function __construct(
        private CartService $cartService
    ) {}

    /**
     * Create order from cart.
     */
    public function createFromCart(array $billingAddress, ?string $notes = null): Order
    {
        $cart = $this->cartService->getSummary();
        
        if (empty($cart['items'])) {
            throw new \InvalidArgumentException('Cannot create order from empty cart');
        }

        DB::beginTransaction();

        try {
            // Get photographer from first item
            $firstItem = $cart['items'][0];
            $photographerId = $firstItem['photographer_id'];

            // Validate all items are from same photographer (or handle multi-photographer orders later)
            foreach ($cart['items'] as $item) {
                if ($item['photographer_id'] !== $photographerId) {
                    throw new \InvalidArgumentException('All items must be from the same photographer');
                }
            }

            // Get user (nullable for guest checkout)
            $userId = Auth::guard('client')->id();

            // Generate order number
            $orderNumber = $this->generateOrderNumber();

            // Create order
            $order = Order::create([
                'user_id' => $userId,
                'photographer_id' => $photographerId,
                'order_type' => 'digital_download',
                'status' => 'pending',
                'order_number' => $orderNumber,
                'subtotal' => $cart['subtotal'],
                'tax' => $cart['tax'],
                'total' => $cart['total'],
                'currency' => 'USD',
                'billing_address' => $billingAddress,
                'notes' => $notes,
            ]);

            // Create order items
            foreach ($cart['items'] as $item) {
                $photo = Photo::findOrFail($item['photo_id']);

                OrderItem::create([
                    'order_id' => $order->id,
                    'photo_id' => $photo->id,
                    'product_type' => $item['product_type'],
                    'product_details' => [
                        'photo_filename' => $photo->filename,
                        'gallery_id' => $photo->gallery_id,
                    ],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total_price' => $item['total_price'],
                ]);
            }

            // Apply discount if present
            $discount = $cart['discount'];
            if ($discount && $discount['discount_amount'] > 0) {
                // Increment discount code usage
                $discountCode = DiscountCode::find($discount['discount_code_id']);
                if ($discountCode) {
                    $discountCode->incrementUsage();
                }

                // Store discount info in order notes or separate field
                // For now, discount is already reflected in subtotal/total calculations
            }

            DB::commit();

            // Clear cart
            $this->cartService->clear();

            Log::info('Order created from cart', [
                'order_id' => $order->id,
                'order_number' => $orderNumber,
                'user_id' => $userId,
                'photographer_id' => $photographerId,
                'total' => $order->total,
            ]);

            // Send order confirmation emails
            $order->load(['user', 'photographer']);
            if ($order->user && !$order->user->email_unsubscribed) {
                \Illuminate\Support\Facades\Mail::to($order->user->email)
                    ->send(new \App\Mail\OrderConfirmationMail($order, false));
            }

            // Send to photographer
            if ($order->photographer) {
                \Illuminate\Support\Facades\Mail::to($order->photographer->email)
                    ->send(new \App\Mail\OrderConfirmationMail($order, true));
            }

            return $order->fresh(['items.photo']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create order from cart', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Generate unique order number.
     * Format: ORD-YYYYMMDD-XXXXX
     */
    public function generateOrderNumber(): string
    {
        $date = date('Ymd');
        $prefix = "ORD-{$date}-";
        
        // Get last order number for today
        $lastOrder = Order::where('order_number', 'like', "{$prefix}%")
            ->orderBy('order_number', 'desc')
            ->first();

        $sequence = 1;
        if ($lastOrder && $lastOrder->order_number) {
            // Extract sequence from existing order number
            $parts = explode('-', $lastOrder->order_number);
            if (count($parts) >= 3) {
                $lastSequence = (int) $parts[2];
                $sequence = $lastSequence + 1;
            }
        }

        return $prefix . str_pad($sequence, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Calculate totals for order items.
     */
    public function calculateTotals(array $items, ?float $discountAmount = 0): array
    {
        $subtotal = 0;
        
        foreach ($items as $item) {
            $subtotal += $item['unit_price'] * $item['quantity'];
        }

        $discountAmount = min($discountAmount, $subtotal); // Discount cannot exceed subtotal
        $taxableAmount = max(0, $subtotal - $discountAmount);
        $tax = round($taxableAmount * 0.08, 2); // 8% tax
        $total = round($subtotal - $discountAmount + $tax, 2);

        return [
            'subtotal' => round($subtotal, 2),
            'discount_amount' => round($discountAmount, 2),
            'tax' => $tax,
            'total' => $total,
        ];
    }
}

