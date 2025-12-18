<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Google\Client;
use Google\Service\Drive;

class TestGoogleDriveAccess extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'google-drive:test-access';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test Google Drive folder access permissions';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🧪 Testing Google Drive folder access...');
        $this->info('');

        try {
            // Setup client
            $client = new Client();
            $client->setAuthConfig(config('services.google.credentials_path'));
            $client->addScope(Drive::DRIVE_FILE);
            
            // Configure HTTP client for SSL
            $httpClient = new \GuzzleHttp\Client(['verify' => false]);
            $client->setHttpClient($httpClient);
            
            $service = new Drive($client);
            $folderId = config('services.google.drive_folder_id');

            $this->info('1. Testing folder access...');
            
            // Try to get folder metadata
            try {
                $folder = $service->files->get($folderId, [
                    'fields' => 'id,name,permissions,capabilities',
                    'supportsAllDrives' => true
                ]);
                
                $this->info('✅ Folder found: ' . $folder->getName());
                $this->info('   Folder ID: ' . $folder->getId());
                
            } catch (\Exception $e) {
                $this->error('❌ Cannot access folder: ' . $e->getMessage());
                $this->info('');
                $this->warn('This usually means the folder is not shared with the service account.');
                return 1;
            }

            $this->info('');
            $this->info('2. Testing folder permissions...');
            
            // Try to list files in the folder
            try {
                $files = $service->files->listFiles([
                    'q' => "'{$folderId}' in parents",
                    'fields' => 'files(id,name)',
                    'supportsAllDrives' => true,
                    'includeItemsFromAllDrives' => true
                ]);
                
                $fileCount = count($files->getFiles());
                $this->info("✅ Can list files in folder ({$fileCount} files found)");
                
            } catch (\Exception $e) {
                $this->error('❌ Cannot list files in folder: ' . $e->getMessage());
                return 1;
            }

            $this->info('');
            $this->info('3. Testing write permissions...');
            
            // Try to create a test file
            try {
                $fileMetadata = new \Google\Service\Drive\DriveFile([
                    'name' => 'test_access_' . time() . '.txt',
                    'parents' => [$folderId]
                ]);

                $content = 'This is a test file created by the Google Drive integration test.';
                
                $createdFile = $service->files->create(
                    $fileMetadata,
                    [
                        'data' => $content,
                        'mimeType' => 'text/plain',
                        'uploadType' => 'multipart',
                        'fields' => 'id,name',
                        'supportsAllDrives' => true
                    ]
                );
                
                $this->info('✅ Can create files in folder');
                $this->info('   Test file created: ' . $createdFile->getName());
                
                // Clean up - delete the test file
                $service->files->delete($createdFile->getId(), [
                    'supportsAllDrives' => true
                ]);
                $this->info('   Test file cleaned up');
                
            } catch (\Exception $e) {
                $this->error('❌ Cannot create files in folder: ' . $e->getMessage());
                $this->info('');
                $this->warn('Error details: ' . $e->getMessage());
                
                if (strpos($e->getMessage(), 'storageQuotaExceeded') !== false) {
                    $this->info('');
                    $this->error('🚨 This is the storage quota issue!');
                    $this->info('The folder is not properly shared with the service account.');
                    $this->info('');
                    $this->info('Solutions:');
                    $this->info('1. Make sure you shared the folder with: hello-deer-invoices@hello-deer-472101.iam.gserviceaccount.com');
                    $this->info('2. Give "Editor" permissions (not just "Viewer")');
                    $this->info('3. Or create a new folder and share it properly');
                }
                
                return 1;
            }

            $this->info('');
            $this->info('🎉 All tests passed! Google Drive integration should work now.');
            
            return 0;

        } catch (\Exception $e) {
            $this->error('❌ Test failed: ' . $e->getMessage());
            return 1;
        }
    }
}
