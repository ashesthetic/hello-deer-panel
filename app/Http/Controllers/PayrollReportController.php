<?php

namespace App\Http\Controllers;

use App\Models\PayrollReport;
use App\Services\PayrollPdfParser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PayrollReportController extends Controller
{
    protected $pdfParser;

    public function __construct(PayrollPdfParser $pdfParser)
    {
        $this->pdfParser = $pdfParser;
    }

    /**
     * Get all payroll reports.
     */
    public function index()
    {
        $reports = PayrollReport::with('user')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($reports);
    }

    /**
     * Upload a payroll report PDF.
     */
    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:pdf|max:10240', // Max 10MB
        ]);

        $file = $request->file('file');
        $originalName = $file->getClientOriginalName();
        $fileName = time() . '_' . $originalName;
        $filePath = $file->storeAs('payroll-reports', $fileName, 'private');

        $report = PayrollReport::create([
            'file_name' => $fileName,
            'file_path' => $filePath,
            'original_name' => $originalName,
            'file_size' => $file->getSize(),
            'status' => 'pending',
            'user_id' => auth()->id(),
        ]);

        return response()->json([
            'message' => 'Payroll report uploaded successfully',
            'report' => $report,
        ], 201);
    }

    /**
     * Get a specific payroll report.
     */
    public function show($id)
    {
        $report = PayrollReport::with('user')->findOrFail($id);
        return response()->json($report);
    }

    /**
     * Process a payroll report (extract and parse data).
     */
    public function process($id)
    {
        $report = PayrollReport::findOrFail($id);

        if ($report->status === 'processed') {
            return response()->json([
                'message' => 'Report already processed',
                'report' => $report,
            ]);
        }

        try {
            $fullPath = Storage::disk('private')->path($report->file_path);
            
            $result = $this->pdfParser->parse($fullPath);

            $report->update([
                'extracted_text' => json_encode($result['employees']), // Store all page texts
                'parsed_data' => $result,
                'status' => 'processed',
            ]);

            return response()->json([
                'message' => 'Report processed successfully',
                'report' => $report,
            ]);
        } catch (\Exception $e) {
            $report->update(['status' => 'failed']);

            return response()->json([
                'message' => 'Failed to process report',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Match employee names from PDF to database employees.
     */
    public function matchEmployees(Request $request)
    {
        $employeeNames = $request->input('employee_names', []);
        $matches = [];

        foreach ($employeeNames as $name) {
            // Try to find employee by full legal name or preferred name
            $employee = \App\Models\User::where('role', 'employee')
                ->where(function($query) use ($name) {
                    $query->whereRaw('LOWER(full_legal_name) LIKE ?', ['%' . strtolower($name) . '%'])
                          ->orWhereRaw('LOWER(preferred_name) LIKE ?', ['%' . strtolower($name) . '%']);
                })
                ->first();

            if ($employee) {
                // Get the employee details from employees table
                $employeeDetails = \App\Models\Employee::where('user_id', $employee->id)->first();
                
                $matches[] = [
                    'pdf_name' => $name,
                    'matched' => true,
                    'employee_id' => $employeeDetails ? $employeeDetails->id : null,
                    'employee_name' => $employeeDetails ? 
                        ($employeeDetails->preferred_name ?: $employeeDetails->full_legal_name) : 
                        $employee->name,
                ];
            } else {
                $matches[] = [
                    'pdf_name' => $name,
                    'matched' => false,
                    'employee_id' => null,
                    'employee_name' => null,
                ];
            }
        }

        return response()->json($matches);
    }

    /**
     * Delete a payroll report (soft delete).
     */
    public function destroy($id)
    {
        $report = PayrollReport::findOrFail($id);
        
        // Delete the file from storage
        if (Storage::disk('private')->exists($report->file_path)) {
            Storage::disk('private')->delete($report->file_path);
        }

        $report->delete();

        return response()->json([
            'message' => 'Payroll report deleted successfully',
        ]);
    }
}
