<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('education_news', function (Blueprint $table): void {
            $table->string('topik_artikel')->nullable()->after('category');
            $table->text('meta_description')->nullable()->after('excerpt');
            $table->boolean('ai_generated')->default(false)->after('generated_by_ai');
            $table->string('tipe_konten')->nullable()->after('ai_generated');
            $table->string('keyword_utama')->nullable()->after('tipe_konten');
            $table->text('keyword_tambahan')->nullable()->after('keyword_utama');
            $table->string('target_pembaca')->nullable()->after('keyword_tambahan');
            $table->string('nama_kampus')->nullable()->after('target_pembaca');
            $table->string('lokasi')->nullable()->after('nama_kampus');
            $table->string('gaya_bahasa')->nullable()->after('lokasi');
            $table->string('tipe_artikel')->nullable()->after('gaya_bahasa');
            $table->string('panjang_artikel')->nullable()->after('tipe_artikel');
        });

        DB::table('education_news')
            ->where('generated_by_ai', true)
            ->update(['ai_generated' => true]);
    }

    public function down(): void
    {
        Schema::table('education_news', function (Blueprint $table): void {
            $table->dropColumn([
                'topik_artikel',
                'meta_description',
                'ai_generated',
                'tipe_konten',
                'keyword_utama',
                'keyword_tambahan',
                'target_pembaca',
                'nama_kampus',
                'lokasi',
                'gaya_bahasa',
                'tipe_artikel',
                'panjang_artikel',
            ]);
        });
    }
};
