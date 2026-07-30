<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('whatsapp_broadcasts', function (Blueprint $table): void {
            $table->boolean('include_leads')->default(true)->after('lead_status');
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_broadcasts', function (Blueprint $table): void {
            $table->dropColumn('include_leads');
        });
    }
};
