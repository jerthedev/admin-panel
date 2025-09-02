# Cards API Reference

## Table of Contents

1. [Card Base Class](#card-base-class)
2. [Factory Methods](#factory-methods)
3. [Meta Data Methods](#meta-data-methods)
4. [Authorization Methods](#authorization-methods)
5. [Naming Methods](#naming-methods)
6. [Component Methods](#component-methods)
7. [Serialization Methods](#serialization-methods)
8. [Vue Component API](#vue-component-api)
9. [Events](#events)
10. [Utilities](#utilities)

## Card Base Class

### JTD\AdminPanel\Cards\Card

Abstract base class for all admin panel cards.

```php
abstract class Card implements JsonSerializable
{
    public string $component;
    public string $name;
    public string $uriKey;
    public array $meta = [];
    public $canSeeCallback;
}
```

#### Properties

| Property | Type | Description |
|----------|------|-------------|
| `$component` | `string` | Vue component name |
| `$name` | `string` | Display name |
| `$uriKey` | `string` | URI identifier |
| `$meta` | `array` | Metadata array |
| `$canSeeCallback` | `callable\|null` | Authorization callback |

## Factory Methods

### make()

Create a new card instance.

```php
public static function make(): static
```

**Returns**: New card instance

**Example**:
```php
$card = MyCard::make();
```

## Meta Data Methods

### withMeta()

Set metadata for the card.

```php
public function withMeta(array $meta): static
```

**Parameters**:
- `$meta` (array): Metadata to merge

**Returns**: Card instance for chaining

**Example**:
```php
$card->withMeta([
    'title' => 'My Card',
    'data' => ['key' => 'value'],
    'refreshInterval' => 30,
]);
```

### meta()

Get all metadata.

```php
public function meta(): array
```

**Returns**: Complete metadata array

**Example**:
```php
$metadata = $card->meta();
```

### getMeta()

Get specific metadata value.

```php
public function getMeta(string $key, mixed $default = null): mixed
```

**Parameters**:
- `$key` (string): Metadata key
- `$default` (mixed): Default value if key not found

**Returns**: Metadata value or default

**Example**:
```php
$title = $card->getMeta('title', 'Default Title');
```

## Authorization Methods

### canSee()

Set authorization callback.

```php
public function canSee(callable $callback): static
```

**Parameters**:
- `$callback` (callable): Authorization function

**Returns**: Card instance for chaining

**Example**:
```php
$card->canSee(function (Request $request) {
    return $request->user()->isAdmin();
});
```

### authorize()

Check if user is authorized to see card.

```php
public function authorize(Request $request): bool
```

**Parameters**:
- `$request` (Request): HTTP request instance

**Returns**: Authorization result

**Example**:
```php
if ($card->authorize($request)) {
    // User can see card
}
```

## Naming Methods

### name()

Get card display name.

```php
public function name(): string
```

**Returns**: Human-readable card name

**Example**:
```php
$name = $card->name(); // "Analytics Card"
```

### withName()

Set custom card name.

```php
public function withName(string $name): static
```

**Parameters**:
- `$name` (string): Custom name

**Returns**: Card instance for chaining

**Example**:
```php
$card->withName('Custom Analytics');
```

### uriKey()

Get URI key for the card.

```php
public function uriKey(): string
```

**Returns**: Kebab-case URI key

**Example**:
```php
$key = $card->uriKey(); // "analytics-card"
```

## Component Methods

### component()

Get Vue component name.

```php
public function component(): string
```

**Returns**: Vue component name

**Example**:
```php
$component = $card->component(); // "AnalyticsCard"
```

### withComponent()

Set custom Vue component.

```php
public function withComponent(string $component): static
```

**Parameters**:
- `$component` (string): Component name

**Returns**: Card instance for chaining

**Example**:
```php
$card->withComponent('CustomAnalytics');
```

## Serialization Methods

### jsonSerialize()

Serialize card for JSON output.

```php
public function jsonSerialize(): array
```

**Returns**: Serialized card data

**Structure**:
```php
[
    'name' => 'Card Name',
    'component' => 'CardComponent',
    'uriKey' => 'card-uri-key',
    'meta' => [
        // All metadata
    ]
]
```

### toArray()

Convert card to array.

```php
public function toArray(): array
```

**Returns**: Card as array (same as jsonSerialize)

## Vue Component API

### Props

All card Vue components receive these props:

```javascript
props: {
  card: {
    type: Object,
    required: true,
    validator: (card) => {
      return card.name && card.component && card.meta
    }
  }
}
```

### Card Object Structure

```javascript
{
  name: 'Card Name',
  component: 'CardComponent', 
  uriKey: 'card-uri-key',
  meta: {
    title: 'Card Title',
    data: { /* card data */ },
    refreshInterval: 30,
    // ... other metadata
  }
}
```

### Component Methods

#### $emit Events

Standard events that card components can emit:

```javascript
// Card action (refresh, export, etc.)
this.$emit('card-action', {
  action: 'refresh',
  card: this.card,
  data: additionalData
})

// Card click
this.$emit('card-click', this.card)

// Card error
this.$emit('card-error', {
  card: this.card,
  error: errorMessage
})

// Card loaded
this.$emit('card-loaded', this.card)
```

### Composables

#### useCard()

Composable for card functionality:

```javascript
import { useCard } from '@/composables/useCard'

export default {
  setup(props) {
    const {
      loading,
      error,
      refresh,
      export: exportCard
    } = useCard(props.card)
    
    return {
      loading,
      error,
      refresh,
      exportCard
    }
  }
}
```

#### useCardData()

Composable for reactive card data:

```javascript
import { useCardData } from '@/composables/useCardData'

export default {
  setup(props) {
    const {
      data,
      isLoading,
      lastUpdated,
      reload
    } = useCardData(props.card)
    
    return {
      data,
      isLoading,
      lastUpdated,
      reload
    }
  }
}
```

## Events

### PHP Events

Cards can dispatch Laravel events:

```php
use JTD\AdminPanel\Events\CardViewed;
use JTD\AdminPanel\Events\CardRefreshed;
use JTD\AdminPanel\Events\CardExported;

class AnalyticsCard extends Card
{
    public function authorize(Request $request): bool
    {
        $authorized = parent::authorize($request);
        
        if ($authorized) {
            event(new CardViewed($this, $request->user()));
        }
        
        return $authorized;
    }
}
```

### JavaScript Events

Cards can listen to and emit custom events:

```javascript
// Listen for global card events
this.$bus.on('card:refresh-all', () => {
  this.refresh()
})

// Emit card-specific events
this.$bus.emit('card:data-updated', {
  cardId: this.card.uriKey,
  data: newData
})
```

## Utilities

### Card Registry

Access registered cards:

```php
use JTD\AdminPanel\Support\CardRegistry;

// Get all registered cards
$cards = CardRegistry::all();

// Get specific card
$card = CardRegistry::get('analytics-card');

// Register card
CardRegistry::register('my-card', MyCard::class);
```

### Card Factory

Create cards dynamically:

```php
use JTD\AdminPanel\Support\CardFactory;

// Create card by class name
$card = CardFactory::make(AnalyticsCard::class);

// Create card with metadata
$card = CardFactory::make(AnalyticsCard::class, [
    'title' => 'Custom Title',
    'data' => $customData,
]);
```

### Card Validator

Validate card structure:

```php
use JTD\AdminPanel\Support\CardValidator;

// Validate card instance
$isValid = CardValidator::validate($card);

// Validate card array
$isValid = CardValidator::validateArray($cardArray);

// Get validation errors
$errors = CardValidator::getErrors($card);
```

### Card Transformer

Transform cards for different contexts:

```php
use JTD\AdminPanel\Support\CardTransformer;

// Transform for API response
$apiData = CardTransformer::forApi($card);

// Transform for dashboard
$dashboardData = CardTransformer::forDashboard($card, $request);

// Transform collection
$transformed = CardTransformer::collection($cards);
```

## Error Handling

### PHP Exceptions

Cards can throw specific exceptions:

```php
use JTD\AdminPanel\Exceptions\CardException;
use JTD\AdminPanel\Exceptions\CardAuthorizationException;
use JTD\AdminPanel\Exceptions\CardDataException;

class MyCard extends Card
{
    protected function getData(): array
    {
        try {
            return $this->loadData();
        } catch (Exception $e) {
            throw new CardDataException(
                "Failed to load card data: {$e->getMessage()}",
                previous: $e
            );
        }
    }
}
```

### JavaScript Error Handling

Handle errors in Vue components:

```javascript
export default {
  data() {
    return {
      error: null,
      loading: false
    }
  },
  
  methods: {
    async loadData() {
      this.loading = true
      this.error = null
      
      try {
        const response = await this.$http.get(`/api/cards/${this.card.uriKey}/data`)
        this.data = response.data
      } catch (error) {
        this.error = error.message
        this.$emit('card-error', {
          card: this.card,
          error: error.message
        })
      } finally {
        this.loading = false
      }
    }
  }
}
```

## Type Definitions

### TypeScript Interfaces

```typescript
interface Card {
  name: string
  component: string
  uriKey: string
  meta: CardMeta
}

interface CardMeta {
  title?: string
  subtitle?: string
  data?: any
  loading?: boolean
  error?: string | null
  refreshInterval?: number
  autoRefresh?: boolean
  exportable?: boolean
  [key: string]: any
}

interface CardAction {
  action: string
  card: Card
  data?: any
}

interface CardError {
  card: Card
  error: string
  timestamp: Date
}
```

## Migration Guide

### From Nova Cards

AdminPanel cards are 100% compatible with Nova cards. To migrate:

1. **Change namespace**:
```php
// Before (Nova)
use Laravel\Nova\Card;

// After (AdminPanel)
use JTD\AdminPanel\Cards\Card;
```

2. **Update component path** (if needed):
```php
// Nova cards work as-is, but you can optimize:
$this->withComponent('OptimizedComponent');
```

3. **Enhanced features** (optional):
```php
// Take advantage of new features
$this->withMeta([
    'refreshInterval' => 30,
    'exportable' => true,
    'fullscreen' => true,
]);
```

## Performance Considerations

### Caching

```php
protected function getData(): array
{
    return Cache::remember(
        "card-{$this->uriKey}-data",
        now()->addMinutes(5),
        fn() => $this->loadExpensiveData()
    );
}
```

### Lazy Loading

```php
$this->withMeta([
    'lazy' => true,
    'loadUrl' => route('api.card.data', $this->uriKey),
]);
```

### Database Optimization

```php
protected function getUsers(): Collection
{
    return User::select(['id', 'name', 'email'])
        ->with('profile:user_id,avatar')
        ->limit(100)
        ->get();
}
```

## Security

### Authorization

Always implement proper authorization:

```php
public static function make(): static
{
    return parent::make()->canSee(function (Request $request) {
        return $request->user()->can('view-card-data');
    });
}
```

### Data Sanitization

```php
protected function sanitizeInput(array $input): array
{
    return [
        'query' => Str::limit(strip_tags($input['query'] ?? ''), 100),
        'filters' => array_intersect_key(
            $input['filters'] ?? [],
            array_flip(['status', 'type', 'date'])
        ),
    ];
}
```

## Testing

### Unit Testing

```php
use Tests\TestCase;
use App\Admin\Cards\MyCard;

class MyCardTest extends TestCase
{
    public function test_card_creation()
    {
        $card = MyCard::make();
        
        $this->assertInstanceOf(MyCard::class, $card);
        $this->assertEquals('My Card', $card->name());
        $this->assertEquals('my-card', $card->uriKey());
    }
    
    public function test_card_authorization()
    {
        $card = MyCard::make();
        $user = User::factory()->create();
        $request = Request::create('/', 'GET');
        $request->setUserResolver(fn() => $user);
        
        $this->assertTrue($card->authorize($request));
    }
}
```

### Integration Testing

```php
public function test_card_api_response()
{
    $response = $this->actingAs($this->user)
        ->getJson('/api/cards/my-card/data');
    
    $response->assertOk()
        ->assertJsonStructure([
            'name',
            'component',
            'meta' => [
                'title',
                'data',
            ]
        ]);
}
```
