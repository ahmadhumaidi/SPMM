/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    './resources/views/components/layouts/public.blade.php',
    './resources/views/public/campuses.blade.php',
    './resources/views/public/partner-campus-site.blade.php',
    './resources/views/public/news-detail.blade.php',
    './resources/views/public/news-index.blade.php',
    './resources/views/public/register.blade.php',
    './resources/views/public/thank-you.blade.php',
    './resources/views/public/pemberkasan.blade.php',
    './resources/views/public/campus-detail.blade.php',
    './resources/views/public/partials/simple-campus-page.blade.php',
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
