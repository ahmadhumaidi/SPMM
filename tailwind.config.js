/** @type {import('tailwindcss').Config} */
export default {
    content: [
        // Scoped to the pages migrated to this Vite build so far. Widen this
        // (e.g. back to './resources/views/**/*.blade.php') as more pages
        // move off the CDN Tailwind build onto this pipeline.
        './resources/views/public/campuses.blade.php',
        './resources/js/**/*.js',
    ],
    theme: {
        extend: {
            colors: {
                navy: '#071a3d',
                ink: '#102033',
                gold: '#f59e0b',
            },
            boxShadow: {
                soft: '0 24px 70px rgba(15, 23, 42, .12)',
            },
        },
    },
    plugins: [],
};
