import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js', 'resources/js/simulador.js', 'resources/js/resultado.js'],
            refresh: true,
        }),
        tailwindcss(),
    ],
    build: {
        manifest: true,
        outDir: 'public/build',
        emptyOutDir: true,
    },
});
