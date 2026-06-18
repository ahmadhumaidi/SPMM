<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campus_education_news', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('campus_id')->constrained()->cascadeOnDelete();
            $table->foreignId('education_news_id')->constrained('education_news')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['campus_id', 'education_news_id']);
        });

        DB::table('education_news')
            ->whereNotNull('campus_id')
            ->orderBy('id')
            ->get(['id', 'campus_id'])
            ->each(function ($news): void {
                DB::table('campus_education_news')->updateOrInsert(
                    [
                        'campus_id' => $news->campus_id,
                        'education_news_id' => $news->id,
                    ],
                    [
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                );
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('campus_education_news');
    }
};
