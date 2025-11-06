<?php

namespace App\Services;

use App\Models\Photo;
use App\Models\DiscountCode;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class CartService
{
    private const DIGITAL_DOWNLOAD_PRICE = 5.00; // Base price per digital download
    private const TAX_RATE = 0.08; // 8% tax

    /**
     * Get cart key for current user/guest.
     */
    private function getCartKey(): string
    {
        $userId = Auth::guard('client')->id() ?? 'guest';
        return "cart_{$userId}";
    }

    /**
     * Add item to cart.
     */
    public function add(Photo $photo, string $productType = 'digital_download', int $quantity = 1): array
    {
        $cart = $this->getItems();
        
        // Check if photo already in cart
        $existingIndex = null;
        foreach ($cart as $index => $item) {
            if ($item['photo_id'] === $photo->id && $item['product_type'] === $productType) {
                $existingIndex = $index;
                break;
            }
        }

        if ($existingIndex !== null) {
            // Update quantity
            $cart[$existingIndex]['quantity'] += $quantity;
        } else {
            // Add new item
            $cart[] = [
                'photo_id' => $photo->id,
                'gallery_id' => $photo->gallery_id,
                'photographer_id' => $photo->gallery->user_id,
                'product_type' => $productType,
                'photo_filename' => $photo->filename,
                'photo_url' => $photo->url ?? null,
                'quantity' => $quantity,
                'unit_price' => self::DIGITAL_DOWNLOAD_PRICE,
                'total_price' => self::DIGITAL_DOWNLOAD_PRICE * $quantity,
            ];
        }

        $this->saveCart($cart);

        return ['success' => true, 'cart_count' => $this->getItemCount()];
    }

    /**
     * Remove item from cart.
     */
    public function remove(string $itemId): bool
    {
        $cart = $this->getItems();
        
        $index = $this->findItemIndex($itemId);
        if ($index === null) {
            return false;
        }

        unset($cart[$index]);
        $cart = array_values($cart); // Re-index array

        $this->saveCart($cart);

        return true;
    }

    /**
     * Update item quantity.
     */
    public function update(string $itemId, int $quantity): bool
    {
        if ($quantity <= 0) {
            return $this->remove($itemId);
        }

        $cart = $this->getItems();
        
        $index = $this->findItemIndex($itemId);
        if ($index === null) {
            return false;
        }

        $cart[$index]['quantity'] = $quantity;
        $cart[$index]['total_price'] = $cart[$index]['unit_price'] * $quantity;

        $this->saveCart($cart);

        return true;
    }

    /**
     * Clear cart.
     */
    public function clear(): void
    {
        Session::forget($this->getCartKey());
        Session::forget($this->getDiscountKey());
    }

    /**
     * Get all cart items.
     */
    public function getItems(): array
    {
        return Session::get($this->getCartKey(), []);
    }

    /**
     * Get cart item count.
     */
    public function getItemCount(): int
    {
        $items = $this->getItems();
        return array_sum(array_column($items, 'quantity'));
    }

    /**
     * Apply discount code.
     */
    public function applyDiscount(string $code): array
    {
        $discountCode = DiscountCode::where('code', strtoupper($code))->first();

        if (!$discountCode || !$discountCode->isValid()) {
            return ['success' => false, 'message' => 'Invalid or expired discount code.'];
        }

        $subtotal = $this->getSubtotal();

        if ($discountCode->minimum_amount && $subtotal < $discountCode->minimum_amount) {
            return [
                'success' => false,
                'message' => "Minimum order amount of $" . number_format($discountCode->minimum_amount, 2) . " required.",
            ];
        }

        $discountAmount = $discountCode->calculateDiscount($subtotal);

        Session::put($this->getDiscountKey(), [
            'code' => $discountCode->code,
            'discount_code_id' => $discountCode->id,
            'discount_amount' => $discountAmount,
            'type' => $discountCode->type,
            'value' => $discountCode->value,
        ]);

        return [
            'success' => true,
            'discount_amount' => $discountAmount,
            'message' => 'Discount code applied successfully!',
        ];
    }

    /**
     * Remove discount code.
     */
    public function removeDiscount(): void
    {
        Session::forget($this->getDiscountKey());
    }

    /**
     * Get applied discount.
     */
    public function getDiscount(): ?array
    {
        return Session::get($this->getDiscountKey());
    }

    /**
     * Calculate subtotal.
     */
    public function getSubtotal(): float
    {
        $items = $this->getItems();
        $subtotal = 0;

        foreach ($items as $item) {
            $subtotal += $item['total_price'];
        }

        return round($subtotal, 2);
    }

    /**
     * Calculate discount amount.
     */
    public function getDiscountAmount(): float
    {
        $discount = $this->getDiscount();
        return $discount ? $discount['discount_amount'] : 0.00;
    }

    /**
     * Calculate tax.
     */
    public function getTax(): float
    {
        $subtotal = $this->getSubtotal();
        $discountAmount = $this->getDiscountAmount();
        $taxableAmount = max(0, $subtotal - $discountAmount);
        
        return round($taxableAmount * self::TAX_RATE, 2);
    }

    /**
     * Calculate total.
     */
    public function getTotal(): float
    {
        $subtotal = $this->getSubtotal();
        $discountAmount = $this->getDiscountAmount();
        $tax = $this->getTax();

        return round($subtotal - $discountAmount + $tax, 2);
    }

    /**
     * Get cart summary.
     */
    public function getSummary(): array
    {
        return [
            'items' => $this->getItems(),
            'item_count' => $this->getItemCount(),
            'subtotal' => $this->getSubtotal(),
            'discount' => $this->getDiscount(),
            'discount_amount' => $this->getDiscountAmount(),
            'tax' => $this->getTax(),
            'total' => $this->getTotal(),
        ];
    }

    /**
     * Save cart to session/database.
     */
    private function saveCart(array $cart): void
    {
        Session::put($this->getCartKey(), $cart);

        // If user is logged in, also save to database (future: cart persistence)
        if (Auth::guard('client')->check()) {
            // TODO: Save cart to database for persistence across devices
        }
    }

    /**
     * Get discount session key.
     */
    private function getDiscountKey(): string
    {
        $userId = Auth::guard('client')->id() ?? 'guest';
        return "cart_discount_{$userId}";
    }

    /**
     * Find item index by ID.
     */
    private function findItemIndex(string $itemId): ?int
    {
        $cart = $this->getItems();
        
        foreach ($cart as $index => $item) {
            if ($this->getItemId($item) === $itemId) {
                return $index;
            }
        }

        return null;
    }

    /**
     * Generate unique item ID.
     */
    private function getItemId(array $item): string
    {
        return "{$item['photo_id']}_{$item['product_type']}";
    }

    /**
     * Merge guest cart with user cart on login.
     */
    public function mergeGuestCart(): void
    {
        if (!Auth::guard('client')->check()) {
            return;
        }

        $guestCart = Session::get('cart_guest', []);
        $userCart = $this->getItems();

        if (empty($guestCart)) {
            return;
        }

        // Merge carts (combine quantities for same items)
        foreach ($guestCart as $guestItem) {
            $found = false;
            foreach ($userCart as &$userItem) {
                if ($guestItem['photo_id'] === $userItem['photo_id'] 
                    && $guestItem['product_type'] === $userItem['product_type']) {
                    $userItem['quantity'] += $guestItem['quantity'];
                    $userItem['total_price'] = $userItem['unit_price'] * $userItem['quantity'];
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                $userCart[] = $guestItem;
            }
        }

        $this->saveCart($userCart);
        Session::forget('cart_guest');
        
        // Also merge discount if present
        $guestDiscount = Session::get('cart_discount_guest');
        if ($guestDiscount) {
            Session::put($this->getDiscountKey(), $guestDiscount);
            Session::forget('cart_discount_guest');
        }
    }
}

