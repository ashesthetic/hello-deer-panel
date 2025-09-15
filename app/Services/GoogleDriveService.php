<?php

namespace App\Services;

use Google\Client;
use Google\Service\Drive;
use Google\Service\Drive\DriveFile;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;
use App\Models\GoogleToken;

class GoogleDriveService
{
    private $client;
    private $service;
    private $folderId;

    public function __construct()
    {
        $this->client = new Client();
        $this->client->setClientId(config('services.google.client_id'));
        $this->client->setClientSecret(config('services.google.client_secret'));
        $this->client->setRedirectUri(config('services.google.redirect_uri'));
        $this->client->addScope(Drive::DRIVE);
        $this->client->setAccessType('offline');
        $this->client->setPrompt('consent');
        
        // Configure HTTP client for SSL certificate handling
        $httpClientConfig = [];
        
        // In production, use proper SSL verification
        if (app()->environment('production')) {
            // Use system's CA bundle or specify a path
            $httpClientConfig['verify'] = true;
        } else {
            // For local development, disable SSL verification
            // Note: This is not secure for production use
            $httpClientConfig['verify'] = false;
        }
        
        $httpClient = new \GuzzleHttp\Client($httpClientConfig);
        $this->client->setHttpClient($httpClient);
        
        // Load access token if available
        $this->loadAccessToken();
        
        $this->service = new Drive($this->client);
        $this->folderId = config('services.google.drive_folder_id');
    }

    /**
     * Get the authorization URL for OAuth2 flow
     *
     * @return string
     */
    public function getAuthUrl(): string
    {
        return $this->client->createAuthUrl();
    }

    /**
     * Exchange authorization code for access token
     *
     * @param string $code
     * @return bool
     */
    public function authenticate(string $code): bool
    {
        try {
            $token = $this->client->fetchAccessTokenWithAuthCode($code);
            
            if (isset($token['error'])) {
                Log::error('OAuth2 authentication error', ['error' => $token['error']]);
                return false;
            }
            
            // Store the token
            $this->storeAccessToken($token);
            
            Log::info('Google Drive OAuth2 authentication successful');
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to authenticate with Google Drive', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Check if user is authenticated
     *
     * @return bool
     */
    public function isAuthenticated(): bool
    {
        $token = $this->getStoredAccessToken();
        
        if (!$token) {
            return false;
        }
        
        $this->client->setAccessToken($token);
        
        // Check if token is expired and try to refresh
        if ($this->client->isAccessTokenExpired()) {
            return $this->refreshAccessToken();
        }
        
        return true;
    }

    /**
     * Load access token from storage
     */
    private function loadAccessToken(): void
    {
        $token = $this->getStoredAccessToken();
        
        if ($token) {
            $this->client->setAccessToken($token);
            
            // Check if token is expired and try to refresh
            if ($this->client->isAccessTokenExpired()) {
                $this->refreshAccessToken();
            }
        }
    }

    /**
     * Get stored access token
     *
     * @return array|null
     */
    private function getStoredAccessToken(): ?array
    {
        // Get current user
        $user = Auth::user();
        
        if (!$user) {
            // Fallback to session for unauthenticated requests
            return Session::get('google_drive_token');
        }

        // Try to get from database first
        $googleToken = GoogleToken::where('user_id', $user->id)
            ->where('service', 'google_drive')
            ->first();

        if ($googleToken) {
            // If token exists but is expired, try to refresh it
            if ($googleToken->isExpired() && $googleToken->refresh_token) {
                if ($this->refreshTokenFromDatabase($googleToken)) {
                    // Reload the token after refresh
                    $googleToken->refresh();
                }
            }
            
            return $googleToken->toGoogleTokenArray();
        }

        // Fallback to cache/session for backward compatibility
        $token = Cache::get('google_drive_token');
        if (!$token) {
            $token = Session::get('google_drive_token');
        }
        
        return $token;
    }

    /**
     * Store access token
     *
     * @param array $token
     */
    private function storeAccessToken(array $token): void
    {
        // Get current user
        $user = Auth::user();
        
        if ($user) {
            // Store in database for persistent storage
            GoogleToken::createFromGoogleToken($user->id, $token);
        }

        // Also store in cache and session for backward compatibility and performance
        $expiresIn = $token['expires_in'] ?? 3600;
        Cache::put('google_drive_token', $token, now()->addSeconds($expiresIn - 300)); // Subtract 5 minutes for safety
        Session::put('google_drive_token', $token);
    }

    /**
     * Refresh access token from database
     *
     * @param GoogleToken $googleToken
     * @return bool
     */
    private function refreshTokenFromDatabase(GoogleToken $googleToken): bool
    {
        try {
            $this->client->setAccessToken($googleToken->toGoogleTokenArray());
            
            $newToken = $this->client->fetchAccessTokenWithRefreshToken($googleToken->refresh_token);
            
            if (isset($newToken['error'])) {
                Log::error('Database token refresh error', ['error' => $newToken['error']]);
                return false;
            }
            
            // Update the token in database
            GoogleToken::createFromGoogleToken($googleToken->user_id, $newToken);
            
            Log::info('Google Drive access token refreshed successfully from database');
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to refresh Google Drive access token from database', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Refresh access token
     *
     * @return bool
     */
    private function refreshAccessToken(): bool
    {
        try {
            $refreshToken = $this->client->getRefreshToken();
            
            if (!$refreshToken) {
                Log::warning('No refresh token available');
                return false;
            }
            
            $newToken = $this->client->fetchAccessTokenWithRefreshToken($refreshToken);
            
            if (isset($newToken['error'])) {
                Log::error('Token refresh error', ['error' => $newToken['error']]);
                return false;
            }
            
            // Store the new token
            $this->storeAccessToken($newToken);
            
            Log::info('Google Drive access token refreshed successfully');
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to refresh Google Drive access token', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Revoke access token and clear stored tokens
     */
    public function revokeAccess(): void
    {
        try {
            $this->client->revokeToken();
        } catch (\Exception $e) {
            Log::warning('Failed to revoke token', ['error' => $e->getMessage()]);
        }
        
        // Clear stored tokens from all sources
        Cache::forget('google_drive_token');
        Session::forget('google_drive_token');
        
        // Clear from database
        $user = Auth::user();
        if ($user) {
            GoogleToken::where('user_id', $user->id)
                ->where('service', 'google_drive')
                ->delete();
        }
        
        Log::info('Google Drive access revoked');
    }

    /**
     * Get or create year/month folder structure
     *
     * @param string|null $date Optional date string, defaults to current date
     * @return string|null The folder ID for the year/month folder, or null on failure
     */
    private function getOrCreateYearMonthFolder(?string $date = null): ?string
    {
        try {
            $targetDate = $date ? \Carbon\Carbon::parse($date) : \Carbon\Carbon::now();
            $year = $targetDate->format('Y');
            $month = $targetDate->format('m - F'); // e.g., "09 - September"
            
            Log::info('Getting or creating year/month folder structure', [
                'year' => $year,
                'month' => $month,
                'base_folder_id' => $this->folderId
            ]);
            
            // Get or create year folder
            $yearFolderId = $this->getOrCreateFolder($year, $this->folderId);
            if (!$yearFolderId) {
                Log::error('Failed to create or find year folder', ['year' => $year]);
                return null;
            }
            
            // Get or create month folder within year folder
            $monthFolderId = $this->getOrCreateFolder($month, $yearFolderId);
            if (!$monthFolderId) {
                Log::error('Failed to create or find month folder', ['month' => $month, 'year' => $year]);
                return null;
            }
            
            Log::info('Year/month folder structure ready', [
                'year_folder_id' => $yearFolderId,
                'month_folder_id' => $monthFolderId
            ]);
            
            return $monthFolderId;
            
        } catch (\Exception $e) {
            Log::error('Error creating year/month folder structure', [
                'error' => $e->getMessage(),
                'date' => $date
            ]);
            return null;
        }
    }
    
    /**
     * Get or create a folder by name within a parent folder
     *
     * @param string $folderName
     * @param string $parentFolderId
     * @return string|null The folder ID, or null on failure
     */
    private function getOrCreateFolder(string $folderName, string $parentFolderId): ?string
    {
        try {
            // Search for existing folder
            $query = "name='{$folderName}' and '{$parentFolderId}' in parents and mimeType='application/vnd.google-apps.folder' and trashed=false";
            
            $response = $this->service->files->listFiles([
                'q' => $query,
                'fields' => 'files(id, name)',
                'supportsAllDrives' => true,
                'includeItemsFromAllDrives' => true
            ]);
            
            $files = $response->getFiles();
            
            if (count($files) > 0) {
                // Folder exists, return its ID
                $folderId = $files[0]->getId();
                Log::info('Found existing folder', [
                    'folder_name' => $folderName,
                    'folder_id' => $folderId,
                    'parent_id' => $parentFolderId
                ]);
                return $folderId;
            }
            
            // Folder doesn't exist, create it
            $folderMetadata = new DriveFile([
                'name' => $folderName,
                'mimeType' => 'application/vnd.google-apps.folder'
            ]);
            $folderMetadata->setParents([$parentFolderId]);
            
            $createdFolder = $this->service->files->create($folderMetadata, [
                'fields' => 'id, name',
                'supportsAllDrives' => true
            ]);
            
            $folderId = $createdFolder->getId();
            Log::info('Created new folder', [
                'folder_name' => $folderName,
                'folder_id' => $folderId,
                'parent_id' => $parentFolderId
            ]);
            
            return $folderId;
            
        } catch (\Exception $e) {
            Log::error('Error getting or creating folder', [
                'folder_name' => $folderName,
                'parent_id' => $parentFolderId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Upload a file to Google Drive
     *
     * @param UploadedFile $file
     * @param string $fileName
     * @param string|null $invoiceDate Optional invoice date for folder organization
     * @return array|null Returns array with file_id and web_view_link, or null on failure
     */
    public function uploadFile(UploadedFile $file, string $fileName, ?string $invoiceDate = null): ?array
    {
        if (!$this->isAuthenticated()) {
            Log::error('Google Drive not authenticated for file upload');
            return null;
        }

        try {
            // Get or create year/month folder structure based on invoice date
            $targetFolderId = $this->getOrCreateYearMonthFolder($invoiceDate);
            if (!$targetFolderId) {
                Log::error('Failed to get or create year/month folder, falling back to main folder');
                $targetFolderId = $this->folderId;
            }
            
            Log::info('Starting Google Drive file upload', [
                'file_name' => $fileName,
                'file_size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'target_folder_id' => $targetFolderId,
                'invoice_date' => $invoiceDate
            ]);

            $fileMetadata = new DriveFile([
                'name' => $fileName
            ]);
            $fileMetadata->setParents([$targetFolderId]);

            $content = file_get_contents($file->getPathname());
            $mimeType = $file->getMimeType();

            $createdFile = $this->service->files->create(
                $fileMetadata,
                [
                    'data' => $content,
                    'mimeType' => $mimeType,
                    'uploadType' => 'multipart',
                    'fields' => 'id,webViewLink,name',
                    'supportsAllDrives' => true
                ]
            );

            // Make the file publicly viewable
            $permission = new \Google\Service\Drive\Permission([
                'type' => 'anyone',
                'role' => 'reader'
            ]);
            $this->service->permissions->create($createdFile->getId(), $permission, [
                'supportsAllDrives' => true
            ]);

            Log::info('File uploaded to Google Drive successfully', [
                'file_id' => $createdFile->getId(),
                'file_name' => $fileName,
                'web_view_link' => $createdFile->getWebViewLink()
            ]);

            return [
                'file_id' => $createdFile->getId(),
                'web_view_link' => $createdFile->getWebViewLink(),
                'name' => $createdFile->getName()
            ];
        } catch (\Google\Exception $e) {
            Log::error('Google API error during file upload', [
                'error' => $e->getMessage(),
                'file_name' => $fileName,
                'error_code' => $e->getCode(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        } catch (\Exception $e) {
            Log::error('Failed to upload file to Google Drive', [
                'error' => $e->getMessage(),
                'file_name' => $fileName,
                'error_type' => get_class($e),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    /**
     * Delete a file from Google Drive
     *
     * @param string $fileId
     * @return bool
     */
    public function deleteFile(string $fileId): bool
    {
        if (!$this->isAuthenticated()) {
            Log::error('Google Drive not authenticated for file deletion');
            return false;
        }

        try {
            $this->service->files->delete($fileId, [
                'supportsAllDrives' => true
            ]);
            Log::info('File deleted from Google Drive', ['file_id' => $fileId]);
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to delete file from Google Drive', [
                'error' => $e->getMessage(),
                'file_id' => $fileId
            ]);
            return false;
        }
    }

    /**
     * Get file metadata from Google Drive
     *
     * @param string $fileId
     * @return array|null
     */
    public function getFileMetadata(string $fileId): ?array
    {
        if (!$this->isAuthenticated()) {
            Log::error('Google Drive not authenticated for file metadata');
            return null;
        }

        try {
            $file = $this->service->files->get($fileId, [
                'fields' => 'id,name,mimeType,size,webViewLink,webContentLink',
                'supportsAllDrives' => true
            ]);

            return [
                'id' => $file->getId(),
                'name' => $file->getName(),
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
                'web_view_link' => $file->getWebViewLink(),
                'web_content_link' => $file->getWebContentLink()
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get file metadata from Google Drive', [
                'error' => $e->getMessage(),
                'file_id' => $fileId
            ]);
            return null;
        }
    }

    /**
     * Download file content from Google Drive
     *
     * @param string $fileId
     * @return string|null
     */
    public function downloadFile(string $fileId): ?string
    {
        if (!$this->isAuthenticated()) {
            Log::error('Google Drive not authenticated for file download');
            return null;
        }

        try {
            $response = $this->service->files->get($fileId, [
                'alt' => 'media',
                'supportsAllDrives' => true
            ]);
            return $response->getBody()->getContents();
        } catch (\Exception $e) {
            Log::error('Failed to download file from Google Drive', [
                'error' => $e->getMessage(),
                'file_id' => $fileId
            ]);
            return null;
        }
    }
}
