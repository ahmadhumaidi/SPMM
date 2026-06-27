<?php

namespace App\Services;

use App\Models\EducationNews;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use SimpleXMLElement;
use Throwable;

class AiEducationNewsDraftService
{
    public function createDraft(?array $campusIds = null, ?string $topic = null): EducationNews
    {
        $source = $this->nextSourceItem($topic);
        $trendContext = $this->trendContext($topic);
        $editorialKnowledge = config('spmm.ai_news.editorial_knowledge', []);
        $article = $this->generateArticle($source, $topic, $trendContext, $editorialKnowledge);
        $imagePath = $this->generateCoverImagePath($article, $source, $topic, $editorialKnowledge);

        $news = EducationNews::query()->create([
            'title' => $article['title'],
            'slug' => $this->uniqueSlug($article['title']),
            'category' => $article['category'],
            'excerpt' => $article['excerpt'],
            'content' => $article['content'],
            'image_path' => $imagePath,
            'author_name' => 'Kampus Media AI',
            'source_name' => $source['source_name'],
            'source_url' => $source['url'],
            'source_hash' => $source['hash'],
            'source_published_at' => $source['published_at'],
            'generated_by_ai' => filled(config('spmm.ai_news.openai_api_key')),
            'ai_generated_at' => now(),
            'ai_prompt_json' => [
                'topic' => $topic,
                'source_title' => $source['title'],
                'source_excerpt' => $source['excerpt'],
                'trend_context' => $trendContext,
                'editorial_knowledge' => $editorialKnowledge,
                'image_prompt' => $this->imagePrompt($article, $source, $topic, $editorialKnowledge),
                'image_path' => $imagePath,
                'fallback_used' => $article['fallback_used'],
            ],
            'status' => 'draft',
            'published_at' => null,
        ]);

        if (! empty($campusIds)) {
            $news->campuses()->sync($campusIds);
        }

        return $news;
    }

    public function createSeoArticleDraft(string $keyword, ?array $campusIds = null): EducationNews
    {
        $keyword = Str::of($keyword)->squish()->limit(160)->toString();
        $trendContext = $this->trendContext($keyword);
        $editorialKnowledge = config('spmm.ai_news.editorial_knowledge', []);
        $source = [
            'source_name' => 'Kampus Media AI',
            'title' => $keyword,
            'url' => null,
            'hash' => null,
            'excerpt' => null,
            'published_at' => null,
        ];
        $article = $this->generateKeywordArticle($keyword, $trendContext, $editorialKnowledge);
        $imagePath = $this->generateCoverImagePath($article, $source, $keyword, $editorialKnowledge);

        $news = EducationNews::query()->create([
            'title' => $article['title'],
            'slug' => $this->uniqueSlug($article['title']),
            'category' => $article['category'],
            'excerpt' => $article['excerpt'],
            'content' => $article['content'],
            'image_path' => $imagePath,
            'author_name' => 'Kampus Media AI',
            'source_name' => 'Artikel SEO AI',
            'source_url' => null,
            'source_hash' => null,
            'source_published_at' => null,
            'generated_by_ai' => filled(config('spmm.ai_news.openai_api_key')),
            'ai_generated_at' => now(),
            'ai_prompt_json' => [
                'mode' => 'seo_article',
                'keyword' => $keyword,
                'trend_context' => $trendContext,
                'editorial_knowledge' => $editorialKnowledge,
                'image_prompt' => $this->imagePrompt($article, $source, $keyword, $editorialKnowledge),
                'image_path' => $imagePath,
                'fallback_used' => $article['fallback_used'],
            ],
            'status' => 'draft',
            'published_at' => null,
        ]);

        if (! empty($campusIds)) {
            $news->campuses()->sync($campusIds);
        }

        return $news;
    }

    private function nextSourceItem(?string $topic = null): array
    {
        $items = collect($this->rssSources($topic))
            ->flatMap(fn (array $source): array => $this->fetchSourceItems($source))
            ->sortByDesc('published_at')
            ->values();

        foreach ($items as $item) {
            if (! EducationNews::query()->where('source_hash', $item['hash'])->exists()) {
                return $item;
            }
        }

        throw new RuntimeException('Tidak ada berita baru dari sumber RSS, atau semua berita sudah pernah dibuat draft.');
    }

    private function rssSources(?string $topic = null): array
    {
        $sources = config('spmm.ai_news.sources', []);

        if (filled($topic)) {
            $query = urlencode($topic.' pendidikan tinggi Indonesia');
            $sources = array_merge([
                'Topik Custom' => "https://news.google.com/rss/search?q={$query}&hl=id&gl=ID&ceid=ID:id",
            ], $sources);
        }

        return collect($sources)
            ->map(fn (string $url, string $name): array => ['name' => $name, 'url' => $url])
            ->values()
            ->all();
    }

    private function fetchSourceItems(array $source): array
    {
        try {
            $response = Http::timeout(12)->retry(2, 300)->get($source['url']);

            if (! $response->successful()) {
                return [];
            }

            $rss = @simplexml_load_string($response->body(), SimpleXMLElement::class, LIBXML_NOCDATA);

            if (! $rss || ! isset($rss->channel->item)) {
                return [];
            }

            $items = [];

            foreach ($rss->channel->item as $item) {
                $title = trim((string) $item->title);
                $url = trim((string) $item->link);

                if ($title === '' || $url === '') {
                    continue;
                }

                $items[] = [
                    'source_name' => $source['name'],
                    'title' => html_entity_decode($title),
                    'url' => $url,
                    'hash' => hash('sha256', $url),
                    'excerpt' => Str::of(strip_tags((string) $item->description))->squish()->limit(500)->toString(),
                    'published_at' => filled((string) $item->pubDate)
                        ? Carbon::parse((string) $item->pubDate)
                        : null,
                ];
            }

            return $items;
        } catch (Throwable) {
            return [];
        }
    }

    private function trendContext(?string $topic = null): array
    {
        $rssTrends = collect(config('spmm.ai_news.trend_sources', []))
            ->flatMap(fn (string $url, string $name): array => $this->fetchSourceItems(['name' => $name, 'url' => $url]))
            ->take(8)
            ->map(fn (array $item): array => [
                'title' => $item['title'],
                'source_name' => $item['source_name'],
                'url' => $item['url'],
            ])
            ->values()
            ->all();

        return [
            'topic' => $topic,
            'education_keywords' => config('spmm.ai_news.trend_keywords', []),
            'google_trends_indonesia' => $rssTrends,
            'seo_focus' => [
                'kampus terpercaya',
                'jurusan kuliah',
                'prospek kerja',
                'kuliah karyawan',
                'kelas karyawan',
                'kuliah online',
                'hybrid learning',
                'program RPL',
                'biaya kuliah',
                'PDDIKTI',
                'akreditasi BAN-PT/LAM',
            ],
            'editorial_rule' => 'Gunakan tren sebagai inspirasi sudut pandang, bukan sebagai klaim statistik. Kaitkan secara wajar dengan kebutuhan calon mahasiswa, pilihan program studi, fleksibilitas kuliah, karier, dan biaya pendidikan.',
        ];
    }

    private function generateArticle(array $source, ?string $topic = null, array $trendContext = [], array $editorialKnowledge = []): array
    {
        $fallback = $this->fallbackArticle($source, $topic, $trendContext, $editorialKnowledge);
        $apiKey = config('spmm.ai_news.openai_api_key');

        if (blank($apiKey)) {
            return $fallback;
        }

        try {
            $response = Http::withToken($apiKey)
                ->timeout(30)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => config('spmm.ai_news.openai_model', 'gpt-4.1-mini'),
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'Anda adalah editor SEO pendidikan Indonesia untuk Kampus Media. Tulis artikel orisinal, aman, tidak copy-paste, informatif, dan berorientasi calon mahasiswa. Artikel harus membantu pembaca memilih kampus, jurusan, kelas fleksibel, RPL, biaya, dan prospek karier tanpa membuat klaim yang tidak punya dasar.',
                        ],
                        [
                            'role' => 'user',
                            'content' => json_encode([
                                'instruction' => 'Buat draft artikel SEO pendidikan untuk Kampus Media. Jangan mengklaim hal yang tidak ada di sumber. Sertakan konteks manfaat untuk calon mahasiswa, karyawan, lulusan D3, calon peserta RPL, dan orang tua. Gunakan konteks Google Trends Indonesia dan keyword tren pendidikan sebagai inspirasi angle, bukan sebagai data statistik pasti. Artikel harus punya struktur SEO: pembuka kuat, beberapa subjudul H2, poin praktis, dan CTA halus ke Kampus Media.',
                                'format' => [
                                    'title' => 'maksimal 90 karakter',
                                    'category' => 'Pendidikan/Karier/RPL/Kampus',
                                    'excerpt' => 'maksimal 280 karakter',
                                    'content_html' => 'HTML sederhana berisi 5-8 blok: paragraf, h2, ul/li bila perlu, dan CTA penutup. Jangan gunakan h1.',
                                ],
                                'topic' => $topic,
                                'source_title' => $source['title'],
                                'source_excerpt' => $source['excerpt'],
                                'source_url' => $source['url'],
                                'trend_context' => $trendContext,
                                'kampus_media_editorial_knowledge' => $editorialKnowledge,
                            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                        ],
                    ],
                    'response_format' => ['type' => 'json_object'],
                ]);

            if (! $response->successful()) {
                return $fallback;
            }

            $payload = json_decode($response->json('choices.0.message.content', ''), true);

            if (! is_array($payload)) {
                return $fallback;
            }

            return [
                'title' => Str::of($payload['title'] ?? $fallback['title'])->squish()->limit(100)->toString(),
                'category' => Str::of($payload['category'] ?? $fallback['category'])->squish()->limit(120)->toString(),
                'excerpt' => Str::of($payload['excerpt'] ?? $fallback['excerpt'])->squish()->limit(500)->toString(),
                'content' => $payload['content_html'] ?? $fallback['content'],
                'fallback_used' => false,
            ];
        } catch (Throwable) {
            return $fallback;
        }
    }

    private function generateKeywordArticle(string $keyword, array $trendContext = [], array $editorialKnowledge = []): array
    {
        $fallback = $this->fallbackKeywordArticle($keyword, $trendContext, $editorialKnowledge);
        $apiKey = config('spmm.ai_news.openai_api_key');

        if (blank($apiKey)) {
            return $fallback;
        }

        try {
            $response = Http::withToken($apiKey)
                ->timeout(45)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => config('spmm.ai_news.openai_model', 'gpt-4.1-mini'),
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'Anda adalah SEO content strategist dan penulis artikel pendidikan Indonesia untuk Kampus Media. Buat artikel evergreen yang orisinal, mudah dibaca, dan membantu calon mahasiswa mengambil keputusan. Jangan mengarang data spesifik seperti ranking resmi, biaya pasti, akreditasi kampus tertentu, atau jaminan kerja.',
                        ],
                        [
                            'role' => 'user',
                            'content' => json_encode([
                                'instruction' => 'Tulis artikel SEO full berdasarkan kata kunci yang diberikan. Artikel tidak perlu berbasis berita RSS. Buat artikel evergreen yang bisa ranking di Google, punya struktur jelas, dan relevan dengan Kampus Media.',
                                'keyword' => $keyword,
                                'format' => [
                                    'title' => 'judul SEO maksimal 90 karakter dan natural',
                                    'category' => 'pilih salah satu: Pendidikan, Karier, Kampus, Jurusan, RPL, Kuliah Karyawan',
                                    'excerpt' => 'meta description/ringkasan maksimal 280 karakter',
                                    'content_html' => 'HTML sederhana 900-1400 kata: pembuka, 5-8 h2, paragraf pendek, ul/li, FAQ pendek bila relevan, dan CTA halus. Jangan gunakan h1.',
                                ],
                                'seo_requirements' => [
                                    'jawab intent pencarian sejak paragraf pertama',
                                    'gunakan keyword utama secara natural di judul, pembuka, salah satu h2, dan penutup',
                                    'sertakan keyword turunan seperti kampus terpercaya, prospek kerja, biaya kuliah, kuliah karyawan, kelas karyawan, kuliah online, hybrid, atau RPL hanya bila relevan',
                                    'tambahkan bagian praktis: cara memilih, hal yang perlu dicek, kesalahan umum, dan langkah berikutnya',
                                    'tambahkan CTA ke Kampus Media sebagai portal pembanding kampus dan PMB',
                                ],
                                'trend_context' => $trendContext,
                                'kampus_media_editorial_knowledge' => $editorialKnowledge,
                            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                        ],
                    ],
                    'response_format' => ['type' => 'json_object'],
                ]);

            if (! $response->successful()) {
                return $fallback;
            }

            $payload = json_decode($response->json('choices.0.message.content', ''), true);

            if (! is_array($payload)) {
                return $fallback;
            }

            return [
                'title' => Str::of($payload['title'] ?? $fallback['title'])->squish()->limit(100)->toString(),
                'category' => Str::of($payload['category'] ?? $fallback['category'])->squish()->limit(120)->toString(),
                'excerpt' => Str::of($payload['excerpt'] ?? $fallback['excerpt'])->squish()->limit(500)->toString(),
                'content' => $payload['content_html'] ?? $fallback['content'],
                'fallback_used' => false,
            ];
        } catch (Throwable) {
            return $fallback;
        }
    }

    private function fallbackArticle(array $source, ?string $topic = null, array $trendContext = [], array $editorialKnowledge = []): array
    {
        $sourceTitle = Str::of($source['title'])->replaceMatches('/\s+-\s+.*$/', '')->squish()->toString();
        $topicText = filled($topic) ? Str::of($topic)->squish()->toString() : 'pendidikan tinggi';
        $variant = abs(crc32($sourceTitle.'|'.$topicText)) % 5;
        $keywords = collect($trendContext['education_keywords'] ?? [])
            ->shuffle()
            ->take(4)
            ->implode(', ');
        $cta = e($editorialKnowledge['preferred_cta'] ?? 'Konsultasikan pilihan kampus, jurusan, dan biaya kuliah melalui Kampus Media.');
        $sourceExcerpt = Str::of($source['excerpt'] ?: 'Informasi terbaru seputar pendidikan tinggi dan peluang kuliah di Indonesia.')->squish()->limit(360)->toString();

        $angle = match ($variant) {
            1 => [
                'title' => 'Dampak '.$sourceTitle.' untuk Pilihan Kuliah dan Karier',
                'category' => 'Karier',
                'intro' => 'Berita ini dapat menjadi pengingat bahwa keputusan kuliah perlu dikaitkan dengan arah karier, kebutuhan industri, dan kesiapan calon mahasiswa menghadapi dunia kerja.',
                'h2' => 'Apa hubungannya dengan prospek karier?',
                'body' => 'Calon mahasiswa sebaiknya tidak hanya melihat nama jurusan, tetapi juga memahami kompetensi yang akan dibangun, peluang kerja, dan bentuk portofolio yang bisa dikembangkan selama kuliah.',
            ],
            2 => [
                'title' => $sourceTitle.' dan Pentingnya Membandingkan Biaya Kuliah',
                'category' => 'Biaya Kuliah',
                'intro' => 'Isu pendidikan seperti ini sering berkaitan dengan cara keluarga dan calon mahasiswa menimbang biaya, jadwal, dan manfaat kuliah secara lebih realistis.',
                'h2' => 'Mengapa biaya perlu dilihat sejak awal?',
                'body' => 'Biaya pendaftaran, herregistrasi, SPP, SPB, UKT, dan pilihan cicilan perlu dipahami sebelum mendaftar agar rencana kuliah tidak berhenti di tengah jalan.',
            ],
            3 => [
                'title' => 'RPL dan Kelas Fleksibel: Pelajaran dari '.$sourceTitle,
                'category' => 'RPL',
                'intro' => 'Bagi pekerja dan lulusan D3, informasi pendidikan terbaru dapat menjadi pintu untuk mempertimbangkan kelas karyawan, kuliah online, hybrid, atau jalur RPL.',
                'h2' => 'Siapa yang perlu mempertimbangkan RPL?',
                'body' => 'RPL relevan bagi calon mahasiswa yang punya pengalaman kerja, pelatihan, sertifikasi, atau pembelajaran nonformal, dengan hasil pengakuan tetap mengikuti asesmen dan kebijakan kampus.',
            ],
            4 => [
                'title' => 'Kampus Terpercaya: Cara Membaca Isu '.$sourceTitle,
                'category' => 'Kampus',
                'intro' => 'Setiap perkembangan pendidikan tinggi sebaiknya dibaca bersama aspek legalitas, akreditasi, transparansi biaya, dan layanan PMB yang mudah dipahami.',
                'h2' => 'Apa saja tanda kampus yang layak dipertimbangkan?',
                'body' => 'Calon mahasiswa bisa mengecek status kampus di PDDIKTI, melihat akreditasi BAN-PT/LAM, membandingkan kurikulum, serta memastikan jadwal dan biaya sesuai kebutuhan.',
            ],
            default => [
                'title' => $sourceTitle,
                'category' => 'Pendidikan',
                'intro' => 'Informasi ini penting bagi calon mahasiswa, pekerja, dan keluarga yang sedang mempertimbangkan pilihan kampus, jurusan, jadwal kuliah, dan prospek masa depan.',
                'h2' => 'Mengapa topik ini penting untuk calon mahasiswa?',
                'body' => 'Topik pendidikan tinggi perlu dipahami dari sisi jurusan, fleksibilitas kelas, biaya, akreditasi, dan peluang karier agar keputusan kuliah lebih matang.',
            ],
        };

        $sourceContext = match ($variant) {
            1 => 'Bagi calon mahasiswa, isu ini bisa dibaca sebagai sinyal bahwa pilihan jurusan perlu dihubungkan dengan kebutuhan kerja nyata, bukan hanya nama program studi.',
            2 => 'Dari sisi keluarga, berita ini juga mengingatkan pentingnya membaca skema biaya sejak awal, termasuk pendaftaran, herregistrasi, SPP, SPB, UKT, dan pilihan cicilan.',
            3 => 'Untuk pekerja, isu ini relevan karena pilihan kuliah tidak selalu harus berhenti di kelas reguler. Kelas karyawan, hybrid, online, dan RPL dapat menjadi opsi yang lebih adaptif.',
            4 => 'Dalam konteks PMB, informasi seperti ini perlu disaring bersama data resmi kampus, status PDDIKTI, akreditasi, dan transparansi layanan pendaftaran.',
            default => 'Konteks ini dapat membantu calon mahasiswa melihat pendidikan tinggi sebagai keputusan jangka panjang yang menyentuh waktu, biaya, kompetensi, dan arah karier.',
        };

        return [
            'title' => Str::of($angle['title'])->squish()->limit(90)->toString(),
            'category' => $angle['category'],
            'excerpt' => Str::of($sourceExcerpt)->limit(280)->toString(),
            'content' => collect([
                '<p>Kampus Media merangkum isu <strong>'.e($sourceTitle).'</strong> sebagai berita pendidikan yang relevan untuk calon mahasiswa yang sedang mencari informasi seputar '.e($topicText).'.</p>',
                '<p>'.e($angle['intro']).'</p>',
                '<blockquote>'.e($sourceExcerpt).'</blockquote>',
                '<h2>Konteks berita untuk calon mahasiswa</h2>',
                '<p>'.e($sourceContext).'</p>',
                '<h2>'.e($angle['h2']).'</h2>',
                '<p>'.e($angle['body']).'</p>',
                filled($keywords) ? '<p>Angle draft ini juga mempertimbangkan kedekatan topik dengan minat pencarian pendidikan seperti '.e($keywords).'.</p>' : '',
                '<h2>Hal yang perlu dibandingkan sebelum memilih kampus</h2>',
                '<p>Dalam mengambil keputusan pendidikan, calon mahasiswa sebaiknya tetap membandingkan program studi, akreditasi, fleksibilitas kelas, dan skema pembiayaan dari kampus tujuan.</p>',
                '<ul><li>Cek legalitas kampus melalui PDDIKTI.</li><li>Bandingkan akreditasi program studi.</li><li>Pastikan pilihan kelas sesuai jadwal kerja atau aktivitas harian.</li><li>Pelajari prospek kerja jurusan dan kebutuhan industri.</li></ul>',
                '<h2>Peran Kampus Media</h2>',
                '<p>'.e($cta).'</p>',
                '<p>Draft ini dibuat otomatis sebagai bahan awal. Admin disarankan memeriksa isi, menyesuaikan sudut pandang, dan melengkapi informasi sebelum dipublikasikan.</p>',
                '<p><strong>Sumber referensi:</strong> <a href="'.e($source['url']).'" target="_blank" rel="noopener">'.e($source['source_name']).'</a></p>',
            ])->implode(''),
            'fallback_used' => true,
        ];
    }

    private function fallbackKeywordArticle(string $keyword, array $trendContext = [], array $editorialKnowledge = []): array
    {
        $keywordPlain = Str::of($keyword)->squish()->toString();
        $keywordText = e($keywordPlain);
        $intent = $this->keywordIntent($keywordPlain);
        $variant = abs(crc32($keywordPlain.'|'.$intent)) % 5;
        $title = $this->seoFallbackTitle($keywordPlain, $intent, $variant);
        $keywords = collect($trendContext['education_keywords'] ?? [])
            ->shuffle()
            ->take(5)
            ->implode(', ');
        $cta = e($editorialKnowledge['preferred_cta'] ?? 'Konsultasikan pilihan kampus, jurusan, dan biaya kuliah melalui Kampus Media.');
        $sections = $this->seoFallbackSections($keywordPlain, $intent, $variant);
        $category = match ($intent) {
            'rpl' => 'RPL',
            'kelas_karyawan' => 'Kuliah Karyawan',
            'jurusan' => 'Jurusan',
            'biaya' => 'Biaya Kuliah',
            'karier' => 'Karier',
            default => 'Pendidikan',
        };

        return [
            'title' => $title,
            'category' => $category,
            'excerpt' => Str::of("Panduan memahami {$keyword} untuk calon mahasiswa yang ingin memilih jurusan, kampus, dan jalur kuliah yang sesuai tujuan karier.")->squish()->limit(280)->toString(),
            'content' => collect([
                '<p><strong>'.$keywordText.'</strong> adalah topik yang perlu dibaca sebagai panduan keputusan, bukan sekadar istilah populer. Artikel ini membantu calon mahasiswa memahami konteks, pilihan kuliah, dan langkah praktis sebelum mendaftar.</p>',
                ...$sections,
                filled($keywords) ? '<p>Untuk memperkaya sudut pandang, sistem juga mempertimbangkan keyword pendidikan yang dekat dengan minat pencarian seperti '.e($keywords).'.</p>' : '',
                '<h2>Langkah berikutnya</h2>',
                '<p>Mulailah dengan membandingkan kampus, program studi, biaya, akreditasi, jadwal kuliah, dan model pembelajaran. Jika sudah bekerja, pertimbangkan kelas karyawan, kuliah online, hybrid learning, atau jalur RPL sesuai kebutuhan.</p>',
                '<h2>Peran Kampus Media</h2>',
                '<p>'.e($cta).'</p>',
                '<p>Draft artikel SEO ini dibuat otomatis berdasarkan kata kunci. Admin disarankan menambah contoh kampus, program studi, atau data biaya yang relevan sebelum dipublikasikan.</p>',
            ])->implode(''),
            'fallback_used' => true,
        ];
    }

    private function keywordIntent(string $keyword): string
    {
        $text = Str::lower($keyword);

        return match (true) {
            Str::contains($text, ['rpl', 'rekognisi', 'pengalaman kerja']) => 'rpl',
            Str::contains($text, ['karyawan', 'sambil kerja', 'kelas malam', 'weekend']) => 'kelas_karyawan',
            Str::contains($text, ['jurusan', 'prodi', 'program studi', 'favorit']) => 'jurusan',
            Str::contains($text, ['biaya', 'spp', 'ukt', 'cicilan', 'murah']) => 'biaya',
            Str::contains($text, ['karier', 'prospek', 'kerja', 'gaji']) => 'karier',
            default => 'umum',
        };
    }

    private function seoFallbackTitle(string $keyword, string $intent, int $variant): string
    {
        $headline = Str::of($keyword)->headline();

        $title = match ($intent) {
            'rpl' => match ($variant) {
                1 => 'Panduan RPL untuk Pekerja dan Profesional',
                2 => 'Cara Memahami RPL Sebelum Lanjut Kuliah',
                default => 'RPL: Pengertian, Syarat Umum, dan Manfaatnya',
            },
            'kelas_karyawan' => match ($variant) {
                1 => 'Kuliah Karyawan: Cara Memilih Kelas yang Tepat',
                2 => 'Kelas Karyawan, Online, atau Hybrid: Mana yang Cocok?',
                default => 'Panduan Kuliah Sambil Kerja untuk Karyawan',
            },
            'jurusan' => match ($variant) {
                1 => 'Jurusan Favorit di Indonesia dan Cara Memilihnya',
                2 => 'Cara Memilih Jurusan Kuliah Sesuai Karier',
                default => $headline->prepend('Panduan Memilih ')->toString(),
            },
            'biaya' => match ($variant) {
                1 => 'Cara Membaca Biaya Kuliah Sebelum Mendaftar',
                2 => 'Biaya Kuliah, SPP, UKT, dan Cicilan: Apa Bedanya?',
                default => $headline->prepend('Panduan ')->toString(),
            },
            'karier' => match ($variant) {
                1 => 'Prospek Kerja Jurusan Kuliah yang Perlu Dicek',
                2 => 'Cara Menghubungkan Kuliah dengan Rencana Karier',
                default => $headline->append(': Prospek dan Tips Memilih')->toString(),
            },
            default => match ($variant) {
                1 => $headline->prepend('Cara Memahami ')->toString(),
                2 => $headline->append(': Panduan untuk Calon Mahasiswa')->toString(),
                default => $headline->prepend('Panduan ')->toString(),
            },
        };

        return Str::of($title)->squish()->limit(90)->toString();
    }

    private function seoFallbackSections(string $keyword, string $intent, int $variant): array
    {
        $keywordText = e($keyword);

        $introSection = match ($intent) {
            'rpl' => '<h2>Apa yang perlu dipahami tentang RPL?</h2><p>RPL atau Rekognisi Pembelajaran Lampau adalah jalur pengakuan atas pengalaman kerja, pelatihan, sertifikasi, atau pembelajaran nonformal. Hasil pengakuannya tetap mengikuti asesmen dan kebijakan kampus.</p>',
            'kelas_karyawan' => '<h2>Mengapa kelas karyawan banyak dicari?</h2><p>Kelas karyawan membantu pekerja tetap kuliah tanpa meninggalkan pekerjaan. Modelnya bisa malam, akhir pekan, online, hybrid, atau kombinasi sesuai kebijakan kampus.</p>',
            'jurusan' => '<h2>Mengapa memilih jurusan tidak boleh asal ikut tren?</h2><p>Jurusan populer belum tentu cocok untuk semua orang. Calon mahasiswa perlu melihat minat, kemampuan, kurikulum, prospek kerja, dan peluang pengembangan karier.</p>',
            'biaya' => '<h2>Komponen biaya yang perlu dicek</h2><p>Biaya kuliah dapat terdiri dari pendaftaran, herregistrasi, SPB atau development fee, SPP per semester, UKT, dan biaya pendukung lain. Pastikan semuanya transparan sebelum daftar.</p>',
            'karier' => '<h2>Hubungan kuliah dan prospek karier</h2><p>Kuliah dapat membantu membangun kompetensi, jejaring, portofolio, dan kredibilitas akademik. Namun, prospek kerja tetap dipengaruhi kemampuan, pengalaman, dan kebutuhan industri.</p>',
            default => '<h2>Mengapa topik ini penting?</h2><p>Bagi calon mahasiswa, memahami '.$keywordText.' membantu proses memilih kampus, jurusan, jadwal, biaya, dan jalur kuliah dengan lebih terarah.</p>',
        };

        $checklist = match ($intent) {
            'rpl' => '<ul><li>Siapkan riwayat pekerjaan, sertifikat, portofolio, dan dokumen pendukung.</li><li>Tanyakan mekanisme asesmen RPL di kampus tujuan.</li><li>Pastikan program studi tersedia dan sesuai pengalaman.</li><li>Jangan menganggap hasil konversi SKS pasti sama untuk semua kampus.</li></ul>',
            'kelas_karyawan' => '<ul><li>Cek jadwal kuliah malam, weekend, online, atau hybrid.</li><li>Pastikan lokasi atau platform belajar mudah diakses.</li><li>Bandingkan biaya bulanan dan biaya awal.</li><li>Pilih jurusan yang mendukung perkembangan karier.</li></ul>',
            'jurusan' => '<ul><li>Lihat mata kuliah inti dan profil lulusan.</li><li>Cek prospek kerja dan kebutuhan industri.</li><li>Bandingkan akreditasi dan legalitas program studi.</li><li>Pilih berdasarkan minat dan kemampuan, bukan hanya tren.</li></ul>',
            'biaya' => '<ul><li>Hitung biaya awal dan cicilan bulan berjalan.</li><li>Bandingkan SPP, UKT, SPB, dan biaya herregistrasi.</li><li>Tanyakan keringanan atau skema pembayaran.</li><li>Pastikan biaya yang ditampilkan sama dengan invoice resmi.</li></ul>',
            'karier' => '<ul><li>Identifikasi bidang kerja yang ingin dituju.</li><li>Cek kemampuan yang dibutuhkan industri.</li><li>Pilih jurusan dengan kurikulum yang relevan.</li><li>Bangun portofolio selama kuliah.</li></ul>',
            default => '<ul><li>Cek legalitas kampus melalui PDDIKTI.</li><li>Bandingkan akreditasi BAN-PT/LAM.</li><li>Lihat program studi, jadwal, dan biaya.</li><li>Pastikan jalur kuliah sesuai kondisi pribadi.</li></ul>',
        };

        $variantSection = match ($variant) {
            1 => '<h2>Kesalahan umum yang sering terjadi</h2><p>Banyak calon mahasiswa memilih terlalu cepat karena tertarik promosi. Padahal, keputusan kuliah sebaiknya dibuat setelah membandingkan data resmi, biaya, jadwal, dan dukungan akademik.</p>',
            2 => '<h2>Cara membandingkan beberapa pilihan</h2><p>Buat daftar kampus dan program studi, lalu beri catatan untuk biaya, akreditasi, jadwal, prospek, dan kemudahan pendaftaran. Cara sederhana ini membuat pilihan lebih objektif.</p>',
            3 => '<h2>Siapa yang paling cocok dengan pilihan ini?</h2><p>Pilihan terbaik biasanya bergantung pada kondisi. Lulusan SMA/SMK, karyawan, lulusan D3, peserta RPL, dan orang tua punya kebutuhan informasi yang berbeda.</p>',
            default => '<h2>Hal yang perlu dipertimbangkan</h2><p>Pertimbangkan minat, waktu, biaya, dukungan keluarga, kesiapan belajar, serta target karier. Jangan lupa mengecek informasi resmi dari kampus tujuan.</p>',
        };

        return [
            $introSection,
            '<h2>Checklist sebelum mengambil keputusan</h2>',
            $checklist,
            $variantSection,
        ];
    }

    private function generateCoverImagePath(array $article, array $source, ?string $topic = null, array $editorialKnowledge = []): ?string
    {
        if (! config('spmm.ai_news.generate_cover_image', true)) {
            return null;
        }

        $prompt = $this->imagePrompt($article, $source, $topic, $editorialKnowledge);
        $slug = Str::slug($article['title'] ?? $source['title'] ?? 'artikel-seo') ?: 'artikel-seo';

        $aiImagePath = $this->generateOpenAiCoverImage($prompt, $slug);

        if (filled($aiImagePath)) {
            return $aiImagePath;
        }

        return $this->generateFallbackCoverImage($article, $topic, $slug);
    }

    private function generateOpenAiCoverImage(string $prompt, string $slug): ?string
    {
        $apiKey = config('spmm.ai_news.openai_api_key');

        if (blank($apiKey)) {
            return null;
        }

        try {
            $response = Http::withToken($apiKey)
                ->timeout(60)
                ->post('https://api.openai.com/v1/images/generations', [
                    'model' => config('spmm.ai_news.openai_image_model', 'gpt-image-1'),
                    'prompt' => $prompt,
                    'size' => config('spmm.ai_news.openai_image_size', '1536x1024'),
                    'n' => 1,
                ]);

            if (! $response->successful()) {
                return null;
            }

            $base64Image = $response->json('data.0.b64_json');

            if (filled($base64Image)) {
                $path = "education-news/ai-{$slug}-".now()->format('YmdHis').'.png';
                Storage::disk('public')->put($path, base64_decode($base64Image));

                return $path;
            }

            $imageUrl = $response->json('data.0.url');

            if (filled($imageUrl)) {
                $imageResponse = Http::timeout(30)->get($imageUrl);

                if ($imageResponse->successful()) {
                    $path = "education-news/ai-{$slug}-".now()->format('YmdHis').'.png';
                    Storage::disk('public')->put($path, $imageResponse->body());

                    return $path;
                }
            }
        } catch (Throwable) {
            return null;
        }

        return null;
    }

    private function generateFallbackCoverImage(array $article, ?string $topic, string $slug): string
    {
        $title = Str::of($article['title'] ?? 'Artikel Pendidikan')->squish()->limit(72)->toString();
        $category = Str::of($article['category'] ?? 'Pendidikan')->squish()->limit(28)->toString();
        $topicText = filled($topic) ? Str::of($topic)->squish()->limit(34)->toString() : 'Kampus Media';
        $path = "education-news/ai-cover-{$slug}-".now()->format('YmdHis').'.svg';

        $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="1200" height="675" viewBox="0 0 1200 675" role="img" aria-label="{$this->svgText($title)}">
  <defs>
    <linearGradient id="bg" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0%" stop-color="#071a3d"/>
      <stop offset="58%" stop-color="#0b3a70"/>
      <stop offset="100%" stop-color="#0ea5e9"/>
    </linearGradient>
    <radialGradient id="glow" cx="78%" cy="18%" r="58%">
      <stop offset="0%" stop-color="#f59e0b" stop-opacity=".46"/>
      <stop offset="100%" stop-color="#f59e0b" stop-opacity="0"/>
    </radialGradient>
    <filter id="shadow" x="-20%" y="-20%" width="140%" height="140%">
      <feDropShadow dx="0" dy="20" stdDeviation="18" flood-color="#020617" flood-opacity=".28"/>
    </filter>
  </defs>
  <rect width="1200" height="675" fill="url(#bg)"/>
  <rect width="1200" height="675" fill="url(#glow)"/>
  <circle cx="1020" cy="120" r="180" fill="#38bdf8" opacity=".18"/>
  <circle cx="110" cy="575" r="230" fill="#f59e0b" opacity=".13"/>
  <g opacity=".18">
    <path d="M100 130h1000M100 245h1000M100 360h1000M100 475h1000" stroke="#fff" stroke-width="1"/>
    <path d="M180 80v520M360 80v520M540 80v520M720 80v520M900 80v520M1080 80v520" stroke="#fff" stroke-width="1"/>
  </g>
  <g filter="url(#shadow)">
    <rect x="78" y="78" width="1044" height="519" rx="42" fill="#ffffff" opacity=".10"/>
    <rect x="96" y="96" width="1008" height="483" rx="34" fill="#ffffff" opacity=".12"/>
  </g>
  <text x="132" y="168" fill="#fde68a" font-family="Arial, sans-serif" font-size="30" font-weight="800" letter-spacing="2">{$this->svgText(strtoupper($category))}</text>
  <foreignObject x="128" y="210" width="740" height="250">
    <div xmlns="http://www.w3.org/1999/xhtml" style="font-family: Arial, sans-serif; color: white; font-size: 58px; line-height: 1.06; font-weight: 900;">
      {$this->svgText($title)}
    </div>
  </foreignObject>
  <text x="132" y="515" fill="#dbeafe" font-family="Arial, sans-serif" font-size="28" font-weight="700">{$this->svgText($topicText)}</text>
  <g transform="translate(900 220)">
    <rect width="178" height="178" rx="42" fill="#ffffff" opacity=".16"/>
    <path d="M54 116V62h70v54H54Zm12-42v30h46V74H66Zm-16 58h78v16H50v-16Z" fill="#ffffff"/>
    <path d="M44 52 90 28l46 24-46 24-46-24Z" fill="#fbbf24"/>
  </g>
  <text x="132" y="560" fill="#ffffff" opacity=".92" font-family="Arial, sans-serif" font-size="24" font-weight="800">Kampus Media</text>
</svg>
SVG;

        Storage::disk('public')->put($path, $svg);

        return $path;
    }

    private function imagePrompt(array $article, array $source, ?string $topic = null, array $editorialKnowledge = []): string
    {
        $brand = $editorialKnowledge['brand']['name'] ?? 'Kampus Media';
        $audience = collect($editorialKnowledge['brand']['audience'] ?? [])->take(4)->implode(', ');

        return Str::of(implode(' ', [
            'Create a modern editorial hero image for an Indonesian education SEO article.',
            'Brand context:', $brand.'.',
            'Article title:', $article['title'] ?? $source['title'] ?? 'Artikel pendidikan.',
            'Topic:', $topic ?: 'kampus, jurusan, kuliah karyawan, RPL, prospek kerja.',
            'Audience:', $audience ?: 'calon mahasiswa dan karyawan.',
            'Visual style: professional education technology, Indonesian campus atmosphere, students or working professionals, navy blue and cyan with warm yellow accent, clean modern composition, premium, optimistic.',
            'Do not include readable text, logos, brand marks, distorted faces, or misleading certificate claims.',
        ]))->squish()->toString();
    }

    private function svgText(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private function uniqueSlug(string $title): string
    {
        $base = Str::slug($title) ?: 'berita-pendidikan';
        $slug = $base;
        $counter = 2;

        while (EducationNews::query()->where('slug', $slug)->exists()) {
            $slug = "{$base}-{$counter}";
            $counter++;
        }

        return $slug;
    }
}
