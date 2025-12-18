<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class UpdateGoogleDriveFolder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'google-drive:set-folder {folder_id : The Google Drive folder ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update the Google Drive folder ID in the .env file';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $folderId = $this->argument('folder_id');
        
        // Validate folder ID format (should be alphanumeric with dashes/underscores)
        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $folderId)) {
            $this->error('Invalid folder ID format');
            return 1;
        }

        $envPath = base_path('.env');
        
        if (!file_exists($envPath)) {
            $this->error('.env file not found');
            return 1;
        }

        $envContent = file_get_contents($envPath);
        
        // Update the folder ID
        if (preg_match('/^GOOGLE_DRIVE_FOLDER_ID=.*$/m', $envContent)) {
            $envContent = preg_replace('/^GOOGLE_DRIVE_FOLDER_ID=.*$/m', "GOOGLE_DRIVE_FOLDER_ID={$folderId}", $envContent);
        } else {
            $envContent .= "\nGOOGLE_DRIVE_FOLDER_ID={$folderId}";
        }

        file_put_contents($envPath, $envContent);

        $this->info('✅ Google Drive folder ID updated successfully!');
        $this->info('   New folder ID: ' . $folderId);
        $this->info('   Folder URL: https://drive.google.com/drive/folders/' . $folderId);
        $this->info('');
        $this->info('Remember to:');
        $this->info('1. Share this folder with: hello-deer-invoices@hello-deer-472101.iam.gserviceaccount.com');
        $this->info('2. Give "Editor" permissions');
        $this->info('3. Test with: php artisan google-drive:test-access');

        return 0;
    }
}
