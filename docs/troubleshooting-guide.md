# Dashboard Phase 3 - Troubleshooting Guide

## Common Issues and Solutions

### Dashboard Not Appearing in Selector

**Problem**: Dashboard is registered but doesn't appear in the dashboard selector.

**Possible Causes & Solutions**:

1. **Authorization Issue**
   ```php
   // Check if user has permission
   public function authorizedToSee($request): bool
   {
       return $request->user()->can('view-dashboard');
   }
   ```

2. **Dashboard Not Properly Registered**
   ```php
   // Ensure dashboard is registered in service provider
   DashboardRegistry::register('analytics', new AnalyticsDashboard());
   ```

3. **Selector Configuration**
   ```php
   // Check selector options
   public function selectorOptions(): array
   {
       return [
           'showInSelector' => true, // Make sure this is true
           'selectorOrder' => 10
       ];
   }
   ```

4. **Cache Issue**
   ```bash
   # Clear application cache
   php artisan cache:clear
   php artisan config:clear
   ```

### Slow Dashboard Loading

**Problem**: Dashboard takes too long to load or appears sluggish.

**Solutions**:

1. **Enable Caching**
   ```php
   public function cacheOptions(): array
   {
       return [
           'enabled' => true,
           'ttl' => 300,
           'strategy' => 'stale-while-revalidate'
       ];
   }
   ```

2. **Optimize Database Queries**
   ```php
   // Use eager loading in dashboard cards
   public function cards(): array
   {
       return [
           new UserMetrics(), // Ensure this doesn't have N+1 queries
           new RevenueChart()
       ];
   }
   ```

3. **Enable Lazy Loading**
   ```javascript
   // Use lazy loading for heavy components
   const LazyDashboard = defineAsyncComponent(() =>
     import('@/Dashboards/HeavyDashboard.vue')
   )
   ```

4. **Check Performance Metrics**
   ```javascript
   // Enable performance monitoring
   const performance = usePerformanceOptimization({
       enablePerformanceMonitoring: true
   })
   ```

### Mobile Navigation Not Working

**Problem**: Touch gestures or mobile navigation features not functioning.

**Solutions**:

1. **Check Mobile Detection**
   ```javascript
   const mobile = useMobileNavigation()
   console.log('Is mobile:', mobile.isMobile.value)
   ```

2. **Verify Touch Event Handlers**
   ```vue
   <template>
     <div
       @touchstart="handleTouchStart"
       @touchmove="handleTouchMove"
       @touchend="handleTouchEnd"
     >
       <!-- Content -->
     </div>
   </template>
   ```

3. **Enable Mobile Features**
   ```php
   // config/admin-panel.php
   'mobile' => [
       'enable_gestures' => true,
       'enable_pull_to_refresh' => true,
       'bottom_navigation' => true
   ]
   ```

4. **Check CSS Touch Actions**
   ```css
   .mobile-element {
       touch-action: manipulation;
       -webkit-overflow-scrolling: touch;
   }
   ```

### Dashboard Switching Errors

**Problem**: Errors occur when switching between dashboards.

**Solutions**:

1. **Check Dashboard Availability**
   ```php
   public function isAvailable(Request $request): bool
   {
       return config('features.analytics_enabled', true);
   }
   ```

2. **Handle Switching Errors**
   ```vue
   <script>
   const handleDashboardSwitch = async (dashboard) => {
     try {
       await dashboardStore.switchToDashboard(dashboard.uriKey)
     } catch (error) {
       console.error('Switch failed:', error)
       // Show user-friendly error message
     }
   }
   </script>
   ```

3. **Check Route Configuration**
   ```php
   // Ensure routes are properly configured
   Route::get('/admin/dashboards/{dashboard}', [DashboardController::class, 'show'])
       ->middleware(['web', 'auth']);
   ```

4. **Verify Dashboard Component**
   ```javascript
   // Check if dashboard component exists
   const dashboardComponent = computed(() => {
     try {
       return defineAsyncComponent(() => 
         import(`@/Dashboards/${currentDashboard.value.component}.vue`)
       )
     } catch (error) {
       console.error('Component not found:', error)
       return null
     }
   })
   ```

### Performance Issues

**Problem**: Dashboard performance is poor, especially on mobile devices.

**Solutions**:

1. **Bundle Size Optimization**
   ```javascript
   // Check bundle size
   npm run build -- --analyze
   
   // Use code splitting
   const routes = [
     {
       path: '/dashboard/:id',
       component: () => import('@/views/Dashboard.vue')
     }
   ]
   ```

2. **Image Optimization**
   ```vue
   <template>
     <!-- Use responsive images -->
     <img
       :src="optimizedImageSrc"
       loading="lazy"
       :sizes="imageSizes"
     />
   </template>
   ```

3. **Memory Management**
   ```javascript
   // Clean up resources in components
   onUnmounted(() => {
     // Clear intervals, remove event listeners, etc.
     if (refreshInterval) {
       clearInterval(refreshInterval)
     }
   })
   ```

4. **Database Query Optimization**
   ```php
   // Use database query optimization
   DB::enableQueryLog();
   // ... run dashboard
   $queries = DB::getQueryLog();
   // Check for N+1 queries and optimize
   ```

### JavaScript Errors

**Problem**: JavaScript errors in browser console affecting dashboard functionality.

**Solutions**:

1. **Check Component Imports**
   ```javascript
   // Ensure all components are properly imported
   import { defineAsyncComponent } from 'vue'
   import DashboardSelector from '@/Components/DashboardSelector.vue'
   ```

2. **Verify Store Configuration**
   ```javascript
   // Check Pinia store setup
   import { createPinia } from 'pinia'
   const pinia = createPinia()
   app.use(pinia)
   ```

3. **Handle Async Errors**
   ```javascript
   // Use try-catch for async operations
   const loadDashboard = async () => {
     try {
       await dashboardStore.loadDashboards()
     } catch (error) {
       console.error('Failed to load dashboards:', error)
       // Handle error appropriately
     }
   }
   ```

4. **Check Browser Compatibility**
   ```javascript
   // Add polyfills if needed
   import 'core-js/stable'
   import 'regenerator-runtime/runtime'
   ```

### CSS/Styling Issues

**Problem**: Dashboard styling appears broken or inconsistent.

**Solutions**:

1. **Check Tailwind CSS Configuration**
   ```javascript
   // tailwind.config.js
   module.exports = {
     content: [
       './resources/**/*.blade.php',
       './resources/**/*.js',
       './resources/**/*.vue'
     ]
   }
   ```

2. **Verify CSS Build Process**
   ```bash
   # Rebuild CSS
   npm run build
   
   # Check for CSS conflicts
   npm run dev
   ```

3. **Check Component Scoping**
   ```vue
   <style scoped>
   /* Scoped styles to avoid conflicts */
   .dashboard-container {
     /* styles */
   }
   </style>
   ```

4. **Mobile CSS Issues**
   ```css
   /* Add mobile-specific styles */
   @media (max-width: 768px) {
     .dashboard-grid {
       grid-template-columns: 1fr;
     }
   }
   ```

## Debug Mode

### Enabling Debug Mode

```php
// config/admin-panel.php
'debug' => env('ADMIN_PANEL_DEBUG', false),
'logging' => [
    'dashboard_switches' => true,
    'performance_metrics' => true,
    'user_interactions' => true
]
```

### Debug Information

```javascript
// Enable client-side debugging
window.ADMIN_PANEL_DEBUG = true;

// View debug information
console.log('Dashboard Store:', dashboardStore.$state)
console.log('Performance Metrics:', window.adminPanelMetrics)
console.log('Navigation History:', navigationHistory.value)
```

### Performance Profiling

```javascript
// Profile dashboard performance
const performance = usePerformanceOptimization()

performance.startTimer('dashboard-load')
// ... load dashboard
const loadTime = performance.endTimer('dashboard-load')

console.log(`Dashboard loaded in ${loadTime}ms`)
```

## Browser-Specific Issues

### Safari Issues

1. **Touch Events**: Safari requires specific touch event handling
2. **CSS Grid**: Some CSS Grid features need prefixes
3. **WebSocket**: Check WebSocket connection handling

### Chrome Issues

1. **Memory Usage**: Monitor memory usage in DevTools
2. **Performance**: Use Chrome DevTools Performance tab
3. **Network**: Check network requests in Network tab

### Firefox Issues

1. **CSS Compatibility**: Some CSS features may need prefixes
2. **JavaScript**: Check for Firefox-specific JavaScript issues
3. **Performance**: Use Firefox Developer Tools

## Getting Help

### Debug Information to Collect

When reporting issues, please include:

1. **Environment Information**
   ```bash
   php artisan about
   npm list
   ```

2. **Browser Console Errors**
   - JavaScript errors
   - Network request failures
   - Performance warnings

3. **Dashboard Configuration**
   ```php
   // Share your dashboard class (without sensitive data)
   ```

4. **Steps to Reproduce**
   - Detailed steps to reproduce the issue
   - Expected vs actual behavior
   - Screenshots or videos if applicable

### Performance Metrics

```javascript
// Collect performance metrics
const metrics = {
  loadTime: performance.getEntriesByType('navigation')[0].loadEventEnd,
  memoryUsage: performance.memory?.usedJSHeapSize,
  dashboardSwitchTime: /* measure dashboard switch time */
}
```

### Log Files

Check these log files for errors:
- `storage/logs/laravel.log`
- Browser console logs
- Network request logs
- Performance monitoring logs

---

For additional support, please check the [GitHub Issues](https://github.com/your-repo/admin-panel/issues) or create a new issue with the debug information above.
