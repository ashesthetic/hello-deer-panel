<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ContactSubmission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class PublicContactController extends Controller
{
    /**
     * Store a new contact submission
     */
    public function store(Request $request): JsonResponse
    {
        try {
            // Log the incoming request for debugging
            \Log::info('Contact form submission received', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'data' => $request->all()
            ]);

            // Rate limiting: 5 submissions per minute per IP
            $key = 'contact-submission:' . $request->ip();
            if (RateLimiter::tooManyAttempts($key, 5)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Too many submissions. Please try again in a few minutes.',
                ], 429);
            }

            // Honeypot validation: 'website' field should be empty
            if ($request->filled('website')) {
                RateLimiter::hit($key, 600); // 10-minute penalty for honeypot triggers
                return response()->json([
                    'success' => false,
                    'message' => 'Submission rejected.',
                ], 422);
            }

            // Validate the request
            $validator = Validator::make($request->all(), ContactSubmission::getValidationRules());
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Additional security checks
            $name = trim($request->input('name'));
            $email = trim($request->input('email'));
            $message = trim($request->input('message'));
            
            // Check for obvious spam patterns
            if ($this->isSpam($name, $email, $message)) {
                RateLimiter::hit($key, 300); // 5-minute penalty for spam
                return response()->json([
                    'success' => false,
                    'message' => 'Submission rejected',
                ], 422);
            }

            // Store the submission
            $submission = ContactSubmission::create([
                'name' => $name,
                'email' => $email,
                'phone' => $request->input('phone') ? trim($request->input('phone')) : null,
                'message' => $message,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            // Increment rate limit counter
            RateLimiter::hit($key, 60);

            return response()->json([
                'success' => true,
                'message' => 'Thank you for your message. We will get back to you soon!',
                'id' => $submission->id
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong. Please try again later.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Basic spam detection
     */
    private function isSpam(string $name, string $email, string $message): bool
    {
        $spamIndicators = [
            // Common spam phrases
            'viagra', 'cialis', 'casino', 'lottery', 'winner', 'congratulations',
            'million dollars', 'click here', 'make money', 'work from home',
            'free money', 'earn money', 'bitcoin', 'crypto', 'investment opportunity',
            
            // Suspicious patterns
            'http://', 'https://', 'www.', '.com', '.net', '.org'
        ];

        $content = strtolower($name . ' ' . $email . ' ' . $message);
        
        // Check for multiple spam indicators
        $spamCount = 0;
        foreach ($spamIndicators as $indicator) {
            if (strpos($content, strtolower($indicator)) !== false) {
                $spamCount++;
            }
        }
        
        // Too many links or spam phrases
        if ($spamCount >= 3) {
            return true;
        }
        
        // Check for excessive repetitive characters
        if (preg_match('/(.)\1{10,}/', $content)) {
            return true;
        }
        
        // Check for message that's too short or too repetitive
        if (strlen(trim($message)) < 10) {
            return true;
        }
        
        return false;
    }
}
