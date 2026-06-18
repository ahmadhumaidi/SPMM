<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('study_programs', function (Blueprint $table): void {
            $table->string('degree_title')->nullable()->after('degree_level');
            $table->text('prospectus')->nullable()->after('accreditation');
        });
    }

    public function down(): void
    {
        Schema::table('study_programs', function (Blueprint $table): void {
            $table->dropColumn(['degree_title', 'prospectus']);
        });
    }
};
