<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('education_news', function (Blueprint $table): void {
            $table->string('source_name')->nullable()->after('author_name');
            $table->text('source_url')->nullable()->after('source_name');
            $table->string('source_hash', 64)->nullable()->unique()->after('source_url');
            $table->timestamp('source_published_at')->nullable()->after('source_hash');
            $table->boolean('generated_by_ai')->default(false)->after('source_published_at');
            $table->timestamp('ai_generated_at')->nullable()->after('generated_by_ai');
            $table->json('ai_prompt_json')->nullable()->after('ai_generated_at');
        });
    }

    public function down(): void
    {
        Schema::table('education_news', function (Blueprint $table): void {
            $table->dropUnique(['source_hash']);
            $table->dropColumn([
                'source_name',
                'source_url',
                'source_hash',
                'source_published_at',
                'generated_by_ai',
                'ai_generated_at',
                'ai_prompt_json',
            ]);
        });
    }
};
