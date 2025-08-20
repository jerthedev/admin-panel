<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Unit\Resources;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use JTD\AdminPanel\Cards\Card;
use JTD\AdminPanel\Fields\Text;
use JTD\AdminPanel\Resources\Resource;
use JTD\AdminPanel\Tests\TestCase;

/**
 * Resource Cards Unit Tests.
 *
 * Tests the cards functionality in the Resource base class.
 */
class ResourceCardsTest extends TestCase
{
    public function test_resource_cards_method_returns_empty_array_by_default(): void
    {
        $resource = new TestResourceWithCards();
        $request = Request::create('/');

        $cards = $resource->cards($request);

        $this->assertIsArray($cards);
        $this->assertEmpty($cards);
    }

    public function test_resource_can_return_cards(): void
    {
        $resource = new TestResourceWithCustomCards();
        $request = Request::create('/');

        $cards = $resource->cards($request);

        $this->assertIsArray($cards);
        $this->assertCount(2, $cards);
        $this->assertInstanceOf(TestResourceCard::class, $cards[0]);
        $this->assertInstanceOf(AnotherTestResourceCard::class, $cards[1]);
    }

    public function test_resource_cards_can_be_context_aware(): void
    {
        $resource = new TestResourceWithContextCards();
        
        // Test index context
        $indexRequest = Request::create('/resources/test');
        $indexCards = $resource->cards($indexRequest);
        
        $this->assertCount(1, $indexCards);
        $this->assertEquals('Index Card', $indexCards[0]->name());
        
        // Test detail context
        $detailRequest = Request::create('/resources/test/1');
        $detailCards = $resource->cards($detailRequest);
        
        $this->assertCount(1, $detailCards);
        $this->assertEquals('Detail Card', $detailCards[0]->name());
    }

    public function test_resource_cards_can_access_resource_model(): void
    {
        $model = new TestModel();
        $model->id = 1;
        $model->name = 'Test Model';
        
        $resource = new TestResourceWithModelAwareCards($model);
        $request = Request::create('/');

        $cards = $resource->cards($request);

        $this->assertCount(1, $cards);
        $card = $cards[0];
        $this->assertEquals('Model Card for Test Model', $card->name());
    }

    public function test_resource_cards_support_authorization(): void
    {
        $resource = new TestResourceWithAuthCards();
        $request = Request::create('/');

        $cards = $resource->cards($request);

        $this->assertCount(2, $cards);
        $this->assertInstanceOf(TestResourceCard::class, $cards[0]);
        $this->assertInstanceOf(UnauthorizedResourceCard::class, $cards[1]);
    }
}

/**
 * Test resource class with default cards behavior.
 */
class TestResourceWithCards extends Resource
{
    public static string $model = TestModel::class;

    public function fields(Request $request): array
    {
        return [Text::make('Name')];
    }
}

/**
 * Test resource class with custom cards.
 */
class TestResourceWithCustomCards extends Resource
{
    public static string $model = TestModel::class;

    public function fields(Request $request): array
    {
        return [Text::make('Name')];
    }

    public function cards(Request $request): array
    {
        return [
            new TestResourceCard(),
            new AnotherTestResourceCard(),
        ];
    }
}

/**
 * Test resource class with context-aware cards.
 */
class TestResourceWithContextCards extends Resource
{
    public static string $model = TestModel::class;

    public function fields(Request $request): array
    {
        return [Text::make('Name')];
    }

    public function cards(Request $request): array
    {
        $path = $request->path();
        
        if (str_contains($path, '/1')) {
            // Detail view
            $card = new TestResourceCard();
            $card->withName('Detail Card');
            return [$card];
        }
        
        // Index view
        $card = new TestResourceCard();
        $card->withName('Index Card');
        return [$card];
    }
}

/**
 * Test resource class with model-aware cards.
 */
class TestResourceWithModelAwareCards extends Resource
{
    public static string $model = TestModel::class;

    public function fields(Request $request): array
    {
        return [Text::make('Name')];
    }

    public function cards(Request $request): array
    {
        $card = new TestResourceCard();
        $card->withName('Model Card for ' . $this->resource->name);
        
        return [$card];
    }
}

/**
 * Test resource class with authorization cards.
 */
class TestResourceWithAuthCards extends Resource
{
    public static string $model = TestModel::class;

    public function fields(Request $request): array
    {
        return [Text::make('Name')];
    }

    public function cards(Request $request): array
    {
        return [
            new TestResourceCard(),
            new UnauthorizedResourceCard(),
        ];
    }
}

/**
 * Test model for resource testing.
 */
class TestModel extends Model
{
    protected $fillable = ['name'];
    public $timestamps = false;
}

/**
 * Test card class for resource testing.
 */
class TestResourceCard extends Card
{
    public function __construct()
    {
        parent::__construct();
        $this->withMeta([
            'title' => 'Resource Test Card',
            'group' => 'Resources',
        ]);
    }

    public function uriKey(): string
    {
        return 'resource-test-card';
    }
}

/**
 * Another test card class for resource testing.
 */
class AnotherTestResourceCard extends Card
{
    public function __construct()
    {
        parent::__construct();
        $this->withMeta([
            'title' => 'Another Resource Card',
            'group' => 'Resources',
        ]);
    }

    public function uriKey(): string
    {
        return 'another-resource-card';
    }
}

/**
 * Unauthorized test card class for resource testing.
 */
class UnauthorizedResourceCard extends Card
{
    public function __construct()
    {
        parent::__construct();
        $this->withMeta([
            'title' => 'Unauthorized Resource Card',
            'group' => 'Unauthorized',
        ]);
    }

    public function authorize(Request $request): bool
    {
        return false; // Always unauthorized
    }

    public function uriKey(): string
    {
        return 'unauthorized-resource-card';
    }
}
