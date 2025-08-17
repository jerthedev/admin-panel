<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\E2E;

use Illuminate\Foundation\Testing\RefreshDatabase;
use JTD\AdminPanel\Fields\Code;
use JTD\AdminPanel\Tests\Fixtures\User;
use JTD\AdminPanel\Tests\TestCase;

/**
 * Code Field E2E Test
 *
 * Tests the complete end-to-end functionality of Code fields
 * including field configuration, data flow, and Nova API compatibility.
 * 
 * Focuses on field integration and behavior with text and JSON storage rather than
 * web interface testing (which is handled by Playwright tests).
 */
class CodeFieldE2ETest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test users with different code values for E2E testing
        User::factory()->create([
            'id' => 1,
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'code' => '<?php
class User {
    public function getName() {
        return $this->name;
    }
}',
            'config' => [
                'database' => [
                    'host' => 'localhost',
                    'port' => 3306,
                    'username' => 'root',
                    'password' => 'secret'
                ],
                'cache' => [
                    'driver' => 'redis',
                    'ttl' => 3600
                ],
                'features' => ['auth', 'logging', 'api']
            ],
        ]);

        User::factory()->create([
            'id' => 2,
            'name' => 'Jane Smith',
            'email' => 'jane@example.com',
            'code' => 'function calculateTotal(items) {
    return items.reduce((sum, item) => sum + item.price, 0);
}

const cart = [
    { name: "Product 1", price: 29.99 },
    { name: "Product 2", price: 19.99 }
];

console.log("Total:", calculateTotal(cart));',
            'config' => [
                'api' => [
                    'version' => 'v2',
                    'timeout' => 5000,
                    'retries' => 3
                ],
                'ui' => [
                    'theme' => 'dark',
                    'language' => 'en',
                    'notifications' => true
                ]
            ],
        ]);

        User::factory()->create([
            'id' => 3,
            'name' => 'Bob Wilson',
            'email' => 'bob@example.com',
            'code' => '',
            'config' => [],
        ]);
    }

    /** @test */
    public function it_handles_code_field_with_php_language(): void
    {
        $field = Code::make('Source Code', 'code')->language('php');

        $user = User::find(1);
        $field->resolve($user);

        $this->assertEquals('php', $field->language);
        $this->assertFalse($field->isJson);
        $this->assertStringContains('<?php', $field->value);
        $this->assertStringContains('class User', $field->value);
        $this->assertStringContains('public function getName()', $field->value);

        // Test serialization for frontend
        $serialized = $field->jsonSerialize();
        $this->assertEquals('php', $serialized['language']);
        $this->assertFalse($serialized['isJson']);
        $this->assertStringContains('<?php', $serialized['value']);
    }

    /** @test */
    public function it_handles_code_field_with_javascript_language(): void
    {
        $field = Code::make('JavaScript Code', 'code')->language('javascript');

        $user = User::find(2);
        $field->resolve($user);

        $this->assertEquals('javascript', $field->language);
        $this->assertFalse($field->isJson);
        $this->assertStringContains('function calculateTotal', $field->value);
        $this->assertStringContains('reduce((sum, item)', $field->value);
        $this->assertStringContains('console.log', $field->value);

        // Test serialization for frontend
        $serialized = $field->jsonSerialize();
        $this->assertEquals('javascript', $serialized['language']);
        $this->assertFalse($serialized['isJson']);
        $this->assertStringContains('function calculateTotal', $serialized['value']);
    }

    /** @test */
    public function it_handles_json_code_field_with_complex_data(): void
    {
        $field = Code::make('Configuration', 'config')->json();

        $user = User::find(1);
        $field->resolve($user);

        $this->assertEquals('javascript', $field->language); // JSON uses JavaScript highlighting
        $this->assertTrue($field->isJson);
        
        $expectedConfig = [
            'database' => [
                'host' => 'localhost',
                'port' => 3306,
                'username' => 'root',
                'password' => 'secret'
            ],
            'cache' => [
                'driver' => 'redis',
                'ttl' => 3600
            ],
            'features' => ['auth', 'logging', 'api']
        ];
        
        $this->assertEquals($expectedConfig, $field->value);

        // Test serialization for frontend
        $serialized = $field->jsonSerialize();
        $this->assertEquals('javascript', $serialized['language']);
        $this->assertTrue($serialized['isJson']);
        $this->assertEquals($expectedConfig, $serialized['value']);
    }

    /** @test */
    public function it_handles_code_field_serialization_for_frontend(): void
    {
        $field = Code::make('Advanced Code Editor', 'code')
            ->language('sql')
            ->help('Enter SQL queries here')
            ->rules('required');

        $user = User::find(2);
        $field->resolve($user);

        $serialized = $field->jsonSerialize();

        // Test basic field properties
        $this->assertEquals('Advanced Code Editor', $serialized['name']);
        $this->assertEquals('code', $serialized['attribute']);
        $this->assertEquals('CodeField', $serialized['component']);
        $this->assertEquals('Enter SQL queries here', $serialized['helpText']);
        $this->assertEquals(['required'], $serialized['rules']);

        // Test Nova-specific code properties
        $this->assertEquals('sql', $serialized['language']);
        $this->assertFalse($serialized['isJson']);
        $this->assertIsArray($serialized['supportedLanguages']);
        $this->assertContains('sql', $serialized['supportedLanguages']);
    }

    /** @test */
    public function it_handles_code_field_with_empty_values(): void
    {
        $field = Code::make('Empty Code', 'code')->language('python');

        $user = User::find(3); // User with empty code
        $field->resolve($user);

        $this->assertEquals('', $field->value);

        // Test serialization
        $serialized = $field->jsonSerialize();
        $this->assertEquals('', $serialized['value']);
        $this->assertEquals('python', $serialized['language']);
        $this->assertFalse($serialized['isJson']);
    }

    /** @test */
    public function it_handles_json_code_field_with_empty_values(): void
    {
        $field = Code::make('Empty Configuration', 'config')->json();

        $user = User::find(3); // User with empty config
        $field->resolve($user);

        $this->assertEquals([], $field->value);

        // Test serialization
        $serialized = $field->jsonSerialize();
        $this->assertEquals([], $serialized['value']);
        $this->assertEquals('javascript', $serialized['language']);
        $this->assertTrue($serialized['isJson']);
    }

    /** @test */
    public function it_handles_code_field_with_complex_nova_configuration(): void
    {
        $field = Code::make('Multi-Language Code')
            ->language('yaml')
            ->json() // This should override language to 'javascript'
            ->nullable()
            ->help('Configuration in JSON format')
            ->rules('required', 'json');

        // Test with different users
        $testCases = [
            [1, ['database' => ['host' => 'localhost', 'port' => 3306, 'username' => 'root', 'password' => 'secret'], 'cache' => ['driver' => 'redis', 'ttl' => 3600], 'features' => ['auth', 'logging', 'api']]],
            [2, ['api' => ['version' => 'v2', 'timeout' => 5000, 'retries' => 3], 'ui' => ['theme' => 'dark', 'language' => 'en', 'notifications' => true]]],
            [3, []],
        ];

        foreach ($testCases as [$userId, $expectedConfig]) {
            $user = User::find($userId);
            $testField = Code::make('Configuration', 'config')
                ->language('yaml')
                ->json()
                ->nullable()
                ->help('Configuration in JSON format')
                ->rules('required', 'json');

            $testField->resolve($user);

            $this->assertEquals($expectedConfig, $testField->value);

            // Test serialization
            $serialized = $testField->jsonSerialize();
            $this->assertEquals('javascript', $serialized['language']); // JSON overrides YAML
            $this->assertTrue($serialized['isJson']);
            $this->assertTrue($serialized['nullable']);
            $this->assertEquals(['required', 'json'], $serialized['rules']);
        }
    }

    /** @test */
    public function it_integrates_with_database_operations(): void
    {
        // Test complete CRUD cycle with code field
        $field = Code::make('Source Code', 'code')->language('ruby');

        // CREATE - Test with new user
        $newUser = User::create([
            'name' => 'New User',
            'email' => 'new@example.com',
            'password' => bcrypt('password'),
            'code' => 'class Calculator
  def add(a, b)
    a + b
  end
end

calc = Calculator.new
puts calc.add(5, 3)',
        ]);

        $field->resolve($newUser);
        $this->assertStringContains('class Calculator', $field->value);
        $this->assertStringContains('def add(a, b)', $field->value);

        // Test serialization for frontend
        $serialized = $field->jsonSerialize();
        $this->assertEquals('ruby', $serialized['language']);
        $this->assertStringContains('class Calculator', $serialized['value']);

        // UPDATE - Change user code
        $newUser->update([
            'code' => 'def fibonacci(n)
  return n if n <= 1
  fibonacci(n - 1) + fibonacci(n - 2)
end

puts fibonacci(10)'
        ]);
        $field->resolve($newUser->fresh());
        $this->assertStringContains('def fibonacci(n)', $field->value);
        $this->assertStringContains('fibonacci(n - 1)', $field->value);

        // READ - Verify persistence
        $retrievedUser = User::find($newUser->id);
        $field->resolve($retrievedUser);
        $this->assertStringContains('def fibonacci(n)', $field->value);

        // DELETE - Clean up
        $retrievedUser->delete();
        $this->assertNull(User::find($newUser->id));
    }

    /** @test */
    public function it_integrates_with_json_database_operations(): void
    {
        // Test complete CRUD cycle with JSON code field
        $field = Code::make('Application Config', 'config')->json();

        // CREATE - Test with new user
        $newUser = User::create([
            'name' => 'New User',
            'email' => 'new@example.com',
            'password' => bcrypt('password'),
            'config' => [
                'app' => [
                    'name' => 'My App',
                    'version' => '1.0.0',
                    'debug' => false
                ],
                'services' => [
                    'mail' => ['driver' => 'smtp', 'host' => 'smtp.gmail.com'],
                    'queue' => ['driver' => 'redis', 'connection' => 'default']
                ]
            ],
        ]);

        $field->resolve($newUser);
        $this->assertEquals('My App', $field->value['app']['name']);
        $this->assertEquals('1.0.0', $field->value['app']['version']);
        $this->assertEquals('smtp', $field->value['services']['mail']['driver']);

        // Test serialization for frontend
        $serialized = $field->jsonSerialize();
        $this->assertEquals('javascript', $serialized['language']);
        $this->assertTrue($serialized['isJson']);
        $this->assertEquals('My App', $serialized['value']['app']['name']);

        // UPDATE - Change user config
        $newUser->update([
            'config' => [
                'app' => [
                    'name' => 'Updated App',
                    'version' => '2.0.0',
                    'debug' => true
                ],
                'services' => [
                    'mail' => ['driver' => 'sendmail'],
                    'queue' => ['driver' => 'database'],
                    'cache' => ['driver' => 'redis', 'prefix' => 'myapp']
                ]
            ]
        ]);
        $field->resolve($newUser->fresh());
        $this->assertEquals('Updated App', $field->value['app']['name']);
        $this->assertEquals('2.0.0', $field->value['app']['version']);
        $this->assertTrue($field->value['app']['debug']);
        $this->assertEquals('database', $field->value['services']['queue']['driver']);

        // READ - Verify persistence
        $retrievedUser = User::find($newUser->id);
        $field->resolve($retrievedUser);
        $this->assertEquals('Updated App', $field->value['app']['name']);
        $this->assertEquals('database', $field->value['services']['queue']['driver']);

        // DELETE - Clean up
        $retrievedUser->delete();
        $this->assertNull(User::find($newUser->id));
    }

    /** @test */
    public function it_handles_code_field_with_validation_rules(): void
    {
        $field = Code::make('Validated Code', 'code')
            ->language('shell')
            ->rules('required', 'string')
            ->nullable(false);

        $user = User::find(2);
        $field->resolve($user);

        // Test that validation rules are properly set
        $this->assertContains('required', $field->rules);
        $this->assertContains('string', $field->rules);
        $this->assertFalse($field->nullable);

        // Test field serialization includes validation rules
        $serialized = $field->jsonSerialize();
        $this->assertEquals(['required', 'string'], $serialized['rules']);
        $this->assertFalse($serialized['nullable']);
        $this->assertEquals('shell', $serialized['language']);
    }

    /** @test */
    public function it_provides_consistent_nova_api_behavior(): void
    {
        // Test that Code field behaves exactly like Nova's Code field
        $field = Code::make('Snippet')
            ->language('dockerfile')
            ->nullable()
            ->help('Code field for Docker configurations');

        // Test method chaining returns Code instance
        $this->assertInstanceOf(Code::class, $field);

        // Test all Nova API methods exist and work
        $this->assertEquals('dockerfile', $field->language);
        $this->assertFalse($field->isJson);
        $this->assertTrue($field->nullable);
        $this->assertEquals('Code field for Docker configurations', $field->helpText);

        // Test component name matches Nova
        $this->assertEquals('CodeField', $field->component);

        // Test serialization includes all Nova properties
        $serialized = $field->jsonSerialize();
        $this->assertEquals('dockerfile', $serialized['language']);
        $this->assertFalse($serialized['isJson']);
        $this->assertTrue($serialized['nullable']);
        $this->assertEquals('Code field for Docker configurations', $serialized['helpText']);
        $this->assertContains('dockerfile', $serialized['supportedLanguages']);
    }

    /** @test */
    public function it_handles_edge_cases_and_boundary_conditions(): void
    {
        // Test with all supported languages
        $novaLanguages = [
            'dockerfile' => 'FROM ubuntu:20.04\nRUN apt-get update',
            'htmlmixed' => '<html><body><h1>Hello</h1></body></html>',
            'javascript' => 'const x = 42; console.log(x);',
            'markdown' => '# Title\n\nThis is **bold** text.',
            'nginx' => 'server {\n    listen 80;\n    server_name example.com;\n}',
            'php' => '<?php echo "Hello World"; ?>',
            'ruby' => 'puts "Hello World"',
            'sass' => '$primary-color: #333;\nbody { color: $primary-color; }',
            'shell' => '#!/bin/bash\necho "Hello World"',
            'sql' => 'SELECT * FROM users WHERE active = 1;',
            'twig' => '{{ name|upper }}',
            'vim' => 'set number\nset tabstop=4',
            'vue' => '<template><div>{{ message }}</div></template>',
            'xml' => '<?xml version="1.0"?><root><item>value</item></root>',
            'yaml' => 'name: test\nversion: 1.0.0',
        ];

        foreach ($novaLanguages as $language => $sampleCode) {
            $field = Code::make('Code')->language($language);
            
            $this->assertEquals($language, $field->language);
            $this->assertFalse($field->isJson);
            $this->assertContains($language, $field->getSupportedLanguages());
        }

        // Test JSON override behavior
        $field = Code::make('Config')
            ->language('yaml')
            ->json();
        
        $this->assertEquals('javascript', $field->language); // JSON overrides YAML
        $this->assertTrue($field->isJson);
    }

    /** @test */
    public function it_maintains_type_integrity_across_operations(): void
    {
        // Test that string values maintain their types throughout the process
        $codeField = Code::make('Code', 'code')->language('python');
        $user = User::find(2);
        $codeField->resolve($user);

        // Test types are preserved
        $this->assertIsString($codeField->value);

        // Test serialization preserves types
        $serialized = $codeField->jsonSerialize();
        $this->assertIsString($serialized['value']);

        // Test JSON field maintains array/object types
        $configField = Code::make('Configuration', 'config')->json();
        $configField->resolve($user);

        $this->assertIsArray($configField->value);

        $serialized = $configField->jsonSerialize();
        $this->assertIsArray($serialized['value']);
    }

    /** @test */
    public function it_handles_complex_real_world_scenarios(): void
    {
        // Scenario: Multi-language development environment
        $phpField = Code::make('PHP Code', 'code')->language('php');
        $configField = Code::make('App Configuration', 'config')->json();

        // Test PHP developer (User 1)
        $phpDeveloper = User::find(1);
        $phpField->resolve($phpDeveloper);
        $configField->resolve($phpDeveloper);

        // PHP developer should have PHP code
        $this->assertStringContains('<?php', $phpField->value);
        $this->assertStringContains('class User', $phpField->value);
        
        // And complex configuration
        $this->assertArrayHasKey('database', $configField->value);
        $this->assertArrayHasKey('cache', $configField->value);
        $this->assertEquals('localhost', $configField->value['database']['host']);

        // Test JavaScript developer (User 2)
        $jsDeveloper = User::find(2);
        $phpField->resolve($jsDeveloper);
        $configField->resolve($jsDeveloper);

        // JS developer should have JavaScript code
        $this->assertStringContains('function calculateTotal', $phpField->value);
        $this->assertStringContains('console.log', $phpField->value);
        
        // And different configuration
        $this->assertArrayHasKey('api', $configField->value);
        $this->assertArrayHasKey('ui', $configField->value);
        $this->assertEquals('v2', $configField->value['api']['version']);

        // Test serialization for both fields
        $phpSerialized = $phpField->jsonSerialize();
        $configSerialized = $configField->jsonSerialize();

        $this->assertEquals('php', $phpSerialized['language']);
        $this->assertFalse($phpSerialized['isJson']);

        $this->assertEquals('javascript', $configSerialized['language']);
        $this->assertTrue($configSerialized['isJson']);
    }
}
