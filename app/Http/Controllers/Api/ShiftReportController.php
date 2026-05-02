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
     * List all .sft files in SFT_RECEIVE_PATH with date extracted from file content.
     */
    public function listFiles(Request $request)
    {
        $user = $request->user();

        if ($user->isStaff()) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied. Staff users do not have permission to access shift reports.'
            ], 403);
        }

        try {
            $files = $this->sftProcessorService->listAllSftFiles();

            return response()->json([
                'success' => true,
                'data' => $files
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error listing files: ' . $e->getMessage()
            ], 500);
        }
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
     * Process specific .sft files by filename.
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
            'files' => 'required|array',
            'files.*' => 'string'
        ]);

        try {
            $result = $this->sftProcessorService->processSftFilesByNames($request->input('files'));

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error processing shift report: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Parse specific SFT files and save item sales + department sales to the DB for the given date.
     */
    public function saveItemSales(Request $request)
    {
        $user = $request->user();

        if ($user->isStaff()) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied. Staff users do not have permission to save shift report data.'
            ], 403);
        }

        $request->validate([
            'date' => 'required|date',
            'files' => 'required|array',
            'files.*' => 'string'
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
            $result = $this->sftProcessorService->saveItemAndDepartmentSales($formattedDate, $request->input('files'));

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error saving item sales: ' . $e->getMessage()
            ], 500);
        }
    }
}
