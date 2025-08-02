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

/**
 * Admin Panel Vue.js Application
 *
 * Main entry point for the admin panel Vue.js application with
 * Inertia.js integration and Pinia state management.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */

const appName = window.document.getElementsByTagName('title')[0]?.innerText || 'Admin Panel'

// Self-contained Inertia app for admin panel
createInertiaApp({
    // Use standard 'app' ID to match @inertia directive
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => {
        // Static page resolution to avoid dynamic import path issues
        const pages = {
            'Auth/Login': Login,
            'Auth/Profile': Profile,
            'Dashboard': Dashboard,
            'Resources/Index': ResourceIndex,
            'Resources/Create': ResourceCreate,
            'Resources/Edit': ResourceEdit,
            'Resources/Show': ResourceShow,
        }

        return pages[name] || (() => {
            throw new Error(`Page component not found: ${name}`)
        })
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
