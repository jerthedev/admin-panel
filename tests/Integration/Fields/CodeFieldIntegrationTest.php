<?php

declare(strict_types=1);

namespace Integration\Fields;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use JTD\AdminPanel\Fields\Code;
use JTD\AdminPanel\Tests\Fixtures\User;
use JTD\AdminPanel\Tests\TestCase;

/**
 * Code Field Integration Test
 *
 * Tests the complete integration between PHP Code field class,
 * API endpoints, and frontend functionality with 100% Nova API compatibility.
 * 
 * Focuses on field configuration and behavior with text and JSON storage,
 * testing the Nova API integration.
 */
class CodeFieldIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test users with different code values for testing
        User::factory()->create([
            'id' => 1,
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'code' => '<?php echo "Hello World"; ?>',
            'config' => ['database' => ['host' => 'localhost', 'port' => 3306], 'cache' => ['driver' => 'redis']],
        ]);

        User::factory()->create([
            'id' => 2,
            'name' => 'Jane Smith',
            'email' => 'jane@example.com',
            'code' => 'console.log("JavaScript code");',
            'config' => ['api' => ['version' => 'v1', 'timeout' => 5000], 'features' => ['auth', 'logging']],
        ]);

        User::factory()->create([
            'id' => 3,
            'name' => 'Bob Wilson',
            'email' => 'bob@example.com',
            'code' => null,
            'config' => null,
        ]);
    }

    /** @test */
    public function it_creates_code_field_with_nova_syntax(): void
    {
        $field = Code::make('Snippet');

        $this->assertEquals('Snippet', $field->name);
        $this->assertEquals('snippet', $field->attribute);
        $this->assertEquals('CodeField', $field->component);
    }

    /** @test */
    public function it_creates_code_field_with_custom_attribute(): void
    {
        $field = Code::make('Source Code', 'source_code');

        $this->assertEquals('Source Code', $field->name);
        $this->assertEquals('source_code', $field->attribute);
    }

    /** @test */
    public function it_supports_all_nova_code_configuration_methods(): void
    {
        $field = Code::make('Configuration')
            ->language('php')
            ->json()
            ->nullable()
            ->help('Enter PHP configuration code');

        $this->assertEquals('javascript', $field->language); // JSON overrides language
        $this->assertTrue($field->isJson);
        $this->assertTrue($field->nullable);
        $this->assertEquals('Enter PHP configuration code', $field->helpText);
    }

    /** @test */
    public function it_supports_nova_language_method(): void
    {
        $field = Code::make('Source Code')->language('php');

        $this->assertEquals('php', $field->language);
        $this->assertFalse($field->isJson);
    }

    /** @test */
    public function it_supports_nova_json_method(): void
    {
        $field = Code::make('Configuration')->json();

        $this->assertTrue($field->isJson);
        $this->assertEquals('javascript', $field->language); // JSON uses JavaScript highlighting
    }

    /** @test */
    public function it_resolves_code_field_value_correctly(): void
    {
        $user = User::find(1);
        $field = Code::make('Code', 'code')->language('php');

        $field->resolve($user);

        $this->assertEquals('<?php echo "Hello World"; ?>', $field->value);
    }

    /** @test */
    public function it_resolves_json_code_field_value_correctly(): void
    {
        $user = User::find(1);
        $field = Code::make('Configuration', 'config')->json();

        $field->resolve($user);

        $this->assertEquals([
            'database' => ['host' => 'localhost', 'port' => 3306],
            'cache' => ['driver' => 'redis']
        ], $field->value);
    }

    /** @test */
    public function it_handles_code_field_fill_with_request_data(): void
    {
        $user = new User();
        $request = new Request([
            'code' => 'console.log("Hello World");'
        ]);
        
        $field = Code::make('Code', 'code')->language('javascript');
        
        $field->fill($request, $user);

        $this->assertEquals('console.log("Hello World");', $user->code);
    }

    /** @test */
    public function it_handles_json_code_field_fill_with_request_data(): void
    {
        $user = new User();
        $request = new Request([
            'config' => ['api' => ['version' => 'v2'], 'debug' => true]
        ]);
        
        $field = Code::make('Configuration', 'config')->json();
        
        $field->fill($request, $user);

        $this->assertEquals(['api' => ['version' => 'v2'], 'debug' => true], $user->config);
    }

    /** @test */
    public function it_handles_code_field_fill_with_empty_data(): void
    {
        $user = new User();
        $request = new Request([
            'code' => ''
        ]);
        
        $field = Code::make('Code', 'code')->language('php');
        
        $field->fill($request, $user);

        $this->assertEquals('', $user->code);
    }

    /** @test */
    public function it_serializes_code_field_for_frontend(): void
    {
        $field = Code::make('Source Code')
            ->language('php')
            ->help('Enter PHP source code here');

        $serialized = $field->jsonSerialize();

        $this->assertEquals('Source Code', $serialized['name']);
        $this->assertEquals('source_code', $serialized['attribute']);
        $this->assertEquals('CodeField', $serialized['component']);
        $this->assertEquals('Enter PHP source code here', $serialized['helpText']);
        
        // Check Nova-specific properties
        $this->assertEquals('php', $serialized['language']);
        $this->assertFalse($serialized['isJson']);
        $this->assertIsArray($serialized['supportedLanguages']);
    }

    /** @test */
    public function it_serializes_json_code_field_for_frontend(): void
    {
        $field = Code::make('Configuration')
            ->json()
            ->help('Enter JSON configuration');

        $serialized = $field->jsonSerialize();

        $this->assertEquals('Configuration', $serialized['name']);
        $this->assertEquals('configuration', $serialized['attribute']);
        $this->assertEquals('CodeField', $serialized['component']);
        $this->assertEquals('Enter JSON configuration', $serialized['helpText']);
        
        // Check Nova-specific properties
        $this->assertEquals('javascript', $serialized['language']); // JSON uses JavaScript
        $this->assertTrue($serialized['isJson']);
        $this->assertIsArray($serialized['supportedLanguages']);
    }

    /** @test */
    public function it_serializes_default_values_correctly(): void
    {
        $field = Code::make('Code');

        $serialized = $field->jsonSerialize();

        $this->assertEquals('htmlmixed', $serialized['language']);
        $this->assertFalse($serialized['isJson']);
        $this->assertIsArray($serialized['supportedLanguages']);
    }

    /** @test */
    public function it_inherits_all_field_methods(): void
    {
        $field = Code::make('Code');

        // Test that Code field inherits all base Field methods
        $this->assertTrue(method_exists($field, 'rules'));
        $this->assertTrue(method_exists($field, 'nullable'));
        $this->assertTrue(method_exists($field, 'readonly'));
        $this->assertTrue(method_exists($field, 'help'));
        $this->assertTrue(method_exists($field, 'resolve'));
        $this->assertTrue(method_exists($field, 'jsonSerialize'));
        
        // Test Nova-specific Code methods
        $this->assertTrue(method_exists($field, 'language'));
        $this->assertTrue(method_exists($field, 'json'));
    }

    /** @test */
    public function it_handles_complex_code_field_configuration(): void
    {
        $field = Code::make('Advanced Configuration')
            ->language('yaml')
            ->json() // This should override language to 'javascript'
            ->nullable()
            ->help('Enter configuration in JSON format')
            ->rules('required');

        // Test all configurations are set correctly
        $this->assertEquals('javascript', $field->language); // JSON overrides YAML
        $this->assertTrue($field->isJson);
        $this->assertTrue($field->nullable);
        $this->assertEquals('Enter configuration in JSON format', $field->helpText);
        $this->assertContains('required', $field->rules);

        // Test serialization includes all configurations
        $serialized = $field->jsonSerialize();
        $this->assertEquals('Advanced Configuration', $serialized['name']);
        $this->assertEquals('advanced_configuration', $serialized['attribute']);
        $this->assertEquals('Enter configuration in JSON format', $serialized['helpText']);
        $this->assertEquals('javascript', $serialized['language']);
        $this->assertTrue($serialized['isJson']);
        $this->assertTrue($serialized['nullable']);
        $this->assertEquals(['required'], $serialized['rules']);
    }

    /** @test */
    public function it_supports_method_chaining_like_nova(): void
    {
        $field = Code::make('Configuration')
            ->language('php')
            ->json()
            ->nullable()
            ->help('Enter configuration')
            ->rules('required');

        $this->assertInstanceOf(Code::class, $field);
        $this->assertEquals('javascript', $field->language); // JSON overrides
        $this->assertTrue($field->isJson);
        $this->assertTrue($field->nullable);
        $this->assertEquals('Enter configuration', $field->helpText);
        $this->assertContains('required', $field->rules);
    }

    /** @test */
    public function it_provides_consistent_api_with_nova_code_field(): void
    {
        $field = Code::make('Snippet');

        // Test Nova-compatible methods exist and return correct types
        $this->assertInstanceOf(Code::class, $field->language('php'));
        $this->assertInstanceOf(Code::class, $field->json());
        
        // Test component name matches Nova
        $this->assertEquals('CodeField', $field->component);
        
        // Test default values match Nova
        $freshField = Code::make('Fresh');
        $this->assertEquals('htmlmixed', $freshField->language);
        $this->assertFalse($freshField->isJson);
    }

    /** @test */
    public function it_integrates_with_database_operations(): void
    {
        // Test complete CRUD cycle with code field
        $field = Code::make('Source Code', 'code')->language('javascript');

        // CREATE - Test with new user
        $newUser = User::create([
            'name' => 'New User',
            'email' => 'new@example.com',
            'password' => bcrypt('password'),
            'code' => 'console.log("new user");',
        ]);

        $field->resolve($newUser);
        $this->assertEquals('console.log("new user");', $field->value);

        // UPDATE - Change user code
        $newUser->update(['code' => 'console.log("updated");']);
        $field->resolve($newUser->fresh());
        $this->assertEquals('console.log("updated");', $field->value);

        // READ - Verify persistence
        $retrievedUser = User::find($newUser->id);
        $field->resolve($retrievedUser);
        $this->assertEquals('console.log("updated");', $field->value);

        // DELETE - Clean up
        $retrievedUser->delete();
        $this->assertNull(User::find($newUser->id));
    }

    /** @test */
    public function it_integrates_with_json_database_operations(): void
    {
        // Test complete CRUD cycle with JSON code field
        $field = Code::make('Configuration', 'config')->json();

        // CREATE - Test with new user
        $newUser = User::create([
            'name' => 'New User',
            'email' => 'new@example.com',
            'password' => bcrypt('password'),
            'config' => ['debug' => false, 'timeout' => 30],
        ]);

        $field->resolve($newUser);
        $this->assertEquals(['debug' => false, 'timeout' => 30], $field->value);

        // UPDATE - Change user config
        $newUser->update(['config' => ['debug' => true, 'timeout' => 60, 'cache' => true]]);
        $field->resolve($newUser->fresh());
        $this->assertEquals(['debug' => true, 'timeout' => 60, 'cache' => true], $field->value);

        // READ - Verify persistence
        $retrievedUser = User::find($newUser->id);
        $field->resolve($retrievedUser);
        $this->assertEquals(['debug' => true, 'timeout' => 60, 'cache' => true], $field->value);

        // DELETE - Clean up
        $retrievedUser->delete();
        $this->assertNull(User::find($newUser->id));
    }

    /** @test */
    public function it_handles_code_field_with_validation_rules(): void
    {
        $field = Code::make('Code', 'code')
            ->language('javascript')
            ->rules('required', 'string')
            ->nullable(false);

        // Test that validation rules are properly set
        $this->assertContains('required', $field->rules);
        $this->assertContains('string', $field->rules);
        $this->assertFalse($field->nullable);

        // Test field serialization includes validation rules
        $serialized = $field->jsonSerialize();
        $this->assertEquals(['required', 'string'], $serialized['rules']);
        $this->assertFalse($serialized['nullable']);
    }

    /** @test */
    public function it_supports_nova_examples_from_documentation(): void
    {
        // Example from Nova docs: Code::make('Snippet')
        $field1 = Code::make('Snippet');
        
        $this->assertEquals('Snippet', $field1->name);
        $this->assertEquals('snippet', $field1->attribute);
        $this->assertEquals('htmlmixed', $field1->language);

        // Example with language
        $field2 = Code::make('Source')->language('php');
        
        $this->assertEquals('php', $field2->language);
        $this->assertFalse($field2->isJson);

        // Example with JSON
        $field3 = Code::make('Configuration')->json();
        
        $this->assertTrue($field3->isJson);
        $this->assertEquals('javascript', $field3->language);
    }

    /** @test */
    public function it_handles_edge_cases_with_language_and_json(): void
    {
        // Test that json() overrides language()
        $field1 = Code::make('Config')
            ->language('php')
            ->json();
        
        $this->assertEquals('javascript', $field1->language);
        $this->assertTrue($field1->isJson);

        // Test that language() after json() still gets overridden
        $field2 = Code::make('Config')
            ->json()
            ->language('php');
        
        $this->assertEquals('php', $field2->language);
        $this->assertTrue($field2->isJson);
    }

    /** @test */
    public function it_handles_all_nova_supported_languages(): void
    {
        $novaLanguages = [
            'dockerfile',
            'htmlmixed',
            'javascript',
            'markdown',
            'nginx',
            'php',
            'ruby',
            'sass',
            'shell',
            'sql',
            'twig',
            'vim',
            'vue',
            'xml',
            'yaml-frontmatter',
            'yaml',
        ];

        foreach ($novaLanguages as $language) {
            $field = Code::make('Code')->language($language);
            
            $this->assertEquals($language, $field->language);
            $this->assertContains($language, $field->getSupportedLanguages());
        }
    }

    /** @test */
    public function it_works_with_all_inherited_field_functionality(): void
    {
        $field = Code::make('Source Code')
            ->language('php')
            ->json()
            ->nullable()
            ->readonly()
            ->help('PHP source code')
            ->rules('required');

        // Test inherited functionality works
        $this->assertTrue($field->nullable);
        $this->assertTrue($field->readonly);
        $this->assertEquals('PHP source code', $field->helpText);
        $this->assertContains('required', $field->rules);
        
        // Test Nova-specific functionality still works
        $this->assertEquals('javascript', $field->language); // JSON overrides
        $this->assertTrue($field->isJson);
    }

    /** @test */
    public function it_handles_request_fill_with_missing_field(): void
    {
        $user = new User();
        $request = new Request([]); // No code field in request
        
        $field = Code::make('Code', 'code')->language('php');
        
        $field->fill($request, $user);

        // When field is missing from request, should be null
        $this->assertNull($user->code);
    }

    /** @test */
    public function it_handles_null_values_correctly(): void
    {
        $user = User::find(3); // User with null code and config
        
        $codeField = Code::make('Code', 'code')->language('php');
        $codeField->resolve($user);
        $this->assertNull($codeField->value);

        $configField = Code::make('Configuration', 'config')->json();
        $configField->resolve($user);
        $this->assertNull($configField->value);
    }

    /** @test */
    public function it_handles_complex_json_structures(): void
    {
        $complexConfig = [
            'database' => [
                'connections' => [
                    'mysql' => ['host' => 'localhost', 'port' => 3306],
                    'redis' => ['host' => 'redis', 'port' => 6379]
                ]
            ],
            'cache' => [
                'stores' => ['file', 'redis'],
                'default' => 'redis'
            ],
            'features' => [
                'auth' => true,
                'logging' => ['level' => 'debug', 'channels' => ['single', 'daily']],
                'api' => ['version' => 'v1', 'rate_limit' => 1000]
            ]
        ];

        $user = User::create([
            'name' => 'Complex User',
            'email' => 'complex@example.com',
            'password' => bcrypt('password'),
            'config' => $complexConfig,
        ]);

        $field = Code::make('Configuration', 'config')->json();
        $field->resolve($user);

        $this->assertEquals($complexConfig, $field->value);

        // Test serialization
        $serialized = $field->jsonSerialize();
        $this->assertEquals($complexConfig, $serialized['value']);

        // Clean up
        $user->delete();
    }
}
