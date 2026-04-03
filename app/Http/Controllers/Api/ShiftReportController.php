<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SftFileProcessorService;
use Illuminate\Http\Request;

class ShiftReportController extends Controller
{
    private $sftProcessorService;

    public function __construct(SftFileProcessorService $sftProcessorService)
    {
        $this->sftProcessorService = $sftProcessorService;
    }

    /**
     * Scan the pos/data directory for .sft files on the given date
     */
    public function scanFiles(Request $request)
    {
        $user = $request->user();

        if ($user->isStaff()) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied. Staff users do not have permission to access shift reports.'
            ], 403);
        }

        $request->validate([
            'date' => 'required|date'
        ]);

        try {
            $formattedDate = \Carbon\Carbon::parse($request->input('date'))->format('Y-m-d');
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid date format.'
            ], 400);
        }

        try {
            $files = $this->sftProcessorService->scanPosDataDirectory($formattedDate);

            return response()->json([
                'success' => true,
                'data' => $files
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error scanning directory: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Process .sft files from the pos/data directory for the given date
     */
    public function processFiles(Request $request)
    {
        $user = $request->user();

        if ($user->isStaff()) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied. Staff users do not have permission to process shift reports.'
            ], 403);
        }

        $request->validate([
            'date' => 'required|date'
        ]);

        try {
            $formattedDate = \Carbon\Carbon::parse($request->input('date'))->format('Y-m-d');
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid date format.'
            ], 400);
        }

        try {
            $result = $this->sftProcessorService->processSftFilesFromPosData($formattedDate);

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error processing shift report: ' . $e->getMessage()
            ], 500);
        }
    }
}
