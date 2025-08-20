<template>
  <div class="mobile-dashboard-navigation" :class="navigationClasses">
    <!-- Mobile Header -->
    <header class="mobile-header" :class="headerClasses">
      <!-- Menu Toggle -->
      <button
        @click="toggleMenu"
        class="menu-toggle"
        :aria-label="menuOpen ? 'Close menu' : 'Open menu'"
        :aria-expanded="menuOpen"
        type="button"
      >
        <Bars3Icon v-if="!menuOpen" class="menu-icon" />
        <XMarkIcon v-else class="menu-icon" />
      </button>

      <!-- Current Dashboard Title -->
      <div class="current-dashboard">
        <h1 class="dashboard-title">{{ currentDashboard?.name || 'Dashboard' }}</h1>
        <p v-if="showBreadcrumbs && breadcrumbs.length > 1" class="breadcrumb-text">
          {{ breadcrumbs[breadcrumbs.length - 2]?.name }}
        </p>
      </div>

      <!-- Header Actions -->
      <div class="header-actions">
        <!-- Search Toggle -->
        <button
          v-if="enableSearch"
          @click="toggleSearch"
          class="header-action search-toggle"
          :class="{ active: searchOpen }"
          aria-label="Toggle search"
          type="button"
        >
          <MagnifyingGlassIcon class="action-icon" />
        </button>

        <!-- Notifications -->
        <button
          v-if="showNotifications"
          @click="toggleNotifications"
          class="header-action notifications-toggle"
          :class="{ active: notificationsOpen, 'has-notifications': hasUnreadNotifications }"
          aria-label="Toggle notifications"
          type="button"
        >
          <BellIcon class="action-icon" />
          <span v-if="unreadCount > 0" class="notification-badge">
            {{ unreadCount > 99 ? '99+' : unreadCount }}
          </span>
        </button>

        <!-- Profile Menu -->
        <button
          v-if="showProfile"
          @click="toggleProfile"
          class="header-action profile-toggle"
          :class="{ active: profileOpen }"
          aria-label="Toggle profile menu"
          type="button"
        >
          <UserCircleIcon class="action-icon" />
        </button>
      </div>
    </header>

    <!-- Search Overlay -->
    <MobileSearchOverlay
      v-if="searchOpen"
      :dashboards="dashboards"
      :recent-searches="recentSearches"
      @close="closeSearch"
      @search="handleSearch"
      @select="handleDashboardSelect"
    />

    <!-- Notifications Panel -->
    <MobileNotificationsPanel
      v-if="notificationsOpen"
      :notifications="notifications"
      @close="closeNotifications"
      @mark-read="markNotificationRead"
      @mark-all-read="markAllNotificationsRead"
    />

    <!-- Profile Menu -->
    <MobileProfileMenu
      v-if="profileOpen"
      :user="user"
      :preferences="userPreferences"
      @close="closeProfile"
      @logout="handleLogout"
      @settings="openSettings"
    />

    <!-- Slide-out Menu -->
    <div
      v-if="menuOpen"
      class="menu-overlay"
      @click="closeMenu"
      @touchstart="handleTouchStart"
      @touchmove="handleTouchMove"
      @touchend="handleTouchEnd"
    >
      <nav
        class="slide-menu"
        :class="slideMenuClasses"
        @click.stop
        ref="slideMenu"
      >
        <!-- Menu Header -->
        <div class="menu-header">
          <div class="menu-title">
            <h2>Dashboards</h2>
            <button
              @click="closeMenu"
              class="close-menu"
              aria-label="Close menu"
              type="button"
            >
              <XMarkIcon class="close-icon" />
            </button>
          </div>

          <!-- Quick Actions -->
          <div class="menu-quick-actions">
            <button
              @click="refreshDashboards"
              class="quick-action"
              :disabled="isRefreshing"
              aria-label="Refresh dashboards"
              type="button"
            >
              <ArrowPathIcon class="quick-action-icon" :class="{ 'animate-spin': isRefreshing }" />
            </button>
            <button
              @click="openDashboardSettings"
              class="quick-action"
              aria-label="Dashboard settings"
              type="button"
            >
              <CogIcon class="quick-action-icon" />
            </button>
          </div>
        </div>

        <!-- Favorites Section -->
        <div v-if="favoriteDashboards.length > 0" class="menu-section">
          <h3 class="section-title">
            <StarIcon class="section-icon" />
            Favorites
          </h3>
          <div class="dashboard-list">
            <MobileDashboardItem
              v-for="dashboard in favoriteDashboards"
              :key="dashboard.uriKey"
              :dashboard="dashboard"
              :is-current="dashboard.uriKey === currentDashboard?.uriKey"
              :show-metadata="true"
              @select="handleDashboardSelect"
              @favorite="toggleFavorite"
            />
          </div>
        </div>

        <!-- Recent Section -->
        <div v-if="recentDashboards.length > 0" class="menu-section">
          <h3 class="section-title">
            <ClockIcon class="section-icon" />
            Recent
          </h3>
          <div class="dashboard-list">
            <MobileDashboardItem
              v-for="dashboard in recentDashboards"
              :key="dashboard.uriKey"
              :dashboard="dashboard"
              :is-current="dashboard.uriKey === currentDashboard?.uriKey"
              :show-metadata="false"
              @select="handleDashboardSelect"
              @favorite="toggleFavorite"
            />
          </div>
        </div>

        <!-- Categories Section -->
        <div v-if="categorizedDashboards.length > 0" class="menu-section">
          <h3 class="section-title">
            <FolderIcon class="section-icon" />
            All Dashboards
          </h3>
          <div class="categories-list">
            <MobileDashboardCategory
              v-for="category in categorizedDashboards"
              :key="category.name"
              :category="category"
              :current-dashboard="currentDashboard"
              :expanded="expandedCategories.includes(category.name)"
              @toggle="toggleCategory"
              @select="handleDashboardSelect"
              @favorite="toggleFavorite"
            />
          </div>
        </div>
      </nav>
    </div>

    <!-- Bottom Navigation -->
    <nav v-if="showBottomNav" class="bottom-navigation">
      <div class="bottom-nav-items">
        <button
          v-for="item in bottomNavItems"
          :key="item.key"
          @click="handleBottomNavClick(item)"
          class="bottom-nav-item"
          :class="{ active: item.active }"
          :aria-label="item.label"
          type="button"
        >
          <component :is="item.icon" class="bottom-nav-icon" />
          <span class="bottom-nav-label">{{ item.label }}</span>
          <span v-if="item.badge" class="bottom-nav-badge">{{ item.badge }}</span>
        </button>
      </div>
    </nav>

    <!-- Gesture Indicators -->
    <div v-if="showGestureHints" class="gesture-hints">
      <div class="gesture-hint swipe-hint">
        <ArrowLeftIcon class="gesture-icon" />
        <span>Swipe to navigate</span>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import {
  Bars3Icon,
  XMarkIcon,
  MagnifyingGlassIcon,
  BellIcon,
  UserCircleIcon,
  ArrowPathIcon,
  CogIcon,
  StarIcon,
  ClockIcon,
  FolderIcon,
  ArrowLeftIcon
} from '@heroicons/vue/24/outline'
import MobileSearchOverlay from './MobileSearchOverlay.vue'
import MobileNotificationsPanel from './MobileNotificationsPanel.vue'
import MobileProfileMenu from './MobileProfileMenu.vue'
import MobileDashboardItem from './MobileDashboardItem.vue'
import MobileDashboardCategory from './MobileDashboardCategory.vue'
import { useDashboardNavigationStore } from '@/stores/dashboardNavigation'
import { useDashboardPreferencesStore } from '@/stores/dashboardPreferences'
import { useMobileGestures } from '@/composables/useMobileGestures'
import { useMobileNavigation } from '@/composables/useMobileNavigation'

export default {
  name: 'MobileDashboardNavigation',
  components: {
    Bars3Icon,
    XMarkIcon,
    MagnifyingGlassIcon,
    BellIcon,
    UserCircleIcon,
    ArrowPathIcon,
    CogIcon,
    StarIcon,
    ClockIcon,
    FolderIcon,
    ArrowLeftIcon,
    MobileSearchOverlay,
    MobileNotificationsPanel,
    MobileProfileMenu,
    MobileDashboardItem,
    MobileDashboardCategory
  },
  props: {
    dashboards: {
      type: Array,
      default: () => []
    },
    currentDashboard: {
      type: Object,
      default: null
    },
    user: {
      type: Object,
      default: null
    },
    notifications: {
      type: Array,
      default: () => []
    },
    showBreadcrumbs: {
      type: Boolean,
      default: true
    },
    enableSearch: {
      type: Boolean,
      default: true
    },
    showNotifications: {
      type: Boolean,
      default: true
    },
    showProfile: {
      type: Boolean,
      default: true
    },
    showBottomNav: {
      type: Boolean,
      default: true
    },
    showGestureHints: {
      type: Boolean,
      default: false
    },
    variant: {
      type: String,
      default: 'default',
      validator: (value) => ['default', 'compact', 'minimal'].includes(value)
    }
  },
  emits: [
    'dashboard-select',
    'search',
    'refresh',
    'settings',
    'logout',
    'favorite-toggle',
    'notification-read',
    'bottom-nav-click'
  ],
  setup(props, { emit }) {
    // Stores
    const navigationStore = useDashboardNavigationStore()
    const preferencesStore = useDashboardPreferencesStore()

    // Composables
    const gestures = useMobileGestures()
    const mobileNav = useMobileNavigation()

    // Reactive state
    const menuOpen = ref(false)
    const searchOpen = ref(false)
    const notificationsOpen = ref(false)
    const profileOpen = ref(false)
    const isRefreshing = ref(false)
    const expandedCategories = ref(['General'])
    const slideMenu = ref(null)

    // Touch handling for slide menu
    const touchStartX = ref(0)
    const touchCurrentX = ref(0)
    const isDragging = ref(false)

    // Computed properties
    const navigationClasses = computed(() => [
      'mobile-dashboard-navigation-base',
      `variant-${props.variant}`,
      {
        'menu-open': menuOpen.value,
        'search-open': searchOpen.value,
        'notifications-open': notificationsOpen.value,
        'profile-open': profileOpen.value
      }
    ])

    const headerClasses = computed(() => [
      'mobile-header-base',
      {
        'with-search': searchOpen.value,
        'with-notifications': notificationsOpen.value
      }
    ])

    const slideMenuClasses = computed(() => [
      'slide-menu-base',
      {
        'dragging': isDragging.value
      }
    ])

    const breadcrumbs = computed(() => navigationStore.breadcrumbs)

    const favoriteDashboards = computed(() => {
      return navigationStore.favoriteDashboards.slice(0, 5)
    })

    const recentDashboards = computed(() => {
      return navigationStore.recentDashboards.slice(0, 5)
    })

    const categorizedDashboards = computed(() => {
      const categories = {}
      
      props.dashboards.forEach(dashboard => {
        const category = dashboard.category || 'General'
        if (!categories[category]) {
          categories[category] = {
            name: category,
            dashboards: []
          }
        }
        categories[category].dashboards.push(dashboard)
      })

      return Object.values(categories).sort((a, b) => a.name.localeCompare(b.name))
    })

    const hasUnreadNotifications = computed(() => {
      return props.notifications.some(notification => !notification.read)
    })

    const unreadCount = computed(() => {
      return props.notifications.filter(notification => !notification.read).length
    })

    const userPreferences = computed(() => preferencesStore.preferences)

    const bottomNavItems = computed(() => [
      {
        key: 'home',
        label: 'Home',
        icon: 'HomeIcon',
        active: true
      },
      {
        key: 'dashboards',
        label: 'Dashboards',
        icon: 'ViewGridIcon',
        active: false
      },
      {
        key: 'favorites',
        label: 'Favorites',
        icon: 'StarIcon',
        active: false,
        badge: favoriteDashboards.value.length > 0 ? favoriteDashboards.value.length : null
      },
      {
        key: 'notifications',
        label: 'Alerts',
        icon: 'BellIcon',
        active: false,
        badge: unreadCount.value > 0 ? unreadCount.value : null
      },
      {
        key: 'profile',
        label: 'Profile',
        icon: 'UserIcon',
        active: false
      }
    ])

    const recentSearches = computed(() => {
      // This would come from a store or localStorage
      return []
    })

    // Methods
    const toggleMenu = () => {
      menuOpen.value = !menuOpen.value
      if (menuOpen.value) {
        closeOtherPanels()
      }
    }

    const closeMenu = () => {
      menuOpen.value = false
    }

    const toggleSearch = () => {
      searchOpen.value = !searchOpen.value
      if (searchOpen.value) {
        closeOtherPanels('search')
      }
    }

    const closeSearch = () => {
      searchOpen.value = false
    }

    const toggleNotifications = () => {
      notificationsOpen.value = !notificationsOpen.value
      if (notificationsOpen.value) {
        closeOtherPanels('notifications')
      }
    }

    const closeNotifications = () => {
      notificationsOpen.value = false
    }

    const toggleProfile = () => {
      profileOpen.value = !profileOpen.value
      if (profileOpen.value) {
        closeOtherPanels('profile')
      }
    }

    const closeProfile = () => {
      profileOpen.value = false
    }

    const closeOtherPanels = (except = null) => {
      if (except !== 'menu') menuOpen.value = false
      if (except !== 'search') searchOpen.value = false
      if (except !== 'notifications') notificationsOpen.value = false
      if (except !== 'profile') profileOpen.value = false
    }

    const handleDashboardSelect = (dashboard) => {
      closeOtherPanels()
      emit('dashboard-select', dashboard)
    }

    const handleSearch = (query) => {
      emit('search', query)
    }

    const refreshDashboards = async () => {
      isRefreshing.value = true
      try {
        await emit('refresh')
      } finally {
        isRefreshing.value = false
      }
    }

    const openDashboardSettings = () => {
      closeOtherPanels()
      emit('settings')
    }

    const toggleFavorite = (dashboard) => {
      emit('favorite-toggle', dashboard)
    }

    const toggleCategory = (categoryName) => {
      const index = expandedCategories.value.indexOf(categoryName)
      if (index > -1) {
        expandedCategories.value.splice(index, 1)
      } else {
        expandedCategories.value.push(categoryName)
      }
    }

    const markNotificationRead = (notification) => {
      emit('notification-read', notification)
    }

    const markAllNotificationsRead = () => {
      emit('notification-read', 'all')
    }

    const handleLogout = () => {
      emit('logout')
    }

    const openSettings = () => {
      closeOtherPanels()
      emit('settings')
    }

    const handleBottomNavClick = (item) => {
      emit('bottom-nav-click', item)
    }

    // Touch gesture handling
    const handleTouchStart = (event) => {
      touchStartX.value = event.touches[0].clientX
      touchCurrentX.value = touchStartX.value
      isDragging.value = false
    }

    const handleTouchMove = (event) => {
      if (!menuOpen.value) return

      touchCurrentX.value = event.touches[0].clientX
      const deltaX = touchCurrentX.value - touchStartX.value

      if (Math.abs(deltaX) > 10) {
        isDragging.value = true
        
        // Only allow closing gesture (swipe left)
        if (deltaX < -50) {
          event.preventDefault()
        }
      }
    }

    const handleTouchEnd = (event) => {
      if (!isDragging.value) return

      const deltaX = touchCurrentX.value - touchStartX.value
      
      // Close menu if swiped left significantly
      if (deltaX < -100) {
        closeMenu()
      }

      isDragging.value = false
    }

    // Keyboard handling
    const handleKeydown = (event) => {
      if (event.key === 'Escape') {
        closeOtherPanels()
      }
    }

    // Lifecycle
    onMounted(() => {
      document.addEventListener('keydown', handleKeydown)
      
      // Setup gesture recognition
      gestures.setup()
      mobileNav.setup()
    })

    onUnmounted(() => {
      document.removeEventListener('keydown', handleKeydown)
      gestures.cleanup()
      mobileNav.cleanup()
    })

    // Watch for outside clicks
    watch([menuOpen, searchOpen, notificationsOpen, profileOpen], () => {
      // Handle body scroll lock
      document.body.style.overflow = 
        menuOpen.value || searchOpen.value || notificationsOpen.value || profileOpen.value 
          ? 'hidden' 
          : ''
    })

    return {
      // State
      menuOpen,
      searchOpen,
      notificationsOpen,
      profileOpen,
      isRefreshing,
      expandedCategories,
      slideMenu,

      // Computed
      navigationClasses,
      headerClasses,
      slideMenuClasses,
      breadcrumbs,
      favoriteDashboards,
      recentDashboards,
      categorizedDashboards,
      hasUnreadNotifications,
      unreadCount,
      userPreferences,
      bottomNavItems,
      recentSearches,

      // Methods
      toggleMenu,
      closeMenu,
      toggleSearch,
      closeSearch,
      toggleNotifications,
      closeNotifications,
      toggleProfile,
      closeProfile,
      handleDashboardSelect,
      handleSearch,
      refreshDashboards,
      openDashboardSettings,
      toggleFavorite,
      toggleCategory,
      markNotificationRead,
      markAllNotificationsRead,
      handleLogout,
      openSettings,
      handleBottomNavClick,
      handleTouchStart,
      handleTouchMove,
      handleTouchEnd
    }
  }
}
</script>

<style scoped>
/* Component styles will be imported from responsive.css */
@import '@/css/responsive.css';

/* Additional component-specific styles */
.mobile-dashboard-navigation {
  @apply relative min-h-screen;
}

.mobile-header {
  @apply sticky top-0 z-50 bg-white border-b border-gray-200 px-4 py-3;
  backdrop-filter: blur(10px);
  background: rgba(255, 255, 255, 0.95);
}

.mobile-header-base {
  @apply flex items-center justify-between;
}

.menu-toggle {
  @apply p-2 -ml-2 text-gray-600 hover:text-gray-900 touch-target;
}

.menu-icon {
  @apply w-6 h-6;
}

.current-dashboard {
  @apply flex-1 mx-4 min-w-0;
}

.dashboard-title {
  @apply text-lg font-semibold text-gray-900 truncate;
}

.breadcrumb-text {
  @apply text-sm text-gray-500 truncate;
}

.header-actions {
  @apply flex items-center space-x-1;
}

.header-action {
  @apply relative p-2 text-gray-600 hover:text-gray-900 touch-target;
}

.action-icon {
  @apply w-5 h-5;
}

.notification-badge {
  @apply absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full min-w-5 h-5 flex items-center justify-center px-1;
}

.menu-overlay {
  @apply fixed inset-0 bg-black bg-opacity-50 z-50;
}

.slide-menu {
  @apply absolute left-0 top-0 bottom-0 w-80 max-w-full bg-white shadow-xl transform transition-transform duration-300 overflow-y-auto;
}

.slide-menu-base {
  transform: translateX(0);
}

.menu-header {
  @apply sticky top-0 bg-white border-b border-gray-200 p-4 z-10;
}

.menu-title {
  @apply flex items-center justify-between mb-3;
}

.menu-title h2 {
  @apply text-lg font-semibold text-gray-900;
}

.close-menu {
  @apply p-1 text-gray-500 hover:text-gray-700 touch-target;
}

.close-icon {
  @apply w-5 h-5;
}

.menu-quick-actions {
  @apply flex space-x-2;
}

.quick-action {
  @apply p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded touch-target;
}

.quick-action-icon {
  @apply w-5 h-5;
}

.menu-section {
  @apply border-b border-gray-100 last:border-b-0;
}

.section-title {
  @apply flex items-center px-4 py-3 text-sm font-medium text-gray-700 bg-gray-50;
}

.section-icon {
  @apply w-4 h-4 mr-2 text-gray-500;
}

.dashboard-list,
.categories-list {
  @apply divide-y divide-gray-100;
}

.bottom-navigation {
  @apply fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 z-40;
}

.bottom-nav-items {
  @apply flex justify-around items-center py-2;
}

.bottom-nav-item {
  @apply relative flex flex-col items-center p-2 min-w-16 touch-target;
}

.bottom-nav-item.active {
  @apply text-blue-600;
}

.bottom-nav-icon {
  @apply w-5 h-5 mb-1;
}

.bottom-nav-label {
  @apply text-xs font-medium;
}

.bottom-nav-badge {
  @apply absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full min-w-4 h-4 flex items-center justify-center px-1;
}

.gesture-hints {
  @apply fixed bottom-20 left-4 right-4 pointer-events-none;
}

.gesture-hint {
  @apply flex items-center justify-center bg-black bg-opacity-75 text-white text-sm rounded-lg p-2 mb-2;
}

.gesture-icon {
  @apply w-4 h-4 mr-2;
}

/* Dark mode */
@media (prefers-color-scheme: dark) {
  .mobile-header {
    @apply bg-gray-900 border-gray-700;
    background: rgba(17, 24, 39, 0.95);
  }

  .dashboard-title {
    @apply text-gray-100;
  }

  .breadcrumb-text {
    @apply text-gray-400;
  }

  .slide-menu {
    @apply bg-gray-900;
  }

  .menu-header {
    @apply bg-gray-900 border-gray-700;
  }

  .menu-title h2 {
    @apply text-gray-100;
  }

  .section-title {
    @apply text-gray-300 bg-gray-800;
  }

  .bottom-navigation {
    @apply bg-gray-900 border-gray-700;
  }
}
</style>
