<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_biodatas', function (Blueprint $table): void {
            $table->string('academic_status')->default('aktif')->after('financial_status');
        });
    }

    public function down(): void
    {
        Schema::table('student_biodatas', function (Blueprint $table): void {
            $table->dropColumn('academic_status');
        });
    }
};
