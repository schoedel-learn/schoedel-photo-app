<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Package;
use App\Models\Photo;
use App\Notifications\PreOrderFinalizedNotification;
use App\Notifications\PreOrderReadyToFinalizeNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PreOrderService
{
    /**
     * Calculate upsell for additional photos beyond package limit.
     *
     * @param Package $package
     * @param int $selectedPhotoCount
     * @param float $additionalPhotoPrice
     * @return float
     */
    public function calculateUpsell(Package $package, int $selectedPhotoCount, float $additionalPhotoPrice = 5.00): float
    {
        if ($selectedPhotoCount <= $package->photo_count) {
            return 0.00;
        }

        $additionalPhotos = $selectedPhotoCount - $package->photo_count;
        return $additionalPhotos * $additionalPhotoPrice;
    }

    /**
     * Finalize pre-order with selected photos.
     *
     * @param Order $order
     * @param array $photoIds
     * @return Order
     */
    public function finalizePreOrder(Order $order, array $photoIds): Order
    {
        if (!$order->isPreOrder()) {
            throw new \InvalidArgumentException('Order is not a pre-order');
        }

        if ($order->status !== 'pre_order_selecting') {
            throw new \InvalidArgumentException('Order is not in selecting status');
        }

        DB::beginTransaction();

        try {
            $package = $order->package;
            $selectedCount = count($photoIds);

            // Validate minimum photo count
            if ($selectedCount < $package->photo_count) {
                throw new \InvalidArgumentException(
                    "Package requires at least {$package->photo_count} photos, but only {$selectedCount} were selected."
                );
            }

            // Calculate upsell
            $upsellAmount = $this->calculateUpsell($package, $selectedCount);

            // Create order items for each selected photo
            foreach ($photoIds as $photoId) {
                $photo = Photo::findOrFail($photoId);

                // Verify photo belongs to order's gallery
                if ($photo->gallery_id !== $order->gallery_id) {
                    throw new \InvalidArgumentException("Photo {$photoId} does not belong to order's gallery");
                }

                // Determine if this is included or upsell
                $photoIndex = array_search($photoId, $photoIds);
                $isUpsell = $photoIndex >= $package->photo_count;

                $order->items()->create([
                    'photo_id' => $photoId,
                    'product_type' => 'photo',
                    'product_details' => [
                        'type' => $isUpsell ? 'upsell' : 'included',
                        'includes_digital' => $package->includes_digital,
                        'includes_print' => $package->includes_prints,
                    ],
                    'quantity' => 1,
                    'unit_price' => $isUpsell ? 5.00 : 0.00, // Included photos are $0
                    'total_price' => $isUpsell ? 5.00 : 0.00,
                ]);
            }

            // Update order totals
            $subtotal = $order->subtotal + $upsellAmount;
            $tax = $subtotal * 0.08; // 8% tax
            $total = $subtotal + $tax;

            $order->update([
                'selected_photo_count' => $selectedCount,
                'upsell_amount' => $upsellAmount,
                'subtotal' => $subtotal,
                'tax' => $tax,
                'total' => $total,
                'status' => 'pre_order_finalized',
            ]);

            DB::commit();

            // Notify client that order is finalized
            $order->user->notify(new PreOrderFinalizedNotification($order));

            Log::info('Pre-order finalized', [
                'order_id' => $order->id,
                'selected_count' => $selectedCount,
                'upsell_amount' => $upsellAmount,
                'total' => $total,
            ]);

            return $order->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to finalize pre-order', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Initialize finalization process (move order to selecting status).
     *
     * @param Order $order
     * @param int $galleryId
     * @return Order
     */
    public function initializeFinalization(Order $order, int $galleryId): Order
    {
        if (!$order->isPreOrder()) {
            throw new \InvalidArgumentException('Order is not a pre-order');
        }

        if (!in_array($order->status, ['pre_order_paid', 'pre_order_pending'])) {
            throw new \InvalidArgumentException('Order must be in paid or pending status to begin finalization');
        }

        $order->update([
            'gallery_id' => $galleryId,
            'status' => 'pre_order_selecting',
        ]);

        // Notify client that selection is ready
        $order->user->notify(new PreOrderReadyToFinalizeNotification($order));

        Log::info('Pre-order finalization initialized', [
            'order_id' => $order->id,
            'gallery_id' => $galleryId,
        ]);

        return $order->fresh();
    }

    /**
     * Get available photos for order selection.
     *
     * @param Order $order
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAvailablePhotos(Order $order)
    {
        if (!$order->gallery_id) {
            return collect([]);
        }

        return Photo::where('gallery_id', $order->gallery_id)
            ->orderBy('sort_order')
            ->orderBy('created_at')
            ->get();
    }
}

