import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js', 'resources/js/road-map.js', 'resources/js/connect-guide.js', 'resources/js/trips-map.js', 'resources/js/map/common.js', 'resources/js/navigation/search.js', 'resources/js/navigation/navigation.js', 'resources/js/go/board.js'],
            refresh: true,
            fonts: [
                bunny('Sora', { weights: [400, 600, 700] }),
                bunny('Inter', { weights: [400, 500, 600] }),
                bunny('JetBrains Mono', { weights: [400, 500] }),
            ],
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
