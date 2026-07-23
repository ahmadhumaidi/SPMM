<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('referral_partners', function (Blueprint $table): void {
            $table->string('password_reset_token', 80)->nullable()->unique()->after('dashboard_token');
            $table->timestamp('password_reset_sent_at')->nullable()->after('password_reset_token');
        });
    }

    public function down(): void
    {
        Schema::table('referral_partners', function (Blueprint $table): void {
            $table->dropUnique(['password_reset_token']);
            $table->dropColumn(['password_reset_token', 'password_reset_sent_at']);
        });
    }
};
