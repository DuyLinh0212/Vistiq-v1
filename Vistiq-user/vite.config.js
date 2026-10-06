import { defineConfig } from 'vite';
import { fileURLToPath } from 'node:url';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
            fonts: [
                bunny('Be Vietnam Pro', {
                    weights: [400, 600, 700],
                }),
            ],
        }),
        tailwindcss(),
    ],
    server: {
        fs: { allow: [fileURLToPath(new URL('.', import.meta.url)), fileURLToPath(new URL('../Vistiq-admin', import.meta.url))] },
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
