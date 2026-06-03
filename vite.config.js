import { defineConfig, normalizePath } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import { viteStaticCopy } from 'vite-plugin-static-copy';
import path from 'path';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
        // Добавляем плагин для копирования логотипов CardInfo
        viteStaticCopy({
            targets: [
                {
                    src: normalizePath(
                        path.resolve(__dirname, 'node_modules/card-info/dist/banks-logos/**/*')
                    ),
                    dest: 'card-info/banks-logos'
                },
                {
                    src: normalizePath(
                        path.resolve(__dirname, 'node_modules/card-info/dist/brands-logos/**/*')
                    ),
                    dest: 'card-info/brands-logos'
                }
            ],
            // Опционально: для отладки (можно раскомментировать)
            // verbose: true,
        }),
    ],
    define: {
        'process.env': {}
    },
    resolve: {
        alias: {
            '@': path.resolve(__dirname, 'resources/js'),
            '@components': path.resolve(__dirname, 'resources/js/components'),
            '@pages': path.resolve(__dirname, 'resources/js/pages'),
            '@layouts': path.resolve(__dirname, 'resources/js/layouts'),
            '@composables': path.resolve(__dirname, 'resources/js/composables'),
            '@stores': path.resolve(__dirname, 'resources/js/stores'),
            '@services': path.resolve(__dirname, 'resources/js/services'),
        }
    },
    build: {
        outDir: 'public/build',
        emptyOutDir: true,
        manifest: 'manifest.json',
        rollupOptions: {
            output: {
                entryFileNames: 'assets/[name].[hash].js',
                chunkFileNames: 'assets/[name].[hash].js',
                assetFileNames: 'assets/[name].[hash].[ext]'
            }
        }
    },
    server: {
        manifest: 'manifest.json'
    }
});