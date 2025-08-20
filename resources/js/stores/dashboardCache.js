/**
 * Dashboard Cache Store
 * 
 * Manages cached dashboard data including dashboard content,
 * metadata, configuration, and performance optimization.
 */

import { defineStore } from 'pinia'
import { ref, computed, watch } from 'vue'

export const useDashboardCacheStore = defineStore('dashboardCache', () => {
  // State
  const cachedDashboards = ref(new Map())
  const cachedMetadata = ref(new Map())
  const cachedConfiguration = ref(new Map())
  const cachedCards = ref(new Map())
  const cacheTimestamps = ref(new Map())
  const cacheHits = ref(new Map())
  const cacheMisses = ref(new Map())
  const isLoading = ref(new Set())
  const errors = ref(new Map())

  // Cache configuration
  const cacheConfig = ref({
    defaultTTL: 300000, // 5 minutes in milliseconds
    maxCacheSize: 50, // Maximum number of cached items
    enablePersistence: true,
    enableCompression: false,
    enableMetrics: true,
    preloadStrategy: 'lazy', // 'eager', 'lazy', 'none'
    invalidationStrategy: 'ttl', // 'ttl', 'manual', 'hybrid'
  })

  // Computed properties
  const cacheSize = computed(() => cachedDashboards.value.size)
  
  const cacheHitRate = computed(() => {
    const totalHits = Array.from(cacheHits.value.values()).reduce((sum, hits) => sum + hits, 0)
    const totalMisses = Array.from(cacheMisses.value.values()).reduce((sum, misses) => sum + misses, 0)
    const total = totalHits + totalMisses
    return total > 0 ? (totalHits / total) * 100 : 0
  })

  const cacheStats = computed(() => ({
    size: cacheSize.value,
    hitRate: cacheHitRate.value,
    totalHits: Array.from(cacheHits.value.values()).reduce((sum, hits) => sum + hits, 0),
    totalMisses: Array.from(cacheMisses.value.values()).reduce((sum, misses) => sum + misses, 0),
    oldestEntry: getOldestCacheEntry(),
    newestEntry: getNewestCacheEntry(),
  }))

  const isLoadingDashboard = computed(() => (dashboardUriKey) => {
    return isLoading.value.has(dashboardUriKey)
  })

  const hasCachedDashboard = computed(() => (dashboardUriKey) => {
    return cachedDashboards.value.has(dashboardUriKey) && !isCacheExpired(dashboardUriKey)
  })

  // Cache operations
  const getCachedDashboard = (dashboardUriKey) => {
    if (!dashboardUriKey) return null

    // Check if cache exists and is not expired
    if (!cachedDashboards.value.has(dashboardUriKey)) {
      recordCacheMiss(dashboardUriKey)
      return null
    }

    if (isCacheExpired(dashboardUriKey)) {
      invalidateCache(dashboardUriKey)
      recordCacheMiss(dashboardUriKey)
      return null
    }

    recordCacheHit(dashboardUriKey)
    return cachedDashboards.value.get(dashboardUriKey)
  }

  const setCachedDashboard = (dashboardUriKey, data, ttl = null) => {
    if (!dashboardUriKey || !data) return

    // Ensure cache size limit
    if (cachedDashboards.value.size >= cacheConfig.value.maxCacheSize) {
      evictOldestEntry()
    }

    const cacheEntry = {
      data,
      timestamp: Date.now(),
      ttl: ttl || cacheConfig.value.defaultTTL,
      accessCount: 0,
      lastAccessed: Date.now(),
    }

    cachedDashboards.value.set(dashboardUriKey, cacheEntry)
    cacheTimestamps.value.set(dashboardUriKey, cacheEntry.timestamp)

    // Persist to localStorage if enabled
    if (cacheConfig.value.enablePersistence) {
      persistCacheEntry(dashboardUriKey, cacheEntry)
    }
  }

  const getCachedMetadata = (dashboardUriKey) => {
    if (!dashboardUriKey) return null

    if (!cachedMetadata.value.has(dashboardUriKey)) {
      return null
    }

    const entry = cachedMetadata.value.get(dashboardUriKey)
    if (isCacheEntryExpired(entry)) {
      cachedMetadata.value.delete(dashboardUriKey)
      return null
    }

    entry.accessCount++
    entry.lastAccessed = Date.now()
    return entry.data
  }

  const setCachedMetadata = (dashboardUriKey, metadata, ttl = null) => {
    if (!dashboardUriKey || !metadata) return

    const cacheEntry = {
      data: metadata,
      timestamp: Date.now(),
      ttl: ttl || cacheConfig.value.defaultTTL,
      accessCount: 0,
      lastAccessed: Date.now(),
    }

    cachedMetadata.value.set(dashboardUriKey, cacheEntry)
  }

  const getCachedConfiguration = (dashboardUriKey) => {
    if (!dashboardUriKey) return null

    if (!cachedConfiguration.value.has(dashboardUriKey)) {
      return null
    }

    const entry = cachedConfiguration.value.get(dashboardUriKey)
    if (isCacheEntryExpired(entry)) {
      cachedConfiguration.value.delete(dashboardUriKey)
      return null
    }

    entry.accessCount++
    entry.lastAccessed = Date.now()
    return entry.data
  }

  const setCachedConfiguration = (dashboardUriKey, config, ttl = null) => {
    if (!dashboardUriKey || !config) return

    const cacheEntry = {
      data: config,
      timestamp: Date.now(),
      ttl: ttl || cacheConfig.value.defaultTTL,
      accessCount: 0,
      lastAccessed: Date.now(),
    }

    cachedConfiguration.value.set(dashboardUriKey, cacheEntry)
  }

  const getCachedCards = (dashboardUriKey) => {
    if (!dashboardUriKey) return null

    if (!cachedCards.value.has(dashboardUriKey)) {
      return null
    }

    const entry = cachedCards.value.get(dashboardUriKey)
    if (isCacheEntryExpired(entry)) {
      cachedCards.value.delete(dashboardUriKey)
      return null
    }

    entry.accessCount++
    entry.lastAccessed = Date.now()
    return entry.data
  }

  const setCachedCards = (dashboardUriKey, cards, ttl = null) => {
    if (!dashboardUriKey || !cards) return

    const cacheEntry = {
      data: cards,
      timestamp: Date.now(),
      ttl: ttl || cacheConfig.value.defaultTTL,
      accessCount: 0,
      lastAccessed: Date.now(),
    }

    cachedCards.value.set(dashboardUriKey, cacheEntry)
  }

  // Cache management
  const invalidateCache = (dashboardUriKey) => {
    if (dashboardUriKey) {
      // Invalidate specific dashboard
      cachedDashboards.value.delete(dashboardUriKey)
      cachedMetadata.value.delete(dashboardUriKey)
      cachedConfiguration.value.delete(dashboardUriKey)
      cachedCards.value.delete(dashboardUriKey)
      cacheTimestamps.value.delete(dashboardUriKey)
      errors.value.delete(dashboardUriKey)
      
      if (cacheConfig.value.enablePersistence) {
        removeCacheEntry(dashboardUriKey)
      }
    } else {
      // Invalidate all cache
      clearAllCache()
    }
  }

  const clearAllCache = () => {
    cachedDashboards.value.clear()
    cachedMetadata.value.clear()
    cachedConfiguration.value.clear()
    cachedCards.value.clear()
    cacheTimestamps.value.clear()
    cacheHits.value.clear()
    cacheMisses.value.clear()
    isLoading.value.clear()
    errors.value.clear()

    if (cacheConfig.value.enablePersistence) {
      clearPersistedCache()
    }
  }

  const evictOldestEntry = () => {
    let oldestKey = null
    let oldestTimestamp = Date.now()

    for (const [key, timestamp] of cacheTimestamps.value) {
      if (timestamp < oldestTimestamp) {
        oldestTimestamp = timestamp
        oldestKey = key
      }
    }

    if (oldestKey) {
      invalidateCache(oldestKey)
    }
  }

  const cleanupExpiredEntries = () => {
    const now = Date.now()
    const expiredKeys = []

    for (const [key, entry] of cachedDashboards.value) {
      if (isCacheEntryExpired(entry, now)) {
        expiredKeys.push(key)
      }
    }

    expiredKeys.forEach(key => invalidateCache(key))
  }

  // Loading state management
  const setLoading = (dashboardUriKey, loading = true) => {
    if (loading) {
      isLoading.value.add(dashboardUriKey)
    } else {
      isLoading.value.delete(dashboardUriKey)
    }
  }

  const setError = (dashboardUriKey, error) => {
    if (error) {
      errors.value.set(dashboardUriKey, {
        message: error.message || error,
        timestamp: Date.now(),
        stack: error.stack
      })
    } else {
      errors.value.delete(dashboardUriKey)
    }
  }

  const getError = (dashboardUriKey) => {
    return errors.value.get(dashboardUriKey) || null
  }

  // Helper functions
  const isCacheExpired = (dashboardUriKey) => {
    const entry = cachedDashboards.value.get(dashboardUriKey)
    return entry ? isCacheEntryExpired(entry) : true
  }

  const isCacheEntryExpired = (entry, now = Date.now()) => {
    return (now - entry.timestamp) > entry.ttl
  }

  const recordCacheHit = (dashboardUriKey) => {
    if (!cacheConfig.value.enableMetrics) return
    
    const hits = cacheHits.value.get(dashboardUriKey) || 0
    cacheHits.value.set(dashboardUriKey, hits + 1)
  }

  const recordCacheMiss = (dashboardUriKey) => {
    if (!cacheConfig.value.enableMetrics) return
    
    const misses = cacheMisses.value.get(dashboardUriKey) || 0
    cacheMisses.value.set(dashboardUriKey, misses + 1)
  }

  const getOldestCacheEntry = () => {
    let oldest = null
    let oldestTimestamp = Date.now()

    for (const [key, timestamp] of cacheTimestamps.value) {
      if (timestamp < oldestTimestamp) {
        oldestTimestamp = timestamp
        oldest = { key, timestamp }
      }
    }

    return oldest
  }

  const getNewestCacheEntry = () => {
    let newest = null
    let newestTimestamp = 0

    for (const [key, timestamp] of cacheTimestamps.value) {
      if (timestamp > newestTimestamp) {
        newestTimestamp = timestamp
        newest = { key, timestamp }
      }
    }

    return newest
  }

  // Persistence methods
  const persistCacheEntry = (dashboardUriKey, entry) => {
    try {
      const cacheKey = `dashboard_cache_${dashboardUriKey}`
      localStorage.setItem(cacheKey, JSON.stringify(entry))
    } catch (error) {
      console.warn('Failed to persist cache entry:', error)
    }
  }

  const removeCacheEntry = (dashboardUriKey) => {
    try {
      const cacheKey = `dashboard_cache_${dashboardUriKey}`
      localStorage.removeItem(cacheKey)
    } catch (error) {
      console.warn('Failed to remove cache entry:', error)
    }
  }

  const clearPersistedCache = () => {
    try {
      const keys = Object.keys(localStorage).filter(key => key.startsWith('dashboard_cache_'))
      keys.forEach(key => localStorage.removeItem(key))
    } catch (error) {
      console.warn('Failed to clear persisted cache:', error)
    }
  }

  const hydrateCacheFromStorage = () => {
    if (!cacheConfig.value.enablePersistence) return

    try {
      const keys = Object.keys(localStorage).filter(key => key.startsWith('dashboard_cache_'))
      
      keys.forEach(key => {
        const dashboardUriKey = key.replace('dashboard_cache_', '')
        const stored = localStorage.getItem(key)
        
        if (stored) {
          const entry = JSON.parse(stored)
          
          // Check if entry is still valid
          if (!isCacheEntryExpired(entry)) {
            cachedDashboards.value.set(dashboardUriKey, entry)
            cacheTimestamps.value.set(dashboardUriKey, entry.timestamp)
          } else {
            // Remove expired entry
            localStorage.removeItem(key)
          }
        }
      })
    } catch (error) {
      console.warn('Failed to hydrate cache from storage:', error)
    }
  }

  // Configuration methods
  const updateCacheConfig = (newConfig) => {
    cacheConfig.value = { ...cacheConfig.value, ...newConfig }
  }

  // Initialize cache
  const initialize = () => {
    hydrateCacheFromStorage()
    
    // Set up periodic cleanup
    setInterval(cleanupExpiredEntries, 60000) // Clean up every minute
  }

  // Initialize on store creation
  initialize()

  return {
    // State
    cachedDashboards,
    cachedMetadata,
    cachedConfiguration,
    cachedCards,
    cacheTimestamps,
    cacheHits,
    cacheMisses,
    isLoading,
    errors,
    cacheConfig,

    // Computed
    cacheSize,
    cacheHitRate,
    cacheStats,
    isLoadingDashboard,
    hasCachedDashboard,

    // Cache operations
    getCachedDashboard,
    setCachedDashboard,
    getCachedMetadata,
    setCachedMetadata,
    getCachedConfiguration,
    setCachedConfiguration,
    getCachedCards,
    setCachedCards,

    // Cache management
    invalidateCache,
    clearAllCache,
    evictOldestEntry,
    cleanupExpiredEntries,

    // Loading and error management
    setLoading,
    setError,
    getError,

    // Configuration
    updateCacheConfig,

    // Utilities
    initialize,
    hydrateCacheFromStorage
  }
})
