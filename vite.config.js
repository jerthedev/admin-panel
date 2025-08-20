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
        sourcemap: process.env.NODE_ENV === 'development',
        minify: 'terser',
        terserOptions: {
            compress: {
                drop_console: process.env.NODE_ENV === 'production',
                drop_debugger: true,
                pure_funcs: ['console.log', 'console.debug']
            },
            mangle: {
                safari10: true
            }
        },
        rollupOptions: {
            output: {
                entryFileNames: 'assets/[name]-[hash].js',
                chunkFileNames: 'assets/[name]-[hash].js',
                assetFileNames: 'assets/[name]-[hash].[ext]',
                format: 'es', // Explicitly use ES modules
                // Advanced code splitting strategy
                manualChunks: {
                    // Vendor libraries
                    'vendor-vue': ['vue', '@inertiajs/vue3'],
                    'vendor-ui': ['@headlessui/vue', '@heroicons/vue'],
                    'vendor-utils': ['axios', 'lodash-es'],

                    // Dashboard-specific chunks
                    'dashboard-core': [
                        './resources/js/stores/dashboardNavigation.js',
                        './resources/js/stores/dashboardCache.js',
                        './resources/js/stores/dashboardPreferences.js'
                    ],
                    'dashboard-components': [
                        './resources/js/Components/Dashboard/DashboardSelector.vue',
                        './resources/js/Components/Dashboard/DashboardNavigation.vue'
                    ],
                    'dashboard-mobile': [
                        './resources/js/Components/Mobile/MobileDashboardNavigation.vue',
                        './resources/js/composables/useMobileGestures.js',
                        './resources/js/composables/useMobileNavigation.js'
                    ],
                    'dashboard-performance': [
                        './resources/js/services/LazyLoadingService.js',
                        './resources/js/services/PerformanceMonitoringService.js',
                        './resources/js/composables/usePerformanceOptimization.js'
                    ]
                },
                // Optimize chunk loading
                experimentalMinChunkSize: 1000,
                maxParallelFileOps: 5
            },
            // External dependencies (if using CDN)
            external: process.env.NODE_ENV === 'production' ? [] : [],
            // Performance optimizations
            treeshake: {
                moduleSideEffects: false,
                propertyReadSideEffects: false,
                unknownGlobalSideEffects: false
            }
        },
        // Chunk size warnings
        chunkSizeWarningLimit: 1000,
        // Asset inlining threshold
        assetsInlineLimit: 4096
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
            '@headlessui/vue',
            '@heroicons/vue/24/outline',
            '@heroicons/vue/24/solid',
            'lodash-es'
        ],
        exclude: [
            // Exclude large libraries that should be code-split
            '@heroicons/vue/24/outline/*',
            '@heroicons/vue/24/solid/*'
        ],
        // Force optimization of specific dependencies
        force: process.env.NODE_ENV === 'development',
        // Enable esbuild optimization
        esbuildOptions: {
            target: 'es2020',
            supported: {
                'top-level-await': true
            }
        }
    },

    // Performance optimizations
    esbuild: {
        // Remove console logs in production
        drop: process.env.NODE_ENV === 'production' ? ['console', 'debugger'] : [],
        // Enable tree shaking
        treeShaking: true,
        // Optimize for size
        minifyIdentifiers: process.env.NODE_ENV === 'production',
        minifySyntax: process.env.NODE_ENV === 'production',
        minifyWhitespace: process.env.NODE_ENV === 'production'
    },

    // Experimental features for performance
    experimental: {
        renderBuiltUrl(filename, { hostType }) {
            if (hostType === 'js') {
                return { js: `/${filename}` }
            } else {
                return { relative: true }
            }
        }
    },

    // CSS processing is handled by @tailwindcss/vite plugin
})
