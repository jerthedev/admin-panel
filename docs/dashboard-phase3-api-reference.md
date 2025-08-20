# Dashboard Phase 3 - API Reference

## Table of Contents

1. [Dashboard Classes](#dashboard-classes)
2. [Vue Components](#vue-components)
3. [Composables](#composables)
4. [Stores](#stores)
5. [Services](#services)
6. [Configuration](#configuration)
7. [Events](#events)
8. [Utilities](#utilities)

## Dashboard Classes

### Dashboard (Base Class)

The base dashboard class that all dashboards should extend.

```php
abstract class Dashboard
{
    /**
     * Get the dashboard name
     */
    abstract public function name(): string;

    /**
     * Get the dashboard description
     */
    public function description(): string;

    /**
     * Get the dashboard icon (Heroicons name)
     */
    public function icon(): string;

    /**
     * Get the dashboard category
     */
    public function category(): string;

    /**
     * Get the dashboard cards
     */
    abstract public function cards(): array;

    /**
     * Get dashboard metadata
     */
    public function meta(): array;

    /**
     * Check if user is authorized to see this dashboard
     */
    public function authorizedToSee(Request $request): bool;

    /**
     * Check if dashboard should show in selector
     */
    public function shouldShowOnDashboard(Request $request): bool;

    /**
     * Get dashboard URI key
     */
    public function uriKey(): string;

    /**
     * Get dashboard order
     */
    public function order(): int;

    /**
     * Get dashboard group
     */
    public function group(): ?string;

    /**
     * Get caching options
     */
    public function cacheOptions(): array;

    /**
     * Get switching options
     */
    public function switchingOptions(): array;

    /**
     * Get selector options
     */
    public function selectorOptions(): array;
}
```

### Dashboard Registry

Manages dashboard registration and retrieval.

```php
class DashboardRegistry
{
    /**
     * Register a dashboard
     */
    public static function register(string $key, Dashboard $dashboard): void;

    /**
     * Get a dashboard by key
     */
    public static function get(string $key): ?Dashboard;

    /**
     * Get all registered dashboards
     */
    public static function all(): array;

    /**
     * Get dashboards for a specific user
     */
    public static function forUser(User $user): array;

    /**
     * Check if dashboard exists
     */
    public static function exists(string $key): bool;

    /**
     * Remove a dashboard
     */
    public static function remove(string $key): void;

    /**
     * Clear all dashboards
     */
    public static function clear(): void;
}
```

## Vue Components

### DashboardSelector

Enhanced dashboard selection component with search and filtering.

```vue
<DashboardSelector
  :dashboards="Array<Dashboard>"
  :current-dashboard="Dashboard|null"
  :loading="Boolean"
  :error="String|null"
  :show-search="Boolean"
  :show-categories="Boolean"
  :show-favorites="Boolean"
  :show-recent="Boolean"
  :max-recent="Number"
  :search-placeholder="String"
  :enable-keyboard-navigation="Boolean"
  @dashboard-selected="(dashboard: Dashboard) => void"
  @dashboard-favorited="(dashboard: Dashboard, favorited: Boolean) => void"
  @search="(query: String) => void"
  @filter="(filters: Object) => void"
/>
```

**Props:**
- `dashboards`: Array of available dashboards
- `current-dashboard`: Currently selected dashboard
- `loading`: Loading state
- `error`: Error message
- `show-search`: Show search input
- `show-categories`: Show category filtering
- `show-favorites`: Show favorites section
- `show-recent`: Show recent dashboards
- `max-recent`: Maximum recent dashboards to show
- `search-placeholder`: Search input placeholder
- `enable-keyboard-navigation`: Enable keyboard shortcuts

**Events:**
- `dashboard-selected`: Emitted when dashboard is selected
- `dashboard-favorited`: Emitted when dashboard is favorited/unfavorited
- `search`: Emitted when search query changes
- `filter`: Emitted when filters change

### DashboardNavigation

Navigation component with breadcrumbs and history.

```vue
<DashboardNavigation
  :current-dashboard="Dashboard|null"
  :navigation-history="Array<Dashboard>"
  :breadcrumbs="Array<Object>"
  :show-breadcrumbs="Boolean"
  :show-back-button="Boolean"
  :show-forward-button="Boolean"
  :show-refresh-button="Boolean"
  :show-settings-button="Boolean"
  @navigate-back="() => void"
  @navigate-forward="() => void"
  @refresh="() => void"
  @settings="() => void"
/>
```

### MobileDashboardNavigation

Mobile-optimized navigation with touch gestures.

```vue
<MobileDashboardNavigation
  :dashboards="Array<Dashboard>"
  :current-dashboard="Dashboard|null"
  :show-bottom-nav="Boolean"
  :show-hamburger-menu="Boolean"
  :enable-swipe-navigation="Boolean"
  :enable-pull-to-refresh="Boolean"
  @dashboard-select="(dashboard: Dashboard) => void"
  @menu-toggle="(open: Boolean) => void"
  @refresh="() => void"
/>
```

### ResponsiveDashboardLayout

Responsive layout component for dashboard content.

```vue
<ResponsiveDashboardLayout
  :dashboard-cards="Array<Object>"
  :grid-columns="Object"
  :gap="Object"
  :enable-drag-drop="Boolean"
  :enable-resize="Boolean"
  :show-dashboard-header="Boolean"
  :show-dashboard-actions="Boolean"
  @card-moved="(cardId: String, newPosition: Number) => void"
  @card-resized="(cardId: String, newSize: Object) => void"
  @dashboard-action="(action: String, data: Any) => void"
/>
```

## Composables

### useDashboardNavigation

Provides dashboard navigation functionality.

```javascript
const navigation = useDashboardNavigation()

// Properties
navigation.currentDashboard // Ref<Dashboard|null>
navigation.navigationHistory // Ref<Array<Dashboard>>
navigation.canGoBack // ComputedRef<Boolean>
navigation.canGoForward // ComputedRef<Boolean>

// Methods
navigation.navigateTo(dashboardKey: String): Promise<void>
navigation.goBack(): void
navigation.goForward(): void
navigation.refresh(): Promise<void>
navigation.clearHistory(): void
navigation.getHistory(): Array<Dashboard>
navigation.registerShortcut(key: String, callback: Function): Function
```

### useDashboardState

Manages dashboard state and preferences.

```javascript
const state = useDashboardState()

// Properties
state.dashboards // Ref<Array<Dashboard>>
state.currentDashboard // Ref<Dashboard|null>
state.loading // Ref<Boolean>
state.error // Ref<String|null>
state.preferences // Ref<Object>

// Methods
state.loadDashboards(): Promise<void>
state.switchToDashboard(key: String): Promise<void>
state.refreshCurrentDashboard(): Promise<void>
state.updatePreferences(preferences: Object): void
state.toggleFavorite(dashboardKey: String): void
state.addToRecent(dashboardKey: String): void
```

### useMobileNavigation

Provides mobile-specific navigation features.

```javascript
const mobile = useMobileNavigation()

// Properties
mobile.isMobile // Ref<Boolean>
mobile.isTablet // Ref<Boolean>
mobile.isDesktop // Ref<Boolean>
mobile.orientation // Ref<String>
mobile.screenWidth // Ref<Number>
mobile.screenHeight // Ref<Number>

// Methods
mobile.setup(): void
mobile.cleanup(): void
mobile.lockOrientation(orientation: String): void
mobile.unlockOrientation(): void
```

### useMobileGestures

Handles touch gestures for mobile devices.

```javascript
const gestures = useMobileGestures(options)

// Methods
gestures.on(event: String, callback: Function): Function
gestures.off(event: String, callback: Function): void
gestures.setup(element: HTMLElement): void
gestures.cleanup(element: HTMLElement): void
gestures.enable(): void
gestures.disable(): void

// Events
// 'tap', 'double-tap', 'long-press', 'swipe', 'pinch', 'pull-to-refresh'
```

### usePerformanceOptimization

Provides performance optimization utilities.

```javascript
const performance = usePerformanceOptimization(options)

// Properties
performance.metrics // Ref<Object>
performance.performanceScore // ComputedRef<Number>
performance.optimizationRecommendations // ComputedRef<Array>

// Methods
performance.createLazyComponent(importFn: Function): Component
performance.optimizeImage(src: String, options: Object): String
performance.preloadChunk(chunkName: String): Promise<void>
performance.measureRenderTime(callback: Function): Promise<Number>
performance.getPerformanceReport(): Object
```

## Stores

### Dashboard Store (Pinia)

Central store for dashboard state management.

```javascript
const dashboardStore = useDashboardStore()

// State
dashboardStore.dashboards // Array<Dashboard>
dashboardStore.currentDashboard // Dashboard|null
dashboardStore.navigationHistory // Array<Dashboard>
dashboardStore.preferences // Object
dashboardStore.loading // Boolean
dashboardStore.error // String|null

// Getters
dashboardStore.availableDashboards // Array<Dashboard>
dashboardStore.favoriteDashboards // Array<Dashboard>
dashboardStore.recentDashboards // Array<Dashboard>
dashboardStore.dashboardsByCategory // Object

// Actions
dashboardStore.loadDashboards(): Promise<void>
dashboardStore.switchToDashboard(key: String): Promise<void>
dashboardStore.refreshCurrentDashboard(): Promise<void>
dashboardStore.goBack(): void
dashboardStore.goForward(): void
dashboardStore.updatePreferences(preferences: Object): void
dashboardStore.toggleFavorite(dashboardKey: String): void
dashboardStore.addToRecent(dashboardKey: String): void
dashboardStore.clearHistory(): void
```

## Services

### LazyLoadingService

Handles intelligent lazy loading of components and resources.

```javascript
class LazyLoadingService {
  constructor(options: Object)
  
  // Methods
  observe(element: HTMLElement): void
  queueLoad(loadRequest: Object): Promise<Any>
  preload(resources: Array): void
  getAnalytics(): Object
  reset(): void
  destroy(): void
}
```

### PerformanceMonitoringService

Monitors and reports performance metrics.

```javascript
class PerformanceMonitoringService {
  constructor(options: Object)
  
  // Methods
  startTimer(name: String): void
  endTimer(name: String): Number
  incrementCounter(name: String, value: Number): void
  recordHistogram(name: String, value: Number): void
  generateReport(): Object
  createAlert(severity: String, message: String): void
  destroy(): void
}
```

## Configuration

### Dashboard Configuration

```php
// config/admin-panel.php
return [
    'dashboards' => [
        'default' => 'main',
        'cache_ttl' => 300,
        'enable_favorites' => true,
        'enable_recent' => true,
        'max_recent' => 10,
        'enable_search' => true,
        'enable_categories' => true,
        'enable_keyboard_shortcuts' => true,
        'transition_duration' => 300,
        'preload_dashboards' => true,
        'performance_monitoring' => true
    ],
    
    'mobile' => [
        'enable_gestures' => true,
        'enable_pull_to_refresh' => true,
        'bottom_navigation' => true,
        'hamburger_menu' => true
    ],
    
    'performance' => [
        'lazy_loading' => true,
        'code_splitting' => true,
        'image_optimization' => true,
        'bundle_optimization' => true
    ]
];
```

---

For complete examples and usage patterns, see the [Examples](./examples/) directory.
