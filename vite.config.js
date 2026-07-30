import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/css/pages/campuses.css',
                'resources/js/pages/campuses.js',
            ],
            refresh: true,
        }),
    ],
});
