<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Unit;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use JTD\AdminPanel\Filters\BooleanFilter;
use JTD\AdminPanel\Filters\DateFilter;
use JTD\AdminPanel\Filters\SelectFilter;
use JTD\AdminPanel\Filters\TextFilter;
use JTD\AdminPanel\Tests\Fixtures\User;
use JTD\AdminPanel\Tests\TestCase;

/**
 * Filter Unit Tests
 * 
 * Tests for filter classes including query application,
 * options generation, and configuration.
 * 
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
class FilterTest extends TestCase
{
    public function test_select_filter_creation(): void
    {
        $filter = SelectFilter::make('Status');
        
        $this->assertEquals('Status', $filter->name);
        $this->assertEquals('status', $filter->key);
        $this->assertEquals('SelectFilter', $filter->component());
    }

    public function test_select_filter_with_options(): void
    {
        $options = [
            'active' => 'Active',
            'inactive' => 'Inactive',
        ];
        
        $filter = SelectFilter::make('Status')->withOptions($options);
        $request = new Request();
        
        $this->assertEquals($options, $filter->options($request));
    }

    public function test_select_filter_applies_to_query(): void
    {
        $filter = SelectFilter::make('Status');
        $query = User::query();
        
        $result = $filter->apply($query, 'active');
        
        $this->assertInstanceOf(Builder::class, $result);
        // Check that where clause was added
        $wheres = $result->getQuery()->wheres;
        $this->assertCount(1, $wheres);
        $this->assertEquals('status', $wheres[0]['column']);
        $this->assertEquals('active', $wheres[0]['value']);
    }

    public function test_select_filter_ignores_null_value(): void
    {
        $filter = SelectFilter::make('Status');
        $query = User::query();
        
        $result = $filter->apply($query, null);
        
        // Query should be unchanged
        $this->assertEmpty($result->getQuery()->wheres);
    }

    public function test_boolean_filter_creation(): void
    {
        $filter = BooleanFilter::make('Active');
        
        $this->assertEquals('Active', $filter->name);
        $this->assertEquals('active', $filter->key);
        $this->assertEquals('BooleanFilter', $filter->component());
    }

    public function test_boolean_filter_options(): void
    {
        $filter = BooleanFilter::make('Active');
        $request = new Request();
        
        $options = $filter->options($request);
        
        $this->assertEquals([
            'true' => 'Yes',
            'false' => 'No',
        ], $options);
    }

    public function test_boolean_filter_with_custom_labels(): void
    {
        $filter = BooleanFilter::make('Active')
            ->withLabels('Enabled', 'Disabled');
        
        $request = new Request();
        $options = $filter->options($request);
        
        $this->assertEquals([
            'true' => 'Enabled',
            'false' => 'Disabled',
        ], $options);
    }

    public function test_boolean_filter_applies_true_value(): void
    {
        $filter = BooleanFilter::make('Active', 'is_active');
        $query = User::query();
        
        $result = $filter->apply($query, 'true');
        
        $wheres = $result->getQuery()->wheres;
        $this->assertCount(1, $wheres);
        $this->assertEquals('is_active', $wheres[0]['column']);
        $this->assertTrue($wheres[0]['value']);
    }

    public function test_boolean_filter_applies_false_value(): void
    {
        $filter = BooleanFilter::make('Active', 'is_active');
        $query = User::query();
        
        $result = $filter->apply($query, 'false');
        
        $wheres = $result->getQuery()->wheres;
        $this->assertCount(1, $wheres);
        $this->assertEquals('is_active', $wheres[0]['column']);
        $this->assertFalse($wheres[0]['value']);
    }

    public function test_text_filter_creation(): void
    {
        $filter = TextFilter::make('Search');
        
        $this->assertEquals('Search', $filter->name);
        $this->assertEquals('search', $filter->key);
        $this->assertEquals('TextFilter', $filter->component());
    }

    public function test_text_filter_applies_like_query(): void
    {
        $filter = TextFilter::make('Search', 'name');
        $query = User::query();
        
        $result = $filter->apply($query, 'john');
        
        $wheres = $result->getQuery()->wheres;
        $this->assertCount(1, $wheres);
        $this->assertEquals('name', $wheres[0]['column']);
        $this->assertEquals('LIKE', $wheres[0]['operator']);
        $this->assertEquals('%john%', $wheres[0]['value']);
    }

    public function test_text_filter_with_multiple_columns(): void
    {
        $filter = TextFilter::make('Search')
            ->withColumns(['name', 'email']);
        $query = User::query();
        
        $result = $filter->apply($query, 'john');
        
        // Should create a nested where with OR conditions
        $wheres = $result->getQuery()->wheres;
        $this->assertCount(1, $wheres);
        $this->assertEquals('Nested', $wheres[0]['type']);
    }

    public function test_date_filter_creation(): void
    {
        $filter = DateFilter::make('Created');
        
        $this->assertEquals('Created', $filter->name);
        $this->assertEquals('created', $filter->key);
        $this->assertEquals('DateFilter', $filter->component());
    }

    public function test_date_filter_applies_date_query(): void
    {
        $filter = DateFilter::make('Created', 'created_at');
        $query = User::query();
        
        $result = $filter->apply($query, '2023-01-01');
        
        $wheres = $result->getQuery()->wheres;
        $this->assertCount(1, $wheres);
        $this->assertEquals('Date', $wheres[0]['type']);
        $this->assertEquals('created_at', $wheres[0]['column']);
    }

    public function test_date_filter_with_operator(): void
    {
        $filter = DateFilter::make('Created', 'created_at')
            ->withOperator('>');
        $query = User::query();
        
        $result = $filter->apply($query, '2023-01-01');
        
        $wheres = $result->getQuery()->wheres;
        $this->assertCount(1, $wheres);
        $this->assertEquals('>', $wheres[0]['operator']);
    }

    public function test_filter_authorization(): void
    {
        $filter = SelectFilter::make('Status');
        $request = new Request();
        
        $this->assertTrue($filter->authorize($request));
    }

    public function test_filter_default_value(): void
    {
        $filter = SelectFilter::make('Status')->withDefault('active');
        
        $this->assertEquals('active', $filter->default());
    }

    public function test_filter_json_serialization(): void
    {
        $filter = SelectFilter::make('Status')
            ->withOptions(['active' => 'Active'])
            ->withDefault('active');
        
        $json = $filter->jsonSerialize();
        
        $this->assertIsArray($json);
        $this->assertEquals('Status', $json['name']);
        $this->assertEquals('status', $json['key']);
        $this->assertEquals('SelectFilter', $json['component']);
        $this->assertEquals(['active' => 'Active'], $json['options']);
        $this->assertEquals('active', $json['default']);
    }

    public function test_filter_factory_methods(): void
    {
        $selectFilter = SelectFilter::make('Status');
        $booleanFilter = BooleanFilter::make('Active');
        $textFilter = TextFilter::make('Search');
        $dateFilter = DateFilter::make('Created');
        
        $this->assertInstanceOf(SelectFilter::class, $selectFilter);
        $this->assertInstanceOf(BooleanFilter::class, $booleanFilter);
        $this->assertInstanceOf(TextFilter::class, $textFilter);
        $this->assertInstanceOf(DateFilter::class, $dateFilter);
    }
}
