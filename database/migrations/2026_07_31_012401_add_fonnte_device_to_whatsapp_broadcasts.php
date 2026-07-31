<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('whatsapp_broadcasts', function (Blueprint $table): void {
            $table->string('fonnte_device')->default('primary')->after('recipients_file_path');
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_broadcasts', function (Blueprint $table): void {
            $table->dropColumn('fonnte_device');
        });
    }
};
