# Vue Card Component

This directory contains the Vue Card component and related implementations for the JTD Admin Panel package.

## Overview

The Vue Card component provides Nova-compatible functionality for rendering dashboard cards with support for:

- **PHP Integration**: Seamless integration with PHP Card base class
- **Meta Data Rendering**: Dynamic content from PHP `withMeta()` method
- **Theming**: Dark/light theme support via admin store
- **Interactive Features**: Click events, loading states, refresh functionality
- **Slot System**: Flexible content customization
- **Responsive Design**: Mobile-friendly layouts

## Base Card Component

The `Card.vue` component (`resources/js/components/Cards/Card.vue`) provides the foundation for all admin panel cards.

### Key Features

- **100% Nova API Compatibility**: Matches Laravel Nova card behavior
- **PHP Data Integration**: Renders data from PHP `Card::jsonSerialize()`
- **Automatic Theming**: Responds to admin store theme changes
- **Loading States**: Built-in loading overlay and spinner
- **Flexible Slots**: Header, body, footer, and actions slots
- **Event System**: Click and refresh event handling

### Props

```vue
<Card
  :card="cardData"
  variant="default"
  padding="md"
  rounded="md"
  :hoverable="false"
  :clickable="false"
  :loading="false"
  :refreshable="false"
  @click="handleClick"
  @refresh="handleRefresh"
/>
```

#### Card Prop Structure

The `card` prop expects data from PHP `Card::jsonSerialize()`:

```javascript
{
  name: 'Dashboard Stats',
  component: 'DashboardStatsCard',
  uriKey: 'dashboard-stats',
  meta: {
    title: 'Key Metrics',
    description: 'Overview of statistics',
    icon: 'ChartBarIcon',
    refreshable: true,
    data: { /* custom data */ }
  }
}
```

#### Styling Props

- **variant**: `'default' | 'bordered' | 'elevated' | 'flat'`
- **padding**: `'none' | 'sm' | 'md' | 'lg' | 'xl'`
- **rounded**: `'none' | 'sm' | 'md' | 'lg' | 'xl' | 'full'`

#### Interactive Props

- **hoverable**: Adds hover effects
- **clickable**: Makes card clickable with cursor pointer
- **loading**: Shows loading overlay
- **refreshable**: Enables refresh functionality

### Slots

```vue
<Card :card="cardData">
  <!-- Header slot -->
  <template #header>
    <div>Custom header content</div>
  </template>

  <!-- Actions slot -->
  <template #actions>
    <button>Action Button</button>
  </template>

  <!-- Default slot (body) -->
  <div>Custom card content</div>

  <!-- Footer slot -->
  <template #footer>
    <div>Custom footer content</div>
  </template>
</Card>
```

## Usage Examples

### Basic Card

```vue
<template>
  <Card :card="statsCard" />
</template>

<script setup>
import Card from '@/components/Cards/Card.vue'

const statsCard = {
  name: 'User Statistics',
  component: 'UserStatsCard',
  uriKey: 'user-stats',
  meta: {
    title: 'User Metrics',
    description: 'Current user statistics',
    data: {
      totalUsers: 1250,
      activeUsers: 890
    }
  }
}
</script>
```

### Interactive Card

```vue
<template>
  <Card
    :card="clickableCard"
    clickable
    hoverable
    @click="handleCardClick"
  />
</template>

<script setup>
const handleCardClick = (event, card) => {
  console.log('Card clicked:', card.name)
  // Navigate to details or open modal
}
</script>
```

### Card with Custom Content

```vue
<template>
  <Card :card="chartCard">
    <template #actions>
      <button @click="refreshData" :disabled="loading">
        Refresh
      </button>
    </template>

    <div class="chart-container">
      <BarChart :data="chartCard.meta.data" />
    </div>

    <template #footer>
      <div class="text-sm text-gray-500">
        Last updated: {{ lastUpdated }}
      </div>
    </template>
  </Card>
</template>
```

### Loading State

```vue
<template>
  <Card
    :card="loadingCard"
    :loading="isLoading"
    refreshable
    @refresh="handleRefresh"
  />
</template>

<script setup>
import { ref } from 'vue'

const isLoading = ref(false)

const handleRefresh = async (card) => {
  isLoading.value = true
  try {
    // Fetch new data
    await fetchCardData(card.uriKey)
  } finally {
    isLoading.value = false
  }
}
</script>
```

## Testing

The Card component includes comprehensive test coverage:

### Unit Tests (`tests/components/Cards/Card.test.js`)
- Tests all props and their validation
- Verifies slot rendering and content
- Tests interactive features and events
- Validates styling variants and theming
- **25 tests, 100% component coverage**

### Integration Tests (`tests/Integration/Cards/components/CardIntegration.test.js`)
- Tests PHP data structure integration
- Validates Laravel request context handling
- Tests complex meta data scenarios
- Verifies performance with large datasets
- **13 tests covering PHP ↔ Vue integration**

### E2E Tests (`tests/e2e/Cards/components/card.spec.js`)
- Playwright tests for real browser scenarios
- Tests user interactions and workflows
- Validates responsive design
- Tests authorization and theming
- **Ready for CI/CD pipeline**

## Running Tests

```bash
# Run unit tests
npm test tests/components/Cards/Card.test.js

# Run integration tests
npm test tests/Integration/Cards/components/CardIntegration.test.js

# Run all Card tests
npm test tests/components/Cards/ tests/Integration/Cards/components/

# Run with coverage
npm test tests/components/Cards/Card.test.js -- --coverage
```

## PHP Integration

The Vue Card component is designed to work seamlessly with the PHP Card base class:

### Data Flow

1. **PHP**: Card created with `Card::make()->withMeta($data)`
2. **PHP**: Card serialized via `Card::jsonSerialize()`
3. **Laravel**: Data passed to Vue via Inertia or API
4. **Vue**: Card component renders data with full interactivity

### Authorization

```php
// PHP
$card = StatsCard::make()->canSee(function (Request $request) {
    return $request->user()->isAdmin();
});

// Vue automatically respects authorization from PHP
```

### Meta Data

```php
// PHP
$card->withMeta([
    'title' => 'Dashboard Stats',
    'refreshInterval' => 60,
    'data' => ['users' => 1250]
]);

// Vue accesses via card.meta.title, card.meta.data.users
```

## Dark Theme Support

The Card component automatically responds to theme changes:

```vue
<!-- Light theme -->
<div class="bg-white border-gray-200">

<!-- Dark theme -->
<div class="dark:bg-gray-800 dark:border-gray-700">
```

Theme is managed by the admin store and applied via CSS classes.

## Best Practices

1. **Use PHP Data**: Always pass data from PHP Card classes
2. **Validate Props**: Ensure card prop has required structure
3. **Handle Loading**: Show loading states for async operations
4. **Responsive Design**: Test cards on mobile devices
5. **Accessibility**: Include proper ARIA labels and keyboard navigation
6. **Performance**: Use Vue's reactivity efficiently for large datasets

## Architecture

```
resources/js/components/Cards/
├── Card.vue              # Base Vue component
├── README.md             # This documentation
└── [future components]   # Specialized card components

tests/
├── components/Cards/           # Unit tests
├── Integration/Cards/components/ # Integration tests
└── e2e/Cards/components/       # E2E tests
```

## Contributing

When adding new card functionality:

1. Extend the base Card component or create specialized components
2. Add comprehensive tests (Unit, Integration, E2E)
3. Update documentation
4. Ensure PHP ↔ Vue compatibility
5. Test dark theme support
6. Verify responsive design
