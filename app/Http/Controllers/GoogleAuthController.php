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
            
            // If this is a web request, return a simple HTML page
            if ($request->wantsJson() || $request->header('Accept') === 'application/json') {
                return response()->json([
                    'success' => false,
                    'message' => 'Authorization denied: ' . $error
                ], 400);
            } else {
                return $this->getCallbackHtml(false, 'Authorization denied: ' . $error);
            }
        }

        if (!$code) {
            $message = 'Authorization code not provided';
            
            if ($request->wantsJson() || $request->header('Accept') === 'application/json') {
                return response()->json([
                    'success' => false,
                    'message' => $message
                ], 400);
            } else {
                return $this->getCallbackHtml(false, $message);
            }
        }

        $success = $this->googleDriveService->authenticate($code);

        if ($request->wantsJson() || $request->header('Accept') === 'application/json') {
            if ($success) {
                return response()->json([
                    'success' => true,
                    'message' => 'Google Drive authentication successful'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Authentication failed'
                ], 400);
            }
        } else {
            // Return HTML response for popup window
            if ($success) {
                return $this->getCallbackHtml(true, 'Google Drive authentication successful');
            } else {
                return $this->getCallbackHtml(false, 'Authentication failed');
            }
        }
    }

    /**
     * Generate HTML response for OAuth2 callback popup
     */
    private function getCallbackHtml(bool $success, string $message): \Illuminate\Http\Response
    {
        $statusColor = $success ? '#10B981' : '#EF4444';
        $statusIcon = $success ? '✅' : '❌';
        
        $html = "
        <!DOCTYPE html>
        <html>
        <head>
            <title>Google Drive Authentication</title>
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
                    background: white;
                    border-radius: 0.5rem;
                    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
                    max-width: 400px;
                }
                .status {
                    font-size: 3rem;
                    margin-bottom: 1rem;
                }
                .message {
                    color: {$statusColor};
                    font-size: 1.125rem;
                    font-weight: 600;
                    margin-bottom: 1rem;
                }
                .close-info {
                    color: #6B7280;
                    font-size: 0.875rem;
                }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='status'>{$statusIcon}</div>
                <div class='message'>{$message}</div>
                <div class='close-info'>You can close this window now.</div>
            </div>
            <script>
                // Auto-close the popup after 3 seconds
                setTimeout(() => {
                    window.close();
                }, 3000);
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
        $isAuthenticated = $this->googleDriveService->isAuthenticated();
        
        return response()->json([
            'success' => true,
            'authenticated' => $isAuthenticated
        ]);
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
