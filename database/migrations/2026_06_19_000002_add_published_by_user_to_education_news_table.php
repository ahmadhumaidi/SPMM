<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('education_news', function (Blueprint $table): void {
            $table->foreignId('published_by_user_id')
                ->nullable()
                ->after('published_at')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('education_news', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('published_by_user_id');
        });
    }
};
