<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Unit\Cards;

use Illuminate\Http\Request;
use JTD\AdminPanel\Cards\Card;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the Card base class.
 */
class CardTest extends TestCase
{
    private TestCard $card;

    protected function setUp(): void
    {
        parent::setUp();
        $this->card = new TestCard;
    }

    public function test_make_creates_new_instance(): void
    {
        $card = TestCard::make();

        $this->assertInstanceOf(TestCard::class, $card);
        $this->assertInstanceOf(Card::class, $card);
    }

    public function test_constructor_sets_default_properties(): void
    {
        $card = new TestCard;

        $this->assertEquals('Test Card', $card->name());
        $this->assertEquals('test-card', $card->uriKey());
        $this->assertEquals('TestCardCard', $card->component());
        $this->assertEquals([], $card->meta());
    }

    public function test_name_returns_card_name(): void
    {
        $this->assertEquals('Test Card', $this->card->name());
    }

    public function test_with_name_sets_custom_name(): void
    {
        $result = $this->card->withName('Custom Card Name');

        $this->assertSame($this->card, $result);
        $this->assertEquals('Custom Card Name', $this->card->name());
    }

    public function test_component_returns_component_name(): void
    {
        $this->assertEquals('TestCardCard', $this->card->component());
    }

    public function test_with_component_sets_custom_component(): void
    {
        $result = $this->card->withComponent('CustomComponent');

        $this->assertSame($this->card, $result);
        $this->assertEquals('CustomComponent', $this->card->component());
    }

    public function test_uri_key_returns_uri_key(): void
    {
        $this->assertEquals('test-card', $this->card->uriKey());
    }

    public function test_with_meta_merges_metadata(): void
    {
        $meta = ['key1' => 'value1', 'key2' => 'value2'];
        $result = $this->card->withMeta($meta);

        $this->assertSame($this->card, $result);
        $this->assertEquals($meta, $this->card->meta());
    }

    public function test_with_meta_merges_additional_metadata(): void
    {
        $this->card->withMeta(['key1' => 'value1']);
        $this->card->withMeta(['key2' => 'value2']);

        $expected = ['key1' => 'value1', 'key2' => 'value2'];
        $this->assertEquals($expected, $this->card->meta());
    }

    public function test_with_meta_overwrites_existing_keys(): void
    {
        $this->card->withMeta(['key1' => 'original']);
        $this->card->withMeta(['key1' => 'updated']);

        $this->assertEquals(['key1' => 'updated'], $this->card->meta());
    }

    public function test_can_see_sets_callback(): void
    {
        $callback = fn (Request $request) => true;
        $result = $this->card->canSee($callback);

        $this->assertSame($this->card, $result);
        $this->assertSame($callback, $this->card->canSeeCallback);
    }

    public function test_authorize_returns_true_when_no_callback_set(): void
    {
        $request = new Request;

        $this->assertTrue($this->card->authorize($request));
    }

    public function test_authorize_calls_callback_when_set(): void
    {
        $request = new Request;
        $callbackCalled = false;
        $passedRequest = null;

        $callback = function (Request $req) use (&$callbackCalled, &$passedRequest) {
            $callbackCalled = true;
            $passedRequest = $req;

            return false;
        };

        $this->card->canSee($callback);
        $result = $this->card->authorize($request);

        $this->assertTrue($callbackCalled);
        $this->assertSame($request, $passedRequest);
        $this->assertFalse($result);
    }

    public function test_authorize_returns_callback_result(): void
    {
        $request = new Request;

        // Test true result
        $this->card->canSee(fn () => true);
        $this->assertTrue($this->card->authorize($request));

        // Test false result
        $this->card->canSee(fn () => false);
        $this->assertFalse($this->card->authorize($request));
    }

    public function test_generate_name_converts_pascal_case_to_title_case(): void
    {
        $card = new class extends Card {};
        $reflection = new \ReflectionClass($card);
        $method = $reflection->getMethod('generateName');
        $method->setAccessible(true);

        // Mock the class name
        $mockCard = new class extends Card
        {
            public static function getClassName(): string
            {
                return 'MyCustomCard';
            }
        };

        // We need to test this indirectly through a concrete implementation
        $testCard = new TestComplexNameCard;
        $this->assertEquals('Test Complex Name Card', $testCard->name());
    }

    public function test_generate_uri_key_converts_pascal_case_to_kebab_case(): void
    {
        $testCard = new TestComplexNameCard;
        $this->assertEquals('test-complex-name-card', $testCard->uriKey());
    }

    public function test_generate_component_appends_card_suffix(): void
    {
        $testCard = new TestComplexNameCard;
        $this->assertEquals('TestComplexNameCardCard', $testCard->component());
    }

    public function test_json_serialize_returns_correct_structure(): void
    {
        $this->card->withMeta(['custom' => 'data']);

        $expected = [
            'name' => 'Test Card',
            'component' => 'TestCardCard',
            'uriKey' => 'test-card',
            'meta' => ['custom' => 'data'],
        ];

        $this->assertEquals($expected, $this->card->jsonSerialize());
    }

    public function test_fluent_interface_chaining(): void
    {
        $result = $this->card
            ->withName('Chained Card')
            ->withComponent('ChainedComponent')
            ->withMeta(['chained' => true])
            ->canSee(fn () => true);

        $this->assertSame($this->card, $result);
        $this->assertEquals('Chained Card', $this->card->name());
        $this->assertEquals('ChainedComponent', $this->card->component());
        $this->assertEquals(['chained' => true], $this->card->meta());
        $this->assertNotNull($this->card->canSeeCallback);
    }
}

/**
 * Test implementation of Card for testing purposes.
 */
class TestCard extends Card
{
    // Concrete implementation for testing
}

/**
 * Test implementation with complex name for testing name generation.
 */
class TestComplexNameCard extends Card
{
    // Concrete implementation for testing name generation
}
