<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\GoogleToken;
use App\Services\GoogleDriveService;
use Google\Client;

class RefreshGoogleTokens extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'google:refresh-tokens {--dry-run : Show what would be refreshed without actually doing it}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Refresh Google OAuth tokens that are about to expire';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        
        // Find tokens that will expire in the next hour
        $expiringTokens = GoogleToken::where('expires_at', '<=', now()->addHour())
            ->whereNotNull('refresh_token')
            ->get();

        if ($expiringTokens->isEmpty()) {
            $this->info('No tokens need refreshing.');
            return;
        }

        $this->info("Found {$expiringTokens->count()} token(s) to refresh:");

        foreach ($expiringTokens as $token) {
            $this->line("- User ID: {$token->user_id}, Service: {$token->service}, Expires: {$token->expires_at}");
            
            if (!$dryRun) {
                if ($this->refreshToken($token)) {
                    $this->info("  ✓ Refreshed successfully");
                } else {
                    $this->error("  ✗ Failed to refresh");
                }
            }
        }

        if ($dryRun) {
            $this->warn('This was a dry run. Use without --dry-run to actually refresh tokens.');
        }
    }

    /**
     * Refresh a single token
     */
    private function refreshToken(GoogleToken $token): bool
    {
        try {
            $client = new Client();
            $client->setClientId(config('services.google.client_id'));
            $client->setClientSecret(config('services.google.client_secret'));
            $client->setAccessToken($token->toGoogleTokenArray());

            $newToken = $client->fetchAccessTokenWithRefreshToken($token->refresh_token);

            if (isset($newToken['error'])) {
                $this->error("Token refresh error: {$newToken['error']}");
                return false;
            }

            // Update the token
            GoogleToken::createFromGoogleToken($token->user_id, $newToken, $token->service);
            
            return true;
        } catch (\Exception $e) {
            $this->error("Exception refreshing token: {$e->getMessage()}");
            return false;
        }
    }
}
