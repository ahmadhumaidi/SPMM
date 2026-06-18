<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table): void {
            $table->string('email')->nullable()->after('whatsapp_number')->index();
        });

        Schema::create('student_accounts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('lead_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('email')->unique();
            $table->string('password');
            $table->string('verification_token')->nullable()->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_accounts');

        Schema::table('leads', function (Blueprint $table): void {
            $table->dropColumn('email');
        });
    }
};
