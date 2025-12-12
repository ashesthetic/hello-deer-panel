<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class FacebookController extends Controller
{
    /**
     * Post fuel prices to Facebook page
     */
    public function postFuelPrices(Request $request)
    {
        $request->validate([
            'regular87' => 'required|numeric|min:0',
            'midgrade91' => 'required|numeric|min:0',
            'premium94' => 'required|numeric|min:0',
            'diesel' => 'required|numeric|min:0',
        ]);

        try {
            $pageAccessToken = env('FACEBOOK_PAGE_ACCESS_TOKEN');
            $pageId = env('FACEBOOK_PAGE_ID');

            if (!$pageAccessToken || !$pageId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Facebook credentials not configured. Please set FACEBOOK_PAGE_ACCESS_TOKEN and FACEBOOK_PAGE_ID in your .env file.'
                ], 500);
            }

            // Format the post message
            $message = $this->formatFuelPriceMessage(
                $request->regular87,
                $request->midgrade91,
                $request->premium94,
                $request->diesel
            );

            // Post to Facebook using Graph API
            $response = Http::post("https://graph.facebook.com/v18.0/{$pageId}/feed", [
                'message' => $message,
                'access_token' => $pageAccessToken
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                Log::info('Fuel prices posted to Facebook', [
                    'post_id' => $data['id'] ?? null,
                    'prices' => [
                        'regular87' => $request->regular87,
                        'midgrade91' => $request->midgrade91,
                        'premium94' => $request->premium94,
                        'diesel' => $request->diesel,
                    ]
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Fuel prices posted to Facebook successfully!',
                    'post_id' => $data['id'] ?? null
                ]);
            } else {
                $errorData = $response->json();
                Log::error('Facebook API error', [
                    'status' => $response->status(),
                    'error' => $errorData
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Failed to post to Facebook: ' . ($errorData['error']['message'] ?? 'Unknown error')
                ], $response->status());
            }

        } catch (\Exception $e) {
            Log::error('Error posting to Facebook', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while posting to Facebook: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Format fuel prices into a Facebook post message
     */
    private function formatFuelPriceMessage($regular, $midgrade, $premium, $diesel)
    {
        $date = now()->format('F j, Y');
        
        return "⛽ Fuel Prices Update - {$date}\n\n" .
               "💵 Regular (87): \${$regular}\n" .
               "💵 Midgrade (91): \${$midgrade}\n" .
               "💵 Premium (94): \${$premium}\n" .
               "💵 Diesel: \${$diesel}\n\n" .
               "#FuelPrices #GasStation #FuelUpdate";
    }

    /**
     * Test Facebook connection
     */
    public function testConnection()
    {
        try {
            $pageAccessToken = env('FACEBOOK_PAGE_ACCESS_TOKEN');
            $pageId = env('FACEBOOK_PAGE_ID');

            if (!$pageAccessToken || !$pageId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Facebook credentials not configured.'
                ], 500);
            }

            // Test the token by getting page info
            $response = Http::get("https://graph.facebook.com/v18.0/{$pageId}", [
                'fields' => 'id,name',
                'access_token' => $pageAccessToken
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return response()->json([
                    'success' => true,
                    'message' => 'Facebook connection successful!',
                    'page_name' => $data['name'] ?? 'Unknown',
                    'page_id' => $data['id'] ?? 'Unknown'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to connect to Facebook.',
                    'error' => $response->json()
                ], $response->status());
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error testing Facebook connection: ' . $e->getMessage()
            ], 500);
        }
    }
}
