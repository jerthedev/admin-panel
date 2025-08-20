/**
 * Performance Monitoring Service
 * 
 * Comprehensive performance monitoring and analytics for dashboard
 * applications with real-time metrics and optimization recommendations.
 */

class PerformanceMonitoringService {
  constructor(options = {}) {
    this.config = {
      enableRealTimeMonitoring: true,
      enableUserTiming: true,
      enableResourceTiming: true,
      enableNavigationTiming: true,
      enableMemoryMonitoring: true,
      enableNetworkMonitoring: true,
      samplingRate: 1.0, // 100% sampling
      reportingInterval: 30000, // 30 seconds
      maxMetricsHistory: 100,
      thresholds: {
        loadTime: 3000, // 3 seconds
        renderTime: 100, // 100ms
        memoryUsage: 100, // 100MB
        cacheHitRate: 80, // 80%
        errorRate: 5 // 5%
      },
      ...options
    }

    this.metrics = {
      navigation: {},
      resources: [],
      userTiming: [],
      memory: [],
      network: [],
      errors: [],
      custom: {}
    }

    this.observers = {
      performance: null,
      memory: null,
      network: null
    }

    this.timers = new Map()
    this.counters = new Map()
    this.histograms = new Map()
    this.alerts = []
    
    this.isMonitoring = false
    this.reportingTimer = null
    
    this.initialize()
  }

  /**
   * Initialize performance monitoring
   */
  initialize() {
    if (typeof window === 'undefined') return

    this.setupPerformanceObserver()
    this.setupMemoryMonitoring()
    this.setupNetworkMonitoring()
    this.setupErrorTracking()
    
    if (this.config.enableRealTimeMonitoring) {
      this.startRealTimeMonitoring()
    }
  }

  /**
   * Setup Performance Observer API
   */
  setupPerformanceObserver() {
    if (!window.PerformanceObserver) return

    try {
      this.observers.performance = new PerformanceObserver((list) => {
        list.getEntries().forEach(entry => {
          this.processPerformanceEntry(entry)
        })
      })

      // Observe different entry types
      const entryTypes = ['navigation', 'resource', 'measure', 'mark', 'paint', 'layout-shift']
      
      entryTypes.forEach(type => {
        try {
          this.observers.performance.observe({ entryTypes: [type] })
        } catch (e) {
          // Some entry types might not be supported
          console.debug(`Performance entry type '${type}' not supported`)
        }
      })
    } catch (error) {
      console.warn('Failed to setup PerformanceObserver:', error)
    }
  }

  /**
   * Process performance entries
   */
  processPerformanceEntry(entry) {
    switch (entry.entryType) {
      case 'navigation':
        this.processNavigationEntry(entry)
        break
      case 'resource':
        this.processResourceEntry(entry)
        break
      case 'measure':
      case 'mark':
        this.processUserTimingEntry(entry)
        break
      case 'paint':
        this.processPaintEntry(entry)
        break
      case 'layout-shift':
        this.processLayoutShiftEntry(entry)
        break
    }
  }

  /**
   * Process navigation timing
   */
  processNavigationEntry(entry) {
    this.metrics.navigation = {
      loadTime: entry.loadEventEnd - entry.loadEventStart,
      domContentLoaded: entry.domContentLoadedEventEnd - entry.domContentLoadedEventStart,
      firstByte: entry.responseStart - entry.requestStart,
      domInteractive: entry.domInteractive - entry.navigationStart,
      domComplete: entry.domComplete - entry.navigationStart,
      redirectTime: entry.redirectEnd - entry.redirectStart,
      dnsTime: entry.domainLookupEnd - entry.domainLookupStart,
      connectTime: entry.connectEnd - entry.connectStart,
      requestTime: entry.responseEnd - entry.requestStart,
      responseTime: entry.responseEnd - entry.responseStart,
      timestamp: Date.now()
    }

    this.checkThresholds('navigation', this.metrics.navigation)
  }

  /**
   * Process resource timing
   */
  processResourceEntry(entry) {
    const resource = {
      name: entry.name,
      type: this.getResourceType(entry.name),
      size: entry.transferSize || entry.encodedBodySize,
      duration: entry.duration,
      startTime: entry.startTime,
      cached: entry.transferSize === 0 && entry.encodedBodySize > 0,
      timestamp: Date.now()
    }

    this.metrics.resources.push(resource)
    
    // Keep only recent resources
    if (this.metrics.resources.length > this.config.maxMetricsHistory) {
      this.metrics.resources = this.metrics.resources.slice(-this.config.maxMetricsHistory)
    }

    this.updateResourceMetrics(resource)
  }

  /**
   * Process user timing entries
   */
  processUserTimingEntry(entry) {
    const timing = {
      name: entry.name,
      type: entry.entryType,
      duration: entry.duration || 0,
      startTime: entry.startTime,
      timestamp: Date.now()
    }

    this.metrics.userTiming.push(timing)
    
    if (this.metrics.userTiming.length > this.config.maxMetricsHistory) {
      this.metrics.userTiming = this.metrics.userTiming.slice(-this.config.maxMetricsHistory)
    }
  }

  /**
   * Process paint timing
   */
  processPaintEntry(entry) {
    this.metrics.custom[entry.name] = {
      value: entry.startTime,
      timestamp: Date.now()
    }
  }

  /**
   * Process layout shift
   */
  processLayoutShiftEntry(entry) {
    if (!this.metrics.custom.cumulativeLayoutShift) {
      this.metrics.custom.cumulativeLayoutShift = { value: 0, timestamp: Date.now() }
    }
    
    this.metrics.custom.cumulativeLayoutShift.value += entry.value
    this.metrics.custom.cumulativeLayoutShift.timestamp = Date.now()
  }

  /**
   * Setup memory monitoring
   */
  setupMemoryMonitoring() {
    if (!this.config.enableMemoryMonitoring || !performance.memory) return

    const measureMemory = () => {
      const memory = {
        used: performance.memory.usedJSHeapSize,
        total: performance.memory.totalJSHeapSize,
        limit: performance.memory.jsHeapSizeLimit,
        timestamp: Date.now()
      }

      this.metrics.memory.push(memory)
      
      if (this.metrics.memory.length > this.config.maxMetricsHistory) {
        this.metrics.memory = this.metrics.memory.slice(-this.config.maxMetricsHistory)
      }

      this.checkMemoryThresholds(memory)
    }

    // Measure memory every 5 seconds
    this.observers.memory = setInterval(measureMemory, 5000)
    measureMemory() // Initial measurement
  }

  /**
   * Setup network monitoring
   */
  setupNetworkMonitoring() {
    if (!this.config.enableNetworkMonitoring || !navigator.connection) return

    const measureNetwork = () => {
      const connection = navigator.connection
      const network = {
        effectiveType: connection.effectiveType,
        downlink: connection.downlink,
        rtt: connection.rtt,
        saveData: connection.saveData,
        timestamp: Date.now()
      }

      this.metrics.network.push(network)
      
      if (this.metrics.network.length > this.config.maxMetricsHistory) {
        this.metrics.network = this.metrics.network.slice(-this.config.maxMetricsHistory)
      }
    }

    // Listen for network changes
    navigator.connection.addEventListener('change', measureNetwork)
    measureNetwork() // Initial measurement
  }

  /**
   * Setup error tracking
   */
  setupErrorTracking() {
    // JavaScript errors
    window.addEventListener('error', (event) => {
      this.recordError({
        type: 'javascript',
        message: event.message,
        filename: event.filename,
        lineno: event.lineno,
        colno: event.colno,
        stack: event.error?.stack,
        timestamp: Date.now()
      })
    })

    // Promise rejections
    window.addEventListener('unhandledrejection', (event) => {
      this.recordError({
        type: 'promise',
        message: event.reason?.message || 'Unhandled promise rejection',
        stack: event.reason?.stack,
        timestamp: Date.now()
      })
    })

    // Resource loading errors
    window.addEventListener('error', (event) => {
      if (event.target !== window) {
        this.recordError({
          type: 'resource',
          message: `Failed to load ${event.target.tagName}: ${event.target.src || event.target.href}`,
          element: event.target.tagName,
          url: event.target.src || event.target.href,
          timestamp: Date.now()
        })
      }
    }, true)
  }

  /**
   * Record an error
   */
  recordError(error) {
    this.metrics.errors.push(error)
    
    if (this.metrics.errors.length > this.config.maxMetricsHistory) {
      this.metrics.errors = this.metrics.errors.slice(-this.config.maxMetricsHistory)
    }

    this.checkErrorThresholds()
  }

  /**
   * Start real-time monitoring
   */
  startRealTimeMonitoring() {
    if (this.isMonitoring) return

    this.isMonitoring = true
    this.reportingTimer = setInterval(() => {
      this.generateReport()
    }, this.config.reportingInterval)
  }

  /**
   * Stop real-time monitoring
   */
  stopRealTimeMonitoring() {
    this.isMonitoring = false
    
    if (this.reportingTimer) {
      clearInterval(this.reportingTimer)
      this.reportingTimer = null
    }
  }

  /**
   * Start a custom timer
   */
  startTimer(name) {
    this.timers.set(name, performance.now())
    performance.mark(`${name}-start`)
  }

  /**
   * End a custom timer
   */
  endTimer(name) {
    const startTime = this.timers.get(name)
    if (!startTime) return null

    const endTime = performance.now()
    const duration = endTime - startTime
    
    performance.mark(`${name}-end`)
    performance.measure(name, `${name}-start`, `${name}-end`)
    
    this.timers.delete(name)
    return duration
  }

  /**
   * Increment a counter
   */
  incrementCounter(name, value = 1) {
    const current = this.counters.get(name) || 0
    this.counters.set(name, current + value)
  }

  /**
   * Record a histogram value
   */
  recordHistogram(name, value) {
    if (!this.histograms.has(name)) {
      this.histograms.set(name, [])
    }
    
    const values = this.histograms.get(name)
    values.push({ value, timestamp: Date.now() })
    
    // Keep only recent values
    if (values.length > this.config.maxMetricsHistory) {
      values.splice(0, values.length - this.config.maxMetricsHistory)
    }
  }

  /**
   * Get resource type from URL
   */
  getResourceType(url) {
    if (url.match(/\.(js|mjs)$/)) return 'script'
    if (url.match(/\.css$/)) return 'stylesheet'
    if (url.match(/\.(jpg|jpeg|png|gif|webp|svg)$/)) return 'image'
    if (url.match(/\.(woff|woff2|ttf|eot)$/)) return 'font'
    if (url.includes('/api/')) return 'api'
    return 'other'
  }

  /**
   * Update resource metrics
   */
  updateResourceMetrics(resource) {
    // Update cache hit rate
    const totalResources = this.metrics.resources.length
    const cachedResources = this.metrics.resources.filter(r => r.cached).length
    const cacheHitRate = totalResources > 0 ? (cachedResources / totalResources) * 100 : 0
    
    this.metrics.custom.cacheHitRate = {
      value: cacheHitRate,
      timestamp: Date.now()
    }
  }

  /**
   * Check performance thresholds
   */
  checkThresholds(category, data) {
    const thresholds = this.config.thresholds
    
    if (category === 'navigation') {
      if (data.loadTime > thresholds.loadTime) {
        this.createAlert('warning', `Page load time (${data.loadTime}ms) exceeds threshold (${thresholds.loadTime}ms)`)
      }
    }
  }

  /**
   * Check memory thresholds
   */
  checkMemoryThresholds(memory) {
    const usedMB = memory.used / (1024 * 1024)
    
    if (usedMB > this.config.thresholds.memoryUsage) {
      this.createAlert('warning', `Memory usage (${usedMB.toFixed(1)}MB) exceeds threshold (${this.config.thresholds.memoryUsage}MB)`)
    }
  }

  /**
   * Check error thresholds
   */
  checkErrorThresholds() {
    const recentErrors = this.metrics.errors.filter(
      error => Date.now() - error.timestamp < 60000 // Last minute
    )
    
    const errorRate = (recentErrors.length / 60) * 100 // Errors per minute as percentage
    
    if (errorRate > this.config.thresholds.errorRate) {
      this.createAlert('critical', `Error rate (${errorRate.toFixed(1)}%) exceeds threshold (${this.config.thresholds.errorRate}%)`)
    }
  }

  /**
   * Create a performance alert
   */
  createAlert(severity, message) {
    const alert = {
      severity,
      message,
      timestamp: Date.now(),
      id: Math.random().toString(36).substr(2, 9)
    }
    
    this.alerts.push(alert)
    
    // Keep only recent alerts
    if (this.alerts.length > 50) {
      this.alerts = this.alerts.slice(-50)
    }
    
    // Emit alert event
    if (typeof window !== 'undefined') {
      window.dispatchEvent(new CustomEvent('performance-alert', {
        detail: alert
      }))
    }
  }

  /**
   * Generate performance report
   */
  generateReport() {
    const report = {
      timestamp: Date.now(),
      navigation: this.metrics.navigation,
      resources: this.getResourceSummary(),
      memory: this.getMemorySummary(),
      network: this.getNetworkSummary(),
      errors: this.getErrorSummary(),
      custom: this.metrics.custom,
      counters: Object.fromEntries(this.counters),
      histograms: this.getHistogramSummary(),
      alerts: this.alerts.slice(-10), // Recent alerts
      score: this.calculatePerformanceScore()
    }

    // Emit report event
    if (typeof window !== 'undefined') {
      window.dispatchEvent(new CustomEvent('performance-report', {
        detail: report
      }))
    }

    return report
  }

  /**
   * Get resource summary
   */
  getResourceSummary() {
    const resources = this.metrics.resources
    const byType = {}
    
    resources.forEach(resource => {
      if (!byType[resource.type]) {
        byType[resource.type] = { count: 0, totalSize: 0, totalDuration: 0, cached: 0 }
      }
      
      byType[resource.type].count++
      byType[resource.type].totalSize += resource.size || 0
      byType[resource.type].totalDuration += resource.duration || 0
      if (resource.cached) byType[resource.type].cached++
    })

    return {
      total: resources.length,
      byType,
      totalSize: resources.reduce((sum, r) => sum + (r.size || 0), 0),
      averageDuration: resources.length > 0 
        ? resources.reduce((sum, r) => sum + (r.duration || 0), 0) / resources.length 
        : 0
    }
  }

  /**
   * Get memory summary
   */
  getMemorySummary() {
    const memory = this.metrics.memory
    if (memory.length === 0) return null

    const latest = memory[memory.length - 1]
    const peak = memory.reduce((max, m) => Math.max(max, m.used), 0)
    
    return {
      current: latest.used,
      peak,
      limit: latest.limit,
      usagePercent: (latest.used / latest.limit) * 100
    }
  }

  /**
   * Get network summary
   */
  getNetworkSummary() {
    const network = this.metrics.network
    if (network.length === 0) return null

    const latest = network[network.length - 1]
    return latest
  }

  /**
   * Get error summary
   */
  getErrorSummary() {
    const errors = this.metrics.errors
    const byType = {}
    
    errors.forEach(error => {
      byType[error.type] = (byType[error.type] || 0) + 1
    })

    return {
      total: errors.length,
      byType,
      recent: errors.filter(e => Date.now() - e.timestamp < 300000).length // Last 5 minutes
    }
  }

  /**
   * Get histogram summary
   */
  getHistogramSummary() {
    const summary = {}
    
    this.histograms.forEach((values, name) => {
      const nums = values.map(v => v.value).sort((a, b) => a - b)
      
      summary[name] = {
        count: nums.length,
        min: nums[0],
        max: nums[nums.length - 1],
        median: nums[Math.floor(nums.length / 2)],
        p95: nums[Math.floor(nums.length * 0.95)],
        average: nums.reduce((sum, n) => sum + n, 0) / nums.length
      }
    })
    
    return summary
  }

  /**
   * Calculate overall performance score
   */
  calculatePerformanceScore() {
    const factors = []
    
    // Load time score
    if (this.metrics.navigation.loadTime) {
      const loadScore = Math.max(0, 100 - (this.metrics.navigation.loadTime - 1000) / 50)
      factors.push(loadScore)
    }
    
    // Cache hit rate score
    if (this.metrics.custom.cacheHitRate) {
      factors.push(this.metrics.custom.cacheHitRate.value)
    }
    
    // Memory usage score
    const memory = this.getMemorySummary()
    if (memory) {
      const memoryScore = Math.max(0, 100 - memory.usagePercent)
      factors.push(memoryScore)
    }
    
    // Error rate score
    const errors = this.getErrorSummary()
    const errorScore = Math.max(0, 100 - errors.recent * 10)
    factors.push(errorScore)
    
    return factors.length > 0 
      ? Math.round(factors.reduce((sum, score) => sum + score, 0) / factors.length)
      : 0
  }

  /**
   * Cleanup and destroy
   */
  destroy() {
    this.stopRealTimeMonitoring()
    
    if (this.observers.performance) {
      this.observers.performance.disconnect()
    }
    
    if (this.observers.memory) {
      clearInterval(this.observers.memory)
    }
    
    this.timers.clear()
    this.counters.clear()
    this.histograms.clear()
  }
}

// Create singleton instance
const performanceMonitoringService = new PerformanceMonitoringService()

export default performanceMonitoringService
export { PerformanceMonitoringService }
