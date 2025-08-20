<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Unit\Console\Commands;

use Illuminate\Filesystem\Filesystem;
use PHPUnit\Framework\TestCase;

/**
 * Card Stub Template Tests.
 *
 * Tests the Card stub template processing, variable replacement,
 * and generated code validity for the MakeCardCommand.
 */
class CardStubTest extends TestCase
{
    protected Filesystem $files;
    protected string $stubPath;
    protected string $stubContent;

    protected function setUp(): void
    {
        $this->files = new Filesystem();
        $this->stubPath = __DIR__ . '/../../../../src/Console/stubs/Card.stub';
        
        // Load the actual stub content
        $this->stubContent = $this->files->get($this->stubPath);
    }

    public function test_card_stub_file_exists(): void
    {
        $this->assertTrue($this->files->exists($this->stubPath));
    }

    public function test_card_stub_contains_required_placeholders(): void
    {
        $requiredPlaceholders = [
            '{{ namespace }}',
            '{{ class }}',
            '{{ icon }}',
            '{{ group }}',
        ];

        foreach ($requiredPlaceholders as $placeholder) {
            $this->assertStringContainsString(
                $placeholder,
                $this->stubContent,
                "Stub should contain placeholder: {$placeholder}"
            );
        }
    }

    public function test_card_stub_extends_correct_base_class(): void
    {
        $this->assertStringContainsString(
            'use JTD\AdminPanel\Cards\Card;',
            $this->stubContent,
            'Stub should import the Card base class'
        );

        $this->assertStringContainsString(
            'extends Card',
            $this->stubContent,
            'Stub should extend the Card base class'
        );
    }

    public function test_card_stub_has_proper_php_structure(): void
    {
        $this->assertStringStartsWith('<?php', $this->stubContent);
        $this->assertStringContainsString('declare(strict_types=1);', $this->stubContent);
        $this->assertStringContainsString('use Illuminate\Http\Request;', $this->stubContent);
    }

    public function test_card_stub_includes_required_methods(): void
    {
        $requiredMethods = [
            '__construct',
            'make',
            'meta',
            'getData',
        ];

        foreach ($requiredMethods as $method) {
            $this->assertStringContainsString(
                "function {$method}",
                $this->stubContent,
                "Stub should contain method: {$method}"
            );
        }

        // Ensure authorize method is NOT overridden (should use base class)
        $this->assertStringNotContainsString(
            'function authorize',
            $this->stubContent,
            'Stub should not override authorize method'
        );
    }

    public function test_card_stub_has_proper_docblocks(): void
    {
        $this->assertStringContainsString('/**', $this->stubContent);
        $this->assertStringContainsString('* {{ class }} Card', $this->stubContent);
        $this->assertStringContainsString('* @package {{ namespace }}', $this->stubContent);
    }

    public function test_variable_replacement_namespace(): void
    {
        $processed = str_replace('{{ namespace }}', 'App\\Admin\\Cards', $this->stubContent);
        
        $this->assertStringContainsString('namespace App\\Admin\\Cards;', $processed);
        $this->assertStringContainsString('* @package App\\Admin\\Cards', $processed);
        $this->assertStringNotContainsString('{{ namespace }}', $processed);
    }

    public function test_variable_replacement_class(): void
    {
        $processed = str_replace('{{ class }}', 'TestCard', $this->stubContent);
        
        $this->assertStringContainsString('class TestCard extends Card', $processed);
        $this->assertStringContainsString('* TestCard Card', $processed);
        $this->assertStringContainsString("'title' => 'TestCard'", $processed);
        $this->assertStringNotContainsString('{{ class }}', $processed);
    }

    public function test_variable_replacement_icon(): void
    {
        $processed = str_replace('{{ icon }}', 'chart-bar', $this->stubContent);
        
        $this->assertStringContainsString("'icon' => 'chart-bar'", $processed);
        $this->assertStringNotContainsString('{{ icon }}', $processed);
    }

    public function test_variable_replacement_group(): void
    {
        $processed = str_replace('{{ group }}', 'Analytics', $this->stubContent);
        
        $this->assertStringContainsString("'group' => 'Analytics'", $processed);
        $this->assertStringNotContainsString('{{ group }}', $processed);
    }

    public function test_complete_variable_replacement(): void
    {
        $replacements = [
            '{{ namespace }}' => 'App\\Admin\\Cards',
            '{{ class }}' => 'StatsCard',
            '{{ icon }}' => 'chart-pie',
            '{{ group }}' => 'Dashboard',
        ];

        $processed = $this->stubContent;
        foreach ($replacements as $placeholder => $value) {
            $processed = str_replace($placeholder, $value, $processed);
        }

        // Verify all placeholders are replaced
        foreach (array_keys($replacements) as $placeholder) {
            $this->assertStringNotContainsString($placeholder, $processed);
        }

        // Verify the processed content has correct structure
        $this->assertStringContainsString('namespace App\\Admin\\Cards;', $processed);
        $this->assertStringContainsString('class StatsCard extends Card', $processed);
        $this->assertStringContainsString("'icon' => 'chart-pie'", $processed);
        $this->assertStringContainsString("'group' => 'Dashboard'", $processed);
    }

    public function test_generated_code_is_valid_php(): void
    {
        $replacements = [
            '{{ namespace }}' => 'App\\Admin\\Cards',
            '{{ class }}' => 'ValidCard',
            '{{ icon }}' => 'star',
            '{{ group }}' => 'Test',
        ];

        $processed = $this->stubContent;
        foreach ($replacements as $placeholder => $value) {
            $processed = str_replace($placeholder, $value, $processed);
        }

        // Test that the generated code is syntactically valid PHP
        $tempFile = tempnam(sys_get_temp_dir(), 'card_stub_test');
        file_put_contents($tempFile, $processed);

        $output = [];
        $returnCode = 0;
        exec("php -l {$tempFile} 2>&1", $output, $returnCode);

        unlink($tempFile);

        $this->assertEquals(0, $returnCode, 'Generated PHP code should be syntactically valid');
    }

    public function test_stub_includes_nova_compatible_methods(): void
    {
        // Test that the stub includes Nova-compatible method patterns
        $this->assertStringContainsString('static function make()', $this->stubContent);
        $this->assertStringContainsString('withMeta([', $this->stubContent);
        $this->assertStringContainsString('parent::meta()', $this->stubContent);

        // Should NOT override authorize method - uses base class implementation
        $this->assertStringNotContainsString('authorize(Request $request)', $this->stubContent);
    }

    public function test_stub_has_proper_meta_structure(): void
    {
        $this->assertStringContainsString("'title' => '{{ class }}'", $this->stubContent);
        $this->assertStringContainsString("'description' => 'Custom {{ class }} card", $this->stubContent);
        $this->assertStringContainsString("'icon' => '{{ icon }}'", $this->stubContent);
        $this->assertStringContainsString("'group' => '{{ group }}'", $this->stubContent);
        $this->assertStringContainsString("'refreshable' => false", $this->stubContent);
    }

    public function test_stub_includes_example_data_method(): void
    {
        $this->assertStringContainsString('protected function getData()', $this->stubContent);
        $this->assertStringContainsString('example_value', $this->stubContent);
        $this->assertStringContainsString('This is example data', $this->stubContent);
    }
}
