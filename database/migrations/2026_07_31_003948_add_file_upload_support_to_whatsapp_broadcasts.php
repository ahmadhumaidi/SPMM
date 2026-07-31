<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('whatsapp_broadcasts', function (Blueprint $table): void {
            $table->string('recipients_file_path')->nullable()->after('lead_status');
        });

        Schema::table('whatsapp_broadcast_recipients', function (Blueprint $table): void {
            $table->foreignId('lead_id')->nullable()->change();
            $table->string('recipient_name')->nullable()->after('recipient_number');
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_broadcast_recipients', function (Blueprint $table): void {
            $table->dropColumn('recipient_name');
            $table->foreignId('lead_id')->nullable(false)->change();
        });

        Schema::table('whatsapp_broadcasts', function (Blueprint $table): void {
            $table->dropColumn('recipients_file_path');
        });
    }
};
