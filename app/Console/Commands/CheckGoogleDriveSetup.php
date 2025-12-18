<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CheckGoogleDriveSetup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'google-drive:check-setup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check Google Drive setup and show service account details';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Checking Google Drive Setup...');
        $this->info('');

        try {
            // Check credentials file
            $credentialsPath = config('services.google.credentials_path');
            $folderId = config('services.google.drive_folder_id');

            if (!file_exists($credentialsPath)) {
                $this->error('❌ Credentials file not found: ' . $credentialsPath);
                return 1;
            }

            $this->info('✅ Credentials file found: ' . $credentialsPath);

            // Read and parse credentials
            $credentials = json_decode(file_get_contents($credentialsPath), true);
            
            if (!$credentials) {
                $this->error('❌ Invalid JSON in credentials file');
                return 1;
            }

            $this->info('✅ Credentials file is valid JSON');

            // Show service account details
            if (isset($credentials['client_email'])) {
                $this->info('');
                $this->info('📧 Service Account Email: ' . $credentials['client_email']);
                $this->info('');
                $this->warn('IMPORTANT: You must share your Google Drive folder with this email address!');
                $this->warn('Give this email "Editor" permissions on the folder.');
                $this->info('');
            } else {
                $this->error('❌ No client_email found in credentials');
                return 1;
            }

            // Show folder ID
            if ($folderId) {
                $this->info('📁 Target Folder ID: ' . $folderId);
                $this->info('🔗 Folder URL: https://drive.google.com/drive/folders/' . $folderId);
            } else {
                $this->error('❌ No folder ID configured');
                return 1;
            }

            $this->info('');
            $this->info('📋 Setup Checklist:');
            $this->info('  1. ✅ Credentials file exists');
            $this->info('  2. ✅ Service account email identified');
            $this->info('  3. ✅ Folder ID configured');
            $this->info('  4. ❓ Folder shared with service account (please verify manually)');
            $this->info('');
            $this->info('To complete setup:');
            $this->info('  → Go to: https://drive.google.com/drive/folders/' . $folderId);
            $this->info('  → Click "Share" button');
            $this->info('  → Add: ' . $credentials['client_email']);
            $this->info('  → Permission: Editor');
            $this->info('  → Click "Send"');

            return 0;

        } catch (\Exception $e) {
            $this->error('❌ Error: ' . $e->getMessage());
            return 1;
        }
    }
}
