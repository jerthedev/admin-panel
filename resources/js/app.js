import { createApp, h } from 'vue'
import { createInertiaApp } from '@inertiajs/vue3'
import { createPinia } from 'pinia'
import { ZiggyVue } from 'ziggy-js'
import '../css/admin.css'

// Static imports to avoid dynamic import path issues
import Login from './pages/Auth/Login.vue'
import Profile from './pages/Auth/Profile.vue'
import Dashboard from './pages/Dashboard.vue'
import ResourceIndex from './pages/Resources/Index.vue'
import ResourceCreate from './pages/Resources/Create.vue'
import ResourceEdit from './pages/Resources/Edit.vue'
import ResourceShow from './pages/Resources/Show.vue'

// Admin panel page components (from main project)
// Note: These need to be imported statically for production builds
import SystemDashboard from '../../../../../resources/js/admin-panel/pages/SystemDashboard.vue'
import Welcome from '../../../../../resources/js/admin-panel/pages/Welcome.vue'
import MultiComponentTestSimple from '../../../../../resources/js/admin-panel/pages/MultiComponentTestSimple.vue'
import MultiComponentTestDashboard from '../../../../../resources/js/admin-panel/pages/MultiComponentTest/Dashboard.vue'
import MultiComponentTestSettings from '../../../../../resources/js/admin-panel/pages/MultiComponentTest/Settings.vue'
import MultiComponentTestAnalytics from '../../../../../resources/js/admin-panel/pages/MultiComponentTest/Analytics.vue'

/**
 * Admin Panel Vue.js Application
 *
 * Main entry point for the admin panel Vue.js application with
 * Inertia.js integration and Pinia state management.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */

const appName = window.document.getElementsByTagName('title')[0]?.innerText || 'Admin Panel'

// Component cache to prevent double loading
const componentCache = new Map();

// Self-contained Inertia app for admin panel
createInertiaApp({
    // Use standard 'app' ID to match @inertia directive
    title: (title) => `${title} - ${appName}`,
    resolve: async (name) => {
        // Check cache first
        if (componentCache.has(name)) {
            console.log(`🔄 Using cached component: ${name}`);
            return componentCache.get(name);
        }
        // Check for manifest-based resolution first (priority-based)
        const manifests = window.adminPanelComponentManifests || {};

        // Try each manifest in priority order (app first, then packages)
        for (const [source, manifest] of Object.entries(manifests)) {
            if (manifest && manifest[name]) {
                // Check if this component should use fallback instead of manifest loading
                const manifestEntry = manifest[name];
                const shouldUseFallback = typeof manifestEntry === 'object' && manifestEntry.useFallback;

                if (shouldUseFallback) {
                    console.log(`📦 ${name} in ${source} manifest marked for fallback, skipping manifest import`);
                    continue; // Skip to next manifest or fall through to development fallback
                }

                try {
                    console.log(`📦 Loading ${name} from ${source} manifest`);

                    // Handle both old format (direct URL) and new format (object with file property)
                    const importPath = typeof manifestEntry === 'string'
                        ? manifestEntry
                        : manifestEntry.file;

                    const module = await import(importPath);
                    const component = module.default || module;
                    console.log(`✅ Loaded manifest component: ${name} from ${source}`);

                    // Ensure the component is properly structured for Vue and is extensible
                    if (component && typeof component === 'object') {
                        // Create a new extensible object to avoid frozen/sealed issues
                        const extensibleComponent = { ...component };

                        // Ensure inheritAttrs is properly set
                        if (!extensibleComponent.hasOwnProperty('inheritAttrs')) {
                            extensibleComponent.inheritAttrs = false;
                        }

                        // Debug component structure
                        console.log(`🔍 Component structure for ${name}:`, {
                            hasRender: typeof extensibleComponent.render === 'function',
                            hasTemplate: !!extensibleComponent.template,
                            hasSetup: typeof extensibleComponent.setup === 'function',
                            keys: Object.keys(extensibleComponent),
                            rawComponent: component,
                            moduleKeys: Object.keys(module)
                        });

                        // Cache the component
                        componentCache.set(name, extensibleComponent);
                        return extensibleComponent;
                    }
                    throw new Error('Invalid component structure');
                } catch (error) {
                    console.warn(`❌ Failed to load manifest component ${name} from ${source}:`, error);
                    continue;
                }
            }
        }

        // Fallback to static package components
        const packagePages = {
            'Auth/Login': Login,
            'Auth/Profile': Profile,
            'Dashboard': Dashboard,
            'Resources/Index': ResourceIndex,
            'Resources/Create': ResourceCreate,
            'Resources/Edit': ResourceEdit,
            'Resources/Show': ResourceShow,
            // Admin panel page components
            'Pages/SystemDashboard': SystemDashboard,
            'Pages/Welcome': Welcome,
            'Pages/MultiComponentTestSimple': MultiComponentTestSimple,
            'Pages/MultiComponentTest/Dashboard': MultiComponentTestDashboard,
            'Pages/MultiComponentTest/Settings': MultiComponentTestSettings,
            'Pages/MultiComponentTest/Analytics': MultiComponentTestAnalytics,
        }

        const packageComponent = packagePages[name];
        if (packageComponent) {
            console.log(`✅ Loaded static package component: ${name}`);
            componentCache.set(name, packageComponent);
            return packageComponent;
        }

        // Development fallback: try dynamic import for custom pages
        if (name.startsWith('Pages/')) {
            try {
                const componentName = name.substring(6); // Remove 'Pages/' prefix
                // Use absolute URL for development imports to work with Vite dev server
                const module = await import(`/resources/js/admin-panel/pages/${componentName}.vue`);
                const component = module.default || module;
                console.log(`✅ Loaded dev app component: ${name}`);

                // Ensure the component is properly structured for Vue and is extensible
                if (component && typeof component === 'object') {
                    // Create a new extensible object to avoid frozen/sealed issues
                    const extensibleComponent = { ...component };

                    // Ensure inheritAttrs is properly set
                    if (!extensibleComponent.hasOwnProperty('inheritAttrs')) {
                        extensibleComponent.inheritAttrs = false;
                    }

                    // Cache the component
                    componentCache.set(name, extensibleComponent);
                    return extensibleComponent;
                }
                throw new Error('Invalid component structure');
            } catch (appError) {
                console.log(`📦 Dev app component not found: ${name}`);
            }
        }

        // If not found anywhere, show helpful fallback
        console.warn(`❌ Component not found: ${name}. Using fallback.`);

        const fallbackComponent = {
            template: `
                <div class="p-6">
                    <div class="bg-yellow-50 border border-yellow-200 rounded-md p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-yellow-800">
                                    Component Not Found
                                </h3>
                                <div class="mt-2 text-sm text-yellow-700">
                                    <p>The component <code class="bg-yellow-100 px-1 rounded font-mono">${name}</code> was not found.</p>
                                    ${name.startsWith('Pages/') ? `
                                        <p class="mt-1">Expected location: <code class="bg-yellow-100 px-1 rounded font-mono">resources/js/admin-panel/pages/${name.substring(6)}.vue</code></p>
                                        <p class="mt-1">Create the component or use: <code class="bg-yellow-100 px-1 rounded font-mono">php artisan admin-panel:make-page ${name.substring(6)}</code></p>
                                        <p class="mt-1">Or run: <code class="bg-yellow-100 px-1 rounded font-mono">php artisan admin-panel:setup-custom-pages</code></p>
                                    ` : ''}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `
        };

        // Cache the fallback component
        componentCache.set(name, fallbackComponent);
        return fallbackComponent;
    },
    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(createPinia())
            .use(ZiggyVue)

        // Global properties
        app.config.globalProperties.$adminPanel = {
            version: '1.0.0',
            path: window.adminPanelConfig?.path || '/admin',
            theme: window.adminPanelConfig?.theme || 'default',
        }

        // Global error handler
        app.config.errorHandler = (error, instance, info) => {
            console.error('Admin Panel Error:', error, info)

            // You can integrate with error reporting services here
            if (window.adminPanelConfig?.debug) {
                console.error('Component instance:', instance)
            }
        }

        return app.mount(el)
    },
    progress: {
        color: '#3b82f6', // Blue-500
        showSpinner: true,
    },
})
