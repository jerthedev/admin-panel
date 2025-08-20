/**
 * Mobile Navigation Composable
 * 
 * Provides mobile-specific navigation functionality including
 * responsive breakpoints, orientation handling, and mobile UX patterns.
 */

import { ref, reactive, computed, onMounted, onUnmounted, watch } from 'vue'

export function useMobileNavigation(options = {}) {
  // Configuration
  const config = reactive({
    breakpoints: {
      mobile: 768,
      tablet: 1024,
      desktop: 1280
    },
    enableOrientationLock: false,
    enableFullscreen: false,
    enableStatusBarHiding: false,
    enableSafeArea: true,
    ...options
  })

  // Reactive state
  const screenWidth = ref(window.innerWidth)
  const screenHeight = ref(window.innerHeight)
  const orientation = ref(getOrientation())
  const isPortrait = computed(() => orientation.value === 'portrait')
  const isLandscape = computed(() => orientation.value === 'landscape')
  
  // Device detection
  const isMobile = computed(() => screenWidth.value < config.breakpoints.mobile)
  const isTablet = computed(() => 
    screenWidth.value >= config.breakpoints.mobile && 
    screenWidth.value < config.breakpoints.tablet
  )
  const isDesktop = computed(() => screenWidth.value >= config.breakpoints.desktop)
  const isTouchDevice = ref('ontouchstart' in window)
  
  // Platform detection
  const isIOS = ref(/iPad|iPhone|iPod/.test(navigator.userAgent))
  const isAndroid = ref(/Android/.test(navigator.userAgent))
  const isSafari = ref(/^((?!chrome|android).)*safari/i.test(navigator.userAgent))
  const isChrome = ref(/Chrome/.test(navigator.userAgent))
  
  // Navigation state
  const navigationHistory = reactive([])
  const canGoBack = computed(() => navigationHistory.length > 1)
  const currentRoute = ref(window.location.pathname)
  
  // UI state
  const isFullscreen = ref(false)
  const statusBarHeight = ref(getStatusBarHeight())
  const safeAreaInsets = reactive(getSafeAreaInsets())
  const keyboardHeight = ref(0)
  const isKeyboardVisible = ref(false)

  // Network state
  const isOnline = ref(navigator.onLine)
  const connectionType = ref(getConnectionType())

  // Performance state
  const isLowEndDevice = ref(detectLowEndDevice())
  const prefersReducedMotion = ref(window.matchMedia('(prefers-reduced-motion: reduce)').matches)

  // Methods
  function getOrientation() {
    if (screen.orientation) {
      return screen.orientation.angle === 0 || screen.orientation.angle === 180 
        ? 'portrait' 
        : 'landscape'
    }
    return window.innerHeight > window.innerWidth ? 'portrait' : 'landscape'
  }

  function getStatusBarHeight() {
    if (isIOS.value) {
      // iOS status bar height varies by device
      const safeAreaTop = parseInt(getComputedStyle(document.documentElement)
        .getPropertyValue('--sat') || '0')
      return safeAreaTop || 20
    }
    return 24 // Android default
  }

  function getSafeAreaInsets() {
    const style = getComputedStyle(document.documentElement)
    return {
      top: parseInt(style.getPropertyValue('--sat') || '0'),
      right: parseInt(style.getPropertyValue('--sar') || '0'),
      bottom: parseInt(style.getPropertyValue('--sab') || '0'),
      left: parseInt(style.getPropertyValue('--sal') || '0')
    }
  }

  function getConnectionType() {
    if (navigator.connection) {
      return navigator.connection.effectiveType || 'unknown'
    }
    return 'unknown'
  }

  function detectLowEndDevice() {
    // Simple heuristic based on available information
    const memory = navigator.deviceMemory || 4
    const cores = navigator.hardwareConcurrency || 4
    
    return memory <= 2 || cores <= 2
  }

  // Navigation methods
  const navigateBack = () => {
    if (canGoBack.value) {
      window.history.back()
    }
  }

  const navigateForward = () => {
    window.history.forward()
  }

  const navigateTo = (path, options = {}) => {
    if (options.replace) {
      window.history.replaceState(null, '', path)
    } else {
      window.history.pushState(null, '', path)
      navigationHistory.push(path)
    }
    currentRoute.value = path
  }

  // Fullscreen methods
  const enterFullscreen = async () => {
    if (!config.enableFullscreen) return false

    try {
      if (document.documentElement.requestFullscreen) {
        await document.documentElement.requestFullscreen()
      } else if (document.documentElement.webkitRequestFullscreen) {
        await document.documentElement.webkitRequestFullscreen()
      } else if (document.documentElement.msRequestFullscreen) {
        await document.documentElement.msRequestFullscreen()
      }
      isFullscreen.value = true
      return true
    } catch (error) {
      console.warn('Failed to enter fullscreen:', error)
      return false
    }
  }

  const exitFullscreen = async () => {
    try {
      if (document.exitFullscreen) {
        await document.exitFullscreen()
      } else if (document.webkitExitFullscreen) {
        await document.webkitExitFullscreen()
      } else if (document.msExitFullscreen) {
        await document.msExitFullscreen()
      }
      isFullscreen.value = false
      return true
    } catch (error) {
      console.warn('Failed to exit fullscreen:', error)
      return false
    }
  }

  const toggleFullscreen = async () => {
    return isFullscreen.value ? await exitFullscreen() : await enterFullscreen()
  }

  // Orientation methods
  const lockOrientation = async (orientationLock) => {
    if (!config.enableOrientationLock || !screen.orientation) return false

    try {
      await screen.orientation.lock(orientationLock)
      return true
    } catch (error) {
      console.warn('Failed to lock orientation:', error)
      return false
    }
  }

  const unlockOrientation = () => {
    if (screen.orientation && screen.orientation.unlock) {
      screen.orientation.unlock()
    }
  }

  // Keyboard handling
  const handleKeyboardShow = () => {
    isKeyboardVisible.value = true
    // Estimate keyboard height (iOS Safari doesn't provide this)
    keyboardHeight.value = isIOS.value ? 300 : 250
  }

  const handleKeyboardHide = () => {
    isKeyboardVisible.value = false
    keyboardHeight.value = 0
  }

  // Viewport utilities
  const getViewportHeight = () => {
    // Use visual viewport if available (better for mobile)
    if (window.visualViewport) {
      return window.visualViewport.height
    }
    return window.innerHeight
  }

  const getViewportWidth = () => {
    if (window.visualViewport) {
      return window.visualViewport.width
    }
    return window.innerWidth
  }

  const getAvailableHeight = () => {
    let height = getViewportHeight()
    
    if (config.enableSafeArea) {
      height -= safeAreaInsets.top + safeAreaInsets.bottom
    }
    
    if (isKeyboardVisible.value) {
      height -= keyboardHeight.value
    }
    
    return height
  }

  // Performance utilities
  const requestIdleCallback = (callback, options = {}) => {
    if (window.requestIdleCallback) {
      return window.requestIdleCallback(callback, options)
    }
    // Fallback for browsers without requestIdleCallback
    return setTimeout(callback, 1)
  }

  const cancelIdleCallback = (id) => {
    if (window.cancelIdleCallback) {
      window.cancelIdleCallback(id)
    } else {
      clearTimeout(id)
    }
  }

  // Event handlers
  const handleResize = () => {
    screenWidth.value = window.innerWidth
    screenHeight.value = window.innerHeight
    orientation.value = getOrientation()
    safeAreaInsets.top = getStatusBarHeight()
  }

  const handleOrientationChange = () => {
    // Delay to ensure dimensions are updated
    setTimeout(() => {
      orientation.value = getOrientation()
      handleResize()
    }, 100)
  }

  const handleOnlineStatusChange = () => {
    isOnline.value = navigator.onLine
  }

  const handleConnectionChange = () => {
    connectionType.value = getConnectionType()
  }

  const handleFullscreenChange = () => {
    isFullscreen.value = !!(
      document.fullscreenElement ||
      document.webkitFullscreenElement ||
      document.msFullscreenElement
    )
  }

  const handleVisualViewportChange = () => {
    if (window.visualViewport) {
      const newHeight = window.visualViewport.height
      const heightDiff = window.innerHeight - newHeight
      
      // Detect keyboard show/hide based on height change
      if (heightDiff > 150) {
        keyboardHeight.value = heightDiff
        if (!isKeyboardVisible.value) {
          handleKeyboardShow()
        }
      } else if (isKeyboardVisible.value && heightDiff < 50) {
        handleKeyboardHide()
      }
    }
  }

  // Setup and cleanup
  const setup = () => {
    // Add event listeners
    window.addEventListener('resize', handleResize)
    window.addEventListener('orientationchange', handleOrientationChange)
    window.addEventListener('online', handleOnlineStatusChange)
    window.addEventListener('offline', handleOnlineStatusChange)
    document.addEventListener('fullscreenchange', handleFullscreenChange)
    document.addEventListener('webkitfullscreenchange', handleFullscreenChange)
    document.addEventListener('msfullscreenchange', handleFullscreenChange)

    // Visual viewport support
    if (window.visualViewport) {
      window.visualViewport.addEventListener('resize', handleVisualViewportChange)
    }

    // Connection API support
    if (navigator.connection) {
      navigator.connection.addEventListener('change', handleConnectionChange)
    }

    // Set up CSS custom properties for safe areas
    if (config.enableSafeArea) {
      updateSafeAreaProperties()
    }

    // Initialize navigation history
    navigationHistory.push(currentRoute.value)
  }

  const cleanup = () => {
    window.removeEventListener('resize', handleResize)
    window.removeEventListener('orientationchange', handleOrientationChange)
    window.removeEventListener('online', handleOnlineStatusChange)
    window.removeEventListener('offline', handleOnlineStatusChange)
    document.removeEventListener('fullscreenchange', handleFullscreenChange)
    document.removeEventListener('webkitfullscreenchange', handleFullscreenChange)
    document.removeEventListener('msfullscreenchange', handleFullscreenChange)

    if (window.visualViewport) {
      window.visualViewport.removeEventListener('resize', handleVisualViewportChange)
    }

    if (navigator.connection) {
      navigator.connection.removeEventListener('change', handleConnectionChange)
    }
  }

  const updateSafeAreaProperties = () => {
    const root = document.documentElement
    root.style.setProperty('--safe-area-inset-top', `${safeAreaInsets.top}px`)
    root.style.setProperty('--safe-area-inset-right', `${safeAreaInsets.right}px`)
    root.style.setProperty('--safe-area-inset-bottom', `${safeAreaInsets.bottom}px`)
    root.style.setProperty('--safe-area-inset-left', `${safeAreaInsets.left}px`)
  }

  // Watch for safe area changes
  watch(safeAreaInsets, updateSafeAreaProperties, { deep: true })

  return {
    // State
    screenWidth,
    screenHeight,
    orientation,
    isPortrait,
    isLandscape,
    isMobile,
    isTablet,
    isDesktop,
    isTouchDevice,
    isIOS,
    isAndroid,
    isSafari,
    isChrome,
    navigationHistory,
    canGoBack,
    currentRoute,
    isFullscreen,
    statusBarHeight,
    safeAreaInsets,
    keyboardHeight,
    isKeyboardVisible,
    isOnline,
    connectionType,
    isLowEndDevice,
    prefersReducedMotion,

    // Methods
    navigateBack,
    navigateForward,
    navigateTo,
    enterFullscreen,
    exitFullscreen,
    toggleFullscreen,
    lockOrientation,
    unlockOrientation,
    getViewportHeight,
    getViewportWidth,
    getAvailableHeight,
    requestIdleCallback,
    cancelIdleCallback,
    setup,
    cleanup,

    // Configuration
    config
  }
}
