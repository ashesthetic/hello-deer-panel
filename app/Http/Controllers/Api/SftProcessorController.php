<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SftFileProcessorService;
use Illuminate\Http\Request;

class SftProcessorController extends Controller
{
    private $sftProcessorService;

    public function __construct(SftFileProcessorService $sftProcessorService)
    {
        $this->sftProcessorService = $sftProcessorService;
    }

    /**
     * Process SFT files for a given date and return aggregated sales data
     * Only admin users can access this endpoint
     */
    public function processSalesData(Request $request)
    {
        $user = $request->user();
        
        // Check if user has admin permissions (not staff)
        if ($user->isStaff()) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied. Staff users do not have permission to process sales data.'
            ], 403);
        }

        $request->validate([
            'import_date' => 'required|date'
        ]);

        $importDate = $request->input('import_date');
        
        // Ensure the date is in Y-m-d format for consistency
        try {
            $formattedDate = \Carbon\Carbon::parse($importDate)->format('Y-m-d');
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid date format. Please provide a valid date.'
            ], 400);
        }

        try {
            $result = $this->sftProcessorService->processSftFiles($formattedDate);

            return response()->json($result);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error processing SFT files: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get available import dates that have SFT files
     * Only admin users can access this endpoint
     */
    public function getAvailableImportDates(Request $request)
    {
        $user = $request->user();
        
        // Check if user has admin permissions (not staff)
        if ($user->isStaff()) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied. Staff users do not have permission to access this resource.'
            ], 403);
        }

        try {
            $dates = $this->sftProcessorService->getAvailableImportDates();

            return response()->json([
                'success' => true,
                'data' => $dates
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching import dates: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get SFT files for a specific import date
     * Only admin users can access this endpoint
     */
    public function getSftFilesForDate(Request $request)
    {
        $user = $request->user();
        
        // Check if user has admin permissions (not staff)
        if ($user->isStaff()) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied. Staff users do not have permission to access this resource.'
            ], 403);
        }

        $request->validate([
            'import_date' => 'required|date'
        ]);

        $importDate = $request->input('import_date');
        
        // Ensure the date is in Y-m-d format for consistency
        try {
            $formattedDate = \Carbon\Carbon::parse($importDate)->format('Y-m-d');
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid date format. Please provide a valid date.'
            ], 400);
        }

        try {
            $files = $this->sftProcessorService->getSftFilesForDate($formattedDate);

            return response()->json([
                'success' => true,
                'data' => $files
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching SFT files: ' . $e->getMessage()
            ], 500);
        }
    }
}
