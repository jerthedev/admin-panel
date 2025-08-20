/**
 * Advanced Lazy Loading Service
 * 
 * Provides intelligent lazy loading for dashboard components, images,
 * and resources with preloading strategies and performance optimization.
 */

class LazyLoadingService {
  constructor(options = {}) {
    this.config = {
      intersectionThreshold: 0.1,
      rootMargin: '50px',
      preloadDistance: 200,
      maxConcurrentLoads: 3,
      retryAttempts: 3,
      retryDelay: 1000,
      enablePreloading: true,
      enablePrioritization: true,
      enableAnalytics: true,
      ...options
    }

    this.loadingQueue = []
    this.loadingPromises = new Map()
    this.loadedResources = new Set()
    this.failedResources = new Set()
    this.preloadedResources = new Set()
    this.currentlyLoading = 0
    this.analytics = {
      totalRequests: 0,
      successfulLoads: 0,
      failedLoads: 0,
      averageLoadTime: 0,
      cacheHits: 0
    }

    this.intersectionObserver = null
    this.mutationObserver = null
    this.setupObservers()
  }

  /**
   * Setup intersection and mutation observers
   */
  setupObservers() {
    if (typeof window === 'undefined' || !window.IntersectionObserver) return

    // Intersection observer for lazy loading
    this.intersectionObserver = new IntersectionObserver(
      this.handleIntersection.bind(this),
      {
        threshold: this.config.intersectionThreshold,
        rootMargin: this.config.rootMargin
      }
    )

    // Mutation observer for dynamic content
    this.mutationObserver = new MutationObserver(
      this.handleMutation.bind(this)
    )

    this.mutationObserver.observe(document.body, {
      childList: true,
      subtree: true
    })
  }

  /**
   * Handle intersection observer entries
   */
  handleIntersection(entries) {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        const element = entry.target
        const loadType = element.dataset.lazyType || 'component'
        const priority = parseInt(element.dataset.priority) || 0

        this.queueLoad({
          element,
          type: loadType,
          priority,
          src: element.dataset.src || element.dataset.component,
          retries: 0
        })

        this.intersectionObserver.unobserve(element)
      }
    })
  }

  /**
   * Handle mutation observer changes
   */
  handleMutation(mutations) {
    mutations.forEach(mutation => {
      mutation.addedNodes.forEach(node => {
        if (node.nodeType === Node.ELEMENT_NODE) {
          this.scanForLazyElements(node)
        }
      })
    })
  }

  /**
   * Scan for lazy loading elements
   */
  scanForLazyElements(root = document) {
    const lazyElements = root.querySelectorAll('[data-lazy], [data-src], [data-component]')
    
    lazyElements.forEach(element => {
      if (!element.dataset.observed) {
        this.observe(element)
        element.dataset.observed = 'true'
      }
    })
  }

  /**
   * Observe an element for lazy loading
   */
  observe(element) {
    if (this.intersectionObserver) {
      this.intersectionObserver.observe(element)
    }
  }

  /**
   * Queue a resource for loading
   */
  queueLoad(loadRequest) {
    // Check if already loaded or loading
    const key = this.getResourceKey(loadRequest)
    if (this.loadedResources.has(key) || this.loadingPromises.has(key)) {
      return this.loadingPromises.get(key) || Promise.resolve()
    }

    // Add to queue with priority
    this.loadingQueue.push(loadRequest)
    this.loadingQueue.sort((a, b) => b.priority - a.priority)

    return this.processQueue()
  }

  /**
   * Process the loading queue
   */
  async processQueue() {
    while (
      this.loadingQueue.length > 0 && 
      this.currentlyLoading < this.config.maxConcurrentLoads
    ) {
      const loadRequest = this.loadingQueue.shift()
      this.processLoadRequest(loadRequest)
    }
  }

  /**
   * Process a single load request
   */
  async processLoadRequest(loadRequest) {
    const key = this.getResourceKey(loadRequest)
    this.currentlyLoading++
    this.analytics.totalRequests++

    const startTime = performance.now()

    try {
      const promise = this.loadResource(loadRequest)
      this.loadingPromises.set(key, promise)

      const result = await promise
      
      this.loadedResources.add(key)
      this.analytics.successfulLoads++
      
      const loadTime = performance.now() - startTime
      this.updateAverageLoadTime(loadTime)

      this.applyLoadResult(loadRequest, result)
      
      return result
    } catch (error) {
      this.analytics.failedLoads++
      this.handleLoadError(loadRequest, error)
      throw error
    } finally {
      this.currentlyLoading--
      this.loadingPromises.delete(key)
      this.processQueue() // Continue processing queue
    }
  }

  /**
   * Load a resource based on its type
   */
  async loadResource(loadRequest) {
    switch (loadRequest.type) {
      case 'component':
        return this.loadComponent(loadRequest)
      case 'image':
        return this.loadImage(loadRequest)
      case 'script':
        return this.loadScript(loadRequest)
      case 'style':
        return this.loadStyle(loadRequest)
      case 'data':
        return this.loadData(loadRequest)
      default:
        throw new Error(`Unknown load type: ${loadRequest.type}`)
    }
  }

  /**
   * Load a Vue component
   */
  async loadComponent(loadRequest) {
    const componentPath = loadRequest.src
    
    try {
      // Try to import the component
      const module = await import(
        /* webpackChunkName: "[request]" */
        /* webpackMode: "lazy" */
        componentPath
      )
      
      return module.default || module
    } catch (error) {
      console.error(`Failed to load component: ${componentPath}`, error)
      throw error
    }
  }

  /**
   * Load an image
   */
  async loadImage(loadRequest) {
    return new Promise((resolve, reject) => {
      const img = new Image()
      
      img.onload = () => resolve(img)
      img.onerror = () => reject(new Error(`Failed to load image: ${loadRequest.src}`))
      
      // Apply optimizations if available
      img.src = this.optimizeImageUrl(loadRequest.src, loadRequest.element)
    })
  }

  /**
   * Load a script
   */
  async loadScript(loadRequest) {
    return new Promise((resolve, reject) => {
      const script = document.createElement('script')
      
      script.onload = () => resolve(script)
      script.onerror = () => reject(new Error(`Failed to load script: ${loadRequest.src}`))
      
      script.src = loadRequest.src
      script.async = true
      
      document.head.appendChild(script)
    })
  }

  /**
   * Load a stylesheet
   */
  async loadStyle(loadRequest) {
    return new Promise((resolve, reject) => {
      const link = document.createElement('link')
      
      link.onload = () => resolve(link)
      link.onerror = () => reject(new Error(`Failed to load style: ${loadRequest.src}`))
      
      link.rel = 'stylesheet'
      link.href = loadRequest.src
      
      document.head.appendChild(link)
    })
  }

  /**
   * Load data via fetch
   */
  async loadData(loadRequest) {
    const response = await fetch(loadRequest.src)
    
    if (!response.ok) {
      throw new Error(`Failed to load data: ${response.status} ${response.statusText}`)
    }
    
    return response.json()
  }

  /**
   * Apply the load result to the element
   */
  applyLoadResult(loadRequest, result) {
    const { element, type } = loadRequest

    switch (type) {
      case 'image':
        if (element.tagName === 'IMG') {
          element.src = result.src
          element.classList.remove('lazy-loading')
          element.classList.add('lazy-loaded')
        }
        break
      
      case 'component':
        // Emit event for component loading
        element.dispatchEvent(new CustomEvent('component-loaded', {
          detail: { component: result }
        }))
        break
      
      case 'data':
        // Emit event for data loading
        element.dispatchEvent(new CustomEvent('data-loaded', {
          detail: { data: result }
        }))
        break
    }
  }

  /**
   * Handle load errors with retry logic
   */
  async handleLoadError(loadRequest, error) {
    const { element, retries } = loadRequest

    if (retries < this.config.retryAttempts) {
      // Retry after delay
      setTimeout(() => {
        this.queueLoad({
          ...loadRequest,
          retries: retries + 1
        })
      }, this.config.retryDelay * (retries + 1))
    } else {
      // Mark as failed
      const key = this.getResourceKey(loadRequest)
      this.failedResources.add(key)
      
      // Apply error state
      element.classList.add('lazy-error')
      element.dispatchEvent(new CustomEvent('lazy-error', {
        detail: { error, loadRequest }
      }))
    }
  }

  /**
   * Optimize image URL with responsive parameters
   */
  optimizeImageUrl(src, element) {
    if (!element) return src

    const rect = element.getBoundingClientRect()
    const devicePixelRatio = window.devicePixelRatio || 1
    
    const width = Math.ceil(rect.width * devicePixelRatio)
    const height = Math.ceil(rect.height * devicePixelRatio)
    
    // Add optimization parameters
    const url = new URL(src, window.location.origin)
    url.searchParams.set('w', width)
    url.searchParams.set('h', height)
    url.searchParams.set('q', '85') // Quality
    url.searchParams.set('f', 'webp') // Format
    
    return url.toString()
  }

  /**
   * Preload resources based on priority
   */
  preload(resources) {
    if (!this.config.enablePreloading) return

    resources.forEach(resource => {
      const key = typeof resource === 'string' ? resource : this.getResourceKey(resource)
      
      if (!this.preloadedResources.has(key)) {
        this.preloadResource(resource)
        this.preloadedResources.add(key)
      }
    })
  }

  /**
   * Preload a single resource
   */
  preloadResource(resource) {
    const link = document.createElement('link')
    
    if (typeof resource === 'string') {
      link.rel = 'preload'
      link.href = resource
      
      // Determine resource type
      if (resource.match(/\.(js|mjs)$/)) {
        link.as = 'script'
      } else if (resource.match(/\.css$/)) {
        link.as = 'style'
      } else if (resource.match(/\.(jpg|jpeg|png|webp|svg)$/)) {
        link.as = 'image'
      }
    } else {
      link.rel = 'modulepreload'
      link.href = resource.src
    }
    
    document.head.appendChild(link)
  }

  /**
   * Get a unique key for a resource
   */
  getResourceKey(loadRequest) {
    return `${loadRequest.type}:${loadRequest.src}`
  }

  /**
   * Update average load time
   */
  updateAverageLoadTime(loadTime) {
    const { successfulLoads, averageLoadTime } = this.analytics
    this.analytics.averageLoadTime = 
      (averageLoadTime * (successfulLoads - 1) + loadTime) / successfulLoads
  }

  /**
   * Get performance analytics
   */
  getAnalytics() {
    return {
      ...this.analytics,
      loadedResources: this.loadedResources.size,
      failedResources: this.failedResources.size,
      preloadedResources: this.preloadedResources.size,
      currentlyLoading: this.currentlyLoading,
      queueLength: this.loadingQueue.length,
      successRate: this.analytics.totalRequests > 0 
        ? (this.analytics.successfulLoads / this.analytics.totalRequests) * 100 
        : 0
    }
  }

  /**
   * Clear all caches and reset state
   */
  reset() {
    this.loadingQueue = []
    this.loadingPromises.clear()
    this.loadedResources.clear()
    this.failedResources.clear()
    this.preloadedResources.clear()
    this.currentlyLoading = 0
    
    this.analytics = {
      totalRequests: 0,
      successfulLoads: 0,
      failedLoads: 0,
      averageLoadTime: 0,
      cacheHits: 0
    }
  }

  /**
   * Cleanup observers and resources
   */
  destroy() {
    if (this.intersectionObserver) {
      this.intersectionObserver.disconnect()
    }
    
    if (this.mutationObserver) {
      this.mutationObserver.disconnect()
    }
    
    this.reset()
  }
}

// Create singleton instance
const lazyLoadingService = new LazyLoadingService()

// Auto-initialize when DOM is ready
if (typeof window !== 'undefined') {
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
      lazyLoadingService.scanForLazyElements()
    })
  } else {
    lazyLoadingService.scanForLazyElements()
  }
}

export default lazyLoadingService
export { LazyLoadingService }
