<?php

namespace App\Console\Commands;

use App\Models\Download;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class RegenerateDownloadTokens extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'downloads:regenerate-tokens 
                            {--expired-only : Only regenerate expired download tokens}
                            {--order-id= : Only regenerate for a specific order ID}
                            {--dry-run : Show what would be regenerated without making changes}
                            {--extend-days=7 : Number of days to extend expiry}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Regenerate download tokens for existing downloads (useful after migration or security incident)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $expiredOnly = $this->option('expired-only');
        $orderId = $this->option('order-id');
        $dryRun = $this->option('dry-run');
        $extendDays = (int) $this->option('extend-days');

        if ($dryRun) {
            $this->info('🔍 Running in DRY RUN mode - no changes will be made');
            $this->newLine();
        }

        // Build query
        $query = Download::query();

        if ($expiredOnly) {
            $query->where('expires_at', '<=', now());
            $this->info('📅 Filtering: Expired downloads only');
        }

        if ($orderId) {
            $query->where('order_id', $orderId);
            $this->info("📦 Filtering: Order ID #{$orderId}");
        }

        $downloads = $query->with(['order', 'photo'])->get();

        if ($downloads->isEmpty()) {
            $this->warn('⚠️  No downloads found matching criteria');
            return Command::SUCCESS;
        }

        $this->info("📊 Found {$downloads->count()} download(s) to regenerate");
        $this->newLine();

        if (!$dryRun && !$this->confirm('Are you sure you want to regenerate these download tokens?', false)) {
            $this->info('Cancelled');
            return Command::SUCCESS;
        }

        $this->newLine();
        $progressBar = $this->output->createProgressBar($downloads->count());
        $progressBar->start();

        $regenerated = 0;
        $skipped = 0;
        $errors = 0;

        foreach ($downloads as $download) {
            try {
                if ($dryRun) {
                    // Just count, don't regenerate
                    $regenerated++;
                } else {
                    // Generate new secret and token
                    $secret = Str::random(64);
                    $expiresAt = now()->addDays($extendDays);

                    // Create signed token
                    $token = $this->makeSignedToken([
                        'type' => 'single',
                        'download_id' => $download->id,
                        'secret' => $secret,
                        'expires_at' => $expiresAt->toIso8601String(),
                    ]);

                    // Update download record
                    $download->update([
                        'token' => Hash::make($secret),
                        'download_url' => route('downloads.single', ['token' => $token]),
                        'expires_at' => $expiresAt,
                        'attempts' => 0, // Reset attempts when regenerating
                    ]);

                    $regenerated++;
                }
            } catch (\Exception $e) {
                $errors++;
                $this->newLine();
                $this->error("Failed to regenerate download #{$download->id}: {$e->getMessage()}");
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        // Summary
        $this->info('✨ Regeneration Summary:');
        $this->table(
            ['Status', 'Count'],
            [
                ['Successfully Regenerated', $regenerated],
                ['Skipped', $skipped],
                ['Errors', $errors],
            ]
        );

        if ($dryRun) {
            $this->newLine();
            $this->warn('⚠️  This was a DRY RUN - no changes were made');
            $this->info('Run without --dry-run to apply changes');
        }

        return Command::SUCCESS;
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

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
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
