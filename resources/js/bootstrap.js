/**
 * Admin Panel Bootstrap
 * 
 * Common utilities, configurations, and global setup for the admin panel.
 * 
 * @author Jeremy Fall <jerthedev@gmail.com>
 */

import axios from 'axios'

// Configure Axios
window.axios = axios
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest'

// CSRF Token
const token = document.head.querySelector('meta[name="csrf-token"]')
if (token) {
    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token.content
} else {
    console.error('CSRF token not found: https://laravel.com/docs/csrf#csrf-x-csrf-token')
}

// Admin Panel Configuration
window.adminPanelConfig = {
    path: document.querySelector('meta[name="admin-panel-path"]')?.content || '/admin',
    theme: document.querySelector('meta[name="admin-panel-theme"]')?.content || 'default',
    debug: document.querySelector('meta[name="admin-panel-debug"]')?.content === 'true',
    version: '1.0.0',
}

// Global utilities
window.adminPanel = {
    /**
     * Format currency values
     */
    formatCurrency(value, currency = '$', decimals = 2) {
        if (value === null || value === undefined) return ''
        const formatted = parseFloat(value).toFixed(decimals)
        return `${currency}${formatted}`
    },

    /**
     * Format dates
     */
    formatDate(date, format = 'Y-m-d') {
        if (!date) return ''
        const d = new Date(date)
        
        // Simple date formatting (you might want to use a library like date-fns)
        const year = d.getFullYear()
        const month = String(d.getMonth() + 1).padStart(2, '0')
        const day = String(d.getDate()).padStart(2, '0')
        
        switch (format) {
            case 'Y-m-d':
                return `${year}-${month}-${day}`
            case 'd/m/Y':
                return `${day}/${month}/${year}`
            case 'M d, Y':
                const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
                              'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']
                return `${months[d.getMonth()]} ${day}, ${year}`
            default:
                return d.toLocaleDateString()
        }
    },

    /**
     * Debounce function
     */
    debounce(func, wait) {
        let timeout
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout)
                func(...args)
            }
            clearTimeout(timeout)
            timeout = setTimeout(later, wait)
        }
    },

    /**
     * Generate admin panel route
     */
    route(name, params = {}) {
        let path = window.adminPanelConfig.path
        
        // Simple route generation (you might want to use a more sophisticated router)
        switch (name) {
            case 'dashboard':
                return path
            case 'resources.index':
                return `${path}/resources/${params.resource}`
            case 'resources.show':
                return `${path}/resources/${params.resource}/${params.id}`
            case 'resources.create':
                return `${path}/resources/${params.resource}/create`
            case 'resources.edit':
                return `${path}/resources/${params.resource}/${params.id}/edit`
            default:
                return path
        }
    },

    /**
     * Show notification (you can integrate with a toast library)
     */
    notify(message, type = 'info') {
        console.log(`[${type.toUpperCase()}] ${message}`)
        // TODO: Integrate with a proper notification system
    },

    /**
     * Confirm dialog
     */
    confirm(message, callback) {
        if (window.confirm(message)) {
            callback()
        }
    }
}

// Request interceptors for loading states
let loadingRequests = 0

axios.interceptors.request.use(config => {
    loadingRequests++
    document.body.classList.add('admin-loading')
    return config
})

axios.interceptors.response.use(
    response => {
        loadingRequests--
        if (loadingRequests === 0) {
            document.body.classList.remove('admin-loading')
        }
        return response
    },
    error => {
        loadingRequests--
        if (loadingRequests === 0) {
            document.body.classList.remove('admin-loading')
        }
        
        // Global error handling
        if (error.response?.status === 419) {
            window.adminPanel.notify('Session expired. Please refresh the page.', 'error')
        } else if (error.response?.status >= 500) {
            window.adminPanel.notify('Server error. Please try again.', 'error')
        }
        
        return Promise.reject(error)
    }
)
