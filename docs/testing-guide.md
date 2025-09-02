# Testing Guide

## Overview

AdminPanel provides comprehensive testing support for dashboards, cards, metrics, and resources. This guide covers testing strategies, tools, and best practices.

## Testing Architecture

### Test Types

1. **Unit Tests** - Test individual components in isolation
2. **Integration Tests** - Test component interactions
3. **Feature Tests** - Test complete user workflows
4. **Browser Tests** - Test UI interactions with real browsers
5. **API Tests** - Test API endpoints and responses

### Test Structure

```
tests/
├── Unit/
│   ├── Cards/
│   ├── Dashboards/
│   ├── Metrics/
│   └── Resources/
├── Feature/
│   ├── Dashboard/
│   ├── Card/
│   └── API/
├── Integration/
│   ├── Cards/
│   └── Dashboards/
└── Browser/
    ├── Dashboard/
    └── Card/
```

## Unit Testing

### Testing Cards

```php
<?php

namespace Tests\Unit\Cards;

use Tests\TestCase;
use App\Admin\Cards\StatsCard;
use Illuminate\Http\Request;
use Illuminate\Foundation\Testing\RefreshDatabase;

class StatsCardTest extends TestCase
{
    use RefreshDatabase;

    public function test_card_creation()
    {
        $card = StatsCard::make();

        $this->assertInstanceOf(StatsCard::class, $card);
        $this->assertEquals('Stats Card', $card->name());
        $this->assertEquals('stats-card', $card->uriKey());
    }

    public function test_card_meta_data()
    {
        $card = StatsCard::make();
        $meta = $card->meta();

        $this->assertIsArray($meta);
        $this->assertArrayHasKey('title', $meta);
        $this->assertArrayHasKey('data', $meta);
    }

    public function test_card_authorization()
    {
        $user = User::factory()->create();
        $request = Request::create('/', 'GET');
        $request->setUserResolver(fn() => $user);

        $card = StatsCard::make();

        $this->assertTrue($card->authorize($request));
    }

    public function test_card_data_loading()
    {
        // Create test data
        User::factory()->count(10)->create();
        Order::factory()->count(5)->create();

        $card = StatsCard::make();
        $data = $card->meta()['data'];

        $this->assertEquals(10, $data['users']);
        $this->assertEquals(5, $data['orders']);
    }
}
```

### Testing Dashboards

```php
<?php

namespace Tests\Unit\Dashboards;

use Tests\TestCase;
use App\Admin\Dashboards\AnalyticsDashboard;
use Illuminate\Http\Request;

class AnalyticsDashboardTest extends TestCase
{
    public function test_dashboard_creation()
    {
        $dashboard = new AnalyticsDashboard();

        $this->assertEquals('Analytics', $dashboard->name());
        $this->assertEquals('analytics', $dashboard->uriKey());
        $this->assertIsArray($dashboard->cards());
    }

    public function test_dashboard_authorization()
    {
        $user = User::factory()->admin()->create();
        $request = Request::create('/', 'GET');
        $request->setUserResolver(fn() => $user);

        $dashboard = new AnalyticsDashboard();

        $this->assertTrue($dashboard->authorizedToSee($request));
    }

    public function test_dashboard_cards()
    {
        $dashboard = new AnalyticsDashboard();
        $cards = $dashboard->cards();

        $this->assertNotEmpty($cards);
        $this->assertContainsOnlyInstancesOf(Card::class, $cards);
    }
}
```

### Testing Metrics

```php
<?php

namespace Tests\Unit\Metrics;

use Tests\TestCase;
use App\Metrics\UserCount;
use Illuminate\Http\Request;

class UserCountTest extends TestCase
{
    public function test_metric_calculation()
    {
        User::factory()->count(15)->create();

        $metric = new UserCount();
        $request = Request::create('/', 'GET', ['range' => 30]);
        
        $result = $metric->calculate($request);

        $this->assertInstanceOf(ValueResult::class, $result);
        $this->assertEquals(15, $result->value);
    }

    public function test_metric_caching()
    {
        Cache::shouldReceive('remember')
            ->once()
            ->andReturn(new ValueResult(10));

        $metric = new UserCount();
        $request = Request::create('/', 'GET');
        
        $result = $metric->calculate($request);

        $this->assertEquals(10, $result->value);
    }
}
```

## Integration Testing

### Card Integration Tests

```php
<?php

namespace Tests\Integration\Cards;

use Tests\TestCase;
use App\Admin\Cards\AnalyticsCard;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AnalyticsCardIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_card_with_real_data()
    {
        // Create test data
        $users = User::factory()->count(100)->create();
        $orders = Order::factory()->count(50)->create();

        $card = AnalyticsCard::make();
        $data = $card->meta()['data'];

        $this->assertEquals(100, $data['total_users']);
        $this->assertEquals(50, $data['total_orders']);
        $this->assertGreaterThan(0, $data['revenue']);
    }

    public function test_card_performance()
    {
        // Create large dataset
        User::factory()->count(1000)->create();

        $start = microtime(true);
        $card = AnalyticsCard::make();
        $card->meta();
        $end = microtime(true);

        $executionTime = $end - $start;
        $this->assertLessThan(1.0, $executionTime, 'Card should load in under 1 second');
    }
}
```

### Dashboard Integration Tests

```php
<?php

namespace Tests\Integration\Dashboards;

use Tests\TestCase;
use App\Admin\Dashboards\MainDashboard;

class MainDashboardIntegrationTest extends TestCase
{
    public function test_dashboard_with_all_cards()
    {
        $dashboard = new MainDashboard();
        $cards = $dashboard->cards();

        foreach ($cards as $card) {
            $this->assertInstanceOf(Card::class, $card);
            $this->assertTrue($card->authorize(request()));
            $this->assertIsArray($card->meta());
        }
    }

    public function test_dashboard_serialization()
    {
        $dashboard = new MainDashboard();
        $serialized = $dashboard->jsonSerialize();

        $this->assertArrayHasKey('name', $serialized);
        $this->assertArrayHasKey('cards', $serialized);
        $this->assertIsArray($serialized['cards']);
    }
}
```

## Feature Testing

### Dashboard Feature Tests

```php
<?php

namespace Tests\Feature\Dashboard;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_view_dashboard()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->get('/admin');

        $response->assertOk()
            ->assertViewIs('admin-panel::dashboard')
            ->assertViewHas('dashboard');
    }

    public function test_unauthorized_user_cannot_view_dashboard()
    {
        $response = $this->get('/admin');

        $response->assertRedirect('/login');
    }

    public function test_dashboard_loads_cards()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->get('/admin');

        $response->assertOk();
        
        $dashboard = $response->viewData('dashboard');
        $this->assertNotEmpty($dashboard['cards']);
    }
}
```

### Card Feature Tests

```php
<?php

namespace Tests\Feature\Card;

use Tests\TestCase;
use App\Admin\Cards\StatsCard;

class CardTest extends TestCase
{
    public function test_card_api_endpoint()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->getJson('/api/admin/cards/stats-card');

        $response->assertOk()
            ->assertJsonStructure([
                'name',
                'component',
                'meta' => [
                    'title',
                    'data',
                ],
            ]);
    }

    public function test_card_refresh_endpoint()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/admin/cards/stats-card/refresh');

        $response->assertOk()
            ->assertJsonHas('meta.data');
    }
}
```

## Browser Testing

### Dusk Tests

```php
<?php

namespace Tests\Browser\Dashboard;

use Tests\DuskTestCase;
use Laravel\Dusk\Browser;

class DashboardTest extends DuskTestCase
{
    public function test_user_can_navigate_dashboard()
    {
        $user = User::factory()->create();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/admin')
                ->assertSee('Dashboard')
                ->assertPresent('.dashboard-cards')
                ->assertPresent('.card');
        });
    }

    public function test_card_interactions()
    {
        $user = User::factory()->create();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/admin')
                ->click('.card .refresh-button')
                ->waitFor('.loading-spinner')
                ->waitUntilMissing('.loading-spinner')
                ->assertPresent('.card-data');
        });
    }
}
```

### Vue Component Tests

```javascript
// tests/js/components/Cards/StatsCard.test.js
import { mount } from '@vue/test-utils'
import StatsCard from '@/components/Cards/StatsCard.vue'

describe('StatsCard', () => {
  const mockCard = {
    name: 'Stats Card',
    component: 'StatsCard',
    meta: {
      title: 'Statistics',
      data: {
        users: 100,
        orders: 50,
        revenue: 10000
      }
    }
  }

  it('renders card title', () => {
    const wrapper = mount(StatsCard, {
      props: { card: mockCard }
    })

    expect(wrapper.text()).toContain('Statistics')
  })

  it('displays metrics', () => {
    const wrapper = mount(StatsCard, {
      props: { card: mockCard }
    })

    expect(wrapper.text()).toContain('100')
    expect(wrapper.text()).toContain('50')
    expect(wrapper.text()).toContain('10000')
  })

  it('handles refresh action', async () => {
    const wrapper = mount(StatsCard, {
      props: { card: mockCard }
    })

    await wrapper.find('.refresh-button').trigger('click')

    expect(wrapper.emitted('card-action')).toBeTruthy()
    expect(wrapper.emitted('card-action')[0][0]).toEqual({
      action: 'refresh',
      card: mockCard
    })
  })
})
```

## API Testing

### Card API Tests

```php
<?php

namespace Tests\Feature\API;

use Tests\TestCase;
use App\Admin\Cards\StatsCard;

class CardAPITest extends TestCase
{
    public function test_get_card_data()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->getJson('/api/admin/cards/stats-card/data');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'users',
                    'orders',
                    'revenue',
                ],
                'meta' => [
                    'last_updated',
                ],
            ]);
    }

    public function test_refresh_card()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/admin/cards/stats-card/refresh');

        $response->assertOk()
            ->assertJsonHas('data')
            ->assertJsonHas('meta.refreshed_at');
    }

    public function test_unauthorized_access()
    {
        $response = $this->getJson('/api/admin/cards/stats-card/data');

        $response->assertUnauthorized();
    }
}
```

## Test Utilities

### Test Factories

```php
// database/factories/CardFactory.php
<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use JTD\AdminPanel\Cards\Card;

class CardFactory extends Factory
{
    public function definition()
    {
        return [
            'name' => $this->faker->words(2, true),
            'component' => $this->faker->word(),
            'meta' => [
                'title' => $this->faker->sentence(),
                'data' => [
                    'value' => $this->faker->numberBetween(1, 1000),
                ],
            ],
        ];
    }
}
```

### Test Helpers

```php
// tests/TestHelpers/CardTestHelper.php
<?php

namespace Tests\TestHelpers;

use JTD\AdminPanel\Cards\Card;

class CardTestHelper
{
    public static function createMockCard(array $meta = []): Card
    {
        return new class($meta) extends Card {
            public function __construct(array $meta = [])
            {
                parent::__construct();
                $this->withMeta(array_merge([
                    'title' => 'Test Card',
                    'data' => ['test' => true],
                ], $meta));
            }
        };
    }

    public static function assertCardStructure(array $card): void
    {
        $this->assertArrayHasKey('name', $card);
        $this->assertArrayHasKey('component', $card);
        $this->assertArrayHasKey('meta', $card);
        $this->assertIsArray($card['meta']);
    }
}
```

## Performance Testing

### Load Testing

```php
<?php

namespace Tests\Performance;

use Tests\TestCase;
use App\Admin\Cards\AnalyticsCard;

class CardPerformanceTest extends TestCase
{
    public function test_card_load_time()
    {
        // Create large dataset
        User::factory()->count(10000)->create();

        $start = microtime(true);
        
        $card = AnalyticsCard::make();
        $data = $card->meta();
        
        $end = microtime(true);
        $executionTime = $end - $start;

        $this->assertLessThan(2.0, $executionTime, 'Card should load in under 2 seconds');
    }

    public function test_concurrent_card_requests()
    {
        $user = User::factory()->create();
        $promises = [];

        // Simulate 10 concurrent requests
        for ($i = 0; $i < 10; $i++) {
            $promises[] = $this->actingAs($user)
                ->getJson('/api/admin/cards/analytics-card/data');
        }

        foreach ($promises as $response) {
            $response->assertOk();
        }
    }
}
```

## Test Configuration

### PHPUnit Configuration

```xml
<!-- phpunit.xml -->
<phpunit>
    <testsuites>
        <testsuite name="Unit">
            <directory suffix="Test.php">./tests/Unit</directory>
        </testsuite>
        <testsuite name="Feature">
            <directory suffix="Test.php">./tests/Feature</directory>
        </testsuite>
        <testsuite name="Integration">
            <directory suffix="Test.php">./tests/Integration</directory>
        </testsuite>
        <testsuite name="Browser">
            <directory suffix="Test.php">./tests/Browser</directory>
        </testsuite>
    </testsuites>
    
    <coverage>
        <include>
            <directory suffix=".php">./src</directory>
            <directory suffix=".php">./app/Admin</directory>
        </include>
    </coverage>
</phpunit>
```

### Jest Configuration

```javascript
// jest.config.js
module.exports = {
  testEnvironment: 'jsdom',
  moduleFileExtensions: ['js', 'vue'],
  transform: {
    '^.+\\.vue$': '@vue/vue3-jest',
    '^.+\\.js$': 'babel-jest'
  },
  moduleNameMapping: {
    '^@/(.*)$': '<rootDir>/resources/js/$1'
  },
  collectCoverageFrom: [
    'resources/js/components/**/*.{js,vue}',
    '!resources/js/components/**/*.test.{js,vue}'
  ]
}
```

## Continuous Integration

### GitHub Actions

```yaml
# .github/workflows/tests.yml
name: Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    
    steps:
    - uses: actions/checkout@v2
    
    - name: Setup PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: '8.1'
        
    - name: Install dependencies
      run: composer install
      
    - name: Run tests
      run: vendor/bin/phpunit
      
    - name: Run browser tests
      run: php artisan dusk
      
    - name: Upload coverage
      uses: codecov/codecov-action@v1
```

## Best Practices

### Test Organization

1. **Group related tests** in the same file
2. **Use descriptive test names** that explain what is being tested
3. **Follow AAA pattern** (Arrange, Act, Assert)
4. **Keep tests independent** and isolated

### Test Data

1. **Use factories** for creating test data
2. **Clean up after tests** with RefreshDatabase
3. **Use realistic test data** that matches production
4. **Avoid hardcoded values** in assertions

### Performance

1. **Test performance** of expensive operations
2. **Use database transactions** for faster tests
3. **Mock external services** to avoid network calls
4. **Profile slow tests** and optimize them

### Coverage

1. **Aim for high test coverage** (>80%)
2. **Test edge cases** and error conditions
3. **Test authorization** and security features
4. **Include integration tests** for critical paths

## Troubleshooting

### Common Issues

1. **Database not refreshing**: Use RefreshDatabase trait
2. **Authentication issues**: Use actingAs() helper
3. **Vue component tests failing**: Check component imports
4. **Browser tests timing out**: Increase wait times

### Debug Tools

```php
// Enable query logging in tests
DB::enableQueryLog();
$queries = DB::getQueryLog();
dump($queries);

// Debug card data
$card = StatsCard::make();
dump($card->meta());

// Debug API responses
$response = $this->getJson('/api/cards/stats');
dump($response->json());
```

## Next Steps

- **[Performance Testing](performance/testing.md)** - Advanced performance testing
- **[Security Testing](security/testing.md)** - Security test strategies
- **[E2E Testing](testing/e2e.md)** - End-to-end testing guide
- **[Test Automation](testing/automation.md)** - CI/CD integration
