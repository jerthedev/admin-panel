/**
 * Performance Optimization Composable Tests
 * 
 * Tests for performance optimization utilities including lazy loading,
 * code splitting, bundle optimization, and performance monitoring.
 */

import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest'
import { usePerformanceOptimization } from '@/composables/usePerformanceOptimization'

// Mock performance APIs
global.performance = {
  now: vi.fn(() => Date.now()),
  mark: vi.fn(),
  measure: vi.fn(),
  memory: {
    usedJSHeapSize: 50000000,
    totalJSHeapSize: 100000000,
    jsHeapSizeLimit: 200000000
  }
}

global.PerformanceObserver = vi.fn().mockImplementation(() => ({
  observe: vi.fn(),
  disconnect: vi.fn()
}))

global.IntersectionObserver = vi.fn().mockImplementation(() => ({
  observe: vi.fn(),
  unobserve: vi.fn(),
  disconnect: vi.fn()
}))

// Mock dynamic imports
vi.mock('vue', () => ({
  defineAsyncComponent: vi.fn((options) => {
    if (typeof options === 'function') {
      return options
    }
    return options.loader
  }),
  ref: vi.fn((value) => ({ value })),
  reactive: vi.fn((obj) => obj),
  computed: vi.fn((fn) => ({ value: fn() })),
  onMounted: vi.fn(),
  onUnmounted: vi.fn(),
  nextTick: vi.fn(() => Promise.resolve())
}))

describe('usePerformanceOptimization', () => {
  let performance

  beforeEach(() => {
    performance = usePerformanceOptimization()
    vi.clearAllMocks()
  })

  afterEach(() => {
    performance.cleanup()
  })

  describe('Configuration', () => {
    it('initializes with default configuration', () => {
      expect(performance.config.enableLazyLoading).toBe(true)
      expect(performance.config.enableCodeSplitting).toBe(true)
      expect(performance.config.enableBundleOptimization).toBe(true)
      expect(performance.config.enablePerformanceMonitoring).toBe(true)
    })

    it('accepts custom configuration options', () => {
      const customPerformance = usePerformanceOptimization({
        enableLazyLoading: false,
        chunkSizeLimit: 1000000,
        imageQuality: 90
      })

      expect(customPerformance.config.enableLazyLoading).toBe(false)
      expect(customPerformance.config.chunkSizeLimit).toBe(1000000)
      expect(customPerformance.config.imageQuality).toBe(90)
    })
  })

  describe('Metrics Tracking', () => {
    it('tracks performance metrics', () => {
      expect(performance.metrics.loadTime).toBe(0)
      expect(performance.metrics.renderTime).toBe(0)
      expect(performance.metrics.bundleSize).toBe(0)
      expect(performance.metrics.lazyLoadedComponents).toBe(0)
    })

    it('calculates performance score', () => {
      // Set some metrics
      performance.metrics.loadTime = 1500 // Good load time
      performance.metrics.renderTime = 50  // Good render time
      performance.metrics.cacheHitRate = 90 // Good cache hit rate
      performance.metrics.bundleSize = 500000 // Good bundle size
      performance.metrics.memoryUsage = 30 // Good memory usage

      const score = performance.performanceScore.value
      expect(score).toBeGreaterThan(80) // Should be a good score
    })

    it('provides optimization recommendations', () => {
      // Set poor metrics
      performance.metrics.loadTime = 5000 // Slow load time
      performance.metrics.bundleSize = 3000000 // Large bundle
      performance.metrics.cacheHitRate = 50 // Poor cache hit rate

      const recommendations = performance.optimizationRecommendations.value
      
      expect(recommendations).toHaveLength(3)
      expect(recommendations[0].type).toBe('critical')
      expect(recommendations[0].action).toBe('enableCodeSplitting')
      expect(recommendations[1].type).toBe('warning')
      expect(recommendations[1].action).toBe('optimizeBundleSize')
    })
  })

  describe('Lazy Loading', () => {
    it('creates lazy components', async () => {
      const mockImportFn = vi.fn(() => Promise.resolve({ default: { name: 'TestComponent' } }))
      
      const lazyComponent = performance.createLazyComponent(mockImportFn)
      
      expect(typeof lazyComponent).toBe('function')
      
      // Simulate component loading
      const component = await lazyComponent()
      expect(mockImportFn).toHaveBeenCalled()
      expect(performance.metrics.lazyLoadedComponents).toBe(1)
    })

    it('handles lazy component loading errors', async () => {
      const mockImportFn = vi.fn(() => Promise.reject(new Error('Load failed')))
      
      const lazyComponent = performance.createLazyComponent(mockImportFn)
      
      try {
        await lazyComponent()
      } catch (error) {
        expect(error.message).toBe('Load failed')
      }
    })

    it('provides fallback components for errors', async () => {
      const mockImportFn = vi.fn(() => Promise.reject(new Error('Load failed')))
      const fallbackComponent = { name: 'FallbackComponent' }
      
      const lazyComponent = performance.createLazyComponent(mockImportFn, fallbackComponent)
      
      const result = await lazyComponent()
      expect(result).toBe(fallbackComponent)
    })
  })

  describe('Code Splitting', () => {
    it('creates split components', () => {
      const componentPath = './components/TestComponent.vue'
      const splitComponent = performance.splitComponent(componentPath, 'test-chunk')
      
      expect(typeof splitComponent).toBe('function')
    })

    it('tracks loaded chunks', async () => {
      const componentPath = './components/TestComponent.vue'
      const splitComponent = performance.splitComponent(componentPath, 'test-chunk')
      
      // Mock successful import
      vi.doMock(componentPath, () => ({ default: { name: 'TestComponent' } }))
      
      await splitComponent()
      
      expect(performance.loadedChunks.value.has('test-chunk')).toBe(true)
      expect(performance.metrics.chunkCount).toBe(1)
    })

    it('preloads chunks', async () => {
      const chunkName = 'test-chunk'
      
      // Mock document.head
      const mockLink = { rel: '', href: '' }
      const mockAppendChild = vi.fn()
      global.document = {
        createElement: vi.fn(() => mockLink),
        head: { appendChild: mockAppendChild }
      }
      
      await performance.preloadChunk(chunkName)
      
      expect(document.createElement).toHaveBeenCalledWith('link')
      expect(mockLink.rel).toBe('modulepreload')
      expect(mockLink.href).toContain(chunkName)
      expect(mockAppendChild).toHaveBeenCalledWith(mockLink)
      expect(performance.preloadedResources.value.has(chunkName)).toBe(true)
    })
  })

  describe('Image Optimization', () => {
    it('optimizes image URLs', () => {
      const originalSrc = 'https://example.com/image.jpg'
      const options = { width: 800, height: 600, quality: 85 }
      
      const optimizedSrc = performance.optimizeImage(originalSrc, options)
      
      expect(optimizedSrc).toContain('w=800')
      expect(optimizedSrc).toContain('h=600')
      expect(optimizedSrc).toContain('q=85')
      expect(performance.metrics.imageOptimizations).toBe(1)
    })

    it('caches optimized images', () => {
      const originalSrc = 'https://example.com/image.jpg'
      const options = { width: 800, height: 600 }
      
      const optimizedSrc1 = performance.optimizeImage(originalSrc, options)
      const optimizedSrc2 = performance.optimizeImage(originalSrc, options)
      
      expect(optimizedSrc1).toBe(optimizedSrc2)
      expect(performance.metrics.imageOptimizations).toBe(1) // Should be cached
    })

    it('creates responsive image srcsets', () => {
      const originalSrc = 'https://example.com/image.jpg'
      const sizes = [320, 640, 1024]
      
      const srcSet = performance.createResponsiveImageSrcSet(originalSrc, sizes)
      
      expect(srcSet).toContain('320w')
      expect(srcSet).toContain('640w')
      expect(srcSet).toContain('1024w')
      expect(srcSet.split(',').length).toBe(3)
    })
  })

  describe('Performance Monitoring', () => {
    it('measures render time', async () => {
      const mockCallback = vi.fn()
      global.performance.now = vi.fn()
        .mockReturnValueOnce(1000) // Start time
        .mockReturnValueOnce(1150) // End time
      
      const renderTime = await performance.measureRenderTime(mockCallback)
      
      expect(mockCallback).toHaveBeenCalled()
      expect(renderTime).toBe(150)
      expect(performance.metrics.renderTime).toBe(150)
    })

    it('measures memory usage', () => {
      performance.measureMemoryUsage()
      
      // Should calculate percentage: (50MB / 200MB) * 100 = 25%
      expect(performance.metrics.memoryUsage).toBe(25)
    })

    it('analyzes bundle size', () => {
      // Mock bundle analyzer data
      global.window = {
        __BUNDLE_ANALYZER__: {
          totalSize: 1500000,
          chunkCount: 5
        }
      }
      
      performance.analyzeBundleSize()
      
      expect(performance.metrics.bundleSize).toBe(1500000)
      expect(performance.metrics.chunkCount).toBe(5)
    })
  })

  describe('Optimization Strategies', () => {
    it('optimizes bundle size when threshold exceeded', () => {
      performance.metrics.bundleSize = 600000 // Exceeds default 500KB limit
      
      const optimization = performance.optimizeBundleSize()
      
      expect(optimization.recommendation).toBe('split-large-components')
      expect(optimization.current).toBe(600000)
      expect(optimization.threshold).toBe(500000)
    })

    it('optimizes caching strategy', () => {
      performance.metrics.cacheHitRate = 65 // Below 70% threshold
      
      const optimization = performance.optimizeCaching()
      
      expect(optimization.currentStrategy).toBe('stale-while-revalidate')
      expect(optimization.hitRate).toBe(65)
      expect(optimization.availableStrategies).toHaveProperty('cache-first')
    })

    it('optimizes memory when usage is high', () => {
      performance.metrics.memoryUsage = 85 // High memory usage
      
      const initialCacheSize = performance.componentCache.value.size
      performance.componentCache.value.set('test', {})
      
      performance.optimizeMemory()
      
      expect(performance.componentCache.value.size).toBe(0) // Should be cleared
    })
  })

  describe('Performance Reports', () => {
    it('generates comprehensive performance report', () => {
      // Set some test metrics
      performance.metrics.loadTime = 2000
      performance.metrics.renderTime = 100
      performance.metrics.bundleSize = 800000
      performance.metrics.cacheHitRate = 75

      const report = performance.getPerformanceReport()
      
      expect(report).toHaveProperty('metrics')
      expect(report).toHaveProperty('score')
      expect(report).toHaveProperty('recommendations')
      expect(report).toHaveProperty('config')
      
      expect(report.metrics.loadTime).toBe(2000)
      expect(report.score).toBeGreaterThan(0)
      expect(Array.isArray(report.recommendations)).toBe(true)
    })

    it('resets metrics', () => {
      // Set some metrics
      performance.metrics.loadTime = 2000
      performance.metrics.renderTime = 100
      performance.loadedChunks.value.add('test-chunk')
      performance.preloadedResources.value.add('test-resource')
      
      performance.resetMetrics()
      
      expect(performance.metrics.loadTime).toBe(0)
      expect(performance.metrics.renderTime).toBe(0)
      expect(performance.loadedChunks.value.size).toBe(0)
      expect(performance.preloadedResources.value.size).toBe(0)
    })
  })

  describe('Lifecycle Management', () => {
    it('sets up performance monitoring', () => {
      const cleanup = performance.setup()
      
      expect(typeof cleanup).toBe('function')
      expect(global.PerformanceObserver).toHaveBeenCalled()
      expect(global.IntersectionObserver).toHaveBeenCalled()
    })

    it('cleans up resources', () => {
      const mockObserver = {
        disconnect: vi.fn()
      }
      
      performance.performanceObserver = { value: mockObserver }
      performance.intersectionObserver = { value: mockObserver }
      performance.mutationObserver = { value: mockObserver }
      
      performance.cleanup()
      
      expect(mockObserver.disconnect).toHaveBeenCalledTimes(3)
    })
  })

  describe('Error Handling', () => {
    it('handles missing performance APIs gracefully', () => {
      // Remove performance APIs
      delete global.performance.memory
      delete global.PerformanceObserver
      delete global.IntersectionObserver
      
      expect(() => {
        const performanceWithoutAPIs = usePerformanceOptimization()
        performanceWithoutAPIs.setup()
      }).not.toThrow()
    })

    it('handles image optimization errors', () => {
      const invalidSrc = 'invalid-url'
      
      expect(() => {
        performance.optimizeImage(invalidSrc)
      }).not.toThrow()
    })

    it('handles preloading errors gracefully', async () => {
      // Mock document.createElement to throw
      global.document = {
        createElement: vi.fn(() => {
          throw new Error('createElement failed')
        }),
        head: { appendChild: vi.fn() }
      }
      
      await expect(performance.preloadChunk('test-chunk')).resolves.toBeUndefined()
    })
  })
})
