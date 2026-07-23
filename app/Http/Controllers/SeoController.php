<?php

namespace App\Http\Controllers;

use App\Enums\CampusStatus;
use App\Models\Campus;
use App\Models\EducationNews;
use Illuminate\Http\Response;

class SeoController extends Controller
{
    public function sitemap(): Response
    {
        $publicUrl = rtrim(config('spmm.public_url', 'https://kampus.media'), '/');

        $urls = collect([
            [
                'loc' => $publicUrl.'/',
                'lastmod' => now(),
                'changefreq' => 'daily',
                'priority' => '1.0',
            ],
            [
                'loc' => $publicUrl.'/kampus',
                'lastmod' => now(),
                'changefreq' => 'daily',
                'priority' => '0.9',
            ],
            [
                'loc' => $publicUrl.'/daftar',
                'lastmod' => now(),
                'changefreq' => 'weekly',
                'priority' => '0.8',
            ],
            [
                'loc' => $publicUrl.'/berita',
                'lastmod' => now(),
                'changefreq' => 'daily',
                'priority' => '0.8',
            ],
        ]);

        Campus::query()
            ->where('status', CampusStatus::Active)
            ->orderBy('updated_at', 'desc')
            ->get()
            ->each(function (Campus $campus) use ($urls): void {
                $urls->push([
                    'loc' => $campus->publicUrl(),
                    'lastmod' => $campus->updated_at,
                    'changefreq' => 'weekly',
                    'priority' => '0.8',
                ]);
            });

        EducationNews::query()
            ->where('status', 'published')
            ->where(fn ($query) => $query->whereNull('published_at')->orWhere('published_at', '<=', now()))
            ->orderBy('published_at', 'desc')
            ->get()
            ->each(function (EducationNews $news) use ($urls): void {
                $urls->push([
                    'loc' => route('news.show', $news),
                    'lastmod' => $news->updated_at,
                    'changefreq' => 'monthly',
                    'priority' => '0.7',
                ]);
            });

        return response()
            ->view('public.sitemap', ['urls' => $urls])
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    public function robots(): Response
    {
        $publicUrl = rtrim(config('spmm.public_url', 'https://kampus.media'), '/');

        return response(implode("\n", [
            'User-agent: *',
            'Allow: /',
            'Disallow: /admin',
            'Disallow: /mahasiswa',
            'Disallow: /siakad',
            'Disallow: /lms',
            '',
            'Sitemap: '.$publicUrl.'/sitemap.xml',
            '',
        ]), 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }
}
