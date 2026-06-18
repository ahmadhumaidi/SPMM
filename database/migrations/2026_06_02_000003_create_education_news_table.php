<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('education_news', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('campus_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('category')->default('Pendidikan');
            $table->string('excerpt', 500)->nullable();
            $table->longText('content')->nullable();
            $table->string('image_path')->nullable();
            $table->string('author_name')->nullable();
            $table->string('status')->default('draft')->index();
            $table->timestamp('published_at')->nullable()->index();
            $table->timestamps();
        });

        DB::table('education_news')->insert([
            [
                'title' => 'Kuliah Fleksibel Makin Diminati Pekerja Muda',
                'slug' => 'kuliah-fleksibel-makin-diminati-pekerja-muda',
                'category' => 'Pendidikan',
                'excerpt' => 'Kelas karyawan, full online, hybrid learning, dan RPL menjadi pilihan populer bagi pekerja yang ingin meningkatkan jenjang pendidikan.',
                'content' => '<p>Model kuliah fleksibel membantu mahasiswa menyesuaikan jadwal belajar dengan aktivitas kerja. Pilihan kelas karyawan, full online, hybrid learning, dan RPL memberi ruang lebih luas bagi calon mahasiswa untuk tetap produktif sambil menempuh pendidikan tinggi.</p>',
                'author_name' => 'Kampus Media',
                'status' => 'published',
                'published_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Tips Memilih Program Studi Sesuai Tujuan Karier',
                'slug' => 'tips-memilih-program-studi-sesuai-tujuan-karier',
                'category' => 'Karier',
                'excerpt' => 'Memilih program studi sebaiknya mempertimbangkan minat, prospek kerja, biaya kuliah, serta fleksibilitas jadwal belajar.',
                'content' => '<p>Calon mahasiswa perlu memahami minat, kemampuan, dan tujuan karier sebelum memilih program studi. Selain akreditasi dan biaya, jadwal kuliah serta dukungan kampus juga penting untuk dipertimbangkan.</p>',
                'author_name' => 'Kampus Media',
                'status' => 'published',
                'published_at' => now()->subDay(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'RPL Membuka Peluang Lanjut Kuliah dari Pengalaman Kerja',
                'slug' => 'rpl-membuka-peluang-lanjut-kuliah-dari-pengalaman-kerja',
                'category' => 'RPL',
                'excerpt' => 'Rekognisi Pembelajaran Lampau membantu pengalaman kerja dan pembelajaran nonformal diakui dalam proses pendidikan tinggi.',
                'content' => '<p>Program RPL dapat menjadi jalur strategis bagi profesional yang sudah memiliki pengalaman kerja. Dengan asesmen yang tepat, pengalaman tersebut dapat menjadi bagian dari perjalanan akademik.</p>',
                'author_name' => 'Kampus Media',
                'status' => 'published',
                'published_at' => now()->subDays(2),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('education_news');
    }
};
