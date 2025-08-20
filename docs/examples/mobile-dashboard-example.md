# Mobile Dashboard Example

This example demonstrates mobile-optimized dashboard features including touch gestures, responsive design, and mobile-specific navigation.

## Mobile-Optimized Dashboard

```vue
<template>
  <div class="mobile-dashboard" :class="{ 'is-mobile': isMobile }">
    <!-- Mobile Header -->
    <div class="mobile-header" data-testid="mobile-header">
      <div class="header-left">
        <button
          class="menu-button"
          data-testid="mobile-menu-button"
          @click="toggleMenu"
          :aria-expanded="showMenu"
        >
          <Bars3Icon class="w-6 h-6" />
        </button>
        <h1 class="dashboard-title">{{ currentDashboard?.name }}</h1>
      </div>
      <div class="header-right">
        <button
          class="refresh-button"
          @click="handleRefresh"
          :disabled="isRefreshing"
        >
          <ArrowPathIcon class="w-5 h-5" :class="{ 'animate-spin': isRefreshing }" />
        </button>
      </div>
    </div>

    <!-- Pull-to-Refresh Container -->
    <div
      ref="pullToRefreshContainer"
      class="pull-to-refresh-container"
      data-testid="pull-to-refresh-container"
    >
      <div
        class="pull-to-refresh-indicator"
        data-testid="pull-to-refresh-indicator"
        :class="{ 'visible': showPullIndicator }"
      >
        <ArrowPathIcon class="w-6 h-6 animate-spin" />
        <span>Pull to refresh</span>
      </div>

      <!-- Dashboard Content -->
      <div
        ref="dashboardContent"
        class="dashboard-content"
        data-testid="dashboard-content"
        @touchstart="handleTouchStart"
        @touchmove="handleTouchMove"
        @touchend="handleTouchEnd"
      >
        <!-- Mobile Dashboard Grid -->
        <div class="mobile-grid" data-testid="mobile-grid">
          <div
            v-for="card in dashboardCards"
            :key="card.id"
            class="mobile-card"
            data-testid="mobile-card"
            @click="handleCardTap(card)"
            @touchstart="handleCardTouchStart(card, $event)"
            @touchend="handleCardTouchEnd(card, $event)"
          >
            <component
              :is="card.component"
              :data="card.data"
              :mobile="true"
              :compact="true"
            />
          </div>
        </div>
      </div>
    </div>

    <!-- Mobile Navigation Menu -->
    <Transition name="slide-menu">
      <div
        v-if="showMenu"
        class="mobile-menu-overlay"
        data-testid="mobile-menu-overlay"
        @click="closeMenu"
      >
        <div
          class="mobile-menu"
          data-testid="mobile-menu"
          @click.stop
        >
          <div class="menu-header">
            <h2>Dashboards</h2>
            <button @click="closeMenu">
              <XMarkIcon class="w-6 h-6" />
            </button>
          </div>
          
          <div class="menu-content">
            <div
              v-for="dashboard in availableDashboards"
              :key="dashboard.uriKey"
              class="menu-item"
              data-testid="mobile-menu-item"
              :class="{ 'active': dashboard.uriKey === currentDashboard?.uriKey }"
              @click="selectDashboard(dashboard)"
            >
              <div class="menu-item-icon">
                <component :is="getIconComponent(dashboard.icon)" class="w-5 h-5" />
              </div>
              <div class="menu-item-content">
                <div class="menu-item-title">{{ dashboard.name }}</div>
                <div class="menu-item-description">{{ dashboard.description }}</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </Transition>

    <!-- Bottom Navigation -->
    <div class="bottom-navigation" data-testid="bottom-navigation">
      <button
        v-for="dashboard in favoritesDashboards.slice(0, 4)"
        :key="dashboard.uriKey"
        class="bottom-nav-item"
        :class="{ 'active': dashboard.uriKey === currentDashboard?.uriKey }"
        @click="selectDashboard(dashboard)"
      >
        <component :is="getIconComponent(dashboard.icon)" class="w-5 h-5" />
        <span class="nav-label">{{ dashboard.name }}</span>
      </button>
      <button class="bottom-nav-item" @click="toggleMenu">
        <EllipsisHorizontalIcon class="w-5 h-5" />
        <span class="nav-label">More</span>
      </button>
    </div>

    <!-- Floating Action Button -->
    <button
      v-if="showFAB"
      class="floating-action-button"
      data-testid="floating-action-button"
      @click="handleFABClick"
    >
      <PlusIcon class="w-6 h-6" />
    </button>

    <!-- Mobile Toast Notifications -->
    <div class="toast-container">
      <Transition name="toast" appear>
        <div
          v-if="toast.show"
          class="toast"
          :class="toast.type"
        >
          {{ toast.message }}
        </div>
      </Transition>
    </div>
  </div>
</template>

<script>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useMobileNavigation } from '@/composables/useMobileNavigation'
import { useMobileGestures } from '@/composables/useMobileGestures'
import { useDashboardStore } from '@/stores/dashboard'
import {
  Bars3Icon,
  XMarkIcon,
  ArrowPathIcon,
  PlusIcon,
  EllipsisHorizontalIcon
} from '@heroicons/vue/24/outline'

export default {
  name: 'MobileDashboard',
  components: {
    Bars3Icon,
    XMarkIcon,
    ArrowPathIcon,
    PlusIcon,
    EllipsisHorizontalIcon
  },
  setup() {
    const dashboardStore = useDashboardStore()
    const mobileNav = useMobileNavigation()
    
    // State
    const showMenu = ref(false)
    const isRefreshing = ref(false)
    const showPullIndicator = ref(false)
    const showFAB = ref(true)
    const toast = ref({ show: false, message: '', type: 'info' })
    
    // Refs
    const pullToRefreshContainer = ref(null)
    const dashboardContent = ref(null)

    // Touch gesture state
    const touchStartY = ref(0)
    const touchCurrentY = ref(0)
    const isPulling = ref(false)
    const pullThreshold = 80

    // Computed
    const isMobile = computed(() => mobileNav.isMobile.value)
    const currentDashboard = computed(() => dashboardStore.currentDashboard)
    const availableDashboards = computed(() => dashboardStore.dashboards)
    const favoritesDashboards = computed(() => dashboardStore.favoriteDashboards)
    const dashboardCards = computed(() => currentDashboard.value?.cards || [])

    // Mobile gestures
    const gestures = useMobileGestures({
      enableSwipe: true,
      enablePinch: false,
      enablePullToRefresh: true,
      swipeThreshold: 50,
      pullThreshold: pullThreshold
    })

    // Methods
    const toggleMenu = () => {
      showMenu.value = !showMenu.value
    }

    const closeMenu = () => {
      showMenu.value = false
    }

    const selectDashboard = async (dashboard) => {
      closeMenu()
      
      if (dashboard.uriKey === currentDashboard.value?.uriKey) return
      
      try {
        await dashboardStore.switchToDashboard(dashboard.uriKey)
        showToast(`Switched to ${dashboard.name}`, 'success')
      } catch (error) {
        showToast('Failed to switch dashboard', 'error')
      }
    }

    const handleRefresh = async () => {
      if (isRefreshing.value) return
      
      isRefreshing.value = true
      try {
        await dashboardStore.refreshCurrentDashboard()
        showToast('Dashboard refreshed', 'success')
      } catch (error) {
        showToast('Failed to refresh dashboard', 'error')
      } finally {
        isRefreshing.value = false
      }
    }

    const handleFABClick = () => {
      // Handle floating action button click
      // Could open quick actions, create new item, etc.
      showToast('Quick action triggered', 'info')
    }

    const showToast = (message, type = 'info') => {
      toast.value = { show: true, message, type }
      setTimeout(() => {
        toast.value.show = false
      }, 3000)
    }

    // Touch event handlers
    const handleTouchStart = (event) => {
      if (event.touches.length === 1) {
        touchStartY.value = event.touches[0].clientY
        isPulling.value = dashboardContent.value.scrollTop === 0
      }
    }

    const handleTouchMove = (event) => {
      if (!isPulling.value || event.touches.length !== 1) return
      
      touchCurrentY.value = event.touches[0].clientY
      const pullDistance = touchCurrentY.value - touchStartY.value
      
      if (pullDistance > 0 && pullDistance < pullThreshold * 2) {
        event.preventDefault()
        showPullIndicator.value = pullDistance > pullThreshold / 2
        
        // Visual feedback for pull distance
        const opacity = Math.min(pullDistance / pullThreshold, 1)
        const indicator = document.querySelector('.pull-to-refresh-indicator')
        if (indicator) {
          indicator.style.opacity = opacity
          indicator.style.transform = `translateY(${Math.min(pullDistance, pullThreshold)}px)`
        }
      }
    }

    const handleTouchEnd = (event) => {
      if (!isPulling.value) return
      
      const pullDistance = touchCurrentY.value - touchStartY.value
      
      if (pullDistance > pullThreshold) {
        handleRefresh()
      }
      
      // Reset pull state
      isPulling.value = false
      showPullIndicator.value = false
      touchStartY.value = 0
      touchCurrentY.value = 0
      
      // Reset indicator position
      const indicator = document.querySelector('.pull-to-refresh-indicator')
      if (indicator) {
        indicator.style.opacity = '0'
        indicator.style.transform = 'translateY(0)'
      }
    }

    // Card interaction handlers
    const handleCardTap = (card) => {
      // Handle card tap - could expand, navigate, etc.
      console.log('Card tapped:', card)
    }

    const handleCardTouchStart = (card, event) => {
      // Handle card touch start for long press detection
      card.touchStartTime = Date.now()
    }

    const handleCardTouchEnd = (card, event) => {
      // Handle long press
      const touchDuration = Date.now() - (card.touchStartTime || 0)
      if (touchDuration > 500) {
        // Long press detected
        handleCardLongPress(card)
      }
    }

    const handleCardLongPress = (card) => {
      // Handle card long press - could show context menu
      showToast(`Long press on ${card.title}`, 'info')
    }

    const getIconComponent = (iconName) => {
      // Dynamic icon component loading
      try {
        return require(`@heroicons/vue/24/outline/${iconName}.js`).default
      } catch {
        return require('@heroicons/vue/24/outline/ViewGridIcon.js').default
      }
    }

    // Setup gesture handlers
    const setupGestureHandlers = () => {
      gestures.on('swipe-left', () => {
        // Navigate to next dashboard
        const currentIndex = availableDashboards.value.findIndex(
          d => d.uriKey === currentDashboard.value?.uriKey
        )
        const nextIndex = (currentIndex + 1) % availableDashboards.value.length
        selectDashboard(availableDashboards.value[nextIndex])
      })

      gestures.on('swipe-right', () => {
        // Navigate to previous dashboard or open menu
        if (currentDashboard.value) {
          const currentIndex = availableDashboards.value.findIndex(
            d => d.uriKey === currentDashboard.value.uriKey
          )
          const prevIndex = currentIndex === 0 
            ? availableDashboards.value.length - 1 
            : currentIndex - 1
          selectDashboard(availableDashboards.value[prevIndex])
        } else {
          toggleMenu()
        }
      })

      gestures.on('pull-to-refresh', () => {
        handleRefresh()
      })
    }

    // Lifecycle
    onMounted(() => {
      setupGestureHandlers()
      
      if (dashboardContent.value) {
        gestures.setup(dashboardContent.value)
      }

      // Handle device orientation changes
      window.addEventListener('orientationchange', () => {
        setTimeout(() => {
          // Recalculate layout after orientation change
          window.dispatchEvent(new Event('resize'))
        }, 100)
      })
    })

    onUnmounted(() => {
      if (dashboardContent.value) {
        gestures.cleanup(dashboardContent.value)
      }
    })

    return {
      // State
      showMenu,
      isRefreshing,
      showPullIndicator,
      showFAB,
      toast,
      
      // Refs
      pullToRefreshContainer,
      dashboardContent,
      
      // Computed
      isMobile,
      currentDashboard,
      availableDashboards,
      favoritesDashboards,
      dashboardCards,
      
      // Methods
      toggleMenu,
      closeMenu,
      selectDashboard,
      handleRefresh,
      handleFABClick,
      handleTouchStart,
      handleTouchMove,
      handleTouchEnd,
      handleCardTap,
      handleCardTouchStart,
      handleCardTouchEnd,
      getIconComponent
    }
  }
}
</script>

<style scoped>
.mobile-dashboard {
  @apply min-h-screen bg-gray-50;
  padding-bottom: env(safe-area-inset-bottom);
}

.mobile-header {
  @apply bg-white shadow-sm border-b border-gray-200 px-4 py-3 flex items-center justify-between;
  padding-top: max(0.75rem, env(safe-area-inset-top));
}

.header-left {
  @apply flex items-center space-x-3;
}

.menu-button {
  @apply p-2 rounded-lg hover:bg-gray-100 transition-colors;
}

.dashboard-title {
  @apply text-lg font-semibold text-gray-900 truncate;
}

.refresh-button {
  @apply p-2 rounded-lg hover:bg-gray-100 transition-colors disabled:opacity-50;
}

.pull-to-refresh-container {
  @apply relative flex-1 overflow-hidden;
}

.pull-to-refresh-indicator {
  @apply absolute top-0 left-0 right-0 bg-blue-50 border-b border-blue-200 px-4 py-2 flex items-center justify-center space-x-2 text-blue-700 text-sm font-medium;
  opacity: 0;
  transform: translateY(-100%);
  transition: all 0.2s ease;
}

.pull-to-refresh-indicator.visible {
  opacity: 1;
  transform: translateY(0);
}

.dashboard-content {
  @apply h-full overflow-y-auto;
  -webkit-overflow-scrolling: touch;
}

.mobile-grid {
  @apply p-4 space-y-4;
}

.mobile-card {
  @apply bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden;
  min-height: 120px;
  touch-action: manipulation;
}

.mobile-menu-overlay {
  @apply fixed inset-0 bg-black bg-opacity-50 z-50;
}

.mobile-menu {
  @apply absolute left-0 top-0 bottom-0 w-80 max-w-full bg-white shadow-xl;
  padding-top: env(safe-area-inset-top);
}

.menu-header {
  @apply px-4 py-3 border-b border-gray-200 flex items-center justify-between;
}

.menu-content {
  @apply p-4 space-y-2 overflow-y-auto;
  max-height: calc(100vh - 80px);
}

.menu-item {
  @apply flex items-center space-x-3 p-3 rounded-lg hover:bg-gray-50 cursor-pointer transition-colors;
}

.menu-item.active {
  @apply bg-blue-50 border border-blue-200;
}

.menu-item-icon {
  @apply flex-shrink-0 text-gray-600;
}

.menu-item.active .menu-item-icon {
  @apply text-blue-600;
}

.menu-item-content {
  @apply flex-1 min-w-0;
}

.menu-item-title {
  @apply font-medium text-gray-900 truncate;
}

.menu-item.active .menu-item-title {
  @apply text-blue-900;
}

.menu-item-description {
  @apply text-sm text-gray-500 truncate;
}

.bottom-navigation {
  @apply bg-white border-t border-gray-200 px-2 py-1 flex items-center justify-around;
  padding-bottom: max(0.25rem, env(safe-area-inset-bottom));
}

.bottom-nav-item {
  @apply flex flex-col items-center justify-center p-2 rounded-lg hover:bg-gray-50 transition-colors min-w-0 flex-1;
}

.bottom-nav-item.active {
  @apply text-blue-600;
}

.nav-label {
  @apply text-xs mt-1 truncate max-w-full;
}

.floating-action-button {
  @apply fixed bottom-20 right-4 w-14 h-14 bg-blue-600 text-white rounded-full shadow-lg hover:bg-blue-700 transition-colors flex items-center justify-center;
  bottom: calc(5rem + env(safe-area-inset-bottom));
}

.toast-container {
  @apply fixed top-4 left-4 right-4 z-50;
  top: calc(1rem + env(safe-area-inset-top));
}

.toast {
  @apply px-4 py-2 rounded-lg shadow-lg text-white text-sm font-medium;
}

.toast.success {
  @apply bg-green-600;
}

.toast.error {
  @apply bg-red-600;
}

.toast.info {
  @apply bg-blue-600;
}

/* Transitions */
.slide-menu-enter-active,
.slide-menu-leave-active {
  transition: all 0.3s ease;
}

.slide-menu-enter-from {
  transform: translateX(-100%);
}

.slide-menu-leave-to {
  transform: translateX(-100%);
}

.toast-enter-active,
.toast-leave-active {
  transition: all 0.3s ease;
}

.toast-enter-from,
.toast-leave-to {
  opacity: 0;
  transform: translateY(-20px);
}

/* Mobile-specific optimizations */
@media (max-width: 768px) {
  .mobile-dashboard.is-mobile .mobile-grid {
    @apply p-2 space-y-2;
  }
  
  .mobile-dashboard.is-mobile .mobile-card {
    min-height: 100px;
  }
  
  .mobile-dashboard.is-mobile .dashboard-title {
    @apply text-base;
  }
}

/* Safe area handling for devices with notches */
@supports (padding: max(0px)) {
  .mobile-header {
    padding-top: max(0.75rem, env(safe-area-inset-top));
  }
  
  .mobile-menu {
    padding-top: env(safe-area-inset-top);
  }
  
  .bottom-navigation {
    padding-bottom: max(0.25rem, env(safe-area-inset-bottom));
  }
  
  .floating-action-button {
    bottom: calc(5rem + env(safe-area-inset-bottom));
  }
  
  .toast-container {
    top: calc(1rem + env(safe-area-inset-top));
  }
}
</style>
```

This mobile dashboard example demonstrates:

1. **Touch-Optimized Interface**: Large touch targets and gesture support
2. **Pull-to-Refresh**: Native-like pull-to-refresh functionality
3. **Mobile Navigation**: Slide-out menu and bottom navigation
4. **Responsive Grid**: Single-column layout optimized for mobile
5. **Safe Area Support**: Proper handling of device notches and home indicators
6. **Touch Gestures**: Swipe navigation between dashboards
7. **Mobile-Specific Components**: FAB, toast notifications, mobile cards
8. **Performance Optimizations**: Touch action manipulation and smooth scrolling

For complete mobile implementation, see:
- [Mobile Gestures Composable](../composables/useMobileGestures.md)
- [Mobile Navigation Composable](../composables/useMobileNavigation.md)
- [Responsive Design Guide](../responsive-design-guide.md)
