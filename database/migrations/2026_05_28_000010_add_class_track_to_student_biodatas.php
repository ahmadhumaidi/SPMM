<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_biodatas', function (Blueprint $table): void {
            $table->foreignId('class_track_id')->nullable()->after('study_program_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('student_biodatas', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('class_track_id');
        });
    }
};
