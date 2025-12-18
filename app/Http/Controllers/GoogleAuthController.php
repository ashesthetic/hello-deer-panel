<?php

namespace App\Http\Controllers;

use App\Services\GoogleDriveService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class GoogleAuthController extends Controller
{
    private $googleDriveService;

    public function __construct(GoogleDriveService $googleDriveService)
    {
        $this->googleDriveService = $googleDriveService;
    }

    /**
     * Get Google OAuth2 authorization URL
     */
    public function getAuthUrl(): JsonResponse
    {
        try {
            $authUrl = $this->googleDriveService->getAuthUrl();
            
            return response()->json([
                'success' => true,
                'auth_url' => $authUrl
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to generate Google auth URL', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate authorization URL'
            ], 500);
        }
    }

    /**
     * Handle OAuth2 callback
     */
    public function handleCallback(Request $request)
    {
        $code = $request->get('code');
        $error = $request->get('error');

        if ($error) {
            Log::warning('Google OAuth2 authorization denied', ['error' => $error]);
            
            // Return HTML with JavaScript redirect
            return $this->getRedirectHtml('/accounting/vendor-invoices/add?auth_error=' . urlencode('Authorization denied: ' . $error));
        }

        if (!$code) {
            $message = 'Authorization code not provided';
            return $this->getRedirectHtml('/accounting/vendor-invoices/add?auth_error=' . urlencode($message));
        }

        $success = $this->googleDriveService->authenticate($code);

        if ($success) {
            // Redirect back to the referring page with success
            return $this->getRedirectHtml('/accounting/vendor-invoices/add?auth_success=1');
        } else {
            return $this->getRedirectHtml('/accounting/vendor-invoices/add?auth_error=' . urlencode('Authentication failed'));
        }
    }

    /**
     * Generate HTML response with JavaScript redirect
     */
    private function getRedirectHtml(string $path): \Illuminate\Http\Response
    {
        $frontendUrl = config('app.frontend_url', 'http://localhost:3000');
        $redirectUrl = $frontendUrl . $path;
        
        $html = "
        <!DOCTYPE html>
        <html>
        <head>
            <title>Redirecting...</title>
            <style>
                body {
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    min-height: 100vh;
                    margin: 0;
                    background-color: #f9fafb;
                }
                .container {
                    text-align: center;
                    padding: 2rem;
                }
                .spinner {
                    border: 3px solid #f3f3f3;
                    border-top: 3px solid #3b82f6;
                    border-radius: 50%;
                    width: 40px;
                    height: 40px;
                    animation: spin 1s linear infinite;
                    margin: 0 auto 1rem;
                }
                @keyframes spin {
                    0% { transform: rotate(0deg); }
                    100% { transform: rotate(360deg); }
                }
                .message {
                    color: #6B7280;
                    font-size: 1rem;
                }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='spinner'></div>
                <div class='message'>Redirecting back to application...</div>
            </div>
            <script>
                window.location.href = '{$redirectUrl}';
            </script>
        </body>
        </html>
        ";
        
        return response($html)->header('Content-Type', 'text/html');
    }

    /**
     * Check authentication status
     */
    public function getAuthStatus(): JsonResponse
    {
        try {
            $isAuthenticated = $this->googleDriveService->isAuthenticated();

            //$token = $this->googleDriveService->getStoredAccessToken();
            //print_r($token);
            
            return response()->json([
                'success' => true,
                'authenticated' => $isAuthenticated,
                'last_check' => now()->toISOString()
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to check Google Drive auth status', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'authenticated' => false,
                'error' => 'Failed to check authentication status'
            ], 500);
        }
    }

    /**
     * Test connection and retry authentication if needed
     */
    public function testConnection(): JsonResponse
    {
        try {
            $isAuthenticated = $this->googleDriveService->isAuthenticated();
            
            if ($isAuthenticated) {
                // Test actual API call to verify connection
                try {
                    // This will test if we can actually make API calls
                    $testResult = $this->googleDriveService->getFileMetadata('test'); // This will fail but tells us auth works
                } catch (\Exception $e) {
                    // If it's an auth error, mark as not authenticated
                    if (strpos($e->getMessage(), 'unauthorized') !== false || 
                        strpos($e->getMessage(), 'invalid_grant') !== false) {
                        $isAuthenticated = false;
                    }
                }
            }
            
            return response()->json([
                'success' => true,
                'authenticated' => $isAuthenticated,
                'connection_test' => $isAuthenticated ? 'passed' : 'failed',
                'timestamp' => now()->toISOString()
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to test Google Drive connection', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'authenticated' => false,
                'connection_test' => 'error',
                'error' => 'Connection test failed'
            ], 500);
        }
    }

    /**
     * Revoke Google Drive access
     */
    public function revokeAccess(): JsonResponse
    {
        try {
            $this->googleDriveService->revokeAccess();
            
            return response()->json([
                'success' => true,
                'message' => 'Google Drive access revoked successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to revoke Google Drive access', [
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to revoke access'
            ], 500);
        }
    }
}
