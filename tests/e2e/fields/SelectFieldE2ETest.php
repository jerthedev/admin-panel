<?php

declare(strict_types=1);

namespace E2E\Fields;

use Illuminate\Http\Request;
use JTD\AdminPanel\Fields\Select;
use JTD\AdminPanel\Tests\Fixtures\User;
use JTD\AdminPanel\Tests\TestCase;

/**
 * Select Field E2E Test (PHP side)
 *
 * Validates end-to-end serialization and fill behavior for Select field to ensure
 * parity with Nova v5.
 */
class SelectFieldE2ETest extends TestCase
{
    /** @test */
    public function it_serializes_and_fills_select_field_end_to_end(): void
    {
        $field = Select::make('Status', 'status')
            ->options([
                'draft' => 'Draft',
                'published' => 'Published',
            ])
            ->searchable()
            ->displayUsingLabels()
            ->help('Select post status');

        // Serialize
        $json = $field->jsonSerialize();
        $this->assertEquals('SelectField', $json['component']);
        $this->assertEquals('status', $json['attribute']);
        $this->assertTrue($json['searchable']);
        $this->assertTrue($json['displayUsingLabels']);
        $this->assertEquals('Draft', $json['options']['draft']);

        // Fill into model
        $user = new User(['name' => 'Bob', 'email' => 'bob@example.com']);
        $request = new Request(['status' => 'published']);
        $field->fill($request, $user);

        $this->assertEquals('published', $user->status);
    }
}

