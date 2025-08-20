/**
 * Performance Optimization Composable
 * 
 * Provides comprehensive performance optimization utilities including
 * lazy loading, code splitting, bundle optimization, and performance monitoring.
 */

import { ref, reactive, computed, onMounted, onUnmounted, nextTick } from 'vue'

export function usePerformanceOptimization(options = {}) {
  // Configuration
  const config = reactive({
    enableLazyLoading: true,
    enableCodeSplitting: true,
    enableBundleOptimization: true,
    enablePerformanceMonitoring: true,
    enableImageOptimization: true,
    enablePreloading: true,
    enableCaching: true,
    enableCompression: false,
    chunkSizeLimit: 500000, // 500KB
    imageQuality: 85,
    preloadThreshold: 0.1, // Intersection observer threshold
    cacheStrategy: 'stale-while-revalidate',
    ...options
  })

  // Performance metrics
  const metrics = reactive({
    loadTime: 0,
    renderTime: 0,
    bundleSize: 0,
    chunkCount: 0,
    cacheHitRate: 0,
    memoryUsage: 0,
    networkRequests: 0,
    imageOptimizations: 0,
    lazyLoadedComponents: 0,
    preloadedResources: 0
  })

  // Performance state
  const isOptimizing = ref(false)
  const optimizationQueue = ref([])
  const loadedChunks = ref(new Set())
  const preloadedResources = ref(new Set())
  const optimizedImages = ref(new Map())
  const componentCache = ref(new Map())

  // Performance observers
  const performanceObserver = ref(null)
  const intersectionObserver = ref(null)
  const mutationObserver = ref(null)

  // Computed properties
  const performanceScore = computed(() => {
    const factors = [
      metrics.loadTime < 2000 ? 100 : Math.max(0, 100 - (metrics.loadTime - 2000) / 100),
      metrics.renderTime < 100 ? 100 : Math.max(0, 100 - (metrics.renderTime - 100) / 10),
      metrics.cacheHitRate,
      metrics.bundleSize < 1000000 ? 100 : Math.max(0, 100 - (metrics.bundleSize - 1000000) / 50000),
      metrics.memoryUsage < 50 ? 100 : Math.max(0, 100 - (metrics.memoryUsage - 50) * 2)
    ]
    
    return Math.round(factors.reduce((sum, factor) => sum + factor, 0) / factors.length)
  })

  const optimizationRecommendations = computed(() => {
    const recommendations = []
    
    if (metrics.loadTime > 3000) {
      recommendations.push({
        type: 'critical',
        message: 'Page load time is too slow. Consider enabling code splitting and lazy loading.',
        action: 'enableCodeSplitting'
      })
    }
    
    if (metrics.bundleSize > 2000000) {
      recommendations.push({
        type: 'warning',
        message: 'Bundle size is large. Consider splitting into smaller chunks.',
        action: 'optimizeBundleSize'
      })
    }
    
    if (metrics.cacheHitRate < 70) {
      recommendations.push({
        type: 'info',
        message: 'Cache hit rate is low. Review caching strategy.',
        action: 'optimizeCaching'
      })
    }
    
    if (metrics.memoryUsage > 100) {
      recommendations.push({
        type: 'warning',
        message: 'High memory usage detected. Consider memory optimization.',
        action: 'optimizeMemory'
      })
    }
    
    return recommendations
  })

  // Lazy loading utilities
  const createLazyComponent = (importFn, fallback = null) => {
    return defineAsyncComponent({
      loader: async () => {
        const startTime = performance.now()
        
        try {
          const component = await importFn()
          const loadTime = performance.now() - startTime
          
          metrics.lazyLoadedComponents++
          updateMetric('loadTime', loadTime)
          
          return component
        } catch (error) {
          console.error('Failed to load lazy component:', error)
          return fallback || createErrorComponent(error)
        }
      },
      loadingComponent: createLoadingComponent(),
      errorComponent: createErrorComponent(),
      delay: 200,
      timeout: 10000
    })
  }

  const createLoadingComponent = () => ({
    template: `
      <div class="lazy-loading-placeholder">
        <div class="animate-pulse bg-gray-200 rounded h-32"></div>
      </div>
    `
  })

  const createErrorComponent = (error = null) => ({
    template: `
      <div class="lazy-loading-error">
        <p class="text-red-600">Failed to load component</p>
        ${error ? `<small class="text-gray-500">${error.message}</small>` : ''}
      </div>
    `
  })

  // Code splitting utilities
  const splitComponent = (componentPath, chunkName = null) => {
    const actualChunkName = chunkName || componentPath.split('/').pop().replace('.vue', '')
    
    return () => import(
      /* webpackChunkName: "[request]" */
      /* webpackMode: "lazy" */
      componentPath
    ).then(module => {
      loadedChunks.value.add(actualChunkName)
      metrics.chunkCount = loadedChunks.value.size
      return module
    })
  }

  const preloadChunk = async (chunkName) => {
    if (preloadedResources.value.has(chunkName)) return

    try {
      const link = document.createElement('link')
      link.rel = 'modulepreload'
      link.href = `/build/assets/${chunkName}.js`
      document.head.appendChild(link)
      
      preloadedResources.value.add(chunkName)
      metrics.preloadedResources++
    } catch (error) {
      console.warn('Failed to preload chunk:', chunkName, error)
    }
  }

  // Image optimization
  const optimizeImage = (src, options = {}) => {
    const cacheKey = `${src}-${JSON.stringify(options)}`
    
    if (optimizedImages.value.has(cacheKey)) {
      return optimizedImages.value.get(cacheKey)
    }

    const optimizedSrc = createOptimizedImageUrl(src, {
      quality: options.quality || config.imageQuality,
      width: options.width,
      height: options.height,
      format: options.format || 'webp',
      ...options
    })

    optimizedImages.value.set(cacheKey, optimizedSrc)
    metrics.imageOptimizations++
    
    return optimizedSrc
  }

  const createOptimizedImageUrl = (src, options) => {
    // This would integrate with your image optimization service
    // For now, return the original src with query parameters
    const params = new URLSearchParams()
    
    if (options.width) params.set('w', options.width)
    if (options.height) params.set('h', options.height)
    if (options.quality) params.set('q', options.quality)
    if (options.format) params.set('f', options.format)
    
    return `${src}${src.includes('?') ? '&' : '?'}${params.toString()}`
  }

  const createResponsiveImageSrcSet = (src, sizes = [320, 640, 1024, 1280]) => {
    return sizes.map(size => {
      const optimizedSrc = optimizeImage(src, { width: size })
      return `${optimizedSrc} ${size}w`
    }).join(', ')
  }

  // Preloading utilities
  const setupIntersectionObserver = () => {
    if (!config.enablePreloading || !window.IntersectionObserver) return

    intersectionObserver.value = new IntersectionObserver(
      (entries) => {
        entries.forEach(entry => {
          if (entry.isIntersecting) {
            const element = entry.target
            const preloadTarget = element.dataset.preload
            
            if (preloadTarget) {
              preloadResource(preloadTarget)
              intersectionObserver.value.unobserve(element)
            }
          }
        })
      },
      { threshold: config.preloadThreshold }
    )
  }

  const preloadResource = async (resource) => {
    if (preloadedResources.value.has(resource)) return

    try {
      if (resource.endsWith('.js')) {
        await preloadChunk(resource.replace('.js', ''))
      } else if (resource.match(/\.(jpg|jpeg|png|webp|svg)$/i)) {
        const link = document.createElement('link')
        link.rel = 'preload'
        link.as = 'image'
        link.href = resource
        document.head.appendChild(link)
      } else {
        // Generic resource preloading
        const link = document.createElement('link')
        link.rel = 'preload'
        link.href = resource
        document.head.appendChild(link)
      }
      
      preloadedResources.value.add(resource)
      metrics.preloadedResources++
    } catch (error) {
      console.warn('Failed to preload resource:', resource, error)
    }
  }

  // Performance monitoring
  const setupPerformanceMonitoring = () => {
    if (!config.enablePerformanceMonitoring || !window.PerformanceObserver) return

    // Monitor navigation timing
    performanceObserver.value = new PerformanceObserver((list) => {
      list.getEntries().forEach(entry => {
        switch (entry.entryType) {
          case 'navigation':
            metrics.loadTime = entry.loadEventEnd - entry.loadEventStart
            break
          case 'measure':
            if (entry.name === 'render-time') {
              metrics.renderTime = entry.duration
            }
            break
          case 'resource':
            metrics.networkRequests++
            break
        }
      })
    })

    performanceObserver.value.observe({ entryTypes: ['navigation', 'measure', 'resource'] })
  }

  const measureRenderTime = async (callback) => {
    const startTime = performance.now()
    
    await callback()
    await nextTick()
    
    const endTime = performance.now()
    const renderTime = endTime - startTime
    
    performance.measure('render-time', { start: startTime, end: endTime })
    metrics.renderTime = renderTime
    
    return renderTime
  }

  const measureMemoryUsage = () => {
    if (performance.memory) {
      metrics.memoryUsage = Math.round(
        (performance.memory.usedJSHeapSize / performance.memory.jsHeapSizeLimit) * 100
      )
    }
  }

  // Bundle optimization
  const analyzeBundleSize = () => {
    if (window.__BUNDLE_ANALYZER__) {
      metrics.bundleSize = window.__BUNDLE_ANALYZER__.totalSize
      metrics.chunkCount = window.__BUNDLE_ANALYZER__.chunkCount
    }
  }

  const optimizeBundleSize = () => {
    // Implement bundle size optimization strategies
    if (metrics.bundleSize > config.chunkSizeLimit) {
      console.warn('Bundle size exceeds limit. Consider code splitting.')
      
      // Suggest code splitting opportunities
      return {
        recommendation: 'split-large-components',
        threshold: config.chunkSizeLimit,
        current: metrics.bundleSize
      }
    }
  }

  // Cache optimization
  const optimizeCaching = () => {
    // Implement cache optimization strategies
    const cacheStrategies = {
      'cache-first': 'Use cached version if available',
      'network-first': 'Try network first, fallback to cache',
      'stale-while-revalidate': 'Use cache, update in background'
    }
    
    return {
      currentStrategy: config.cacheStrategy,
      availableStrategies: cacheStrategies,
      hitRate: metrics.cacheHitRate
    }
  }

  // Memory optimization
  const optimizeMemory = () => {
    // Clear component cache if memory usage is high
    if (metrics.memoryUsage > 80) {
      componentCache.value.clear()
      
      // Force garbage collection if available
      if (window.gc) {
        window.gc()
      }
      
      // Re-measure memory usage
      setTimeout(measureMemoryUsage, 100)
    }
  }

  // Utility functions
  const updateMetric = (key, value) => {
    if (typeof value === 'number') {
      metrics[key] = value
    }
  }

  const getPerformanceReport = () => {
    return {
      metrics: { ...metrics },
      score: performanceScore.value,
      recommendations: optimizationRecommendations.value,
      config: { ...config }
    }
  }

  const resetMetrics = () => {
    Object.keys(metrics).forEach(key => {
      metrics[key] = 0
    })
    loadedChunks.value.clear()
    preloadedResources.value.clear()
    optimizedImages.value.clear()
    componentCache.value.clear()
  }

  // Lifecycle
  const setup = () => {
    setupPerformanceMonitoring()
    setupIntersectionObserver()
    
    // Initial measurements
    measureMemoryUsage()
    analyzeBundleSize()
    
    // Periodic monitoring
    const monitoringInterval = setInterval(() => {
      measureMemoryUsage()
    }, 5000)
    
    return () => {
      clearInterval(monitoringInterval)
    }
  }

  const cleanup = () => {
    if (performanceObserver.value) {
      performanceObserver.value.disconnect()
    }
    
    if (intersectionObserver.value) {
      intersectionObserver.value.disconnect()
    }
    
    if (mutationObserver.value) {
      mutationObserver.value.disconnect()
    }
  }

  return {
    // Configuration
    config,

    // Metrics
    metrics,
    performanceScore,
    optimizationRecommendations,

    // State
    isOptimizing,
    loadedChunks,
    preloadedResources,

    // Lazy loading
    createLazyComponent,
    splitComponent,

    // Image optimization
    optimizeImage,
    createResponsiveImageSrcSet,

    // Preloading
    preloadChunk,
    preloadResource,

    // Performance monitoring
    measureRenderTime,
    measureMemoryUsage,

    // Optimization
    optimizeBundleSize,
    optimizeCaching,
    optimizeMemory,

    // Utilities
    getPerformanceReport,
    resetMetrics,
    setup,
    cleanup
  }
}
