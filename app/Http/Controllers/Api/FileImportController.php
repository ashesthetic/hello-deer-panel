<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FileImport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class FileImportController extends Controller
{
    /**
     * Upload multiple files for import
     */
    public function uploadFiles(Request $request)
    {
        $request->validate([
            'import_date' => 'required|date',
            'files.*' => 'required|file|max:10240', // 10MB max per file
        ]);

        $importDate = $request->input('import_date');
        $files = $request->file('files');
        $uploadedFiles = [];
        $errors = [];

        // Create folder name based on date
        $folderName = 'imports/' . Carbon::parse($importDate)->format('Y-m-d');

        foreach ($files as $file) {
            try {
                // Generate unique filename
                $originalName = $file->getClientOriginalName();
                $extension = $file->getClientOriginalExtension();
                $fileName = uniqid() . '_' . time() . '.' . $extension;
                
                // Store file in the date-based folder
                $filePath = $file->storeAs($folderName, $fileName, 'local');

                // Create database record
                $fileImport = FileImport::create([
                    'import_date' => $importDate,
                    'file_name' => $fileName,
                    'original_name' => $originalName,
                    'file_path' => $filePath,
                    'file_size' => $file->getSize(),
                    'mime_type' => $file->getMimeType(),
                    'processed' => 0,
                ]);

                $uploadedFiles[] = [
                    'id' => $fileImport->id,
                    'original_name' => $originalName,
                    'file_size' => $file->getSize(),
                    'uploaded_at' => $fileImport->created_at,
                ];

            } catch (\Exception $e) {
                $errors[] = [
                    'file' => $originalName,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return response()->json([
            'success' => true,
            'message' => count($uploadedFiles) . ' files uploaded successfully',
            'uploaded_files' => $uploadedFiles,
            'errors' => $errors,
            'folder_path' => $folderName,
        ]);
    }

    /**
     * Get all file imports
     */
    public function index(Request $request)
    {
        $query = FileImport::query();
        
        // Filter by date if provided
        if ($request->has('import_date')) {
            $query->where('import_date', $request->input('import_date'));
        }
        
        // Filter by processed status if provided
        if ($request->has('processed')) {
            $query->where('processed', $request->input('processed'));
        }
        
        $fileImports = $query->orderBy('created_at', 'desc')->paginate(20);
        
        return response()->json($fileImports);
    }

    /**
     * Get file import by ID
     */
    public function show(FileImport $fileImport)
    {
        return response()->json($fileImport);
    }

    /**
     * Update file import (e.g., mark as processed)
     */
    public function update(Request $request, FileImport $fileImport)
    {
        $request->validate([
            'processed' => 'sometimes|in:0,1',
            'notes' => 'sometimes|string|nullable',
        ]);

        $fileImport->update($request->only(['processed', 'notes']));
        
        return response()->json([
            'success' => true,
            'message' => 'File import updated successfully',
            'file_import' => $fileImport,
        ]);
    }

    /**
     * Delete file import and remove file
     */
    public function destroy(FileImport $fileImport)
    {
        try {
            // Remove file from storage
            if (Storage::disk('local')->exists($fileImport->file_path)) {
                Storage::disk('local')->delete($fileImport->file_path);
            }
            
            // Delete database record
            $fileImport->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'File import deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting file import: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get import statistics
     */
    public function stats()
    {
        $totalFiles = FileImport::count();
        $processedFiles = FileImport::where('processed', 1)->count();
        $pendingFiles = FileImport::where('processed', 0)->count();
        
        // Get recent import dates
        $recentDates = FileImport::select('import_date')
            ->distinct()
            ->orderBy('import_date', 'desc')
            ->limit(10)
            ->pluck('import_date');
        
        return response()->json([
            'total_files' => $totalFiles,
            'processed_files' => $processedFiles,
            'pending_files' => $pendingFiles,
            'recent_import_dates' => $recentDates,
        ]);
    }

    /**
     * Process sale data for a specific date
     */
    public function processSaleData(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
        ]);

        $date = Carbon::parse($request->input('date'))->format('Y-m-d');
        
        // Get all files for the specified date
        $fileImports = FileImport::where('import_date', $date)->get();
        
        if ($fileImports->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No files found for the specified date',
                'date' => $date,
                'files' => [],
            ]);
        }

        $processedFiles = [];
        $errors = [];
        $totalCashSaleAmount = 0;

        foreach ($fileImports as $fileImport) {
            try {
                // Parse JSON file to calculate cash sale amount
                $cashAmount = 0;
                
                // Only process JSON files
                if (strtolower(pathinfo($fileImport->original_name, PATHINFO_EXTENSION)) === 'json') {
                    $filePath = storage_path('app/' . $fileImport->file_path);
                    
                    if (file_exists($filePath)) {
                        // Read file with proper UTF-8 handling
                        $jsonContent = file_get_contents($filePath);
                        
                        // Handle potential UTF-8 BOM and encoding issues
                        $jsonContent = mb_convert_encoding($jsonContent, 'UTF-8', 'UTF-8');
                        $jsonContent = trim($jsonContent, "\xEF\xBB\xBF"); // Remove UTF-8 BOM if present
                        
                        // Attempt to decode JSON
                        $data = json_decode($jsonContent, true);
                        
                        // Check for JSON decode errors
                        if (json_last_error() !== JSON_ERROR_NONE) {
                            throw new \Exception('JSON decode error: ' . json_last_error_msg());
                        }
                        
                        if ($data && is_array($data)) {
                            // Iterate through transactions to find Canadian_Cash_Used amounts
                            foreach ($data as $transaction) {
                                if (isset($transaction['Tenders']['Canadian_Cash_Used'])) {
                                    // Convert from cents to dollars (430 -> 4.30)
                                    $cashAmount += $transaction['Tenders']['Canadian_Cash_Used'] / 100;
                                }
                            }
                            
                            $totalCashSaleAmount += $cashAmount;
                        }
                    }
                }

                // Return the file information
                $processedFiles[] = [
                    'id' => $fileImport->id,
                    'original_name' => $fileImport->original_name,
                    'file_name' => $fileImport->file_name,
                    'file_size' => $fileImport->file_size,
                    'mime_type' => $fileImport->mime_type,
                    'status' => $fileImport->processed ? 'processed' : 'pending',
                    'cash_amount' => round($cashAmount, 2),
                    'processed_at' => now()->toISOString(),
                ];

                // Mark file as processed
                $fileImport->update([
                    'processed' => 1,
                    'notes' => 'Processed via sale-data API at ' . now()->toDateTimeString()
                ]);

            } catch (\Exception $e) {
                $errors[] = [
                    'file_id' => $fileImport->id,
                    'file_name' => $fileImport->original_name,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Sale data processing completed',
            'date' => $date,
            'total_files' => $fileImports->count(),
            'processed_files' => count($processedFiles),
            'failed_files' => count($errors),
            'total_cash_sale_amount' => round($totalCashSaleAmount, 2),
            'files' => $processedFiles,
            'errors' => $errors,
            'processed_at' => now()->toISOString(),
        ]);
    }
}
