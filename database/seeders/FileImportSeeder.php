<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\FileImport;
use Carbon\Carbon;

class FileImportSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create some sample file imports for testing
        FileImport::create([
            'import_date' => Carbon::today(),
            'file_name' => 'sample_file_1.csv',
            'original_name' => 'sample_file_1.csv',
            'file_path' => 'imports/' . Carbon::today()->format('Y-m-d') . '/sample_file_1.csv',
            'file_size' => 1024,
            'mime_type' => 'text/csv',
            'processed' => 0,
            'notes' => 'Sample file for testing',
        ]);
    }
}
