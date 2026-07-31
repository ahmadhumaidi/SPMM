<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('lms_submissions') || Schema::hasIndex('lms_submissions', 'lms_submissions_lead_id_index')) {
            return;
        }

        Schema::table('lms_submissions', function (Blueprint $table): void {
            $table->index('lead_id');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('lms_submissions') || ! Schema::hasIndex('lms_submissions', 'lms_submissions_lead_id_index')) {
            return;
        }

        Schema::table('lms_submissions', function (Blueprint $table): void {
            $table->dropIndex(['lead_id']);
        });
    }
};
