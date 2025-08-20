# Dashboard Components

This directory contains Vue.js components for the Admin Panel dashboard system, providing a complete Nova v5-compatible dashboard interface.

## Components Overview

### 1. Dashboard.vue
The main dashboard component that renders dashboard content, cards, and handles dashboard-level interactions.

**Props:**
- `dashboard` (Object, required): Dashboard configuration object
- `cards` (Array): Array of card configurations
- `availableDashboards` (Array): List of available dashboards for switching

**Features:**
- Dashboard header with title and description
- Refresh functionality for dashboards that support it
- Dashboard selector for switching between multiple dashboards
- Responsive card grid layout
- Loading, error, and empty states
- Keyboard navigation support

**Usage:**
```vue
<Dashboard
  :dashboard="dashboard"
  :cards="cards"
  :available-dashboards="availableDashboards"
/>
```

### 2. DashboardCard.vue
A flexible card component that can render different types of dashboard cards dynamically.

**Props:**
- `card` (Object, required): Card configuration object
- `dashboard` (Object, required): Parent dashboard object

**Features:**
- Dynamic component loading based on card type
- Card header with title, subtitle, and actions
- Loading and error states
- Card footer with metadata and links
- Action handling and event emission
- Fallback rendering for unknown card types

**Card Configuration:**
```javascript
{
  component: 'MetricCard',
  title: 'Total Users',
  subtitle: 'Active users this month',
  actions: [
    { name: 'refresh', type: 'primary', icon: 'RefreshIcon' }
  ],
  meta: {
    'Last Updated': '2 minutes ago'
  },
  links: [
    { label: 'View Details', url: '/admin/users', external: false }
  ],
  // Card-specific props
  value: 1234,
  format: 'number'
}
```

### 3. DashboardSelector.vue
A dropdown component for switching between multiple dashboards.

**Props:**
- `dashboards` (Array, required): Available dashboards
- `currentDashboard` (Object, required): Currently active dashboard
- `showFooter` (Boolean): Show dashboard count in footer

**Features:**
- Dropdown interface with dashboard list
- Current dashboard highlighting
- Dashboard icons and descriptions
- Badge support for dashboard status
- Keyboard navigation (arrow keys, enter, escape)
- Click outside to close
- Accessibility support (ARIA attributes)

**Events:**
- `dashboard-changed`: Emitted when user selects a different dashboard

### 4. Cards/BaseCard.vue
Base card component that other card types can extend.

**Props:**
- `dashboard` (Object, required): Parent dashboard object

**Features:**
- Basic card structure
- Event handling setup
- Slot for custom content

### 5. Cards/MetricCard.vue
A specialized card for displaying metrics and KPIs.

**Props:**
- `value` (Number|String, required): The metric value
- `label` (String): Metric label/name
- `description` (String): Metric description
- `format` (String): Value format ('number', 'currency', 'percentage')
- `currency` (String): Currency code for currency format
- `change` (Number): Change value for trend indication
- `changePeriod` (String): Period for the change (e.g., "vs last month")
- `target` (Number): Target value
- `chartData` (Array): Data points for sparkline chart
- `chartColor` (String): Color for the chart line
- `lastUpdated` (String|Date): Last update timestamp

**Features:**
- Formatted value display (number, currency, percentage)
- Change indicators with up/down arrows and colors
- Mini sparkline charts
- Target comparison
- Last updated timestamp
- Responsive design

**Usage:**
```vue
<MetricCard
  :dashboard="dashboard"
  :value="1234"
  label="Total Sales"
  format="currency"
  currency="USD"
  :change="15.5"
  change-period="vs last month"
  :target="1500"
  :chart-data="[100, 120, 110, 140, 130, 160, 150]"
  chart-color="#10B981"
  last-updated="2024-01-15T10:30:00Z"
/>
```

## Styling

All components use Tailwind CSS classes for styling and support both light and dark themes. The components are designed to be:

- **Responsive**: Work well on desktop, tablet, and mobile devices
- **Accessible**: Include proper ARIA attributes and keyboard navigation
- **Themeable**: Support light/dark mode switching
- **Consistent**: Follow the admin panel design system

## State Management

Components use Vue 3's Composition API with:
- `ref()` for reactive state
- `computed()` for derived state
- `watch()` for side effects
- Proper event emission for parent communication

## Error Handling

All components include comprehensive error handling:
- Loading states during async operations
- Error states with retry functionality
- Graceful fallbacks for missing data
- Console logging for debugging

## Accessibility

Components follow accessibility best practices:
- Semantic HTML structure
- ARIA attributes for screen readers
- Keyboard navigation support
- Focus management
- Color contrast compliance

## Testing

Components are designed to be testable with:
- `data-testid` attributes for test targeting
- Predictable event emission
- Isolated component logic
- Mock-friendly external dependencies

## Extension

To create custom card types:

1. Create a new component in the `Cards/` directory
2. Extend `BaseCard.vue` or create from scratch
3. Follow the card prop interface
4. Emit `action` and `error` events as needed
5. Register the component name in your dashboard configuration

Example custom card:
```vue
<template>
  <div class="custom-card">
    <!-- Your custom content -->
  </div>
</template>

<script>
export default {
  name: 'CustomCard',
  props: {
    dashboard: { type: Object, required: true },
    // Your custom props
  },
  emits: ['action', 'error']
}
</script>
```

## Integration

These components integrate with:
- **Inertia.js**: For server-side rendering and navigation
- **Laravel Admin Panel**: Backend dashboard system
- **Tailwind CSS**: For styling and theming
- **Vue 3**: Composition API and modern Vue features
