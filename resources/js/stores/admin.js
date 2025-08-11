/**
 * Admin Panel Store
 *
 * Pinia store for managing admin panel global state including
 * user data, theme preferences, and UI state.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */

import { defineStore } from 'pinia'
import { ref, computed } from 'vue'

export const useAdminStore = defineStore('admin', () => {
    // State
    const user = ref(null)
    const theme = ref('default')
    const sidebarOpen = ref(true)
    const loading = ref(false)
    const notifications = ref([])
    const resources = ref([])
    const currentResource = ref(null)
    const fullscreenMode = ref(false)

    // Getters
    const isAuthenticated = computed(() => !!user.value)
    const isDarkTheme = computed(() => theme.value === 'dark')
    const hasNotifications = computed(() => notifications.value.length > 0)
    const unreadNotifications = computed(() =>
        notifications.value.filter(n => !n.read).length
    )

    // Actions
    function setUser(userData) {
        user.value = userData
    }

    function setTheme(newTheme) {
        theme.value = newTheme
        localStorage.setItem('admin-panel-theme', newTheme)

        // Apply theme to document
        if (newTheme === 'dark') {
            document.documentElement.setAttribute('data-theme', 'dark')
        } else {
            document.documentElement.removeAttribute('data-theme')
        }
    }

    function toggleSidebar() {
        sidebarOpen.value = !sidebarOpen.value
        localStorage.setItem('admin-panel-sidebar', sidebarOpen.value ? 'open' : 'closed')
    }

    function setSidebarOpen(open) {
        sidebarOpen.value = open
        localStorage.setItem('admin-panel-sidebar', open ? 'open' : 'closed')
    }

    function setFullscreenMode(isFullscreen) {
        fullscreenMode.value = isFullscreen
    }

    function toggleFullscreenMode() {
        fullscreenMode.value = !fullscreenMode.value
    }

    function setLoading(isLoading) {
        loading.value = isLoading
    }

    function addNotification(notification) {
        const id = Date.now() + Math.random()
        notifications.value.push({
            id,
            read: false,
            timestamp: new Date(),
            ...notification
        })

        // Auto-remove after 5 seconds for success/info notifications
        if (['success', 'info'].includes(notification.type)) {
            setTimeout(() => {
                removeNotification(id)
            }, 5000)
        }
    }

    function removeNotification(id) {
        const index = notifications.value.findIndex(n => n.id === id)
        if (index > -1) {
            notifications.value.splice(index, 1)
        }
    }

    function markNotificationAsRead(id) {
        const notification = notifications.value.find(n => n.id === id)
        if (notification) {
            notification.read = true
        }
    }

    function clearNotifications() {
        notifications.value = []
    }

    function setResources(resourceList) {
        resources.value = resourceList
    }

    function setCurrentResource(resource) {
        currentResource.value = resource
    }

    function findResource(uriKey) {
        return resources.value.find(r => r.uriKey === uriKey)
    }

    // Initialize store
    function initialize() {
        // Load theme from localStorage
        const savedTheme = localStorage.getItem('admin-panel-theme')
        if (savedTheme) {
            setTheme(savedTheme)
        }

        // Load sidebar state from localStorage
        const savedSidebar = localStorage.getItem('admin-panel-sidebar')
        if (savedSidebar) {
            setSidebarOpen(savedSidebar === 'open')
        }

        // Set up responsive sidebar behavior
        const mediaQuery = window.matchMedia('(max-width: 768px)')
        const handleResize = (e) => {
            if (e.matches) {
                setSidebarOpen(false)
            } else {
                setSidebarOpen(true)
            }
        }

        mediaQuery.addListener(handleResize)
        handleResize(mediaQuery)
    }

    // Utility functions
    function notify(message, type = 'info', title = null) {
        addNotification({
            title,
            message,
            type
        })
    }

    function notifySuccess(message, title = 'Success') {
        notify(message, 'success', title)
    }

    function notifyError(message, title = 'Error') {
        notify(message, 'error', title)
    }

    function notifyWarning(message, title = 'Warning') {
        notify(message, 'warning', title)
    }

    function notifyInfo(message, title = 'Info') {
        notify(message, 'info', title)
    }

    // Debounce utility function
    function debounce(func, wait = 300) {
        let timeout
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout)
                func(...args)
            }
            clearTimeout(timeout)
            timeout = setTimeout(later, wait)
        }
    }

    return {
        // State
        user,
        theme,
        sidebarOpen,
        loading,
        notifications,
        resources,
        currentResource,
        fullscreenMode,

        // Getters
        isAuthenticated,
        isDarkTheme,
        hasNotifications,
        unreadNotifications,

        // Actions
        setUser,
        setTheme,
        toggleSidebar,
        setSidebarOpen,
        setLoading,
        setFullscreenMode,
        toggleFullscreenMode,
        addNotification,
        removeNotification,
        markNotificationAsRead,
        clearNotifications,
        setResources,
        setCurrentResource,
        findResource,
        initialize,

        // Utilities
        notify,
        notifySuccess,
        notifyError,
        notifyWarning,
        notifyInfo,
        debounce
    }
})
