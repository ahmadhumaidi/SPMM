<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_accounts', function (Blueprint $table): void {
            $table->string('password_reset_token')->nullable()->unique()->after('verification_token');
            $table->timestamp('password_reset_token_expires_at')->nullable()->after('password_reset_token');
        });
    }

    public function down(): void
    {
        Schema::table('student_accounts', function (Blueprint $table): void {
            $table->dropColumn(['password_reset_token', 'password_reset_token_expires_at']);
        });
    }
};
