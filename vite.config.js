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
import adminPanel from './vite/index.js'

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
        adminPanel({
            adminPagesPath: '../../../resources/js/admin-panel/pages',
            manifestPath: '../../../public/admin-panel-pages-manifest.json',
            hotReload: true
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
            '@layouts': resolve(__dirname, 'resources/js/Layouts'),
            '@pages': resolve(__dirname, 'resources/js/pages'),
            '@stores': resolve(__dirname, 'resources/js/stores'),
            '@css': resolve(__dirname, 'resources/css'),
            // Package-scoped aliases
            '@jerthedev-admin-panel': resolve(__dirname, 'resources/js'),
            '@jerthedev-admin-panel/components': resolve(__dirname, 'resources/js/Components'),
            '@jerthedev-admin-panel/layouts': resolve(__dirname, 'resources/js/Layouts'),
            '@jerthedev-admin-panel/stores': resolve(__dirname, 'resources/js/stores'),
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
