<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SftUpload;
use App\Models\FileImport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use ZipArchive;

class SftUploadController extends Controller
{
    /**
     * Upload SFT zip file
     */
    public function upload(Request $request)
    {
        $request->validate([
            'upload_date' => 'required|date',
            'file' => 'required|file|mimes:zip|max:102400', // 100MB max
        ]);

        $uploadDate = Carbon::parse($request->input('upload_date'));
        $file = $request->file('file');
        
        // Format filename as DD-MMM-YYYY-sft.zip (e.g., 11-jan-2026-sft.zip)
        $formattedDate = strtolower($uploadDate->format('d-M-Y'));
        $fileName = $formattedDate . '-sft.zip';
        
        // Store file in /storage/private/data/sft folder
        $filePath = $file->storeAs('private/data/sft', $fileName);
        
        // Check if a file with the same date already exists
        $existingUpload = SftUpload::where('upload_date', $uploadDate->format('Y-m-d'))->first();
        
        if ($existingUpload) {
            // Delete old file
            if (Storage::exists($existingUpload->file_path)) {
                Storage::delete($existingUpload->file_path);
            }
            
            // Update existing record
            $existingUpload->update([
                'file_name' => $fileName,
                'file_path' => $filePath,
                'uploaded_by' => $request->user()->id,
            ]);
            
            $sftUpload = $existingUpload;
            $message = 'SFT file updated successfully';
        } else {
            // Create new database record
            $sftUpload = SftUpload::create([
                'upload_date' => $uploadDate->format('Y-m-d'),
                'file_name' => $fileName,
                'file_path' => $filePath,
                'uploaded_by' => $request->user()->id,
            ]);
            
            $message = 'SFT file uploaded successfully';
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $sftUpload->load('uploader'),
        ], 201);
    }

    /**
     * Get all SFT uploads
     */
    public function index(Request $request)
    {
        $query = SftUpload::with('uploader');
        
        // Filter by date if provided
        if ($request->has('upload_date')) {
            $query->where('upload_date', $request->input('upload_date'));
        }
        
        $sftUploads = $query->orderBy('upload_date', 'desc')->paginate(20);
        
        return response()->json($sftUploads);
    }

    /**
     * Get specific SFT upload
     */
    public function show(SftUpload $sftUpload)
    {
        return response()->json([
            'success' => true,
            'data' => $sftUpload->load('uploader'),
        ]);
    }

    /**
     * Delete SFT upload
     */
    public function destroy(SftUpload $sftUpload)
    {
        try {
            // Delete file from storage
            if (Storage::exists($sftUpload->file_path)) {
                Storage::delete($sftUpload->file_path);
            }
            
            // Delete database record
            $sftUpload->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'SFT upload deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting SFT upload: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get upload statistics
     */
    public function stats()
    {
        $totalUploads = SftUpload::count();
        
        // Get recent upload dates
        $recentDates = SftUpload::select('upload_date')
            ->distinct()
            ->orderBy('upload_date', 'desc')
            ->limit(10)
            ->pluck('upload_date');
        
        return response()->json([
            'total_uploads' => $totalUploads,
            'recent_upload_dates' => $recentDates,
        ]);
    }

    /**
     * Extract zip and list SFT files from logs folder
     */
    public function extractAndListFiles(SftUpload $sftUpload)
    {
        try {
            // Use Storage facade to get the correct path
            $zipPath = Storage::path($sftUpload->file_path);
            
            if (!Storage::exists($sftUpload->file_path)) {
                return response()->json([
                    'success' => false,
                    'message' => 'ZIP file not found in storage',
                    'debug' => [
                        'file_path' => $sftUpload->file_path,
                        'full_path' => $zipPath,
                        'storage_exists' => Storage::exists($sftUpload->file_path),
                        'file_exists' => file_exists($zipPath),
                    ]
                ], 404);
            }

            $zip = new ZipArchive;
            
            if ($zip->open($zipPath) !== true) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to open ZIP file',
                    'debug' => [
                        'zip_path' => $zipPath,
                    ]
                ], 500);
            }

            $sftFiles = [];
            
            // Look for logs folder in the zip
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $filename = $zip->getNameIndex($i);
                $basename = basename($filename);
                
                // Skip files that start with ._ (macOS metadata files)
                if (strpos($basename, '._') === 0) {
                    continue;
                }
                
                // Check if file is in logs folder and has .SFT extension
                if (stripos($filename, 'logs/') !== false && 
                    strtoupper(pathinfo($filename, PATHINFO_EXTENSION)) === 'SFT') {
                    
                    $stat = $zip->statIndex($i);
                    $sftFiles[] = [
                        'name' => $basename,
                        'path' => $filename,
                        'size' => $stat['size'],
                        'compressed_size' => $stat['comp_size'],
                    ];
                }
            }

            $zip->close();

            return response()->json([
                'success' => true,
                'data' => [
                    'upload_id' => $sftUpload->id,
                    'file_name' => $sftUpload->file_name,
                    'sft_files' => $sftFiles,
                    'total_files' => count($sftFiles),
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error extracting files: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Import SFT files from ZIP to file_imports table
     */
    public function importSftFiles(SftUpload $sftUpload)
    {
        try {
            // Use Storage facade to get the correct path
            $zipPath = Storage::path($sftUpload->file_path);
            
            if (!Storage::exists($sftUpload->file_path)) {
                return response()->json([
                    'success' => false,
                    'message' => 'ZIP file not found in storage',
                ], 404);
            }

            $zip = new ZipArchive;
            
            if ($zip->open($zipPath) !== true) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to open ZIP file',
                ], 500);
            }

            // Create folder name based on upload date (matching file_imports pattern)
            $folderName = 'imports/' . Carbon::parse($sftUpload->upload_date)->format('Y-m-d');
            
            $importedFiles = [];
            $errors = [];
            $skippedFiles = [];
            
            // Look for SFT files in logs folder
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $filename = $zip->getNameIndex($i);
                $basename = basename($filename);
                
                // Skip files that start with ._ (macOS metadata files)
                if (strpos($basename, '._') === 0) {
                    continue;
                }
                
                // Check if file is in logs folder and has .SFT extension
                if (stripos($filename, 'logs/') !== false && 
                    strtoupper(pathinfo($filename, PATHINFO_EXTENSION)) === 'SFT') {
                    
                    try {
                        // Check if this file already exists in file_imports for this date
                        $existingImport = FileImport::where('import_date', $sftUpload->upload_date)
                            ->where('original_name', $basename)
                            ->first();
                        
                        if ($existingImport) {
                            $skippedFiles[] = [
                                'name' => $basename,
                                'reason' => 'Already imported',
                            ];
                            continue;
                        }
                        
                        // Extract file content from zip
                        $fileContent = $zip->getFromIndex($i);
                        
                        if ($fileContent === false) {
                            $errors[] = [
                                'file' => $basename,
                                'error' => 'Failed to extract file from ZIP',
                            ];
                            continue;
                        }
                        
                        // Generate unique filename
                        $extension = pathinfo($basename, PATHINFO_EXTENSION);
                        $uniqueFileName = uniqid() . '_' . time() . '.' . $extension;
                        
                        // Store file in the imports folder
                        $filePath = $folderName . '/' . $uniqueFileName;
                        Storage::disk('local')->put($filePath, $fileContent);
                        
                        // Create file_imports record
                        $fileImport = FileImport::create([
                            'import_date' => $sftUpload->upload_date,
                            'file_name' => $uniqueFileName,
                            'original_name' => $basename,
                            'file_path' => $filePath,
                            'file_size' => strlen($fileContent),
                            'mime_type' => 'application/octet-stream',
                            'processed' => 0,
                        ]);
                        
                        $importedFiles[] = [
                            'id' => $fileImport->id,
                            'original_name' => $basename,
                            'file_size' => strlen($fileContent),
                        ];
                        
                    } catch (\Exception $e) {
                        $errors[] = [
                            'file' => $basename,
                            'error' => $e->getMessage(),
                        ];
                    }
                }
            }

            $zip->close();

            return response()->json([
                'success' => true,
                'message' => count($importedFiles) . ' SFT file(s) imported successfully',
                'data' => [
                    'imported_files' => $importedFiles,
                    'skipped_files' => $skippedFiles,
                    'errors' => $errors,
                    'total_imported' => count($importedFiles),
                    'total_skipped' => count($skippedFiles),
                    'total_errors' => count($errors),
                    'folder_path' => $folderName,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error importing files: ' . $e->getMessage(),
            ], 500);
        }
    }
}
