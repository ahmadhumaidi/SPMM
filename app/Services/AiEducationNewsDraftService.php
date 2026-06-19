<?php

namespace App\Services;

use App\Models\EducationNews;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
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
        $article = $this->generateArticle($source, $topic, $trendContext);

        $news = EducationNews::query()->create([
            'title' => $article['title'],
            'slug' => $this->uniqueSlug($article['title']),
            'category' => $article['category'],
            'excerpt' => $article['excerpt'],
            'content' => $article['content'],
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
            'editorial_rule' => 'Gunakan tren sebagai inspirasi sudut pandang, bukan sebagai klaim statistik. Kaitkan secara wajar dengan kebutuhan calon mahasiswa, pilihan program studi, fleksibilitas kuliah, karier, dan biaya pendidikan.',
        ];
    }

    private function generateArticle(array $source, ?string $topic = null, array $trendContext = []): array
    {
        $fallback = $this->fallbackArticle($source, $topic, $trendContext);
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
                            'content' => 'Anda adalah editor berita pendidikan Indonesia. Tulis artikel orisinal, aman, tidak copy-paste, ringkas, dan berorientasi calon mahasiswa.',
                        ],
                        [
                            'role' => 'user',
                            'content' => json_encode([
                                'instruction' => 'Buat draft artikel berita pendidikan untuk Kampus Media. Jangan mengklaim hal yang tidak ada di sumber. Sertakan konteks manfaat untuk calon mahasiswa/karyawan. Gunakan konteks Google Trends Indonesia dan keyword tren pendidikan sebagai inspirasi angle, bukan sebagai data statistik pasti.',
                                'format' => [
                                    'title' => 'maksimal 90 karakter',
                                    'category' => 'Pendidikan/Karier/RPL/Kampus',
                                    'excerpt' => 'maksimal 280 karakter',
                                    'content_html' => '4-6 paragraf HTML sederhana',
                                ],
                                'topic' => $topic,
                                'source_title' => $source['title'],
                                'source_excerpt' => $source['excerpt'],
                                'source_url' => $source['url'],
                                'trend_context' => $trendContext,
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

    private function fallbackArticle(array $source, ?string $topic = null, array $trendContext = []): array
    {
        $title = Str::of($source['title'])->replaceMatches('/\s+-\s+.*$/', '')->squish()->limit(90)->toString();
        $topicText = filled($topic) ? e($topic) : 'pendidikan tinggi';
        $keywords = collect($trendContext['education_keywords'] ?? [])->take(5)->implode(', ');

        return [
            'title' => $title,
            'category' => 'Pendidikan',
            'excerpt' => Str::of($source['excerpt'] ?: 'Informasi terbaru seputar pendidikan tinggi dan peluang kuliah di Indonesia.')->squish()->limit(280)->toString(),
            'content' => collect([
                '<p>Kampus Media merangkum informasi terbaru seputar '.e($topicText).' dari sumber berita pendidikan yang tersedia.</p>',
                '<p>Topik ini penting bagi calon mahasiswa, pekerja, dan keluarga yang sedang mempertimbangkan pilihan kuliah, jadwal belajar, biaya, serta prospek karier ke depan.</p>',
                filled($keywords) ? '<p>Dalam menyusun angle berita, sistem juga mempertimbangkan minat pencarian yang dekat dengan dunia pendidikan seperti '.e($keywords).'.</p>' : '',
                '<p>Dalam mengambil keputusan pendidikan, calon mahasiswa sebaiknya tetap membandingkan program studi, akreditasi, fleksibilitas kelas, dan skema pembiayaan dari kampus tujuan.</p>',
                '<p>Draft ini dibuat otomatis sebagai bahan awal. Admin disarankan memeriksa isi, menyesuaikan sudut pandang, dan melengkapi informasi sebelum dipublikasikan.</p>',
                '<p><strong>Sumber referensi:</strong> <a href="'.e($source['url']).'" target="_blank" rel="noopener">'.e($source['source_name']).'</a></p>',
            ])->implode(''),
            'fallback_used' => true,
        ];
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
