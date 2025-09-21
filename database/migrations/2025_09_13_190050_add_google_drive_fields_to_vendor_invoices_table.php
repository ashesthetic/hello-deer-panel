<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('vendor_invoices', function (Blueprint $table) {
            $table->string('google_drive_file_id')->nullable()->after('invoice_file_path');
            $table->string('google_drive_file_name')->nullable()->after('google_drive_file_id');
            $table->text('google_drive_web_view_link')->nullable()->after('google_drive_file_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vendor_invoices', function (Blueprint $table) {
            $table->dropColumn(['google_drive_file_id', 'google_drive_file_name', 'google_drive_web_view_link']);
        });
    }
};
