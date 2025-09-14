<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Google\Client;
use Google\Service\Drive;

class TestGoogleOAuthSimple extends Command
{
    protected $signature = 'google-drive:test-simple';
    protected $description = 'Simple test of Google OAuth2 credentials without authentication flow';

    public function handle()
    {
        $this->info('Testing Google OAuth2 Credentials Configuration...');
        
        try {
            // Test configuration values
            $clientId = config('services.google.client_id');
            $clientSecret = config('services.google.client_secret');
            $redirectUri = config('services.google.redirect_uri');
            $folderId = config('services.google.drive_folder_id');
            
            if (!$clientId) {
                $this->error('❌ GOOGLE_CLIENT_ID is not configured');
                return 1;
            }
            
            if (!$clientSecret) {
                $this->error('❌ GOOGLE_CLIENT_SECRET is not configured');
                return 1;
            }
            
            if (!$redirectUri) {
                $this->error('❌ GOOGLE_REDIRECT_URI is not configured');
                return 1;
            }
            
            if (!$folderId) {
                $this->error('❌ GOOGLE_DRIVE_FOLDER_ID is not configured');
                return 1;
            }
            
            $this->info('✅ Configuration Values:');
            $this->line("Client ID: {$clientId}");
            $this->line("Client Secret: " . substr($clientSecret, 0, 10) . "...");
            $this->line("Redirect URI: {$redirectUri}");
            $this->line("Folder ID: {$folderId}");
            
            // Test Google Client initialization
            $client = new Client();
            $client->setClientId($clientId);
            $client->setClientSecret($clientSecret);
            $client->setRedirectUri($redirectUri);
            $client->addScope(Drive::DRIVE);
            $client->setAccessType('offline');
            
            $this->info('✅ Google Client initialized successfully');
            
            // Generate auth URL to verify configuration
            $authUrl = $client->createAuthUrl();
            
            $this->info('✅ Authorization URL generated successfully');
            $this->info('');
            $this->info('Configuration is valid! Next steps:');
            $this->info('1. Add files.hellodeer@gmail.com as a test user in Google Cloud Console');
            $this->info('2. Visit: https://console.cloud.google.com/apis/credentials/consent');
            $this->info('3. Click "ADD USERS" and add your email address');
            $this->info('4. Then try the OAuth flow again');
            
            return 0;
            
        } catch (\Exception $e) {
            $this->error('❌ Error: ' . $e->getMessage());
            return 1;
        }
    }
}
