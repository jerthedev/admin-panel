/**
 * Dashboard Transitions Composable
 * 
 * Handles smooth transitions between dashboards with loading states,
 * error handling, and data persistence.
 */

import { ref, computed, nextTick } from 'vue'
import { router } from '@inertiajs/vue3'
import { useDashboardNavigationStore } from '@/stores/dashboardNavigation'

export function useDashboardTransitions() {
  // State
  const isTransitioning = ref(false)
  const transitionError = ref(null)
  const transitionProgress = ref(0)
  const currentTransition = ref(null)
  const transitionQueue = ref([])
  const preservedData = ref({})

  // Store
  const navigationStore = useDashboardNavigationStore()

  // Computed
  const canTransition = computed(() => !isTransitioning.value)
  const hasError = computed(() => !!transitionError.value)
  const isQueued = computed(() => transitionQueue.value.length > 0)

  // Transition types
  const TRANSITION_TYPES = {
    NAVIGATE: 'navigate',
    SWITCH: 'switch',
    REFRESH: 'refresh',
    BACK: 'back',
    FORWARD: 'forward'
  }

  // Transition options
  const DEFAULT_OPTIONS = {
    preserveScroll: true,
    preserveState: true,
    preserveData: true,
    showProgress: true,
    timeout: 10000,
    retryAttempts: 3,
    animation: 'fade',
    onStart: null,
    onProgress: null,
    onSuccess: null,
    onError: null,
    onComplete: null
  }

  /**
   * Navigate to a dashboard with smooth transition
   */
  const navigateToDashboard = async (dashboard, options = {}) => {
    const config = { ...DEFAULT_OPTIONS, ...options }
    
    if (!dashboard) {
      throw new Error('Dashboard is required for navigation')
    }

    // Check if already on the same dashboard
    if (navigationStore.currentDashboard?.uriKey === dashboard.uriKey) {
      return { success: true, skipped: true }
    }

    // Queue transition if one is already in progress
    if (isTransitioning.value) {
      return queueTransition(dashboard, config)
    }

    return executeTransition(dashboard, TRANSITION_TYPES.NAVIGATE, config)
  }

  /**
   * Switch to a dashboard (quick switch)
   */
  const switchToDashboard = async (dashboard, options = {}) => {
    const config = { 
      ...DEFAULT_OPTIONS, 
      animation: 'slide',
      preserveScroll: false,
      ...options 
    }

    return navigateToDashboard(dashboard, { 
      ...config, 
      type: TRANSITION_TYPES.SWITCH 
    })
  }

  /**
   * Refresh current dashboard
   */
  const refreshDashboard = async (options = {}) => {
    const config = { 
      ...DEFAULT_OPTIONS, 
      preserveState: false,
      animation: 'pulse',
      ...options 
    }

    const currentDashboard = navigationStore.currentDashboard
    if (!currentDashboard) {
      throw new Error('No current dashboard to refresh')
    }

    return executeTransition(currentDashboard, TRANSITION_TYPES.REFRESH, config)
  }

  /**
   * Navigate back in history
   */
  const navigateBack = async (options = {}) => {
    if (!navigationStore.canGoBack) {
      return { success: false, error: 'Cannot navigate back' }
    }

    const config = { 
      ...DEFAULT_OPTIONS, 
      animation: 'slideRight',
      ...options 
    }

    const previousDashboard = navigationStore.previousDashboard
    return executeTransition(previousDashboard, TRANSITION_TYPES.BACK, config)
  }

  /**
   * Navigate forward in history
   */
  const navigateForward = async (options = {}) => {
    if (!navigationStore.canGoForward) {
      return { success: false, error: 'Cannot navigate forward' }
    }

    const config = { 
      ...DEFAULT_OPTIONS, 
      animation: 'slideLeft',
      ...options 
    }

    const nextDashboard = navigationStore.nextDashboard
    return executeTransition(nextDashboard, TRANSITION_TYPES.FORWARD, config)
  }

  /**
   * Execute a dashboard transition
   */
  const executeTransition = async (dashboard, type, options) => {
    const transitionId = generateTransitionId()
    
    try {
      // Start transition
      await startTransition(transitionId, dashboard, type, options)

      // Preserve data if requested
      if (options.preserveData) {
        preserveCurrentData()
      }

      // Execute the navigation
      const result = await performNavigation(dashboard, type, options)

      // Complete transition
      await completeTransition(transitionId, result, options)

      return { success: true, result }

    } catch (error) {
      await handleTransitionError(transitionId, error, options)
      return { success: false, error: error.message }
    }
  }

  /**
   * Start a transition
   */
  const startTransition = async (transitionId, dashboard, type, options) => {
    isTransitioning.value = true
    transitionError.value = null
    transitionProgress.value = 0
    
    currentTransition.value = {
      id: transitionId,
      dashboard,
      type,
      options,
      startTime: Date.now()
    }

    // Call start callback
    if (options.onStart) {
      await options.onStart(dashboard, type)
    }

    // Emit transition start event
    window.dispatchEvent(new CustomEvent('dashboard-transition-start', {
      detail: { dashboard, type, transitionId }
    }))

    // Start progress animation
    if (options.showProgress) {
      animateProgress()
    }
  }

  /**
   * Perform the actual navigation
   */
  const performNavigation = async (dashboard, type, options) => {
    return new Promise((resolve, reject) => {
      const url = getDashboardUrl(dashboard)
      const visitOptions = {
        preserveScroll: options.preserveScroll,
        preserveState: options.preserveState,
        replace: type === TRANSITION_TYPES.REFRESH,
        onStart: () => {
          updateProgress(25)
        },
        onProgress: (progress) => {
          updateProgress(25 + (progress.percentage * 0.5))
          if (options.onProgress) {
            options.onProgress(progress)
          }
        },
        onSuccess: (page) => {
          updateProgress(90)
          
          // Update navigation store
          navigationStore.setCurrentDashboard(dashboard)
          
          resolve(page)
        },
        onError: (errors) => {
          reject(new Error(errors.message || 'Navigation failed'))
        },
        onFinish: () => {
          updateProgress(100)
        }
      }

      // Set timeout
      const timeout = setTimeout(() => {
        reject(new Error('Navigation timeout'))
      }, options.timeout)

      // Clear timeout on completion
      const originalOnFinish = visitOptions.onFinish
      visitOptions.onFinish = () => {
        clearTimeout(timeout)
        if (originalOnFinish) originalOnFinish()
      }

      // Execute navigation
      router.visit(url, visitOptions)
    })
  }

  /**
   * Complete a transition
   */
  const completeTransition = async (transitionId, result, options) => {
    // Restore preserved data if needed
    if (options.preserveData && preservedData.value) {
      await restorePreservedData()
    }

    // Call success callback
    if (options.onSuccess) {
      await options.onSuccess(result)
    }

    // Emit transition complete event
    window.dispatchEvent(new CustomEvent('dashboard-transition-complete', {
      detail: { 
        transitionId, 
        result,
        duration: Date.now() - currentTransition.value.startTime
      }
    }))

    // Clean up
    finishTransition(options)

    // Process queue
    await processTransitionQueue()
  }

  /**
   * Handle transition error
   */
  const handleTransitionError = async (transitionId, error, options) => {
    transitionError.value = error

    // Call error callback
    if (options.onError) {
      await options.onError(error)
    }

    // Emit error event
    window.dispatchEvent(new CustomEvent('dashboard-transition-error', {
      detail: { transitionId, error }
    }))

    // Retry if configured
    if (options.retryAttempts > 0) {
      const retryOptions = { 
        ...options, 
        retryAttempts: options.retryAttempts - 1 
      }
      
      // Wait before retry
      await new Promise(resolve => setTimeout(resolve, 1000))
      
      return executeTransition(
        currentTransition.value.dashboard, 
        currentTransition.value.type, 
        retryOptions
      )
    }

    // Clean up
    finishTransition(options)
  }

  /**
   * Queue a transition
   */
  const queueTransition = async (dashboard, options) => {
    return new Promise((resolve) => {
      transitionQueue.value.push({
        dashboard,
        options,
        resolve
      })
    })
  }

  /**
   * Process transition queue
   */
  const processTransitionQueue = async () => {
    if (transitionQueue.value.length === 0 || isTransitioning.value) {
      return
    }

    const next = transitionQueue.value.shift()
    const result = await navigateToDashboard(next.dashboard, next.options)
    next.resolve(result)
  }

  /**
   * Preserve current dashboard data
   */
  const preserveCurrentData = () => {
    preservedData.value = {
      scrollPosition: {
        x: window.scrollX,
        y: window.scrollY
      },
      formData: getFormData(),
      timestamp: Date.now()
    }
  }

  /**
   * Restore preserved data
   */
  const restorePreservedData = async () => {
    if (!preservedData.value) return

    await nextTick()

    // Restore scroll position
    if (preservedData.value.scrollPosition) {
      window.scrollTo(
        preservedData.value.scrollPosition.x,
        preservedData.value.scrollPosition.y
      )
    }

    // Restore form data
    if (preservedData.value.formData) {
      restoreFormData(preservedData.value.formData)
    }

    // Clear preserved data
    preservedData.value = {}
  }

  /**
   * Get form data from current page
   */
  const getFormData = () => {
    const forms = document.querySelectorAll('form')
    const data = {}

    forms.forEach((form, index) => {
      const formData = new FormData(form)
      data[`form_${index}`] = Object.fromEntries(formData.entries())
    })

    return data
  }

  /**
   * Restore form data to page
   */
  const restoreFormData = (data) => {
    const forms = document.querySelectorAll('form')
    
    forms.forEach((form, index) => {
      const formKey = `form_${index}`
      if (data[formKey]) {
        Object.entries(data[formKey]).forEach(([name, value]) => {
          const input = form.querySelector(`[name="${name}"]`)
          if (input) {
            input.value = value
          }
        })
      }
    })
  }

  /**
   * Update transition progress
   */
  const updateProgress = (progress) => {
    transitionProgress.value = Math.min(100, Math.max(0, progress))
  }

  /**
   * Animate progress bar
   */
  const animateProgress = () => {
    const duration = 200
    const start = Date.now()
    const startProgress = transitionProgress.value

    const animate = () => {
      const elapsed = Date.now() - start
      const progress = Math.min(elapsed / duration, 1)
      
      transitionProgress.value = startProgress + (progress * 10)
      
      if (progress < 1) {
        requestAnimationFrame(animate)
      }
    }

    requestAnimationFrame(animate)
  }

  /**
   * Finish transition cleanup
   */
  const finishTransition = (options) => {
    isTransitioning.value = false
    transitionProgress.value = 0
    currentTransition.value = null

    // Call complete callback
    if (options.onComplete) {
      options.onComplete()
    }
  }

  /**
   * Get dashboard URL
   */
  const getDashboardUrl = (dashboard) => {
    if (!dashboard) return '#'
    
    if (dashboard.uriKey === 'main') {
      return route('admin-panel.dashboard')
    }
    
    return route('admin-panel.dashboards.show', { uriKey: dashboard.uriKey })
  }

  /**
   * Generate unique transition ID
   */
  const generateTransitionId = () => {
    return `transition_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`
  }

  /**
   * Clear transition error
   */
  const clearError = () => {
    transitionError.value = null
  }

  /**
   * Cancel current transition
   */
  const cancelTransition = () => {
    if (currentTransition.value) {
      finishTransition(currentTransition.value.options)
    }
    
    // Clear queue
    transitionQueue.value = []
  }

  return {
    // State
    isTransitioning,
    transitionError,
    transitionProgress,
    currentTransition,
    preservedData,

    // Computed
    canTransition,
    hasError,
    isQueued,

    // Methods
    navigateToDashboard,
    switchToDashboard,
    refreshDashboard,
    navigateBack,
    navigateForward,
    clearError,
    cancelTransition,

    // Constants
    TRANSITION_TYPES
  }
}
