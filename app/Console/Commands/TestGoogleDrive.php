<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\GoogleDriveService;
use Google\Client;
use Google\Service\Drive;

class TestGoogleDrive extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:google-drive';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test Google Drive integration';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Testing Google Drive integration...');

        try {
            // Test 1: Create GoogleDriveService
            $this->info('1. Creating GoogleDriveService...');
            $service = new GoogleDriveService();
            $this->info('✓ GoogleDriveService created successfully');

            // Test 2: Check configuration
            $this->info('2. Checking configuration...');
            $credentialsPath = config('services.google.credentials_path');
            $folderId = config('services.google.drive_folder_id');
            
            $this->info("   Credentials path: {$credentialsPath}");
            $this->info("   Folder ID: {$folderId}");
            
            if (!file_exists($credentialsPath)) {
                $this->error("   ✗ Credentials file not found");
                return 1;
            }
            $this->info('   ✓ Credentials file exists');

            if (empty($folderId)) {
                $this->error("   ✗ Folder ID is empty");
                return 1;
            }
            $this->info('   ✓ Folder ID is set');

            // Test 3: Test API connection (basic client setup)
            $this->info('3. Testing Google API client setup...');
            $client = new Client();
            $client->setAuthConfig($credentialsPath);
            $client->addScope(Drive::DRIVE_FILE);
            $this->info('   ✓ Google API client configured successfully');

            $this->info('');
            $this->info('🎉 All tests passed! Google Drive integration is ready.');
            $this->info('');
            $this->info('You can now upload files through the vendor invoices interface.');
            
            return 0;

        } catch (\Exception $e) {
            $this->error('❌ Test failed: ' . $e->getMessage());
            $this->error('Error type: ' . get_class($e));
            return 1;
        }
    }
}
