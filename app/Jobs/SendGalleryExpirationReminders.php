<?php

namespace App\Jobs;

use App\Mail\GalleryExpiringMail;
use App\Models\Gallery;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendGalleryExpirationReminders implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $reminderDays = 7;
        $expirationDate = now()->addDays($reminderDays);

        // Find galleries expiring in 7 days
        $galleries = Gallery::whereNotNull('expires_at')
            ->where('expires_at', '>=', now()->addDays($reminderDays - 1)->startOfDay())
            ->where('expires_at', '<=', $expirationDate->endOfDay())
            ->whereNotNull('published_at')
            ->with('user')
            ->get();

        $remindersSent = 0;

        foreach ($galleries as $gallery) {
            try {
                // Find clients who have interacted with this gallery (have selections or orders)
                // For simplicity, we'll send to the gallery owner's clients or find via orders
                $clients = $this->getGalleryClients($gallery);

                if ($clients->isEmpty()) {
                    continue;
                }

                $daysRemaining = now()->diffInDays($gallery->expires_at, false);

                foreach ($clients as $client) {
                    // Check if user has unsubscribed
                    if ($client->email_unsubscribed ?? false) {
                        continue;
                    }

                    // Send expiration reminder
                    Mail::to($client->email)->send(
                        new GalleryExpiringMail($gallery, $client, $daysRemaining)
                    );

                    $remindersSent++;
                }

                Log::info('Gallery expiration reminders sent', [
                    'gallery_id' => $gallery->id,
                    'clients_count' => $clients->count(),
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to send gallery expiration reminders', [
                    'gallery_id' => $gallery->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('Gallery expiration reminder job completed', [
            'galleries_processed' => $galleries->count(),
            'reminders_sent' => $remindersSent,
        ]);
    }

    /**
     * Get clients who should receive expiration reminders for a gallery.
     */
    private function getGalleryClients(Gallery $gallery): \Illuminate\Database\Eloquent\Collection
    {
        // Find clients who have orders associated with this gallery
        $clientIds = \App\Models\Order::where('gallery_id', $gallery->id)
            ->whereNotNull('user_id')
            ->distinct()
            ->pluck('user_id');

        if ($clientIds->isEmpty()) {
            // If no orders, try to find clients via gallery access (for pre-orders)
            // or return empty collection
            return collect([]);
        }

        return User::whereIn('id', $clientIds)
            ->where('role', 'client')
            ->get();
    }
}
