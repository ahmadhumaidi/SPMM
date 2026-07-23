<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_accounts', function (Blueprint $table): void {
            if (! Schema::hasColumn('student_accounts', 'password_reset_token')) {
                $table->string('password_reset_token', 80)->nullable()->unique()->after('verification_token');
            }

            if (! Schema::hasColumn('student_accounts', 'password_reset_sent_at')) {
                $table->timestamp('password_reset_sent_at')->nullable()->after('password_reset_token');
            }
        });
    }

    public function down(): void
    {
        Schema::table('student_accounts', function (Blueprint $table): void {
            if (Schema::hasColumn('student_accounts', 'password_reset_token')) {
                $table->dropColumn('password_reset_token');
            }

            if (Schema::hasColumn('student_accounts', 'password_reset_sent_at')) {
                $table->dropColumn('password_reset_sent_at');
            }
        });
    }
};
