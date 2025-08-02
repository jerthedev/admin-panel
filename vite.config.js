/**
 * Vite Configuration for Admin Panel - Tailwind v4 Compatible
 *
 * Build configuration for the JTD AdminPanel Vue.js frontend
 * with optimizations for development and production.
 * Updated for Tailwind CSS v4 compatibility.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 * @version 1.0.0 - Tailwind v4 Compatible
 */

import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import laravel from 'laravel-vite-plugin'
import tailwindcss from '@tailwindcss/vite'
import { resolve } from 'path'

export default defineConfig({
    base: '/vendor/admin-panel/',
    plugins: [
        tailwindcss(), // Tailwind v4 Vite plugin
        laravel({
            input: [
                'resources/js/app.js',
                'resources/css/admin.css'
            ],
            publicDirectory: 'public',
            buildDirectory: 'build',
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
    ],

    build: {
        outDir: 'public/build',
        emptyOutDir: true,
        manifest: true,
        target: 'es2020', // Ensure compatibility while maintaining modern features
        rollupOptions: {
            output: {
                entryFileNames: 'assets/[name]-[hash].js',
                chunkFileNames: 'assets/[name]-[hash].js',
                assetFileNames: 'assets/[name]-[hash].[ext]',
                format: 'es', // Explicitly use ES modules
                // Force single bundle to avoid dynamic import path issues
                manualChunks: () => 'vendor',
            },
        },
    },

    resolve: {
        alias: {
            '@': resolve(__dirname, 'resources/js'),
            '@components': resolve(__dirname, 'resources/js/components'),
            '@pages': resolve(__dirname, 'resources/js/pages'),
            '@stores': resolve(__dirname, 'resources/js/stores'),
            '@css': resolve(__dirname, 'resources/css'),
        },
    },

    server: {
        hmr: {
            host: 'localhost',
        },
        watch: {
            usePolling: true,
        },
    },

    optimizeDeps: {
        include: [
            'vue',
            '@inertiajs/vue3',
            'pinia',
            'axios',
        ],
    },

    // CSS processing is handled by @tailwindcss/vite plugin
})
