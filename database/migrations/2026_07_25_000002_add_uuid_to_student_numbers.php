<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_numbers', function (Blueprint $table): void {
            $table->uuid('uuid')->nullable()->after('lead_id');
        });

        foreach (DB::table('student_numbers')->whereNull('uuid')->orderBy('id')->get(['id']) as $row) {
            DB::table('student_numbers')->where('id', $row->id)->update(['uuid' => (string) Str::uuid()]);
        }

        Schema::table('student_numbers', function (Blueprint $table): void {
            $table->unique('uuid');
        });
    }

    public function down(): void
    {
        Schema::table('student_numbers', function (Blueprint $table): void {
            $table->dropUnique(['uuid']);
            $table->dropColumn('uuid');
        });
    }
};
