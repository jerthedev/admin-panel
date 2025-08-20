/**
 * Mobile Gestures Composable
 * 
 * Provides touch gesture recognition and handling for mobile dashboard interactions
 * including swipe, pinch, tap, and pull-to-refresh gestures.
 */

import { ref, reactive, onMounted, onUnmounted } from 'vue'

export function useMobileGestures(options = {}) {
  // Configuration
  const config = reactive({
    swipeThreshold: 50,
    swipeVelocityThreshold: 0.3,
    tapTimeout: 300,
    doubleTapTimeout: 300,
    longPressTimeout: 500,
    pinchThreshold: 0.1,
    pullThreshold: 80,
    enableSwipe: true,
    enablePinch: true,
    enableTap: true,
    enablePullToRefresh: true,
    enableLongPress: true,
    ...options
  })

  // State
  const isActive = ref(false)
  const currentGesture = ref(null)
  const gestureData = reactive({
    startX: 0,
    startY: 0,
    currentX: 0,
    currentY: 0,
    deltaX: 0,
    deltaY: 0,
    distance: 0,
    velocity: 0,
    direction: null,
    startTime: 0,
    duration: 0
  })

  // Touch tracking
  const touches = ref([])
  const initialDistance = ref(0)
  const currentDistance = ref(0)
  const scale = ref(1)

  // Timers
  const tapTimer = ref(null)
  const longPressTimer = ref(null)
  const lastTapTime = ref(0)

  // Event handlers
  const handleTouchStart = (event) => {
    if (!isActive.value) return

    const touch = event.touches[0]
    touches.value = Array.from(event.touches)

    // Reset gesture data
    gestureData.startX = touch.clientX
    gestureData.startY = touch.clientY
    gestureData.currentX = touch.clientX
    gestureData.currentY = touch.clientY
    gestureData.deltaX = 0
    gestureData.deltaY = 0
    gestureData.startTime = Date.now()
    gestureData.direction = null

    // Handle multi-touch for pinch
    if (event.touches.length === 2 && config.enablePinch) {
      const touch1 = event.touches[0]
      const touch2 = event.touches[1]
      initialDistance.value = getDistance(touch1, touch2)
      currentDistance.value = initialDistance.value
      scale.value = 1
      currentGesture.value = 'pinch-start'
    } else if (event.touches.length === 1) {
      // Start long press timer
      if (config.enableLongPress) {
        longPressTimer.value = setTimeout(() => {
          if (currentGesture.value === null) {
            currentGesture.value = 'long-press'
            emitGesture('long-press', {
              x: gestureData.currentX,
              y: gestureData.currentY,
              target: event.target
            })
          }
        }, config.longPressTimeout)
      }
    }

    // Prevent default for certain elements
    if (shouldPreventDefault(event.target)) {
      event.preventDefault()
    }
  }

  const handleTouchMove = (event) => {
    if (!isActive.value || touches.value.length === 0) return

    const touch = event.touches[0]
    gestureData.currentX = touch.clientX
    gestureData.currentY = touch.clientY
    gestureData.deltaX = gestureData.currentX - gestureData.startX
    gestureData.deltaY = gestureData.currentY - gestureData.startY
    gestureData.distance = Math.sqrt(
      gestureData.deltaX * gestureData.deltaX + gestureData.deltaY * gestureData.deltaY
    )

    // Clear long press timer on movement
    if (longPressTimer.value) {
      clearTimeout(longPressTimer.value)
      longPressTimer.value = null
    }

    // Handle pinch gesture
    if (event.touches.length === 2 && config.enablePinch) {
      const touch1 = event.touches[0]
      const touch2 = event.touches[1]
      currentDistance.value = getDistance(touch1, touch2)
      const newScale = currentDistance.value / initialDistance.value

      if (Math.abs(newScale - scale.value) > config.pinchThreshold) {
        scale.value = newScale
        currentGesture.value = 'pinch'
        emitGesture('pinch', {
          scale: scale.value,
          delta: newScale - 1,
          center: getCenter(touch1, touch2)
        })
      }
    }
    // Handle swipe gesture
    else if (event.touches.length === 1 && config.enableSwipe) {
      if (gestureData.distance > config.swipeThreshold) {
        const direction = getSwipeDirection(gestureData.deltaX, gestureData.deltaY)
        
        if (direction !== gestureData.direction) {
          gestureData.direction = direction
          currentGesture.value = 'swipe'
          
          emitGesture('swipe-move', {
            direction,
            deltaX: gestureData.deltaX,
            deltaY: gestureData.deltaY,
            distance: gestureData.distance
          })
        }
      }
    }

    // Handle pull-to-refresh
    if (config.enablePullToRefresh && gestureData.deltaY > 0) {
      const scrollTop = document.documentElement.scrollTop || document.body.scrollTop
      
      if (scrollTop === 0 && gestureData.deltaY > config.pullThreshold) {
        currentGesture.value = 'pull-to-refresh'
        emitGesture('pull-to-refresh', {
          distance: gestureData.deltaY,
          progress: Math.min(gestureData.deltaY / config.pullThreshold, 1)
        })
      }
    }

    // Prevent default for active gestures
    if (currentGesture.value && shouldPreventDefault(event.target)) {
      event.preventDefault()
    }
  }

  const handleTouchEnd = (event) => {
    if (!isActive.value) return

    gestureData.duration = Date.now() - gestureData.startTime
    gestureData.velocity = gestureData.distance / gestureData.duration

    // Clear timers
    if (longPressTimer.value) {
      clearTimeout(longPressTimer.value)
      longPressTimer.value = null
    }

    // Handle completed gestures
    if (currentGesture.value === 'swipe' && config.enableSwipe) {
      if (gestureData.velocity > config.swipeVelocityThreshold) {
        emitGesture('swipe', {
          direction: gestureData.direction,
          deltaX: gestureData.deltaX,
          deltaY: gestureData.deltaY,
          distance: gestureData.distance,
          velocity: gestureData.velocity,
          duration: gestureData.duration
        })
      }
    } else if (currentGesture.value === 'pinch' && config.enablePinch) {
      emitGesture('pinch-end', {
        scale: scale.value,
        finalScale: scale.value
      })
    } else if (currentGesture.value === 'pull-to-refresh' && config.enablePullToRefresh) {
      const shouldRefresh = gestureData.deltaY > config.pullThreshold
      emitGesture('pull-to-refresh-end', {
        shouldRefresh,
        distance: gestureData.deltaY
      })
    } else if (!currentGesture.value && config.enableTap) {
      // Handle tap gestures
      handleTap(event)
    }

    // Reset state
    currentGesture.value = null
    touches.value = []
    scale.value = 1
  }

  const handleTap = (event) => {
    const now = Date.now()
    const timeSinceLastTap = now - lastTapTime.value

    if (timeSinceLastTap < config.doubleTapTimeout) {
      // Double tap
      if (tapTimer.value) {
        clearTimeout(tapTimer.value)
        tapTimer.value = null
      }
      
      emitGesture('double-tap', {
        x: gestureData.currentX,
        y: gestureData.currentY,
        target: event.target
      })
    } else {
      // Single tap (with delay to detect double tap)
      tapTimer.value = setTimeout(() => {
        emitGesture('tap', {
          x: gestureData.currentX,
          y: gestureData.currentY,
          target: event.target
        })
        tapTimer.value = null
      }, config.doubleTapTimeout)
    }

    lastTapTime.value = now
  }

  // Utility functions
  const getDistance = (touch1, touch2) => {
    const dx = touch1.clientX - touch2.clientX
    const dy = touch1.clientY - touch2.clientY
    return Math.sqrt(dx * dx + dy * dy)
  }

  const getCenter = (touch1, touch2) => {
    return {
      x: (touch1.clientX + touch2.clientX) / 2,
      y: (touch1.clientY + touch2.clientY) / 2
    }
  }

  const getSwipeDirection = (deltaX, deltaY) => {
    const absDeltaX = Math.abs(deltaX)
    const absDeltaY = Math.abs(deltaY)

    if (absDeltaX > absDeltaY) {
      return deltaX > 0 ? 'right' : 'left'
    } else {
      return deltaY > 0 ? 'down' : 'up'
    }
  }

  const shouldPreventDefault = (target) => {
    // Don't prevent default on form inputs
    const tagName = target.tagName.toLowerCase()
    const inputTypes = ['input', 'textarea', 'select', 'button']
    
    if (inputTypes.includes(tagName)) {
      return false
    }

    // Don't prevent default on scrollable elements
    const isScrollable = target.scrollHeight > target.clientHeight ||
                        target.scrollWidth > target.clientWidth

    return !isScrollable
  }

  // Event emission
  const gestureCallbacks = reactive({})

  const emitGesture = (type, data) => {
    if (gestureCallbacks[type]) {
      gestureCallbacks[type].forEach(callback => {
        try {
          callback(data)
        } catch (error) {
          console.error(`Error in gesture callback for ${type}:`, error)
        }
      })
    }

    // Also emit as custom event
    const event = new CustomEvent(`gesture-${type}`, {
      detail: data,
      bubbles: true
    })
    document.dispatchEvent(event)
  }

  // Public API
  const on = (type, callback) => {
    if (!gestureCallbacks[type]) {
      gestureCallbacks[type] = []
    }
    gestureCallbacks[type].push(callback)

    // Return unsubscribe function
    return () => {
      const index = gestureCallbacks[type].indexOf(callback)
      if (index > -1) {
        gestureCallbacks[type].splice(index, 1)
      }
    }
  }

  const off = (type, callback) => {
    if (gestureCallbacks[type]) {
      const index = gestureCallbacks[type].indexOf(callback)
      if (index > -1) {
        gestureCallbacks[type].splice(index, 1)
      }
    }
  }

  const enable = () => {
    isActive.value = true
  }

  const disable = () => {
    isActive.value = false
    currentGesture.value = null
    
    // Clear timers
    if (tapTimer.value) {
      clearTimeout(tapTimer.value)
      tapTimer.value = null
    }
    if (longPressTimer.value) {
      clearTimeout(longPressTimer.value)
      longPressTimer.value = null
    }
  }

  const updateConfig = (newConfig) => {
    Object.assign(config, newConfig)
  }

  const setup = (element = document) => {
    enable()
    
    element.addEventListener('touchstart', handleTouchStart, { passive: false })
    element.addEventListener('touchmove', handleTouchMove, { passive: false })
    element.addEventListener('touchend', handleTouchEnd, { passive: false })
    element.addEventListener('touchcancel', handleTouchEnd, { passive: false })
  }

  const cleanup = (element = document) => {
    disable()
    
    element.removeEventListener('touchstart', handleTouchStart)
    element.removeEventListener('touchmove', handleTouchMove)
    element.removeEventListener('touchend', handleTouchEnd)
    element.removeEventListener('touchcancel', handleTouchEnd)
  }

  // Gesture recognition helpers
  const isSwipeLeft = () => gestureData.direction === 'left'
  const isSwipeRight = () => gestureData.direction === 'right'
  const isSwipeUp = () => gestureData.direction === 'up'
  const isSwipeDown = () => gestureData.direction === 'down'
  const isPinching = () => currentGesture.value === 'pinch'
  const isPulling = () => currentGesture.value === 'pull-to-refresh'

  return {
    // State
    isActive,
    currentGesture,
    gestureData,
    config,

    // Methods
    on,
    off,
    enable,
    disable,
    updateConfig,
    setup,
    cleanup,

    // Helpers
    isSwipeLeft,
    isSwipeRight,
    isSwipeUp,
    isSwipeDown,
    isPinching,
    isPulling
  }
}
