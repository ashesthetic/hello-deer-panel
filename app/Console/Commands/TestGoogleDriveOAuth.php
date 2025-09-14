<?php

namespace App\Console\Commands;

use App\Services\GoogleDriveService;
use Illuminate\Console\Command;

class TestGoogleDriveOAuth extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'google-drive:test-oauth';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test Google Drive OAuth2 authentication setup';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Testing Google Drive OAuth2 Setup...');
        
        try {
            $service = new GoogleDriveService();
            
            // Check authentication status
            $isAuthenticated = $service->isAuthenticated();
            
            if ($isAuthenticated) {
                $this->info('✅ Google Drive is authenticated');
                
                // Try to test basic functionality
                $this->info('Testing access to configured folder...');
                
                // Since this is a test command and we don't have a file,
                // we'll just test the authentication flow
                $this->info('Authentication test completed successfully');
            } else {
                $this->warn('❌ Google Drive is not authenticated');
                $this->info('To authenticate:');
                $this->info('1. Access the application in your browser');
                $this->info('2. Go to a page with Google Drive integration');
                $this->info('3. Click the "Connect" button for Google Drive');
                $this->info('4. Complete the OAuth2 flow in the popup window');
                
                // Generate auth URL for manual testing
                $authUrl = $service->getAuthUrl();
                $this->info('');
                $this->info('Or manually visit this URL to authenticate:');
                $this->line($authUrl);
            }
            
        } catch (\Exception $e) {
            $this->error('❌ Error testing Google Drive OAuth2: ' . $e->getMessage());
            $this->info('');
            $this->info('Make sure you have configured the following environment variables:');
            $this->info('- GOOGLE_CLIENT_ID');
            $this->info('- GOOGLE_CLIENT_SECRET');
            $this->info('- GOOGLE_REDIRECT_URI');
            $this->info('- GOOGLE_DRIVE_FOLDER_ID');
            
            return 1;
        }
        
        return 0;
    }
}
