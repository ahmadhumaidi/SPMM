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

    public function createSeoArticleDraft(array $payload, ?array $campusIds = null): EducationNews
    {
        $payload = $this->normalizeSeoPayload($payload);
        $this->ensureOpenAiConfigured();

        $trendContext = $this->seoTrendContext($payload['topik_artikel'].' '.$payload['keyword_utama']);
        $editorialKnowledge = config('spmm.ai_news.editorial_knowledge', []);
        $source = [
            'source_name' => 'Kampus Media AI',
            'title' => $payload['topik_artikel'],
            'url' => null,
            'hash' => null,
            'excerpt' => null,
            'published_at' => null,
        ];
        $article = $this->generateKeywordArticle($payload, $trendContext, $editorialKnowledge);
        $imagePath = $this->generateFastCoverImagePath($article, $source, $payload['topik_artikel'], $editorialKnowledge);

        $news = EducationNews::query()->create([
            'title' => $article['title'],
            'slug' => $this->uniqueSlug($article['slug'] ?? $article['title']),
            'category' => $payload['category'],
            'topik_artikel' => $payload['topik_artikel'],
            'excerpt' => $article['excerpt'],
            'meta_description' => $article['meta_description'],
            'content' => $article['content'],
            'image_path' => $imagePath,
            'author_name' => 'Kampus Media AI',
            'source_name' => 'Artikel SEO AI',
            'source_url' => null,
            'source_hash' => null,
            'source_published_at' => null,
            'generated_by_ai' => filled(config('spmm.ai_news.openai_api_key')),
            'ai_generated' => true,
            'tipe_konten' => 'artikel_seo_ai',
            'keyword_utama' => $payload['keyword_utama'],
            'keyword_tambahan' => $payload['keyword_tambahan'],
            'target_pembaca' => $payload['target_pembaca'],
            'nama_kampus' => $payload['nama_kampus'],
            'lokasi' => $payload['lokasi'],
            'gaya_bahasa' => $payload['gaya_bahasa'],
            'tipe_artikel' => $payload['tipe_artikel'],
            'panjang_artikel' => $payload['panjang_artikel'],
            'ai_generated_at' => now(),
            'ai_prompt_json' => [
                'mode' => 'seo_article',
                'payload' => $payload,
                'prompt' => $this->seoPromptInstruction($payload),
                'trend_context' => $trendContext,
                'editorial_knowledge' => $editorialKnowledge,
                'image_prompt' => $this->imagePrompt($article, $source, $payload['topik_artikel'], $editorialKnowledge),
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

    private function ensureOpenAiConfigured(): void
    {
        if (blank(config('spmm.ai_news.openai_api_key'))) {
            throw new RuntimeException('OPENAI_API_KEY belum dikonfigurasi.');
        }
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

    private function generateKeywordArticle(array $payload, array $trendContext = [], array $editorialKnowledge = []): array
    {
        $fallback = $this->fallbackKeywordArticle($payload, $trendContext, $editorialKnowledge);
        $apiKey = config('spmm.ai_news.openai_api_key');

        if (blank($apiKey)) {
            throw new RuntimeException('OPENAI_API_KEY belum dikonfigurasi.');
        }

        try {
            $response = Http::withToken($apiKey)
                ->timeout(45)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => config('spmm.ai_news.openai_model', 'gpt-4.1-mini'),
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'Anda adalah SEO content strategist dan penulis artikel pendidikan Indonesia untuk Kampus Media. Buat artikel orisinal, natural, mudah dibaca, dan membantu calon mahasiswa mengambil keputusan. Jangan mengarang data spesifik seperti ranking resmi, biaya pasti, akreditasi kampus tertentu, atau jaminan kerja. Balas hanya dalam JSON valid tanpa markdown.',
                        ],
                        [
                            'role' => 'user',
                            'content' => json_encode([
                                'instruction' => $this->seoPromptInstruction($payload),
                                'response_rule' => 'Balas hanya JSON valid dengan key: title, meta_description, slug, excerpt, content_html, primary_keyword, secondary_keywords, internal_link_suggestions. Jangan bungkus dengan markdown.',
                                'topik_artikel' => $payload['topik_artikel'],
                                'keyword_utama' => $payload['keyword_utama'],
                                'keyword_tambahan' => $payload['keyword_tambahan'],
                                'kategori' => $payload['category'],
                                'target_pembaca' => $payload['target_pembaca'],
                                'nama_kampus' => $payload['nama_kampus'],
                                'lokasi' => $payload['lokasi'],
                                'tipe_artikel' => $payload['tipe_artikel'],
                                'gaya_bahasa' => $payload['gaya_bahasa'],
                                'panjang_artikel' => $payload['panjang_artikel'],
                                'format' => [
                                    'title' => 'judul SEO maksimal 90 karakter dan natural',
                                    'meta_description' => 'maksimal 160 karakter',
                                    'slug' => 'slug URL SEO lowercase pakai tanda hubung',
                                    'excerpt' => 'ringkasan maksimal 280 karakter',
                                    'content_html' => 'HTML sederhana sesuai panjang artikel: pembuka, beberapa h2, paragraf pendek, ul/li, kesimpulan, CTA ringan bila relevan. Jangan gunakan h1.',
                                    'primary_keyword' => 'keyword utama',
                                    'secondary_keywords' => 'array keyword turunan',
                                    'internal_link_suggestions' => 'array saran internal link',
                                ],
                                'seo_requirements' => [
                                    'jawab intent pencarian sejak paragraf pertama',
                                    'gunakan keyword utama secara natural di judul, pembuka, salah satu h2, dan penutup',
                                    'jangan terlalu hard selling',
                                    'pastikan artikel tidak terlihat seperti hasil AI yang kaku',
                                    'tambahkan CTA ringan ke Kampus Media bila relevan',
                                ],
                                'trend_context' => $trendContext,
                                'kampus_media_editorial_knowledge' => $editorialKnowledge,
                            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                        ],
                    ],
                    'response_format' => ['type' => 'json_object'],
                ]);

            if (! $response->successful()) {
                throw new RuntimeException('OpenAI mengembalikan respons gagal.');
            }

            $payload = json_decode($response->json('choices.0.message.content', ''), true);

            if (! is_array($payload)) {
                throw new RuntimeException('Format respons OpenAI tidak sesuai.');
            }

            return [
                'title' => Str::of($payload['title'] ?? $fallback['title'])->squish()->limit(100)->toString(),
                'slug' => Str::slug($payload['slug'] ?? $payload['title'] ?? $fallback['title']),
                'category' => $fallback['category'],
                'excerpt' => Str::of($payload['excerpt'] ?? $fallback['excerpt'])->squish()->limit(500)->toString(),
                'meta_description' => Str::of($payload['meta_description'] ?? $fallback['meta_description'])->squish()->limit(180)->toString(),
                'content' => $this->appendSeoExtras(
                    $payload['content_html'] ?? $fallback['content'],
                    $payload['primary_keyword'] ?? $fallback['primary_keyword'],
                    $payload['secondary_keywords'] ?? $fallback['secondary_keywords'],
                    $payload['internal_link_suggestions'] ?? $fallback['internal_link_suggestions'],
                ),
                'primary_keyword' => $payload['primary_keyword'] ?? $fallback['primary_keyword'],
                'secondary_keywords' => $payload['secondary_keywords'] ?? $fallback['secondary_keywords'],
                'internal_link_suggestions' => $payload['internal_link_suggestions'] ?? $fallback['internal_link_suggestions'],
                'fallback_used' => false,
            ];
        } catch (Throwable $exception) {
            throw new RuntimeException('Generate AI gagal. Silakan coba lagi atau periksa konfigurasi API.', previous: $exception);
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

    private function fallbackKeywordArticle(array $payload, array $trendContext = [], array $editorialKnowledge = []): array
    {
        $keyword = $payload['keyword_utama'];
        $topic = $payload['topik_artikel'];
        $keywordPlain = Str::of($keyword)->squish()->toString();
        $keywordText = e($keywordPlain);
        $intent = $this->keywordIntent($keywordPlain.' '.$topic.' '.$payload['category']);
        $variant = abs(crc32($keywordPlain.'|'.$topic.'|'.$payload['category'].'|'.$payload['tipe_artikel'])) % 5;
        $title = $this->seoFallbackTitle($topic, $keywordPlain, $payload, $intent, $variant);
        $keywords = collect($trendContext['education_keywords'] ?? [])
            ->shuffle()
            ->take(5)
            ->implode(', ');
        $cta = e($editorialKnowledge['preferred_cta'] ?? 'Konsultasikan pilihan kampus, jurusan, dan biaya kuliah melalui Kampus Media.');
        $sections = $this->seoFallbackSections($payload, $intent, $variant);
        $secondaryKeywords = $this->secondarySeoKeywords($payload, $trendContext);
        $internalLinks = $this->internalLinkSuggestions($payload);
        $metaDescription = Str::of("Pelajari {$topic}: {$keywordPlain}, pilihan kampus, biaya, jadwal, dan prospek kuliah bersama Kampus Media.")
            ->squish()
            ->limit(160)
            ->toString();
        $audienceSentence = filled($payload['target_pembaca'])
            ? ' Artikel ini ditujukan untuk '.e($payload['target_pembaca']).'.'
            : '';
        $locationSentence = filled($payload['lokasi'])
            ? ' Jika relevan, konteks lokasi yang digunakan adalah '.e($payload['lokasi']).'.'
            : '';
        $campusSentence = filled($payload['nama_kampus'])
            ? ' Nama kampus yang dapat dijadikan konteks adalah '.e($payload['nama_kampus']).', tanpa membuat klaim yang belum diverifikasi.'
            : '';

        $content = collect([
            '<p><strong>'.e($topic).'</strong> berkaitan erat dengan pencarian <strong>'.$keywordText.'</strong>.'.$audienceSentence.$locationSentence.$campusSentence.' Artikel ini dibuat dengan gaya '.e(Str::lower($payload['gaya_bahasa'])).' agar mudah dipahami dan tetap SEO friendly.</p>',
            ...$sections,
            filled($keywords) ? '<p>Untuk memperkaya sudut pandang, sistem juga mempertimbangkan keyword pendidikan yang dekat dengan minat pencarian seperti '.e($keywords).'.</p>' : '',
            '<h2>Kesimpulan</h2>',
            '<p>'.e($topic).' perlu dipahami secara praktis: cek kebutuhan pribadi, bandingkan kampus, pahami biaya, lihat akreditasi, dan pastikan jalur kuliah sesuai rencana karier.</p>',
            '<h2>Langkah berikutnya</h2>',
            '<p>Mulailah dengan membandingkan kampus, program studi, biaya, akreditasi, jadwal kuliah, dan model pembelajaran. Jika sudah bekerja, pertimbangkan kelas karyawan, kuliah online, hybrid learning, atau jalur RPL sesuai kebutuhan.</p>',
            '<h2>Peran Kampus Media</h2>',
            '<p>'.e($cta).'</p>',
        ])->implode('');

        return [
            'title' => $title,
            'slug' => Str::slug($title),
            'category' => $payload['category'],
            'excerpt' => Str::of($metaDescription)->limit(280)->toString(),
            'meta_description' => $metaDescription,
            'content' => $this->appendSeoExtras($content, $keywordPlain, $secondaryKeywords, $internalLinks),
            'primary_keyword' => $keywordPlain,
            'secondary_keywords' => $secondaryKeywords,
            'internal_link_suggestions' => $internalLinks,
            'fallback_used' => true,
        ];
    }

    private function seoTrendContext(?string $topic = null): array
    {
        return [
            'topic' => $topic,
            'education_keywords' => config('spmm.ai_news.trend_keywords', []),
            'google_trends_indonesia' => [],
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
            'editorial_rule' => 'Gunakan keyword pendidikan sebagai inspirasi sudut pandang. Hindari klaim statistik tanpa sumber.',
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

    private function seoFallbackTitle(string $topic, string $keyword, array $payload, string $intent, int $variant): string
    {
        $topicHeadline = Str::of($topic)->headline()->toString();
        $keywordHeadline = Str::of($keyword)->headline()->toString();

        $title = match ($intent) {
            'rpl' => match ($variant) {
                1 => 'Panduan '.$topicHeadline.' untuk Pekerja dan Profesional',
                2 => 'Cara Memahami '.$keywordHeadline.' Sebelum Lanjut Kuliah',
                default => $topicHeadline.': Pengertian, Syarat Umum, dan Manfaatnya',
            },
            'kelas_karyawan' => match ($variant) {
                1 => $topicHeadline.': Cara Memilih Kelas yang Tepat',
                2 => $keywordHeadline.', Online, atau Hybrid: Mana yang Cocok?',
                default => 'Panduan '.$topicHeadline.' untuk Karyawan',
            },
            'jurusan' => match ($variant) {
                1 => $topicHeadline.' dan Cara Memilihnya',
                2 => 'Cara Memilih '.$keywordHeadline.' Sesuai Karier',
                default => 'Panduan Memilih '.$topicHeadline,
            },
            'biaya' => match ($variant) {
                1 => 'Cara Membaca '.$topicHeadline.' Sebelum Mendaftar',
                2 => $keywordHeadline.': Komponen dan Tips Membandingkan',
                default => 'Panduan '.$topicHeadline,
            },
            'karier' => match ($variant) {
                1 => $topicHeadline.' yang Perlu Dicek',
                2 => 'Cara Menghubungkan '.$keywordHeadline.' dengan Karier',
                default => $topicHeadline.': Prospek dan Tips Memilih',
            },
            default => match ($variant) {
                1 => 'Cara Memahami '.$topicHeadline,
                2 => $topicHeadline.': Panduan untuk Calon Mahasiswa',
                default => 'Panduan '.$topicHeadline,
            },
        };

        return Str::of($title)->squish()->limit(90)->toString();
    }

    private function seoFallbackSections(array $payload, string $intent, int $variant): array
    {
        $keywordText = e($payload['keyword_utama']);
        $topicText = e($payload['topik_artikel']);

        $introSection = match ($intent) {
            'rpl' => '<h2>Apa yang perlu dipahami tentang RPL?</h2><p>RPL atau Rekognisi Pembelajaran Lampau adalah jalur pengakuan atas pengalaman kerja, pelatihan, sertifikasi, atau pembelajaran nonformal. Hasil pengakuannya tetap mengikuti asesmen dan kebijakan kampus.</p>',
            'kelas_karyawan' => '<h2>Mengapa kelas karyawan banyak dicari?</h2><p>Kelas karyawan membantu pekerja tetap kuliah tanpa meninggalkan pekerjaan. Modelnya bisa malam, akhir pekan, online, hybrid, atau kombinasi sesuai kebijakan kampus.</p>',
            'jurusan' => '<h2>Mengapa memilih jurusan tidak boleh asal ikut tren?</h2><p>Jurusan populer belum tentu cocok untuk semua orang. Calon mahasiswa perlu melihat minat, kemampuan, kurikulum, prospek kerja, dan peluang pengembangan karier.</p>',
            'biaya' => '<h2>Komponen biaya yang perlu dicek</h2><p>Biaya kuliah dapat terdiri dari pendaftaran, herregistrasi, SPB atau development fee, SPP per semester, UKT, dan biaya pendukung lain. Pastikan semuanya transparan sebelum daftar.</p>',
            'karier' => '<h2>Hubungan kuliah dan prospek karier</h2><p>Kuliah dapat membantu membangun kompetensi, jejaring, portofolio, dan kredibilitas akademik. Namun, prospek kerja tetap dipengaruhi kemampuan, pengalaman, dan kebutuhan industri.</p>',
            default => '<h2>Mengapa '.$topicText.' penting?</h2><p>Bagi calon mahasiswa, memahami '.$keywordText.' membantu proses memilih kampus, jurusan, jadwal, biaya, dan jalur kuliah dengan lebih terarah.</p>',
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

    private function normalizeSeoPayload(array $payload): array
    {
        $normalized = [
            'topik_artikel' => Str::of($payload['topik_artikel'] ?? '')->squish()->limit(160)->toString(),
            'keyword_utama' => Str::of($payload['keyword_utama'] ?? '')->squish()->limit(160)->toString(),
            'category' => Str::of($payload['category'] ?? 'Pendidikan')->squish()->limit(120)->toString(),
            'gaya_bahasa' => Str::of($payload['gaya_bahasa'] ?? 'Informatif')->squish()->limit(120)->toString(),
            'nama_kampus' => Str::of($payload['nama_kampus'] ?? '')->squish()->limit(160)->toString(),
            'target_pembaca' => Str::of($payload['target_pembaca'] ?? '')->squish()->limit(160)->toString(),
            'keyword_tambahan' => Str::of($payload['keyword_tambahan'] ?? '')->squish()->limit(500)->toString(),
            'lokasi' => Str::of($payload['lokasi'] ?? '')->squish()->limit(160)->toString(),
            'tipe_artikel' => Str::of($payload['tipe_artikel'] ?? 'Artikel SEO')->squish()->limit(120)->toString(),
            'panjang_artikel' => Str::of($payload['panjang_artikel'] ?? 'Sedang: 700-900 kata')->squish()->limit(120)->toString(),
        ];

        if (blank($normalized['topik_artikel']) || blank($normalized['keyword_utama']) || blank($normalized['category']) || blank($normalized['gaya_bahasa'])) {
            throw new RuntimeException('Topik Artikel, Keyword Utama, Kategori, dan Gaya Bahasa wajib diisi.');
        }

        return $normalized;
    }

    private function seoPromptInstruction(array $payload): string
    {
        return Str::of(<<<PROMPT
Buatkan artikel SEO dengan topik: {$payload['topik_artikel']}.
Keyword utama: {$payload['keyword_utama']}.
Keyword tambahan: {$payload['keyword_tambahan']}.
Kategori artikel: {$payload['category']}.
Target pembaca: {$payload['target_pembaca']}.
Nama kampus jika relevan: {$payload['nama_kampus']}.
Lokasi jika relevan: {$payload['lokasi']}.
Tipe artikel: {$payload['tipe_artikel']}.
Gunakan gaya bahasa: {$payload['gaya_bahasa']}.
Panjang artikel: {$payload['panjang_artikel']}.

Buat artikel yang natural, informatif, mudah dipahami, dan SEO friendly.
Jangan terlalu hard selling.
Sertakan:
1. Judul SEO
2. Meta description maksimal 160 karakter
3. Slug URL
4. Artikel utama
5. Keyword utama
6. Keyword turunan
7. Saran internal link

Struktur artikel utama:
- Paragraf pembuka
- Pembahasan utama dengan beberapa subjudul H2
- Manfaat atau poin penting
- Kesimpulan
- Call to action ringan jika relevan

Pastikan artikel tidak terlihat seperti hasil AI yang kaku.
PROMPT)->squish()->toString();
    }

    private function secondarySeoKeywords(array $payload, array $trendContext): array
    {
        $manual = collect(explode(',', (string) $payload['keyword_tambahan']))
            ->map(fn (string $keyword): string => Str::of($keyword)->squish()->toString())
            ->filter();

        $contextual = collect([
            $payload['category'],
            $payload['topik_artikel'],
            'Kampus Media',
            'kampus terpercaya',
            'biaya kuliah',
            'prospek kerja',
        ]);

        $trends = collect($trendContext['education_keywords'] ?? [])->take(6);

        return $manual
            ->merge($contextual)
            ->merge($trends)
            ->unique()
            ->take(10)
            ->values()
            ->all();
    }

    private function internalLinkSuggestions(array $payload): array
    {
        $links = [
            '/kampus' => 'Daftar kampus mitra Kampus Media',
            '/daftar' => 'Form pendaftaran calon mahasiswa',
        ];

        if (Str::contains(Str::lower($payload['category'].' '.$payload['topik_artikel']), ['rpl'])) {
            $links['/kampus?program=RPL'] = 'Pilihan kampus dengan program RPL';
        }

        if (Str::contains(Str::lower($payload['category'].' '.$payload['topik_artikel']), ['karyawan', 'online', 'hybrid'])) {
            $links['/kampus?program=Kelas%20Professional'] = 'Pilihan kuliah fleksibel untuk karyawan';
        }

        return collect($links)
            ->map(fn (string $label, string $url): array => ['url' => $url, 'label' => $label])
            ->values()
            ->all();
    }

    private function appendSeoExtras(string $content, ?string $primaryKeyword, array|string|null $secondaryKeywords, array|string|null $internalLinks): string
    {
        $secondary = collect(is_array($secondaryKeywords) ? $secondaryKeywords : explode(',', (string) $secondaryKeywords))
            ->map(fn (mixed $keyword): string => is_array($keyword) ? (string) ($keyword['keyword'] ?? '') : (string) $keyword)
            ->map(fn (string $keyword): string => Str::of($keyword)->squish()->toString())
            ->filter()
            ->unique()
            ->values();

        $links = collect(is_array($internalLinks) ? $internalLinks : [])
            ->map(function (mixed $link): ?array {
                if (is_array($link)) {
                    return [
                        'url' => (string) ($link['url'] ?? '#'),
                        'label' => (string) ($link['label'] ?? $link['title'] ?? $link['url'] ?? 'Internal link'),
                    ];
                }

                return null;
            })
            ->filter();

        $extras = collect();

        if (filled($primaryKeyword) || $secondary->isNotEmpty()) {
            $extras->push('<h2>Keyword Artikel</h2>');
            $extras->push('<p><strong>Keyword utama:</strong> '.e((string) $primaryKeyword).'</p>');

            if ($secondary->isNotEmpty()) {
                $extras->push('<p><strong>Keyword turunan:</strong> '.e($secondary->implode(', ')).'</p>');
            }
        }

        if ($links->isNotEmpty()) {
            $extras->push('<h2>Saran Internal Link</h2>');
            $extras->push('<ul>'.$links->map(fn (array $link): string => '<li><a href="'.e($link['url']).'">'.e($link['label']).'</a></li>')->implode('').'</ul>');
        }

        return $content.$extras->implode('');
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

    private function generateFastCoverImagePath(array $article, array $source, ?string $topic = null, array $editorialKnowledge = []): ?string
    {
        if (! config('spmm.ai_news.generate_cover_image', true)) {
            return null;
        }

        $slug = Str::slug($article['title'] ?? $source['title'] ?? 'artikel-seo') ?: 'artikel-seo';

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
