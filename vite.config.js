import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/css/pages/campuses.css',
                'resources/js/pages/campuses.js',
                'resources/css/pages/public-site.css',
                'resources/css/pages/partner-campus.css',
                'resources/js/pages/partner-campus.js',
                'resources/js/pages/news.js',
            ],
            refresh: true,
        }),
    ],
});
