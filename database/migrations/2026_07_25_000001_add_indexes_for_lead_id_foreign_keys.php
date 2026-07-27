<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lead_activities', function (Blueprint $table): void {
            $table->index('lead_id');
        });

        Schema::table('whatsapp_messages', function (Blueprint $table): void {
            $table->index('lead_id');
        });

        Schema::table('lead_prospect_events', function (Blueprint $table): void {
            $table->index('lead_id');
        });

        Schema::table('external_lead_events', function (Blueprint $table): void {
            $table->index('lead_id');
        });

        if (Schema::hasTable('lms_submissions')) {
            Schema::table('lms_submissions', function (Blueprint $table): void {
                $table->index('lead_id');
            });
        }
    }

    public function down(): void
    {
        Schema::table('lead_activities', function (Blueprint $table): void {
            $table->dropIndex(['lead_id']);
        });

        Schema::table('whatsapp_messages', function (Blueprint $table): void {
            $table->dropIndex(['lead_id']);
        });

        Schema::table('lead_prospect_events', function (Blueprint $table): void {
            $table->dropIndex(['lead_id']);
        });

        Schema::table('external_lead_events', function (Blueprint $table): void {
            $table->dropIndex(['lead_id']);
        });

        if (Schema::hasTable('lms_submissions')) {
            Schema::table('lms_submissions', function (Blueprint $table): void {
                $table->dropIndex(['lead_id']);
            });
        }
    }
};
