import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import { VitePWA } from 'vite-plugin-pwa';
import path from 'path';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        vue(),
        VitePWA({
            registerType: 'autoUpdate',
            injectRegister: 'auto',
            manifest: {
                name: 'Freezing Fish Factory Warehouse Management',
                short_name: 'FishWarehouse',
                description: 'Freezing Fish Factory Warehouse Management System PWA',
                theme_color: '#1976D2',
                background_color: '#0F172A',
                display: 'standalone',
                orientation: 'any',
                icons: [
                    {
                        src: 'https://cdn.jsdelivr.net/gh/twitter/twemoji@14.0.2/assets/72x72/1f41f.png',
                        sizes: '72x72',
                        type: 'image/png'
                    },
                    {
                        src: 'https://cdn.jsdelivr.net/gh/twitter/twemoji@14.0.2/assets/72x72/1f41f.png',
                        sizes: '192x192',
                        type: 'image/png'
                    },
                    {
                        src: 'https://cdn.jsdelivr.net/gh/twitter/twemoji@14.0.2/assets/72x72/1f41f.png',
                        sizes: '512x512',
                        type: 'image/png'
                    }
                ]
            }
        })
    ],
    resolve: {
        alias: {
            '@': path.resolve(__dirname, './resources/js'),
        },
    },
});
