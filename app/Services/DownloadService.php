<?php

namespace App\Services;

use App\Models\Download;
use App\Models\Order;
use App\Models\Photo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DownloadService
{
    protected int $defaultExpirationDays = 7;
    protected int $defaultMaxAttempts = 3;

    /**
     * Generate download link for a single photo.
     */
    public function generateDownloadLink(Photo $photo, Order $order, ?int $expirationDays = null): Download
    {
        $expirationDays = $expirationDays ?? $this->defaultExpirationDays;
        $expiresAt = now()->addDays($expirationDays);

        // Use firstOrCreate to handle race conditions via unique constraint
        // This is safe because of the unique index on (order_id, photo_id, user_id)
        $secret = Str::random(64);
        
        $download = Download::firstOrCreate(
            [
                'photo_id' => $photo->id,
                'order_id' => $order->id,
                'user_id' => $order->user_id,
            ],
            [
                'token' => Hash::make($secret),
                'download_url' => '', // Will be set below
                'expires_at' => $expiresAt,
                'max_attempts' => $this->defaultMaxAttempts,
                'attempts' => 0,
            ]
        );

        // If this was an existing record, handle expiry
        if (!$download->wasRecentlyCreated) {
            if ($download->isExpired()) {
                // Truly expired - must regenerate token for security
                $this->refreshDownloadToken($download, $expiresAt, true);
                return $download->fresh();
            } elseif ($download->expires_at->lt(now()->addDays(2))) {
                // Expiring soon - extend expiry WITHOUT changing token
                // This preserves existing email links while extending validity
                $download->update(['expires_at' => $expiresAt]);
                return $download->fresh();
            }
            // Still valid, return as-is
            return $download;
        }

        // New record - apply the download URL
        $this->applyDownloadUrl($download, $secret, $expiresAt, false);

        return $download->fresh();
    }

    /**
     * Generate download links for all photos in an order.
     */
    public function generateDownloadLinksForOrder(Order $order): array
    {
        if (!$order->relationLoaded('items')) {
            $order->load(['items.photo']);
        } else {
            $order->loadMissing(['items.photo']);
        }

        // Preload existing downloads to avoid N+1 queries
        // This single query fetches all downloads for this order/user combination
        $existingDownloads = Download::where('order_id', $order->id)
            ->where('user_id', $order->user_id)
            ->get()
            ->keyBy('photo_id');

        $downloads = [];
        $expiresAt = now()->addDays($this->defaultExpirationDays);

        foreach ($order->items as $item) {
            if (!$item->photo_id || !$item->photo) {
                continue;
            }

            $photo = $item->photo;
            $existingDownload = $existingDownloads->get($photo->id);

            if ($existingDownload) {
                // Handle existing download
                if ($existingDownload->isExpired()) {
                    // Truly expired - regenerate token for security
                    $this->refreshDownloadToken($existingDownload, $expiresAt, true);
                    $downloads[] = $existingDownload->fresh();
                } elseif ($existingDownload->expires_at->lt(now()->addDays(2))) {
                    // Expiring soon - extend expiry WITHOUT changing token
                    $existingDownload->update(['expires_at' => $expiresAt]);
                    $downloads[] = $existingDownload->fresh();
                } else {
                    // Still valid
                    $downloads[] = $existingDownload;
                }
            } else {
                // Create new download
                $downloads[] = $this->createNewDownloadLink($photo, $order, $expiresAt);
            }
        }

        return $downloads;
    }

    /**
     * Create a new download link without checking for existing records.
     * Used internally when we already know the download doesn't exist.
     */
    private function createNewDownloadLink(Photo $photo, Order $order, Carbon $expiresAt): Download
    {
        $secret = Str::random(64);

        $download = Download::create([
            'order_id' => $order->id,
            'photo_id' => $photo->id,
            'user_id' => $order->user_id,
            'token' => Hash::make($secret),
            'download_url' => '',
            'expires_at' => $expiresAt,
            'max_attempts' => $this->defaultMaxAttempts,
            'attempts' => 0,
        ]);

        $this->applyDownloadUrl($download, $secret, $expiresAt, false);

        return $download->fresh();
    }

    /**
     * Validate download token.
     */
    public function validateDownloadToken(string $token, Photo $photo, Order $order): ?Download
    {
        $download = $this->validateDownloadFromUrl($token);

        if (!$download) {
            return null;
        }

        if ($download->photo_id !== $photo->id || $download->order_id !== $order->id) {
            return null;
        }

        return $download;
    }

    /**
     * Validate download token from URL.
     */
    public function validateDownloadFromUrl(string $token): ?Download
    {
        try {
            $payload = $this->parseSignedToken($token);
            if (!$payload || ($payload['type'] ?? null) !== 'single') {
                return null;
            }

            $downloadId = (int) ($payload['download_id'] ?? 0);
            $secret = $payload['secret'] ?? null;
            $expiresAt = isset($payload['expires_at']) ? Carbon::parse($payload['expires_at']) : null;

            // Only validate presence, not expiry - DB record is authoritative for expiry
            if (!$downloadId || !$secret || !$expiresAt) {
                return null;
            }

            $download = Download::with(['photo', 'order'])
                ->find($downloadId);

            if (!$download) {
                return null;
            }

            if ($download->isExpired()) {
                return null;
            }

            if (!Hash::check($secret, $download->token)) {
                return null;
            }

            return $download;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Record download attempt.
     */
    public function recordDownload(Download $download, string $ipAddress, ?string $userAgent = null): void
    {
        $download->markAsDownloaded($ipAddress, $userAgent);
    }

    /**
     * Get download URL with token encoded.
     */
    public function getDownloadUrl(Download $download): string
    {
        if ($download->isExpired()) {
            throw new \RuntimeException('Cannot obtain download URL for expired download.');
        }

        // Extend expiry if expiring soon, but don't change the token
        // This prevents breaking existing links sent via email
        if ($download->expires_at->lte(now()->addDays(2))) {
            $download->update(['expires_at' => now()->addDays($this->defaultExpirationDays)]);
            $download->refresh();
        }

        return $download->download_url;
    }

    /**
     * Generate batch download link (ZIP).
     */
    public function generateBatchDownloadLink(Order $order): string
    {
        $expiresAt = now()->addDays($this->defaultExpirationDays);

        $token = $this->makeSignedToken([
            'type' => 'batch',
            'order_id' => $order->id,
            'user_id' => $order->user_id,
            'expires_at' => $expiresAt->toIso8601String(),
        ]);

        return route('downloads.batch', ['token' => $token]);
    }

    /**
     * Validate batch download token.
     */
    public function validateBatchToken(string $token): ?Order
    {
        try {
            $payload = $this->parseSignedToken($token);
            if (!$payload || ($payload['type'] ?? null) !== 'batch') {
                return null;
            }

            $expiresAt = isset($payload['expires_at']) ? Carbon::parse($payload['expires_at']) : null;
            // Only validate presence - expiry will be checked when order is loaded
            if (!$expiresAt) {
                return null;
            }

            $orderId = (int) ($payload['order_id'] ?? 0);
            $userId = (int) ($payload['user_id'] ?? 0);

            if (!$orderId || !$userId) {
                return null;
            }

            $order = Order::where('id', $orderId)
                ->where('user_id', $userId)
                ->first();

            return $order;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Refresh the token and URL for a download.
     */
    private function refreshDownloadToken(Download $download, Carbon $expiresAt, bool $resetAttempts): void
    {
        $secret = Str::random(64);
        $this->applyDownloadUrl($download, $secret, $expiresAt, $resetAttempts);
    }

    /**
     * Persist the signed download URL and hashed secret.
     */
    private function applyDownloadUrl(Download $download, string $secret, Carbon $expiresAt, bool $resetAttempts): void
    {
        $token = $this->makeSignedToken([
            'type' => 'single',
            'download_id' => $download->id,
            'secret' => $secret,
            'expires_at' => $expiresAt->toIso8601String(),
        ]);

        $attributes = [
            'token' => Hash::make($secret),
            'download_url' => route('downloads.single', ['token' => $token]),
            'expires_at' => $expiresAt,
        ];

        if ($resetAttempts) {
            $attributes['attempts'] = 0;
        }

        $download->forceFill($attributes);
        $download->save();
    }

    /**
     * Create an HMAC signed token for download payloads.
     */
    private function makeSignedToken(array $payload): string
    {
        $encoded = $this->base64UrlEncode(json_encode($payload, JSON_THROW_ON_ERROR));
        $signature = hash_hmac('sha256', $encoded, $this->signingKey());

        return $encoded . '.' . $signature;
    }

    /**
     * Decode and verify a signed token payload.
     */
    private function parseSignedToken(string $token): ?array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 2) {
            return null;
        }

        [$encoded, $signature] = $parts;
        $expectedSignature = hash_hmac('sha256', $encoded, $this->signingKey());

        if (!hash_equals($expectedSignature, $signature)) {
            return null;
        }

        $decoded = $this->base64UrlDecode($encoded);
        if ($decoded === null) {
            return null;
        }

        $payload = json_decode($decoded, true, 512, JSON_THROW_ON_ERROR);

        return is_array($payload) ? $payload : null;
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $value): ?string
    {
        $padding = 4 - (strlen($value) % 4);
        if ($padding !== 4) {
            $value .= str_repeat('=', $padding);
        }

        $decoded = base64_decode(strtr($value, '-_', '+/'), true);

        return $decoded === false ? null : $decoded;
    }

    private function signingKey(): string
    {
        $key = config('app.key');

        if (!$key) {
            throw new \RuntimeException('Application key is not configured.');
        }

        if (str_starts_with($key, 'base64:')) {
            $decoded = base64_decode(substr($key, 7), true);
            if ($decoded === false) {
                throw new \RuntimeException('Invalid base64 application key.');
            }
            return $decoded;
        }

        return $key;
    }
}


