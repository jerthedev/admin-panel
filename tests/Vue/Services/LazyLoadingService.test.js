/**
 * Lazy Loading Service Tests
 * 
 * Tests for advanced lazy loading functionality including component loading,
 * image optimization, preloading strategies, and performance analytics.
 */

import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest'
import { LazyLoadingService } from '@/services/LazyLoadingService'

// Mock DOM APIs
global.IntersectionObserver = vi.fn().mockImplementation((callback) => ({
  observe: vi.fn(),
  unobserve: vi.fn(),
  disconnect: vi.fn(),
  callback
}))

global.MutationObserver = vi.fn().mockImplementation((callback) => ({
  observe: vi.fn(),
  disconnect: vi.fn(),
  callback
}))

global.Image = vi.fn().mockImplementation(() => ({
  onload: null,
  onerror: null,
  src: ''
}))

global.fetch = vi.fn()

// Mock document
global.document = {
  createElement: vi.fn(() => ({
    rel: '',
    href: '',
    src: '',
    async: false,
    onload: null,
    onerror: null
  })),
  head: {
    appendChild: vi.fn()
  },
  body: {
    querySelectorAll: vi.fn(() => [])
  },
  querySelectorAll: vi.fn(() => []),
  readyState: 'complete'
}

global.window = {
  location: {
    origin: 'https://example.com'
  },
  devicePixelRatio: 2
}

describe('LazyLoadingService', () => {
  let service

  beforeEach(() => {
    service = new LazyLoadingService({
      maxConcurrentLoads: 2,
      retryAttempts: 2,
      retryDelay: 100
    })
    vi.clearAllMocks()
  })

  afterEach(() => {
    service.destroy()
  })

  describe('Initialization', () => {
    it('initializes with default configuration', () => {
      expect(service.config.intersectionThreshold).toBe(0.1)
      expect(service.config.maxConcurrentLoads).toBe(2)
      expect(service.config.enablePreloading).toBe(true)
    })

    it('sets up observers', () => {
      expect(global.IntersectionObserver).toHaveBeenCalled()
      expect(global.MutationObserver).toHaveBeenCalled()
    })

    it('scans for lazy elements on initialization', () => {
      const mockElement = {
        dataset: { lazy: 'true', component: 'TestComponent' },
        querySelectorAll: vi.fn(() => [])
      }
      
      global.document.querySelectorAll = vi.fn(() => [mockElement])
      
      service.scanForLazyElements()
      
      expect(service.intersectionObserver.observe).toHaveBeenCalledWith(mockElement)
    })
  })

  describe('Element Observation', () => {
    it('observes elements for lazy loading', () => {
      const element = {
        dataset: { lazy: 'true', component: 'TestComponent' }
      }
      
      service.observe(element)
      
      expect(service.intersectionObserver.observe).toHaveBeenCalledWith(element)
    })

    it('handles intersection observer entries', () => {
      const element = {
        dataset: { 
          lazyType: 'component',
          component: 'TestComponent',
          priority: '1'
        }
      }
      
      const entries = [{
        isIntersecting: true,
        target: element
      }]
      
      const queueLoadSpy = vi.spyOn(service, 'queueLoad')
      service.handleIntersection(entries)
      
      expect(queueLoadSpy).toHaveBeenCalledWith({
        element,
        type: 'component',
        priority: 1,
        src: 'TestComponent',
        retries: 0
      })
      
      expect(service.intersectionObserver.unobserve).toHaveBeenCalledWith(element)
    })

    it('ignores non-intersecting entries', () => {
      const entries = [{
        isIntersecting: false,
        target: { dataset: {} }
      }]
      
      const queueLoadSpy = vi.spyOn(service, 'queueLoad')
      service.handleIntersection(entries)
      
      expect(queueLoadSpy).not.toHaveBeenCalled()
    })
  })

  describe('Loading Queue Management', () => {
    it('queues load requests with priority', () => {
      const lowPriorityRequest = {
        element: {},
        type: 'component',
        priority: 1,
        src: 'LowPriority',
        retries: 0
      }
      
      const highPriorityRequest = {
        element: {},
        type: 'component',
        priority: 5,
        src: 'HighPriority',
        retries: 0
      }
      
      service.queueLoad(lowPriorityRequest)
      service.queueLoad(highPriorityRequest)
      
      expect(service.loadingQueue[0]).toBe(highPriorityRequest)
      expect(service.loadingQueue[1]).toBe(lowPriorityRequest)
    })

    it('respects concurrent loading limit', async () => {
      const requests = [
        { element: {}, type: 'component', priority: 1, src: 'Component1', retries: 0 },
        { element: {}, type: 'component', priority: 1, src: 'Component2', retries: 0 },
        { element: {}, type: 'component', priority: 1, src: 'Component3', retries: 0 }
      ]
      
      // Mock loadResource to simulate slow loading
      vi.spyOn(service, 'loadResource').mockImplementation(() => 
        new Promise(resolve => setTimeout(resolve, 100))
      )
      
      requests.forEach(request => service.queueLoad(request))
      
      // Should only process maxConcurrentLoads (2) initially
      expect(service.currentlyLoading).toBeLessThanOrEqual(2)
    })

    it('prevents duplicate loading of same resource', () => {
      const request = {
        element: {},
        type: 'component',
        priority: 1,
        src: 'TestComponent',
        retries: 0
      }
      
      service.queueLoad(request)
      service.queueLoad(request) // Duplicate
      
      expect(service.loadingQueue.length).toBe(1)
    })
  })

  describe('Component Loading', () => {
    it('loads Vue components', async () => {
      const loadRequest = {
        type: 'component',
        src: './TestComponent.vue'
      }
      
      const mockModule = { default: { name: 'TestComponent' } }
      vi.doMock('./TestComponent.vue', () => mockModule)
      
      const result = await service.loadComponent(loadRequest)
      
      expect(result).toBe(mockModule.default)
    })

    it('handles component loading errors', async () => {
      const loadRequest = {
        type: 'component',
        src: './NonExistentComponent.vue'
      }
      
      await expect(service.loadComponent(loadRequest)).rejects.toThrow()
    })
  })

  describe('Image Loading', () => {
    it('loads images successfully', async () => {
      const loadRequest = {
        type: 'image',
        src: 'https://example.com/image.jpg',
        element: {
          getBoundingClientRect: () => ({ width: 400, height: 300 })
        }
      }
      
      const mockImg = {
        onload: null,
        onerror: null,
        src: ''
      }
      
      global.Image = vi.fn(() => mockImg)
      
      const loadPromise = service.loadImage(loadRequest)
      
      // Simulate successful load
      mockImg.onload()
      
      const result = await loadPromise
      expect(result).toBe(mockImg)
      expect(mockImg.src).toContain('w=800') // 400 * 2 (devicePixelRatio)
      expect(mockImg.src).toContain('h=600') // 300 * 2
    })

    it('handles image loading errors', async () => {
      const loadRequest = {
        type: 'image',
        src: 'https://example.com/invalid.jpg'
      }
      
      const mockImg = {
        onload: null,
        onerror: null,
        src: ''
      }
      
      global.Image = vi.fn(() => mockImg)
      
      const loadPromise = service.loadImage(loadRequest)
      
      // Simulate error
      mockImg.onerror()
      
      await expect(loadPromise).rejects.toThrow('Failed to load image')
    })

    it('optimizes image URLs with responsive parameters', () => {
      const src = 'https://example.com/image.jpg'
      const element = {
        getBoundingClientRect: () => ({ width: 200, height: 150 })
      }
      
      const optimizedUrl = service.optimizeImageUrl(src, element)
      
      expect(optimizedUrl).toContain('w=400') // 200 * 2 (devicePixelRatio)
      expect(optimizedUrl).toContain('h=300') // 150 * 2
      expect(optimizedUrl).toContain('q=85')
      expect(optimizedUrl).toContain('f=webp')
    })
  })

  describe('Script and Style Loading', () => {
    it('loads scripts', async () => {
      const loadRequest = {
        type: 'script',
        src: 'https://example.com/script.js'
      }
      
      const mockScript = {
        onload: null,
        onerror: null,
        src: '',
        async: false
      }
      
      global.document.createElement = vi.fn(() => mockScript)
      
      const loadPromise = service.loadScript(loadRequest)
      
      // Simulate successful load
      mockScript.onload()
      
      const result = await loadPromise
      expect(result).toBe(mockScript)
      expect(mockScript.src).toBe(loadRequest.src)
      expect(mockScript.async).toBe(true)
    })

    it('loads stylesheets', async () => {
      const loadRequest = {
        type: 'style',
        src: 'https://example.com/style.css'
      }
      
      const mockLink = {
        onload: null,
        onerror: null,
        rel: '',
        href: ''
      }
      
      global.document.createElement = vi.fn(() => mockLink)
      
      const loadPromise = service.loadStyle(loadRequest)
      
      // Simulate successful load
      mockLink.onload()
      
      const result = await loadPromise
      expect(result).toBe(mockLink)
      expect(mockLink.rel).toBe('stylesheet')
      expect(mockLink.href).toBe(loadRequest.src)
    })
  })

  describe('Data Loading', () => {
    it('loads data via fetch', async () => {
      const loadRequest = {
        type: 'data',
        src: 'https://api.example.com/data'
      }
      
      const mockData = { id: 1, name: 'Test' }
      global.fetch = vi.fn(() => Promise.resolve({
        ok: true,
        json: () => Promise.resolve(mockData)
      }))
      
      const result = await service.loadData(loadRequest)
      
      expect(global.fetch).toHaveBeenCalledWith(loadRequest.src)
      expect(result).toEqual(mockData)
    })

    it('handles fetch errors', async () => {
      const loadRequest = {
        type: 'data',
        src: 'https://api.example.com/data'
      }
      
      global.fetch = vi.fn(() => Promise.resolve({
        ok: false,
        status: 404,
        statusText: 'Not Found'
      }))
      
      await expect(service.loadData(loadRequest)).rejects.toThrow('Failed to load data: 404 Not Found')
    })
  })

  describe('Error Handling and Retries', () => {
    it('retries failed loads', async () => {
      const loadRequest = {
        element: {
          classList: { add: vi.fn() },
          dispatchEvent: vi.fn()
        },
        type: 'component',
        src: 'TestComponent',
        retries: 0
      }
      
      const error = new Error('Load failed')
      
      vi.useFakeTimers()
      
      const queueLoadSpy = vi.spyOn(service, 'queueLoad')
      service.handleLoadError(loadRequest, error)
      
      // Fast-forward time to trigger retry
      vi.advanceTimersByTime(service.config.retryDelay)
      
      expect(queueLoadSpy).toHaveBeenCalledWith({
        ...loadRequest,
        retries: 1
      })
      
      vi.useRealTimers()
    })

    it('marks resources as failed after max retries', () => {
      const loadRequest = {
        element: {
          classList: { add: vi.fn() },
          dispatchEvent: vi.fn()
        },
        type: 'component',
        src: 'TestComponent',
        retries: service.config.retryAttempts
      }
      
      const error = new Error('Load failed')
      service.handleLoadError(loadRequest, error)
      
      const key = service.getResourceKey(loadRequest)
      expect(service.failedResources.has(key)).toBe(true)
      expect(loadRequest.element.classList.add).toHaveBeenCalledWith('lazy-error')
    })
  })

  describe('Preloading', () => {
    it('preloads resources', () => {
      const resources = [
        'https://example.com/script.js',
        { src: 'https://example.com/module.js', type: 'module' }
      ]
      
      const mockLink = {
        rel: '',
        href: '',
        as: ''
      }
      
      global.document.createElement = vi.fn(() => mockLink)
      
      service.preload(resources)
      
      expect(global.document.createElement).toHaveBeenCalledTimes(2)
      expect(service.preloadedResources.size).toBe(2)
    })

    it('avoids duplicate preloading', () => {
      const resource = 'https://example.com/script.js'
      
      service.preload([resource])
      service.preload([resource]) // Duplicate
      
      expect(service.preloadedResources.size).toBe(1)
    })
  })

  describe('Analytics', () => {
    it('tracks loading analytics', () => {
      const analytics = service.getAnalytics()
      
      expect(analytics).toHaveProperty('totalRequests')
      expect(analytics).toHaveProperty('successfulLoads')
      expect(analytics).toHaveProperty('failedLoads')
      expect(analytics).toHaveProperty('averageLoadTime')
      expect(analytics).toHaveProperty('successRate')
      expect(analytics).toHaveProperty('loadedResources')
      expect(analytics).toHaveProperty('currentlyLoading')
      expect(analytics).toHaveProperty('queueLength')
    })

    it('calculates success rate correctly', () => {
      service.analytics.totalRequests = 10
      service.analytics.successfulLoads = 8
      
      const analytics = service.getAnalytics()
      expect(analytics.successRate).toBe(80)
    })

    it('handles zero requests for success rate', () => {
      service.analytics.totalRequests = 0
      service.analytics.successfulLoads = 0
      
      const analytics = service.getAnalytics()
      expect(analytics.successRate).toBe(0)
    })
  })

  describe('Cleanup and Reset', () => {
    it('resets all state', () => {
      // Add some state
      service.loadingQueue.push({ test: 'data' })
      service.loadedResources.add('test-resource')
      service.analytics.totalRequests = 5
      
      service.reset()
      
      expect(service.loadingQueue.length).toBe(0)
      expect(service.loadedResources.size).toBe(0)
      expect(service.analytics.totalRequests).toBe(0)
    })

    it('destroys observers and cleans up', () => {
      const mockObserver = {
        disconnect: vi.fn()
      }
      
      service.intersectionObserver = mockObserver
      service.mutationObserver = mockObserver
      
      service.destroy()
      
      expect(mockObserver.disconnect).toHaveBeenCalledTimes(2)
    })
  })

  describe('Resource Key Generation', () => {
    it('generates unique keys for resources', () => {
      const request1 = { type: 'component', src: 'Component1' }
      const request2 = { type: 'component', src: 'Component2' }
      const request3 = { type: 'image', src: 'Component1' }
      
      const key1 = service.getResourceKey(request1)
      const key2 = service.getResourceKey(request2)
      const key3 = service.getResourceKey(request3)
      
      expect(key1).toBe('component:Component1')
      expect(key2).toBe('component:Component2')
      expect(key3).toBe('image:Component1')
      
      expect(key1).not.toBe(key2)
      expect(key1).not.toBe(key3)
    })
  })
})
