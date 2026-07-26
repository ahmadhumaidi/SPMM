<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_payments', function (Blueprint $table): void {
            $table->string('receipt_pdf_path')->nullable()->after('notes');
            $table->timestamp('receipt_archived_at')->nullable()->after('receipt_pdf_path');
        });
    }

    public function down(): void
    {
        Schema::table('student_payments', function (Blueprint $table): void {
            $table->dropColumn(['receipt_pdf_path', 'receipt_archived_at']);
        });
    }
};
