<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fee_schemes', function (Blueprint $table): void {
            $table->unsignedTinyInteger('tuition_semesters')->default(1)->after('monthly_tuition_fee');
        });
    }

    public function down(): void
    {
        Schema::table('fee_schemes', function (Blueprint $table): void {
            $table->dropColumn('tuition_semesters');
        });
    }
};
