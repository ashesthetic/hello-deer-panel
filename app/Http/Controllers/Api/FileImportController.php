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
}
