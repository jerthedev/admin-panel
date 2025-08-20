/**
 * Mobile Gestures Composable Tests
 * 
 * Tests for touch gesture recognition including swipe, pinch, tap,
 * and pull-to-refresh gestures on mobile devices.
 */

import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest'
import { useMobileGestures } from '@/composables/useMobileGestures'

// Mock touch events
const createTouchEvent = (type, touches, changedTouches = touches) => {
  const event = new Event(type, { bubbles: true, cancelable: true })
  event.touches = touches
  event.changedTouches = changedTouches
  event.targetTouches = touches
  event.preventDefault = vi.fn()
  return event
}

const createTouch = (clientX, clientY, identifier = 0) => ({
  clientX,
  clientY,
  identifier,
  target: document.body
})

describe('useMobileGestures', () => {
  let gestures
  let mockElement
  let gestureCallbacks

  beforeEach(() => {
    mockElement = document.createElement('div')
    document.body.appendChild(mockElement)
    
    gestureCallbacks = {
      tap: vi.fn(),
      'double-tap': vi.fn(),
      'long-press': vi.fn(),
      swipe: vi.fn(),
      'swipe-move': vi.fn(),
      pinch: vi.fn(),
      'pinch-start': vi.fn(),
      'pinch-end': vi.fn(),
      'pull-to-refresh': vi.fn(),
      'pull-to-refresh-end': vi.fn()
    }

    gestures = useMobileGestures({
      swipeThreshold: 50,
      swipeVelocityThreshold: 0.3,
      tapTimeout: 300,
      doubleTapTimeout: 300,
      longPressTimeout: 500,
      pinchThreshold: 0.1,
      pullThreshold: 80
    })

    // Register callbacks
    Object.keys(gestureCallbacks).forEach(type => {
      gestures.on(type, gestureCallbacks[type])
    })

    gestures.setup(mockElement)
  })

  afterEach(() => {
    gestures.cleanup(mockElement)
    document.body.removeChild(mockElement)
    vi.clearAllTimers()
  })

  describe('Tap Gestures', () => {
    it('recognizes single tap', async () => {
      const touch = createTouch(100, 100)
      
      // Touch start
      const startEvent = createTouchEvent('touchstart', [touch])
      mockElement.dispatchEvent(startEvent)
      
      // Touch end (quick)
      const endEvent = createTouchEvent('touchend', [])
      mockElement.dispatchEvent(endEvent)
      
      // Wait for tap timeout
      await vi.advanceTimersByTimeAsync(350)
      
      expect(gestureCallbacks.tap).toHaveBeenCalledWith({
        x: 100,
        y: 100,
        target: expect.any(Object)
      })
    })

    it('recognizes double tap', async () => {
      const touch = createTouch(100, 100)
      
      // First tap
      let startEvent = createTouchEvent('touchstart', [touch])
      mockElement.dispatchEvent(startEvent)
      
      let endEvent = createTouchEvent('touchend', [])
      mockElement.dispatchEvent(endEvent)
      
      // Second tap (within double tap timeout)
      await vi.advanceTimersByTimeAsync(100)
      
      startEvent = createTouchEvent('touchstart', [touch])
      mockElement.dispatchEvent(startEvent)
      
      endEvent = createTouchEvent('touchend', [])
      mockElement.dispatchEvent(endEvent)
      
      expect(gestureCallbacks['double-tap']).toHaveBeenCalledWith({
        x: 100,
        y: 100,
        target: expect.any(Object)
      })
    })

    it('recognizes long press', async () => {
      const touch = createTouch(100, 100)
      
      // Touch start
      const startEvent = createTouchEvent('touchstart', [touch])
      mockElement.dispatchEvent(startEvent)
      
      // Wait for long press timeout
      await vi.advanceTimersByTimeAsync(600)
      
      expect(gestureCallbacks['long-press']).toHaveBeenCalledWith({
        x: 100,
        y: 100,
        target: expect.any(Object)
      })
    })

    it('cancels long press on movement', async () => {
      const touch1 = createTouch(100, 100)
      const touch2 = createTouch(120, 100) // Moved
      
      // Touch start
      const startEvent = createTouchEvent('touchstart', [touch1])
      mockElement.dispatchEvent(startEvent)
      
      // Move before long press timeout
      await vi.advanceTimersByTimeAsync(200)
      const moveEvent = createTouchEvent('touchmove', [touch2])
      mockElement.dispatchEvent(moveEvent)
      
      // Wait past long press timeout
      await vi.advanceTimersByTimeAsync(400)
      
      expect(gestureCallbacks['long-press']).not.toHaveBeenCalled()
    })
  })

  describe('Swipe Gestures', () => {
    it('recognizes horizontal swipe right', async () => {
      const startTouch = createTouch(100, 100)
      const endTouch = createTouch(200, 100) // 100px right
      
      // Touch start
      const startEvent = createTouchEvent('touchstart', [startTouch])
      mockElement.dispatchEvent(startEvent)
      
      // Touch move
      const moveEvent = createTouchEvent('touchmove', [endTouch])
      mockElement.dispatchEvent(moveEvent)
      
      // Touch end
      const endEvent = createTouchEvent('touchend', [])
      mockElement.dispatchEvent(endEvent)
      
      expect(gestureCallbacks['swipe-move']).toHaveBeenCalledWith({
        direction: 'right',
        deltaX: 100,
        deltaY: 0,
        distance: 100
      })
      
      expect(gestureCallbacks.swipe).toHaveBeenCalledWith({
        direction: 'right',
        deltaX: 100,
        deltaY: 0,
        distance: 100,
        velocity: expect.any(Number),
        duration: expect.any(Number)
      })
    })

    it('recognizes horizontal swipe left', async () => {
      const startTouch = createTouch(200, 100)
      const endTouch = createTouch(100, 100) // 100px left
      
      // Touch start
      const startEvent = createTouchEvent('touchstart', [startTouch])
      mockElement.dispatchEvent(startEvent)
      
      // Touch move
      const moveEvent = createTouchEvent('touchmove', [endTouch])
      mockElement.dispatchEvent(moveEvent)
      
      // Touch end
      const endEvent = createTouchEvent('touchend', [])
      mockElement.dispatchEvent(endEvent)
      
      expect(gestureCallbacks['swipe-move']).toHaveBeenCalledWith({
        direction: 'left',
        deltaX: -100,
        deltaY: 0,
        distance: 100
      })
    })

    it('recognizes vertical swipe up', async () => {
      const startTouch = createTouch(100, 200)
      const endTouch = createTouch(100, 100) // 100px up
      
      // Touch start
      const startEvent = createTouchEvent('touchstart', [startTouch])
      mockElement.dispatchEvent(startEvent)
      
      // Touch move
      const moveEvent = createTouchEvent('touchmove', [endTouch])
      mockElement.dispatchEvent(moveEvent)
      
      // Touch end
      const endEvent = createTouchEvent('touchend', [])
      mockElement.dispatchEvent(endEvent)
      
      expect(gestureCallbacks['swipe-move']).toHaveBeenCalledWith({
        direction: 'up',
        deltaX: 0,
        deltaY: -100,
        distance: 100
      })
    })

    it('recognizes vertical swipe down', async () => {
      const startTouch = createTouch(100, 100)
      const endTouch = createTouch(100, 200) // 100px down
      
      // Touch start
      const startEvent = createTouchEvent('touchstart', [startTouch])
      mockElement.dispatchEvent(startEvent)
      
      // Touch move
      const moveEvent = createTouchEvent('touchmove', [endTouch])
      mockElement.dispatchEvent(moveEvent)
      
      // Touch end
      const endEvent = createTouchEvent('touchend', [])
      mockElement.dispatchEvent(endEvent)
      
      expect(gestureCallbacks['swipe-move']).toHaveBeenCalledWith({
        direction: 'down',
        deltaX: 0,
        deltaY: 100,
        distance: 100
      })
    })

    it('does not trigger swipe for small movements', async () => {
      const startTouch = createTouch(100, 100)
      const endTouch = createTouch(120, 100) // Only 20px
      
      // Touch start
      const startEvent = createTouchEvent('touchstart', [startTouch])
      mockElement.dispatchEvent(startEvent)
      
      // Touch move
      const moveEvent = createTouchEvent('touchmove', [endTouch])
      mockElement.dispatchEvent(moveEvent)
      
      // Touch end
      const endEvent = createTouchEvent('touchend', [])
      mockElement.dispatchEvent(endEvent)
      
      expect(gestureCallbacks['swipe-move']).not.toHaveBeenCalled()
      expect(gestureCallbacks.swipe).not.toHaveBeenCalled()
    })
  })

  describe('Pinch Gestures', () => {
    it('recognizes pinch gesture', async () => {
      const touch1Start = createTouch(100, 100, 0)
      const touch2Start = createTouch(200, 100, 1)
      const touch1End = createTouch(80, 100, 0)   // Closer
      const touch2End = createTouch(220, 100, 1)  // Further
      
      // Two finger touch start
      const startEvent = createTouchEvent('touchstart', [touch1Start, touch2Start])
      mockElement.dispatchEvent(startEvent)
      
      expect(gestureCallbacks['pinch-start']).toHaveBeenCalled()
      
      // Pinch movement
      const moveEvent = createTouchEvent('touchmove', [touch1End, touch2End])
      mockElement.dispatchEvent(moveEvent)
      
      expect(gestureCallbacks.pinch).toHaveBeenCalledWith({
        scale: expect.any(Number),
        delta: expect.any(Number),
        center: {
          x: 150, // Center between touches
          y: 100
        }
      })
      
      // Touch end
      const endEvent = createTouchEvent('touchend', [])
      mockElement.dispatchEvent(endEvent)
      
      expect(gestureCallbacks['pinch-end']).toHaveBeenCalledWith({
        scale: expect.any(Number),
        finalScale: expect.any(Number)
      })
    })

    it('calculates pinch scale correctly', async () => {
      const touch1Start = createTouch(100, 100, 0)
      const touch2Start = createTouch(200, 100, 1) // 100px apart
      const touch1End = createTouch(50, 100, 0)
      const touch2End = createTouch(250, 100, 1)   // 200px apart (2x scale)
      
      // Touch start
      const startEvent = createTouchEvent('touchstart', [touch1Start, touch2Start])
      mockElement.dispatchEvent(startEvent)
      
      // Pinch out (zoom in)
      const moveEvent = createTouchEvent('touchmove', [touch1End, touch2End])
      mockElement.dispatchEvent(moveEvent)
      
      expect(gestureCallbacks.pinch).toHaveBeenCalledWith({
        scale: 2, // Double the distance
        delta: 1, // Scale - 1
        center: { x: 150, y: 100 }
      })
    })
  })

  describe('Pull to Refresh', () => {
    beforeEach(() => {
      // Mock scroll position at top
      Object.defineProperty(document.documentElement, 'scrollTop', {
        value: 0,
        writable: true
      })
      Object.defineProperty(document.body, 'scrollTop', {
        value: 0,
        writable: true
      })
    })

    it('recognizes pull to refresh gesture', async () => {
      const startTouch = createTouch(100, 100)
      const endTouch = createTouch(100, 200) // 100px down
      
      // Touch start
      const startEvent = createTouchEvent('touchstart', [startTouch])
      mockElement.dispatchEvent(startEvent)
      
      // Pull down
      const moveEvent = createTouchEvent('touchmove', [endTouch])
      mockElement.dispatchEvent(moveEvent)
      
      expect(gestureCallbacks['pull-to-refresh']).toHaveBeenCalledWith({
        distance: 100,
        progress: expect.any(Number)
      })
      
      // Touch end
      const endEvent = createTouchEvent('touchend', [])
      mockElement.dispatchEvent(endEvent)
      
      expect(gestureCallbacks['pull-to-refresh-end']).toHaveBeenCalledWith({
        shouldRefresh: true, // Above threshold
        distance: 100
      })
    })

    it('does not trigger pull to refresh when not at top', async () => {
      // Mock scroll position not at top
      Object.defineProperty(document.documentElement, 'scrollTop', {
        value: 100,
        writable: true
      })
      
      const startTouch = createTouch(100, 100)
      const endTouch = createTouch(100, 200) // 100px down
      
      // Touch start
      const startEvent = createTouchEvent('touchstart', [startTouch])
      mockElement.dispatchEvent(startEvent)
      
      // Pull down
      const moveEvent = createTouchEvent('touchmove', [endTouch])
      mockElement.dispatchEvent(moveEvent)
      
      expect(gestureCallbacks['pull-to-refresh']).not.toHaveBeenCalled()
    })
  })

  describe('Configuration', () => {
    it('allows updating configuration', () => {
      const newConfig = {
        swipeThreshold: 100,
        tapTimeout: 500
      }
      
      gestures.updateConfig(newConfig)
      
      expect(gestures.config.swipeThreshold).toBe(100)
      expect(gestures.config.tapTimeout).toBe(500)
    })

    it('can disable specific gestures', () => {
      gestures.updateConfig({
        enableSwipe: false,
        enablePinch: false
      })
      
      expect(gestures.config.enableSwipe).toBe(false)
      expect(gestures.config.enablePinch).toBe(false)
    })
  })

  describe('Event Management', () => {
    it('allows subscribing and unsubscribing from events', () => {
      const callback = vi.fn()
      
      // Subscribe
      const unsubscribe = gestures.on('tap', callback)
      
      // Trigger tap
      const touch = createTouch(100, 100)
      const startEvent = createTouchEvent('touchstart', [touch])
      mockElement.dispatchEvent(startEvent)
      
      const endEvent = createTouchEvent('touchend', [])
      mockElement.dispatchEvent(endEvent)
      
      // Unsubscribe
      unsubscribe()
      
      // Trigger another tap
      mockElement.dispatchEvent(startEvent)
      mockElement.dispatchEvent(endEvent)
      
      // Should only be called once (before unsubscribe)
      expect(callback).toHaveBeenCalledTimes(1)
    })

    it('can remove specific callbacks', () => {
      const callback1 = vi.fn()
      const callback2 = vi.fn()
      
      gestures.on('tap', callback1)
      gestures.on('tap', callback2)
      
      // Remove specific callback
      gestures.off('tap', callback1)
      
      // Trigger tap
      const touch = createTouch(100, 100)
      const startEvent = createTouchEvent('touchstart', [touch])
      mockElement.dispatchEvent(startEvent)
      
      const endEvent = createTouchEvent('touchend', [])
      mockElement.dispatchEvent(endEvent)
      
      expect(callback1).not.toHaveBeenCalled()
      expect(callback2).toHaveBeenCalled()
    })
  })

  describe('State Management', () => {
    it('can be enabled and disabled', () => {
      expect(gestures.isActive.value).toBe(true)
      
      gestures.disable()
      expect(gestures.isActive.value).toBe(false)
      
      gestures.enable()
      expect(gestures.isActive.value).toBe(true)
    })

    it('does not process gestures when disabled', async () => {
      gestures.disable()
      
      const touch = createTouch(100, 100)
      const startEvent = createTouchEvent('touchstart', [touch])
      mockElement.dispatchEvent(startEvent)
      
      const endEvent = createTouchEvent('touchend', [])
      mockElement.dispatchEvent(endEvent)
      
      await vi.advanceTimersByTimeAsync(350)
      
      expect(gestureCallbacks.tap).not.toHaveBeenCalled()
    })
  })

  describe('Helper Methods', () => {
    it('provides gesture state helpers', () => {
      expect(typeof gestures.isSwipeLeft).toBe('function')
      expect(typeof gestures.isSwipeRight).toBe('function')
      expect(typeof gestures.isSwipeUp).toBe('function')
      expect(typeof gestures.isSwipeDown).toBe('function')
      expect(typeof gestures.isPinching).toBe('function')
      expect(typeof gestures.isPulling).toBe('function')
    })

    it('correctly identifies swipe directions', async () => {
      const startTouch = createTouch(100, 100)
      const endTouch = createTouch(200, 100) // Right swipe
      
      // Touch start
      const startEvent = createTouchEvent('touchstart', [startTouch])
      mockElement.dispatchEvent(startEvent)
      
      // Touch move
      const moveEvent = createTouchEvent('touchmove', [endTouch])
      mockElement.dispatchEvent(moveEvent)
      
      expect(gestures.isSwipeRight()).toBe(true)
      expect(gestures.isSwipeLeft()).toBe(false)
      expect(gestures.isSwipeUp()).toBe(false)
      expect(gestures.isSwipeDown()).toBe(false)
    })
  })
})
