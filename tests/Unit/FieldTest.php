<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Unit;

use JTD\AdminPanel\Fields\Avatar;
use JTD\AdminPanel\Fields\Badge;
use JTD\AdminPanel\Fields\BelongsTo;
use JTD\AdminPanel\Fields\Boolean;
use JTD\AdminPanel\Fields\Code;
use JTD\AdminPanel\Fields\Color;
use JTD\AdminPanel\Fields\Currency;
use JTD\AdminPanel\Fields\DateTime;
use JTD\AdminPanel\Fields\Email;
use JTD\AdminPanel\Fields\File;
use JTD\AdminPanel\Fields\Gravatar;
use JTD\AdminPanel\Fields\HasMany;
use JTD\AdminPanel\Fields\Hidden;
use JTD\AdminPanel\Fields\ID;
use JTD\AdminPanel\Fields\Image;
use JTD\AdminPanel\Fields\ManyToMany;
use JTD\AdminPanel\Fields\MultiSelect;
use JTD\AdminPanel\Fields\Number;
use JTD\AdminPanel\Fields\Password;
use JTD\AdminPanel\Fields\PasswordConfirmation;
use JTD\AdminPanel\Fields\Select;
use JTD\AdminPanel\Fields\Slug;
use JTD\AdminPanel\Fields\Textarea;
use JTD\AdminPanel\Fields\Text;
use JTD\AdminPanel\Fields\Timezone;
use JTD\AdminPanel\Fields\URL;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use JTD\AdminPanel\Tests\TestCase;

/**
 * Field Unit Tests
 *
 * Tests for field classes including validation, visibility,
 * and value handling.
 *
 * @author Jeremy Fall <jerthedev@gmail.com>
 */
class FieldTest extends TestCase
{
    public function test_text_field_creation(): void
    {
        $field = Text::make('Name');

        $this->assertEquals('Name', $field->name);
        $this->assertEquals('name', $field->attribute);
        $this->assertEquals('TextField', $field->component);
    }

    public function test_field_with_custom_attribute(): void
    {
        $field = Text::make('Full Name', 'full_name');

        $this->assertEquals('Full Name', $field->name);
        $this->assertEquals('full_name', $field->attribute);
    }

    public function test_field_rules_configuration(): void
    {
        $field = Text::make('Name')->rules('required', 'max:255');

        $this->assertEquals(['required', 'max:255'], $field->rules);
    }

    public function test_field_creation_rules(): void
    {
        $field = Text::make('Name')
            ->rules('required')
            ->creationRules('min:3');

        $this->assertEquals(['required'], $field->rules);
        $this->assertEquals(['min:3'], $field->creationRules);
    }

    public function test_field_update_rules(): void
    {
        $field = Text::make('Name')
            ->rules('required')
            ->updateRules('nullable');

        $this->assertEquals(['required'], $field->rules);
        $this->assertEquals(['nullable'], $field->updateRules);
    }

    public function test_field_sortable(): void
    {
        $field = Text::make('Name')->sortable();

        $this->assertTrue($field->sortable);
    }

    public function test_field_nullable(): void
    {
        $field = Text::make('Name')->nullable();

        $this->assertTrue($field->nullable);
    }

    public function test_field_readonly(): void
    {
        $field = Text::make('Name')->readonly();

        $this->assertTrue($field->readonly);
    }

    public function test_field_visibility_methods(): void
    {
        $field = Text::make('Name');

        // Default visibility
        $this->assertTrue($field->isShownOnIndex());
        $this->assertTrue($field->isShownOnDetail());
        $this->assertTrue($field->isShownOnForms());

        // Hide on index
        $field->hideFromIndex();
        $this->assertFalse($field->isShownOnIndex());

        // Only on forms
        $field = Text::make('Password')->onlyOnForms();
        $this->assertFalse($field->isShownOnIndex());
        $this->assertFalse($field->isShownOnDetail());
        $this->assertTrue($field->isShownOnForms());

        // Except on forms
        $field = Text::make('ID')->exceptOnForms();
        $this->assertTrue($field->isShownOnIndex());
        $this->assertTrue($field->isShownOnDetail());
        $this->assertFalse($field->isShownOnForms());
    }

    public function test_email_field_component(): void
    {
        $field = Email::make('Email');

        $this->assertEquals('EmailField', $field->component);
    }

    public function test_password_field_component(): void
    {
        $field = Password::make('Password');

        $this->assertEquals('PasswordField', $field->component);
    }

    public function test_textarea_field_component(): void
    {
        $field = Textarea::make('Description');

        $this->assertEquals('TextareaField', $field->component);
    }

    public function test_textarea_field_rows(): void
    {
        $field = Textarea::make('Description')->rows(6);

        $this->assertEquals(6, $field->rows);
    }

    public function test_textarea_field_max_length(): void
    {
        $field = Textarea::make('Description')->maxLength(500);

        $this->assertEquals(500, $field->maxLength);
        $this->assertTrue($field->showCharacterCount);
    }

    public function test_textarea_field_auto_resize(): void
    {
        $field = Textarea::make('Description')->autoResize();

        $this->assertTrue($field->autoResize);
    }

    public function test_textarea_field_auto_resize_false(): void
    {
        $field = Textarea::make('Description')->autoResize(false);

        $this->assertFalse($field->autoResize);
    }

    public function test_textarea_field_show_character_count(): void
    {
        $field = Textarea::make('Description')->showCharacterCount();

        $this->assertTrue($field->showCharacterCount);
    }

    public function test_textarea_field_always_show(): void
    {
        $field = Textarea::make('Description')->alwaysShow();

        $this->assertTrue($field->alwaysShow);
    }

    public function test_textarea_field_always_show_false(): void
    {
        $field = Textarea::make('Description')->alwaysShow(false);

        $this->assertFalse($field->alwaysShow);
    }

    public function test_textarea_field_meta_information(): void
    {
        $field = Textarea::make('Description')
            ->rows(8)
            ->maxLength(1000)
            ->autoResize()
            ->alwaysShow();

        $meta = $field->meta();

        $this->assertArrayHasKey('rows', $meta);
        $this->assertArrayHasKey('maxLength', $meta);
        $this->assertArrayHasKey('autoResize', $meta);
        $this->assertArrayHasKey('showCharacterCount', $meta);
        $this->assertArrayHasKey('alwaysShow', $meta);
        $this->assertEquals(8, $meta['rows']);
        $this->assertEquals(1000, $meta['maxLength']);
        $this->assertTrue($meta['autoResize']);
        $this->assertTrue($meta['showCharacterCount']);
        $this->assertTrue($meta['alwaysShow']);
    }

    public function test_number_field_component(): void
    {
        $field = Number::make('Age');

        $this->assertEquals('NumberField', $field->component);
    }

    public function test_number_field_configuration(): void
    {
        $field = Number::make('Price')
            ->min(0)
            ->max(1000)
            ->step(0.01);

        $this->assertEquals(0, $field->min);
        $this->assertEquals(1000, $field->max);
        $this->assertEquals(0.01, $field->step);
    }

    public function test_boolean_field_component(): void
    {
        $field = Boolean::make('Active');

        $this->assertEquals('BooleanField', $field->component);
    }

    public function test_boolean_field_values(): void
    {
        $field = Boolean::make('Active')
            ->trueValue('yes')
            ->falseValue('no');

        $this->assertEquals('yes', $field->trueValue);
        $this->assertEquals('no', $field->falseValue);
    }

    public function test_select_field_component(): void
    {
        $field = Select::make('Status');

        $this->assertEquals('SelectField', $field->component);
    }

    public function test_select_field_options(): void
    {
        $options = [
            'draft' => 'Draft',
            'published' => 'Published',
        ];

        $field = Select::make('Status')->options($options);

        $this->assertEquals($options, $field->options);
    }

    public function test_select_field_searchable(): void
    {
        $field = Select::make('Status')->searchable();

        $this->assertTrue($field->searchable);
    }

    public function test_select_field_searchable_false(): void
    {
        $field = Select::make('Status')->searchable(false);

        $this->assertFalse($field->searchable);
    }

    public function test_select_field_enum_integration(): void
    {
        // Create a mock enum for testing
        $field = Select::make('Status');

        // Test that enum method exists and can be called
        $this->assertTrue(method_exists($field, 'enum'));
    }

    public function test_select_field_display_using_labels(): void
    {
        $field = Select::make('Status')->displayUsingLabels(false);

        $this->assertFalse($field->displayUsingLabels);
    }

    public function test_select_field_meta_information(): void
    {
        $options = ['draft' => 'Draft', 'published' => 'Published'];
        $field = Select::make('Status')
            ->options($options)
            ->searchable()
            ->displayUsingLabels(false);

        $meta = $field->meta();

        $this->assertArrayHasKey('options', $meta);
        $this->assertArrayHasKey('searchable', $meta);
        $this->assertArrayHasKey('displayUsingLabels', $meta);
        $this->assertEquals($options, $meta['options']);
        $this->assertTrue($meta['searchable']);
        $this->assertFalse($meta['displayUsingLabels']);
    }

    public function test_field_help_text(): void
    {
        $field = Text::make('Name')->help('Enter your full name');

        $this->assertEquals('Enter your full name', $field->helpText);
    }

    public function test_field_placeholder(): void
    {
        $field = Text::make('Name')->placeholder('Enter name...');

        $this->assertEquals('Enter name...', $field->placeholder);
    }

    public function test_field_default_value(): void
    {
        $field = Text::make('Status')->default('active');

        $this->assertEquals('active', $field->default);
    }

    public function test_field_json_serialization(): void
    {
        $field = Text::make('Name')
            ->rules('required')
            ->sortable()
            ->help('Enter your name');

        $json = $field->jsonSerialize();

        $this->assertIsArray($json);
        $this->assertEquals('Name', $json['name']);
        $this->assertEquals('name', $json['attribute']);
        $this->assertEquals('TextField', $json['component']);
        $this->assertEquals(['required'], $json['rules']);
        $this->assertTrue($json['sortable']);
        $this->assertEquals('Enter your name', $json['helpText']);
    }

    public function test_field_resolve_value(): void
    {
        $field = Text::make('Name');
        $model = (object) ['name' => 'John Doe'];

        $value = $field->resolveValue($model);

        $this->assertEquals('John Doe', $value);
    }

    public function test_field_resolve_nested_value(): void
    {
        $field = Text::make('User Name', 'user.name');
        $model = (object) [
            'user' => (object) ['name' => 'John Doe']
        ];

        $value = $field->resolveValue($model);

        $this->assertEquals('John Doe', $value);
    }

    public function test_field_can_be_made_searchable(): void
    {
        $field = Text::make('Name');
        $field->searchable();

        $this->assertTrue($field->searchable);
    }

    public function test_searchable_property_is_included_in_json(): void
    {
        $field = Text::make('Name')->searchable();
        $json = $field->jsonSerialize();

        $this->assertArrayHasKey('searchable', $json);
        $this->assertTrue($json['searchable']);
    }

    public function test_searchable_defaults_to_false(): void
    {
        $field = Text::make('Name');
        $json = $field->jsonSerialize();

        $this->assertArrayHasKey('searchable', $json);
        $this->assertFalse($json['searchable']);
    }

    public function test_field_can_be_made_required(): void
    {
        $field = Text::make('Name');
        $field->required();

        $this->assertContains('required', $field->rules);
    }

    public function test_required_method_adds_to_existing_rules(): void
    {
        $field = Text::make('Name');
        $field->rules('max:255');
        $field->required();

        $this->assertContains('required', $field->rules);
        $this->assertContains('max:255', $field->rules);
    }

    public function test_required_method_does_not_duplicate_rule(): void
    {
        $field = Text::make('Name');
        $field->rules('required', 'max:255');
        $field->required(); // Should not add duplicate

        $requiredCount = count(array_filter($field->rules, fn($rule) => $rule === 'required'));
        $this->assertEquals(1, $requiredCount, 'Should not duplicate required rule');
    }

    public function test_required_can_be_disabled(): void
    {
        $field = Text::make('Name');
        $field->required();
        $field->required(false);

        $this->assertNotContains('required', $field->rules);
    }

    // Test the 5 critical Nova compatibility methods
    public function test_show_on_index_method(): void
    {
        $field = Text::make('Name');
        $field->showOnIndex();

        $this->assertTrue($field->showOnIndex);
    }

    public function test_show_on_detail_method(): void
    {
        $field = Text::make('Name');
        $field->showOnDetail();

        $this->assertTrue($field->showOnDetail);
    }

    public function test_show_on_creating_method(): void
    {
        $field = Text::make('Name');
        $field->showOnCreating();

        $this->assertTrue($field->showOnCreation);
    }

    public function test_show_on_updating_method(): void
    {
        $field = Text::make('Name');
        $field->showOnUpdating();

        $this->assertTrue($field->showOnUpdate);
    }

    public function test_display_using_method(): void
    {
        $field = Text::make('Name');
        $field->displayUsing(function ($value) {
            return strtoupper($value);
        });

        $model = (object) ['name' => 'john doe'];
        $value = $field->resolveValue($model);

        $this->assertEquals('JOHN DOE', $value);
    }

    public function test_display_using_receives_resource_and_attribute(): void
    {
        $field = Text::make('Name');
        $field->displayUsing(function ($value, $resource, $attribute) {
            return "{$attribute}: {$value} (ID: {$resource->id})";
        });

        $model = (object) ['name' => 'john', 'id' => 123];
        $value = $field->resolveValue($model);

        $this->assertEquals('name: john (ID: 123)', $value);
    }

    // ========================================
    // DateTime Field Tests
    // ========================================

    public function test_datetime_field_creation(): void
    {
        $field = DateTime::make('Published At');

        $this->assertEquals('Published At', $field->name);
        $this->assertEquals('published_at', $field->attribute);
        $this->assertEquals('DateTimeField', $field->component);
    }

    public function test_datetime_field_with_custom_attribute(): void
    {
        $field = DateTime::make('Created Date', 'created_at');

        $this->assertEquals('Created Date', $field->name);
        $this->assertEquals('created_at', $field->attribute);
    }

    public function test_datetime_field_format_configuration(): void
    {
        $field = DateTime::make('Published At')
            ->format('Y-m-d H:i:s')
            ->displayFormat('F j, Y g:i A');

        $this->assertEquals('Y-m-d H:i:s', $field->storageFormat);
        $this->assertEquals('F j, Y g:i A', $field->displayFormat);
    }

    public function test_datetime_field_timezone_configuration(): void
    {
        $field = DateTime::make('Published At')
            ->timezone('America/New_York');

        $this->assertEquals('America/New_York', $field->timezone);
    }

    public function test_datetime_field_step_configuration(): void
    {
        $field = DateTime::make('Published At')
            ->step(15); // 15-minute intervals

        $this->assertEquals(15, $field->step);
    }

    public function test_datetime_field_min_max_configuration(): void
    {
        $field = DateTime::make('Published At')
            ->min('2024-01-01 00:00:00')
            ->max('2024-12-31 23:59:59');

        $this->assertEquals('2024-01-01 00:00:00', $field->minDateTime);
        $this->assertEquals('2024-12-31 23:59:59', $field->maxDateTime);
    }

    public function test_datetime_field_json_serialization(): void
    {
        $field = DateTime::make('Published At')
            ->format('Y-m-d H:i:s')
            ->displayFormat('F j, Y g:i A')
            ->timezone('UTC')
            ->step(30)
            ->rules('required');

        $json = $field->jsonSerialize();

        $this->assertIsArray($json);
        $this->assertEquals('Published At', $json['name']);
        $this->assertEquals('published_at', $json['attribute']);
        $this->assertEquals('DateTimeField', $json['component']);
        $this->assertEquals('Y-m-d H:i:s', $json['storageFormat']);
        $this->assertEquals('F j, Y g:i A', $json['displayFormat']);
        $this->assertEquals('UTC', $json['timezone']);
        $this->assertEquals(30, $json['step']);
        $this->assertEquals(['required'], $json['rules']);
    }

    public function test_datetime_field_resolve_value(): void
    {
        $field = DateTime::make('Published At');
        $model = (object) ['published_at' => '2024-01-15 14:30:00'];

        $value = $field->resolveValue($model);

        $this->assertEquals('2024-01-15 14:30:00', $value);
    }

    public function test_datetime_field_with_display_callback(): void
    {
        $field = DateTime::make('Published At')
            ->displayUsing(function ($value) {
                return $value ? date('M j, Y \a\t g:i A', strtotime($value)) : null;
            });

        $model = (object) ['published_at' => '2024-01-15 14:30:00'];
        $value = $field->resolveValue($model);

        $this->assertEquals('Jan 15, 2024 at 2:30 PM', $value);
    }

    // ========================================
    // Hidden Field Tests
    // ========================================

    public function test_hidden_field_creation(): void
    {
        $field = Hidden::make('ID');

        $this->assertEquals('ID', $field->name);
        $this->assertEquals('id', $field->attribute);
        $this->assertEquals('HiddenField', $field->component);
    }

    public function test_hidden_field_with_custom_attribute(): void
    {
        $field = Hidden::make('User ID', 'user_id');

        $this->assertEquals('User ID', $field->name);
        $this->assertEquals('user_id', $field->attribute);
    }

    public function test_hidden_field_default_visibility(): void
    {
        $field = Hidden::make('ID');

        // Hidden fields should not be shown on index or detail by default
        $this->assertFalse($field->isShownOnIndex());
        $this->assertFalse($field->isShownOnDetail());
        $this->assertTrue($field->isShownOnForms()); // But should be on forms
    }

    public function test_hidden_field_with_default_value(): void
    {
        $field = Hidden::make('Status')
            ->default('active');

        $this->assertEquals('active', $field->default);
    }

    public function test_hidden_field_json_serialization(): void
    {
        $field = Hidden::make('Token')
            ->default('abc123')
            ->rules('required');

        $json = $field->jsonSerialize();

        $this->assertIsArray($json);
        $this->assertEquals('Token', $json['name']);
        $this->assertEquals('token', $json['attribute']);
        $this->assertEquals('HiddenField', $json['component']);
        $this->assertEquals('abc123', $json['default']);
        $this->assertEquals(['required'], $json['rules']);
        $this->assertFalse($json['showOnIndex']);
        $this->assertFalse($json['showOnDetail']);
        $this->assertTrue($json['showOnCreation']);
        $this->assertTrue($json['showOnUpdate']);
    }

    public function test_hidden_field_resolve_value(): void
    {
        $field = Hidden::make('Token');
        $model = (object) ['token' => 'secret123'];

        $value = $field->resolveValue($model);

        $this->assertEquals('secret123', $value);
    }

    // ========================================
    // File Field Tests
    // ========================================

    public function test_file_field_creation(): void
    {
        $field = File::make('Document');

        $this->assertEquals('Document', $field->name);
        $this->assertEquals('document', $field->attribute);
        $this->assertEquals('FileField', $field->component);
    }

    public function test_file_field_with_custom_attribute(): void
    {
        $field = File::make('Resume', 'resume_file');

        $this->assertEquals('Resume', $field->name);
        $this->assertEquals('resume_file', $field->attribute);
    }

    public function test_file_field_disk_configuration(): void
    {
        $field = File::make('Document')
            ->disk('public');

        $this->assertEquals('public', $field->disk);
    }

    public function test_file_field_path_configuration(): void
    {
        $field = File::make('Document')
            ->path('documents');

        $this->assertEquals('documents', $field->path);
    }

    public function test_file_field_accepted_types_configuration(): void
    {
        $field = File::make('Document')
            ->acceptedTypes('.pdf,.doc,.docx');

        $this->assertEquals('.pdf,.doc,.docx', $field->acceptedTypes);
    }

    public function test_file_field_max_size_configuration(): void
    {
        $field = File::make('Document')
            ->maxSize(10240); // 10MB in KB

        $this->assertEquals(10240, $field->maxSize);
    }

    public function test_file_field_multiple_files_configuration(): void
    {
        $field = File::make('Documents')
            ->multiple();

        $this->assertTrue($field->multiple);
    }

    public function test_file_field_download_callback(): void
    {
        $downloadCallback = function ($request, $model) {
            return Storage::download($model->document_path);
        };

        $field = File::make('Document')
            ->download($downloadCallback);

        $this->assertEquals($downloadCallback, $field->downloadCallback);
    }

    public function test_file_field_json_serialization(): void
    {
        $field = File::make('Document')
            ->disk('public')
            ->path('documents')
            ->acceptedTypes('.pdf,.doc,.docx')
            ->maxSize(5120)
            ->rules('required', 'mimes:pdf,doc,docx');

        $json = $field->jsonSerialize();

        $this->assertIsArray($json);
        $this->assertEquals('Document', $json['name']);
        $this->assertEquals('document', $json['attribute']);
        $this->assertEquals('FileField', $json['component']);
        $this->assertEquals('public', $json['disk']);
        $this->assertEquals('documents', $json['path']);
        $this->assertEquals('.pdf,.doc,.docx', $json['acceptedTypes']);
        $this->assertEquals(5120, $json['maxSize']);
        $this->assertFalse($json['multiple']);
        $this->assertEquals(['required', 'mimes:pdf,doc,docx'], $json['rules']);
    }

    public function test_file_field_resolve_value(): void
    {
        $field = File::make('Document');
        $model = (object) ['document' => 'documents/file.pdf'];

        $value = $field->resolveValue($model);

        $this->assertEquals('documents/file.pdf', $value);
    }

    public function test_file_field_with_display_callback(): void
    {
        $field = File::make('Document')
            ->displayUsing(function ($value) {
                return $value ? basename($value) : null;
            });

        $model = (object) ['document' => 'documents/my-file.pdf'];
        $value = $field->resolveValue($model);

        $this->assertEquals('my-file.pdf', $value);
    }

    public function test_file_field_fill_request(): void
    {
        Storage::fake('public');

        $field = File::make('Document')
            ->disk('public')
            ->path('documents');

        $file = UploadedFile::fake()->create('test.pdf', 100);
        $request = Request::create('/', 'POST', [], [], ['document' => $file]);
        $model = (object) [];

        $field->fill($request, $model);

        // The file should be stored and the path set on the model
        $this->assertObjectHasProperty('document', $model);
        $this->assertStringContains('documents/', $model->document);
        $this->assertStringContains('.pdf', $model->document);
    }

    public function test_file_field_fill_request_with_null_value(): void
    {
        $field = File::make('Document');
        $request = Request::create('/', 'POST', ['document' => null]);
        $model = (object) ['document' => 'existing-file.pdf'];

        $field->fill($request, $model);

        // Should not change existing value when null is provided
        $this->assertEquals('existing-file.pdf', $model->document);
    }

    // ========================================
    // Image Field Tests
    // ========================================

    public function test_image_field_creation(): void
    {
        $field = Image::make('Featured Image');

        $this->assertEquals('Featured Image', $field->name);
        $this->assertEquals('featured_image', $field->attribute);
        $this->assertEquals('ImageField', $field->component);
    }

    public function test_image_field_with_custom_attribute(): void
    {
        $field = Image::make('Profile Picture', 'avatar');

        $this->assertEquals('Profile Picture', $field->name);
        $this->assertEquals('avatar', $field->attribute);
    }

    public function test_image_field_inherits_file_functionality(): void
    {
        $field = Image::make('Featured Image')
            ->disk('public')
            ->path('images')
            ->maxSize(5120);

        $this->assertEquals('public', $field->disk);
        $this->assertEquals('images', $field->path);
        $this->assertEquals(5120, $field->maxSize);
    }

    public function test_image_field_squared_configuration(): void
    {
        $field = Image::make('Avatar')
            ->squared();

        $this->assertTrue($field->squared);
    }

    public function test_image_field_thumbnail_callback(): void
    {
        $thumbnailCallback = function () {
            return $this->featured_image_thumbnail;
        };

        $field = Image::make('Featured Image')
            ->thumbnail($thumbnailCallback);

        $this->assertEquals($thumbnailCallback, $field->thumbnailCallback);
    }

    public function test_image_field_preview_callback(): void
    {
        $previewCallback = function () {
            return $this->featured_image_url;
        };

        $field = Image::make('Featured Image')
            ->preview($previewCallback);

        $this->assertEquals($previewCallback, $field->previewCallback);
    }

    public function test_image_field_dimensions_configuration(): void
    {
        $field = Image::make('Featured Image')
            ->width(800)
            ->height(600);

        $this->assertEquals(800, $field->width);
        $this->assertEquals(600, $field->height);
    }

    public function test_image_field_quality_configuration(): void
    {
        $field = Image::make('Featured Image')
            ->quality(85);

        $this->assertEquals(85, $field->quality);
    }

    public function test_image_field_json_serialization(): void
    {
        $field = Image::make('Featured Image')
            ->disk('public')
            ->path('images')
            ->squared()
            ->width(800)
            ->height(600)
            ->quality(90)
            ->rules('required', 'image', 'max:5120');

        $json = $field->jsonSerialize();

        $this->assertIsArray($json);
        $this->assertEquals('Featured Image', $json['name']);
        $this->assertEquals('featured_image', $json['attribute']);
        $this->assertEquals('ImageField', $json['component']);
        $this->assertEquals('public', $json['disk']);
        $this->assertEquals('images', $json['path']);
        $this->assertTrue($json['squared']);
        $this->assertEquals(800, $json['width']);
        $this->assertEquals(600, $json['height']);
        $this->assertEquals(90, $json['quality']);
        $this->assertEquals(['required', 'image', 'max:5120'], $json['rules']);
    }

    public function test_image_field_resolve_value(): void
    {
        $field = Image::make('Featured Image');
        $model = (object) ['featured_image' => 'images/photo.jpg'];

        $value = $field->resolveValue($model);

        $this->assertEquals('images/photo.jpg', $value);
    }

    public function test_image_field_with_thumbnail_display(): void
    {
        $field = Image::make('Featured Image')
            ->thumbnail(function () {
                return 'images/thumbnails/photo_thumb.jpg';
            });

        $model = (object) ['featured_image' => 'images/photo.jpg'];

        // Test that thumbnail callback is properly set
        $this->assertIsCallable($field->thumbnailCallback);
    }

    public function test_image_field_fill_request(): void
    {
        Storage::fake('public');

        $field = Image::make('Featured Image')
            ->disk('public')
            ->path('images');

        $file = UploadedFile::fake()->image('test.jpg', 800, 600);
        $request = Request::create('/', 'POST', [], [], ['featured_image' => $file]);
        $model = (object) [];

        $field->fill($request, $model);

        // The image should be stored and the path set on the model
        $this->assertObjectHasProperty('featured_image', $model);
        $this->assertStringContains('images/', $model->featured_image);
        $this->assertStringContains('.jpg', $model->featured_image);
    }

    public function test_image_field_accepts_only_images(): void
    {
        $field = Image::make('Featured Image');

        // Image fields should have image-specific accepted types by default
        $this->assertStringContains('image', $field->acceptedTypes ?? '');
    }

    public function test_image_field_with_display_callback(): void
    {
        $field = Image::make('Featured Image')
            ->displayUsing(function ($value) {
                return $value ? asset('storage/' . $value) : null;
            });

        $model = (object) ['featured_image' => 'images/photo.jpg'];
        $value = $field->resolveValue($model);

        $this->assertStringContains('storage/images/photo.jpg', $value);
    }

    // ========================================
    // Currency Field Tests
    // ========================================

    public function test_currency_field_creation(): void
    {
        $field = Currency::make('Price');

        $this->assertEquals('Price', $field->name);
        $this->assertEquals('price', $field->attribute);
        $this->assertEquals('CurrencyField', $field->component);
    }

    public function test_currency_field_with_custom_attribute(): void
    {
        $field = Currency::make('Product Price', 'product_price');

        $this->assertEquals('Product Price', $field->name);
        $this->assertEquals('product_price', $field->attribute);
    }

    public function test_currency_field_locale_configuration(): void
    {
        $field = Currency::make('Price')
            ->locale('en_US');

        $this->assertEquals('en_US', $field->locale);
    }

    public function test_currency_field_currency_code_configuration(): void
    {
        $field = Currency::make('Price')
            ->currency('USD');

        $this->assertEquals('USD', $field->currency);
    }

    public function test_currency_field_symbol_configuration(): void
    {
        $field = Currency::make('Price')
            ->symbol('$');

        $this->assertEquals('$', $field->symbol);
    }

    public function test_currency_field_precision_configuration(): void
    {
        $field = Currency::make('Price')
            ->precision(3);

        $this->assertEquals(3, $field->precision);
    }

    public function test_currency_field_min_max_configuration(): void
    {
        $field = Currency::make('Price')
            ->min(0.01)
            ->max(999999.99);

        $this->assertEquals(0.01, $field->minValue);
        $this->assertEquals(999999.99, $field->maxValue);
    }

    public function test_currency_field_display_format_configuration(): void
    {
        $field = Currency::make('Price')
            ->displayFormat('symbol'); // symbol, code, name

        $this->assertEquals('symbol', $field->displayFormat);
    }

    public function test_currency_field_json_serialization(): void
    {
        $field = Currency::make('Price')
            ->locale('en_US')
            ->currency('USD')
            ->symbol('$')
            ->precision(2)
            ->min(0)
            ->max(10000)
            ->displayFormat('symbol')
            ->rules('required', 'numeric');

        $json = $field->jsonSerialize();

        $this->assertIsArray($json);
        $this->assertEquals('Price', $json['name']);
        $this->assertEquals('price', $json['attribute']);
        $this->assertEquals('CurrencyField', $json['component']);
        $this->assertEquals('en_US', $json['locale']);
        $this->assertEquals('USD', $json['currency']);
        $this->assertEquals('$', $json['symbol']);
        $this->assertEquals(2, $json['precision']);
        $this->assertEquals(0, $json['minValue']);
        $this->assertEquals(10000, $json['maxValue']);
        $this->assertEquals('symbol', $json['displayFormat']);
        $this->assertEquals(['required', 'numeric'], $json['rules']);
    }

    public function test_currency_field_resolve_value(): void
    {
        $field = Currency::make('Price');
        $model = (object) ['price' => 1234.56];

        $value = $field->resolveValue($model);

        $this->assertEquals(1234.56, $value);
    }

    public function test_currency_field_with_display_callback(): void
    {
        $field = Currency::make('Price')
            ->currency('USD')
            ->symbol('$')
            ->displayUsing(function ($value) {
                return $value ? '$' . number_format($value, 2) : null;
            });

        $model = (object) ['price' => 1234.56];
        $value = $field->resolveValue($model);

        $this->assertEquals('$1,234.56', $value);
    }

    public function test_currency_field_different_locales(): void
    {
        $usdField = Currency::make('Price USD')
            ->locale('en_US')
            ->currency('USD');

        $eurField = Currency::make('Price EUR')
            ->locale('de_DE')
            ->currency('EUR');

        $gbpField = Currency::make('Price GBP')
            ->locale('en_GB')
            ->currency('GBP');

        $this->assertEquals('en_US', $usdField->locale);
        $this->assertEquals('USD', $usdField->currency);

        $this->assertEquals('de_DE', $eurField->locale);
        $this->assertEquals('EUR', $eurField->currency);

        $this->assertEquals('en_GB', $gbpField->locale);
        $this->assertEquals('GBP', $gbpField->currency);
    }

    public function test_currency_field_step_configuration(): void
    {
        $field = Currency::make('Price')
            ->step(0.01);

        $this->assertEquals(0.01, $field->step);
    }

    // ========================================
    // URL Field Tests
    // ========================================

    public function test_url_field_creation(): void
    {
        $field = URL::make('Website');

        $this->assertEquals('Website', $field->name);
        $this->assertEquals('website', $field->attribute);
        $this->assertEquals('URLField', $field->component);
    }

    public function test_url_field_with_custom_attribute(): void
    {
        $field = URL::make('Company Website', 'company_url');

        $this->assertEquals('Company Website', $field->name);
        $this->assertEquals('company_url', $field->attribute);
    }

    public function test_url_field_clickable_configuration(): void
    {
        $field = URL::make('Website')
            ->clickable();

        $this->assertTrue($field->clickable);
    }

    public function test_url_field_target_configuration(): void
    {
        $field = URL::make('Website')
            ->target('_blank');

        $this->assertEquals('_blank', $field->target);
    }

    public function test_url_field_link_text_configuration(): void
    {
        $field = URL::make('Website')
            ->linkText('Visit Site');

        $this->assertEquals('Visit Site', $field->linkText);
    }

    public function test_url_field_show_favicon_configuration(): void
    {
        $field = URL::make('Website')
            ->showFavicon();

        $this->assertTrue($field->showFavicon);
    }

    public function test_url_field_protocol_configuration(): void
    {
        $field = URL::make('Website')
            ->protocol('https');

        $this->assertEquals('https', $field->protocol);
    }

    public function test_url_field_validation_configuration(): void
    {
        $field = URL::make('Website')
            ->validateUrl();

        $this->assertTrue($field->validateUrl);
    }

    public function test_url_field_json_serialization(): void
    {
        $field = URL::make('Website')
            ->clickable()
            ->target('_blank')
            ->linkText('Visit')
            ->showFavicon()
            ->protocol('https')
            ->validateUrl()
            ->rules('required', 'url');

        $json = $field->jsonSerialize();

        $this->assertIsArray($json);
        $this->assertEquals('Website', $json['name']);
        $this->assertEquals('website', $json['attribute']);
        $this->assertEquals('URLField', $json['component']);
        $this->assertTrue($json['clickable']);
        $this->assertEquals('_blank', $json['target']);
        $this->assertEquals('Visit', $json['linkText']);
        $this->assertTrue($json['showFavicon']);
        $this->assertEquals('https', $json['protocol']);
        $this->assertTrue($json['validateUrl']);
        $this->assertEquals(['required', 'url'], $json['rules']);
    }

    public function test_url_field_resolve_value(): void
    {
        $field = URL::make('Website');
        $model = (object) ['website' => 'https://example.com'];

        $value = $field->resolveValue($model);

        $this->assertEquals('https://example.com', $value);
    }

    public function test_url_field_with_display_callback(): void
    {
        $field = URL::make('Website')
            ->displayUsing(function ($value) {
                return $value ? parse_url($value, PHP_URL_HOST) : null;
            });

        $model = (object) ['website' => 'https://www.example.com/path'];
        $value = $field->resolveValue($model);

        $this->assertEquals('www.example.com', $value);
    }

    public function test_url_field_protocol_normalization(): void
    {
        $field = URL::make('Website')
            ->protocol('https')
            ->normalizeProtocol();

        $this->assertTrue($field->normalizeProtocol);
        $this->assertEquals('https', $field->protocol);
    }

    public function test_url_field_with_custom_link_text_callback(): void
    {
        $field = URL::make('Website')
            ->linkTextUsing(function ($value) {
                return $value ? 'Visit ' . parse_url($value, PHP_URL_HOST) : 'No URL';
            });

        $this->assertIsCallable($field->linkTextCallback);
    }

    public function test_url_field_preview_configuration(): void
    {
        $field = URL::make('Website')
            ->showPreview();

        $this->assertTrue($field->showPreview);
    }

    public function test_url_field_max_length_configuration(): void
    {
        $field = URL::make('Website')
            ->maxLength(255);

        $this->assertEquals(255, $field->maxLength);
    }

    public function test_url_field_placeholder_configuration(): void
    {
        $field = URL::make('Website')
            ->placeholder('https://example.com');

        $this->assertEquals('https://example.com', $field->placeholder);
    }

    public function test_url_field_with_validation_rules(): void
    {
        $field = URL::make('Website')
            ->rules('required', 'url', 'max:255');

        $this->assertEquals(['required', 'url', 'max:255'], $field->rules);
    }

    // ========================================
    // Badge Field Tests
    // ========================================

    public function test_badge_field_creation(): void
    {
        $field = Badge::make('Status');

        $this->assertEquals('Status', $field->name);
        $this->assertEquals('status', $field->attribute);
        $this->assertEquals('BadgeField', $field->component);
    }

    public function test_badge_field_with_custom_attribute(): void
    {
        $field = Badge::make('Order Status', 'order_status');

        $this->assertEquals('Order Status', $field->name);
        $this->assertEquals('order_status', $field->attribute);
    }

    public function test_badge_field_color_mapping(): void
    {
        $field = Badge::make('Status')
            ->map([
                'active' => 'success',
                'pending' => 'warning',
                'inactive' => 'error'
            ]);

        $this->assertEquals([
            'active' => 'success',
            'pending' => 'warning',
            'inactive' => 'error'
        ], $field->colorMap);
    }

    public function test_badge_field_default_color(): void
    {
        $field = Badge::make('Status')
            ->defaultColor('secondary');

        $this->assertEquals('secondary', $field->defaultColor);
    }

    public function test_badge_field_with_icons(): void
    {
        $field = Badge::make('Status')
            ->withIcons();

        $this->assertTrue($field->showIcons);
    }

    public function test_badge_field_icon_mapping(): void
    {
        $field = Badge::make('Status')
            ->iconMap([
                'active' => 'check-circle',
                'pending' => 'clock',
                'inactive' => 'x-circle'
            ]);

        $this->assertEquals([
            'active' => 'check-circle',
            'pending' => 'clock',
            'inactive' => 'x-circle'
        ], $field->iconMap);
    }

    public function test_badge_field_style_configuration(): void
    {
        $field = Badge::make('Status')
            ->style('outline');

        $this->assertEquals('outline', $field->style);
    }

    public function test_badge_field_size_configuration(): void
    {
        $field = Badge::make('Status')
            ->size('large');

        $this->assertEquals('large', $field->size);
    }

    public function test_badge_field_json_serialization(): void
    {
        $field = Badge::make('Status')
            ->map(['active' => 'success', 'inactive' => 'error'])
            ->defaultColor('secondary')
            ->withIcons()
            ->style('solid')
            ->size('medium')
            ->rules('required');

        $json = $field->jsonSerialize();

        $this->assertIsArray($json);
        $this->assertEquals('Status', $json['name']);
        $this->assertEquals('status', $json['attribute']);
        $this->assertEquals('BadgeField', $json['component']);
        $this->assertEquals(['active' => 'success', 'inactive' => 'error'], $json['colorMap']);
        $this->assertEquals('secondary', $json['defaultColor']);
        $this->assertTrue($json['showIcons']);
        $this->assertEquals('solid', $json['style']);
        $this->assertEquals('medium', $json['size']);
        $this->assertEquals(['required'], $json['rules']);
    }

    public function test_badge_field_resolve_color(): void
    {
        $field = Badge::make('Status')
            ->map(['active' => 'success', 'pending' => 'warning'])
            ->defaultColor('secondary');

        $this->assertEquals('success', $field->resolveColor('active'));
        $this->assertEquals('warning', $field->resolveColor('pending'));
        $this->assertEquals('secondary', $field->resolveColor('unknown'));
    }

    public function test_badge_field_resolve_icon(): void
    {
        $field = Badge::make('Status')
            ->iconMap(['active' => 'check', 'inactive' => 'x']);

        $this->assertEquals('check', $field->resolveIcon('active'));
        $this->assertEquals('x', $field->resolveIcon('inactive'));
        $this->assertNull($field->resolveIcon('unknown'));
    }

    public function test_badge_field_with_display_callback(): void
    {
        $field = Badge::make('Status')
            ->displayUsing(function ($value) {
                return strtoupper($value);
            });

        $model = (object) ['status' => 'active'];
        $value = $field->resolveValue($model);

        $this->assertEquals('ACTIVE', $value);
    }

    // ========================================
    // Enhanced Boolean Field Tests
    // ========================================

    public function test_boolean_field_custom_true_value(): void
    {
        $field = Boolean::make('Published')
            ->labels('Published', 'Draft');

        $this->assertEquals('Published', $field->trueText);
    }

    public function test_boolean_field_custom_false_value(): void
    {
        $field = Boolean::make('Published')
            ->labels('Published', 'Draft');

        $this->assertEquals('Draft', $field->falseText);
    }

    public function test_boolean_field_display_as_switch(): void
    {
        $field = Boolean::make('Active')
            ->displayAsSwitch();

        $this->assertEquals('switch', $field->displayMode);
    }

    public function test_boolean_field_display_as_button(): void
    {
        $field = Boolean::make('Active')
            ->displayAsButton();

        $this->assertEquals('button', $field->displayMode);
    }

    public function test_boolean_field_display_as_checkbox(): void
    {
        $field = Boolean::make('Active')
            ->displayAsCheckbox();

        $this->assertEquals('checkbox', $field->displayMode);
    }

    public function test_boolean_field_color_configuration(): void
    {
        $field = Boolean::make('Active')
            ->color('success');

        $this->assertEquals('success', $field->color);
    }

    public function test_boolean_field_size_configuration(): void
    {
        $field = Boolean::make('Active')
            ->size('large');

        $this->assertEquals('large', $field->size);
    }

    public function test_boolean_field_enhanced_json_serialization(): void
    {
        $field = Boolean::make('Published')
            ->labels('Published', 'Draft')
            ->displayAsSwitch()
            ->color('success')
            ->size('medium');

        $json = $field->jsonSerialize();

        $this->assertEquals('Published', $json['trueText']);
        $this->assertEquals('Draft', $json['falseText']);
        $this->assertEquals('switch', $json['displayMode']);
        $this->assertEquals('success', $json['color']);
        $this->assertEquals('medium', $json['size']);
    }

    public function test_boolean_field_resolve_display_value(): void
    {
        $field = Boolean::make('Published')
            ->labels('Published', 'Draft');

        $this->assertEquals('Published', $field->resolveDisplayValue(true));
        $this->assertEquals('Draft', $field->resolveDisplayValue(false));
        $this->assertEquals('Draft', $field->resolveDisplayValue(null));
    }

    // ========================================
    // Code Field Tests
    // ========================================

    public function test_code_field_creation(): void
    {
        $field = Code::make('Configuration');

        $this->assertEquals('Configuration', $field->name);
        $this->assertEquals('configuration', $field->attribute);
        $this->assertEquals('CodeField', $field->component);
    }

    public function test_code_field_with_custom_attribute(): void
    {
        $field = Code::make('API Config', 'api_configuration');

        $this->assertEquals('API Config', $field->name);
        $this->assertEquals('api_configuration', $field->attribute);
    }

    public function test_code_field_language_configuration(): void
    {
        $field = Code::make('Script')
            ->language('javascript');

        $this->assertEquals('javascript', $field->language);
    }

    public function test_code_field_theme_configuration(): void
    {
        $field = Code::make('Code')
            ->theme('dark');

        $this->assertEquals('dark', $field->theme);
    }

    public function test_code_field_line_numbers(): void
    {
        $field = Code::make('Code')
            ->lineNumbers();

        $this->assertTrue($field->showLineNumbers);
    }

    public function test_code_field_height_configuration(): void
    {
        $field = Code::make('Code')
            ->height(400);

        $this->assertEquals(400, $field->height);
    }

    public function test_code_field_readonly_mode(): void
    {
        $field = Code::make('Code')
            ->readOnly();

        $this->assertTrue($field->readOnly);
    }

    public function test_code_field_wrap_lines(): void
    {
        $field = Code::make('Code')
            ->wrapLines();

        $this->assertTrue($field->wrapLines);
    }

    public function test_code_field_json_serialization(): void
    {
        $field = Code::make('Configuration')
            ->language('json')
            ->theme('dark')
            ->lineNumbers()
            ->height(300)
            ->wrapLines()
            ->rules('required');

        $json = $field->jsonSerialize();

        $this->assertIsArray($json);
        $this->assertEquals('Configuration', $json['name']);
        $this->assertEquals('configuration', $json['attribute']);
        $this->assertEquals('CodeField', $json['component']);
        $this->assertEquals('json', $json['language']);
        $this->assertEquals('dark', $json['theme']);
        $this->assertTrue($json['showLineNumbers']);
        $this->assertEquals(300, $json['height']);
        $this->assertTrue($json['wrapLines']);
        $this->assertEquals(['required'], $json['rules']);
    }

    public function test_code_field_supported_languages(): void
    {
        $field = Code::make('Code');

        $supportedLanguages = $field->getSupportedLanguages();

        $this->assertIsArray($supportedLanguages);
        $this->assertContains('php', $supportedLanguages);
        $this->assertContains('javascript', $supportedLanguages);
        $this->assertContains('python', $supportedLanguages);
        $this->assertContains('sql', $supportedLanguages);
        $this->assertContains('json', $supportedLanguages);
    }

    public function test_code_field_auto_detect_language(): void
    {
        $field = Code::make('Code')
            ->autoDetectLanguage();

        $this->assertTrue($field->autoDetectLanguage);
    }

    public function test_code_field_with_display_callback(): void
    {
        $field = Code::make('Code')
            ->displayUsing(function ($value) {
                return strlen($value) . ' characters';
            });

        $model = (object) ['code' => 'console.log("hello");'];
        $value = $field->resolveValue($model);

        $this->assertEquals('21 characters', $value);
    }

    // ========================================
    // Color Field Tests
    // ========================================

    public function test_color_field_creation(): void
    {
        $field = Color::make('Brand Color');

        $this->assertEquals('Brand Color', $field->name);
        $this->assertEquals('brand_color', $field->attribute);
        $this->assertEquals('ColorField', $field->component);
    }

    public function test_color_field_with_custom_attribute(): void
    {
        $field = Color::make('Primary Color', 'primary_color');

        $this->assertEquals('Primary Color', $field->name);
        $this->assertEquals('primary_color', $field->attribute);
    }

    public function test_color_field_format_configuration(): void
    {
        $field = Color::make('Color')
            ->format('rgb');

        $this->assertEquals('rgb', $field->format);
    }

    public function test_color_field_with_alpha(): void
    {
        $field = Color::make('Color')
            ->withAlpha();

        $this->assertTrue($field->withAlpha);
    }

    public function test_color_field_palette_configuration(): void
    {
        $palette = ['#FF0000', '#00FF00', '#0000FF'];
        $field = Color::make('Color')
            ->palette($palette);

        $this->assertEquals($palette, $field->palette);
    }

    public function test_color_field_show_preview(): void
    {
        $field = Color::make('Color')
            ->showPreview();

        $this->assertTrue($field->showPreview);
    }

    public function test_color_field_swatches_configuration(): void
    {
        $swatches = [
            'primary' => '#007bff',
            'success' => '#28a745',
            'danger' => '#dc3545'
        ];
        $field = Color::make('Color')
            ->swatches($swatches);

        $this->assertEquals($swatches, $field->swatches);
    }

    public function test_color_field_json_serialization(): void
    {
        $field = Color::make('Brand Color')
            ->format('hex')
            ->withAlpha()
            ->palette(['#FF0000', '#00FF00'])
            ->showPreview()
            ->swatches(['primary' => '#007bff'])
            ->rules('required');

        $json = $field->jsonSerialize();

        $this->assertIsArray($json);
        $this->assertEquals('Brand Color', $json['name']);
        $this->assertEquals('brand_color', $json['attribute']);
        $this->assertEquals('ColorField', $json['component']);
        $this->assertEquals('hex', $json['format']);
        $this->assertTrue($json['withAlpha']);
        $this->assertEquals(['#FF0000', '#00FF00'], $json['palette']);
        $this->assertTrue($json['showPreview']);
        $this->assertEquals(['primary' => '#007bff'], $json['swatches']);
        $this->assertEquals(['required'], $json['rules']);
    }

    public function test_color_field_validate_hex_color(): void
    {
        $field = Color::make('Color');

        $this->assertTrue($field->isValidHexColor('#FF0000'));
        $this->assertTrue($field->isValidHexColor('#ff0000'));
        $this->assertTrue($field->isValidHexColor('#F00'));
        $this->assertFalse($field->isValidHexColor('FF0000'));
        $this->assertFalse($field->isValidHexColor('#GG0000'));
        $this->assertFalse($field->isValidHexColor('#FF00'));
    }

    public function test_color_field_validate_rgb_color(): void
    {
        $field = Color::make('Color');

        $this->assertTrue($field->isValidRgbColor('rgb(255, 0, 0)'));
        $this->assertTrue($field->isValidRgbColor('rgba(255, 0, 0, 0.5)'));
        $this->assertFalse($field->isValidRgbColor('rgb(256, 0, 0)'));
        $this->assertFalse($field->isValidRgbColor('rgb(255, 0)'));
    }

    public function test_color_field_convert_formats(): void
    {
        $field = Color::make('Color');

        $this->assertEquals('rgb(255, 0, 0)', $field->hexToRgb('#FF0000'));
        $this->assertEquals('#ff0000', $field->rgbToHex('rgb(255, 0, 0)'));
    }

    public function test_color_field_with_display_callback(): void
    {
        $field = Color::make('Color')
            ->displayUsing(function ($value) {
                return strtoupper($value);
            });

        $model = (object) ['color' => '#ff0000'];
        $value = $field->resolveValue($model);

        $this->assertEquals('#FF0000', $value);
    }

    public function test_color_field_default_value(): void
    {
        $field = Color::make('Color')
            ->default('#000000');

        $this->assertEquals('#000000', $field->default);
    }

    // ========================================
    // ID Field Tests
    // ========================================

    public function test_id_field_creation(): void
    {
        $field = ID::make();

        $this->assertEquals('ID', $field->name);
        $this->assertEquals('id', $field->attribute);
        $this->assertEquals('IDField', $field->component);
    }

    public function test_id_field_with_custom_name(): void
    {
        $field = ID::make('User ID');

        $this->assertEquals('User ID', $field->name);
        $this->assertEquals('id', $field->attribute); // Should still default to 'id'
    }

    public function test_id_field_with_custom_attribute(): void
    {
        $field = ID::make('User ID', 'user_id');

        $this->assertEquals('User ID', $field->name);
        $this->assertEquals('user_id', $field->attribute);
    }

    public function test_id_field_default_visibility(): void
    {
        $field = ID::make();

        // ID fields should be shown on index, detail, and update by default
        $this->assertTrue($field->isShownOnIndex());
        $this->assertTrue($field->isShownOnDetail());
        $this->assertTrue($field->showOnUpdate);

        // But hidden from creation forms by default (readonly on create)
        $this->assertFalse($field->showOnCreation);
    }

    public function test_id_field_is_sortable_by_default(): void
    {
        $field = ID::make();

        $this->assertTrue($field->sortable);
    }

    public function test_id_field_is_readonly_on_creation(): void
    {
        $field = ID::make();

        // ID fields should be readonly on creation forms
        $this->assertFalse($field->showOnCreation);
    }

    public function test_id_field_copyable_functionality(): void
    {
        $field = ID::make()->copyable();

        $this->assertTrue($field->copyable);
    }

    public function test_id_field_json_serialization(): void
    {
        $field = ID::make()
            ->sortable()
            ->copyable();

        $json = $field->jsonSerialize();

        $this->assertIsArray($json);
        $this->assertEquals('ID', $json['name']);
        $this->assertEquals('id', $json['attribute']);
        $this->assertEquals('IDField', $json['component']);
        $this->assertTrue($json['sortable']);
        $this->assertTrue($json['copyable']);
        $this->assertTrue($json['showOnIndex']);
        $this->assertTrue($json['showOnDetail']);
        $this->assertTrue($json['showOnUpdate']);
        $this->assertFalse($json['showOnCreation']);
    }

    public function test_id_field_resolve_value(): void
    {
        $field = ID::make();
        $model = (object) ['id' => 123];

        $value = $field->resolveValue($model);

        $this->assertEquals(123, $value);
    }

    public function test_id_field_with_custom_attribute_resolve(): void
    {
        $field = ID::make('User ID', 'user_id');
        $model = (object) ['user_id' => 456];

        $value = $field->resolveValue($model);

        $this->assertEquals(456, $value);
    }

    public function test_id_field_with_display_callback(): void
    {
        $field = ID::make()
            ->displayUsing(function ($value) {
                return '#' . $value;
            });

        $model = (object) ['id' => 123];
        $value = $field->resolveValue($model);

        $this->assertEquals('#123', $value);
    }

    public function test_id_field_can_be_made_visible_on_creation(): void
    {
        $field = ID::make()->showOnCreating();

        $this->assertTrue($field->showOnCreation);
    }

    public function test_id_field_copyable_defaults_to_false(): void
    {
        $field = ID::make();

        $this->assertFalse($field->copyable);
    }

    public function test_id_field_copyable_included_in_json(): void
    {
        $field = ID::make()->copyable();
        $json = $field->jsonSerialize();

        $this->assertArrayHasKey('copyable', $json);
        $this->assertTrue($json['copyable']);
    }

    public function test_id_field_copyable_false_included_in_json(): void
    {
        $field = ID::make();
        $json = $field->jsonSerialize();

        $this->assertArrayHasKey('copyable', $json);
        $this->assertFalse($json['copyable']);
    }

    // ========================================
    // PasswordConfirmation Field Tests
    // ========================================

    public function test_password_confirmation_field_creation(): void
    {
        $field = PasswordConfirmation::make('Password Confirmation');

        $this->assertEquals('Password Confirmation', $field->name);
        $this->assertEquals('password_confirmation', $field->attribute);
        $this->assertEquals('PasswordConfirmationField', $field->component);
    }

    public function test_password_confirmation_field_with_custom_attribute(): void
    {
        $field = PasswordConfirmation::make('Confirm Password', 'confirm_password');

        $this->assertEquals('Confirm Password', $field->name);
        $this->assertEquals('confirm_password', $field->attribute);
    }

    public function test_password_confirmation_field_default_visibility(): void
    {
        $field = PasswordConfirmation::make('Password Confirmation');

        // Password confirmation fields should only be shown on forms
        $this->assertFalse($field->isShownOnIndex());
        $this->assertFalse($field->isShownOnDetail());
        $this->assertTrue($field->isShownOnForms());
    }

    public function test_password_confirmation_field_never_resolves_value(): void
    {
        $field = PasswordConfirmation::make('Password Confirmation');
        $model = (object) ['password_confirmation' => 'secret123'];

        $field->resolve($model);

        // Should always be null for security
        $this->assertNull($field->value);
    }

    public function test_password_confirmation_field_does_not_fill_model(): void
    {
        $field = PasswordConfirmation::make('Password Confirmation');
        $request = \Illuminate\Http\Request::create('/', 'POST', ['password_confirmation' => 'secret123']);
        $model = (object) [];

        $field->fill($request, $model);

        // Should not set any attribute on the model
        $this->assertObjectNotHasProperty('password_confirmation', $model);
    }

    public function test_password_confirmation_field_json_serialization(): void
    {
        $field = PasswordConfirmation::make('Password Confirmation')
            ->rules('required');

        $json = $field->jsonSerialize();

        $this->assertIsArray($json);
        $this->assertEquals('Password Confirmation', $json['name']);
        $this->assertEquals('password_confirmation', $json['attribute']);
        $this->assertEquals('PasswordConfirmationField', $json['component']);
        $this->assertEquals(['required'], $json['rules']);
        $this->assertFalse($json['showOnIndex']);
        $this->assertFalse($json['showOnDetail']);
        $this->assertTrue($json['showOnCreation']);
        $this->assertTrue($json['showOnUpdate']);
    }

    public function test_password_confirmation_field_with_min_length(): void
    {
        $field = PasswordConfirmation::make('Password Confirmation')
            ->minLength(8);

        $this->assertEquals(8, $field->minLength);
        $this->assertContains('min:8', $field->rules);
    }

    public function test_password_confirmation_field_with_strength_indicator(): void
    {
        $field = PasswordConfirmation::make('Password Confirmation')
            ->showStrengthIndicator();

        $this->assertTrue($field->showStrengthIndicator);
    }

    public function test_password_confirmation_field_meta_information(): void
    {
        $field = PasswordConfirmation::make('Password Confirmation')
            ->minLength(8)
            ->showStrengthIndicator();

        $meta = $field->meta();

        $this->assertArrayHasKey('minLength', $meta);
        $this->assertArrayHasKey('showStrengthIndicator', $meta);
        $this->assertEquals(8, $meta['minLength']);
        $this->assertTrue($meta['showStrengthIndicator']);
    }

    // ========================================
    // MultiSelect Field Tests
    // ========================================

    public function test_multiselect_field_creation(): void
    {
        $field = MultiSelect::make('Tags');

        $this->assertEquals('Tags', $field->name);
        $this->assertEquals('tags', $field->attribute);
        $this->assertEquals('MultiSelectField', $field->component);
    }

    public function test_multiselect_field_with_custom_attribute(): void
    {
        $field = MultiSelect::make('Categories', 'category_ids');

        $this->assertEquals('Categories', $field->name);
        $this->assertEquals('category_ids', $field->attribute);
    }

    public function test_multiselect_field_options(): void
    {
        $options = [
            'red' => 'Red',
            'blue' => 'Blue',
            'green' => 'Green',
        ];

        $field = MultiSelect::make('Colors')->options($options);

        $this->assertEquals($options, $field->options);
    }

    public function test_multiselect_field_searchable(): void
    {
        $field = MultiSelect::make('Tags')->searchable();

        $this->assertTrue($field->searchable);
    }

    public function test_multiselect_field_searchable_false(): void
    {
        $field = MultiSelect::make('Tags')->searchable(false);

        $this->assertFalse($field->searchable);
    }

    public function test_multiselect_field_taggable(): void
    {
        $field = MultiSelect::make('Tags')->taggable();

        $this->assertTrue($field->taggable);
    }

    public function test_multiselect_field_taggable_false(): void
    {
        $field = MultiSelect::make('Tags')->taggable(false);

        $this->assertFalse($field->taggable);
    }

    public function test_multiselect_field_max_selections(): void
    {
        $field = MultiSelect::make('Tags')->maxSelections(5);

        $this->assertEquals(5, $field->maxSelections);
    }

    public function test_multiselect_field_resolve_array_value(): void
    {
        $field = MultiSelect::make('Tags');
        $model = (object) ['tags' => ['red', 'blue']];

        $field->resolve($model);

        $this->assertEquals(['red', 'blue'], $field->value);
    }

    public function test_multiselect_field_resolve_json_value(): void
    {
        $field = MultiSelect::make('Tags');
        $model = (object) ['tags' => '["red","blue"]'];

        $field->resolve($model);

        $this->assertEquals(['red', 'blue'], $field->value);
    }

    public function test_multiselect_field_fill_request(): void
    {
        $field = MultiSelect::make('Tags');
        $request = \Illuminate\Http\Request::create('/', 'POST', ['tags' => ['red', 'blue']]);
        $model = (object) [];

        $field->fill($request, $model);

        $this->assertEquals(['red', 'blue'], $model->tags);
    }

    public function test_multiselect_field_json_serialization(): void
    {
        $options = ['red' => 'Red', 'blue' => 'Blue'];
        $field = MultiSelect::make('Tags')
            ->options($options)
            ->searchable()
            ->taggable()
            ->maxSelections(3);

        $json = $field->jsonSerialize();

        $this->assertIsArray($json);
        $this->assertEquals('Tags', $json['name']);
        $this->assertEquals('tags', $json['attribute']);
        $this->assertEquals('MultiSelectField', $json['component']);
        $this->assertEquals($options, $json['options']);
        $this->assertTrue($json['searchable']);
        $this->assertTrue($json['taggable']);
        $this->assertEquals(3, $json['maxSelections']);
    }

    public function test_multiselect_field_meta_information(): void
    {
        $options = ['red' => 'Red', 'blue' => 'Blue'];
        $field = MultiSelect::make('Tags')
            ->options($options)
            ->searchable()
            ->taggable()
            ->maxSelections(5);

        $meta = $field->meta();

        $this->assertArrayHasKey('options', $meta);
        $this->assertArrayHasKey('searchable', $meta);
        $this->assertArrayHasKey('taggable', $meta);
        $this->assertArrayHasKey('maxSelections', $meta);
        $this->assertEquals($options, $meta['options']);
        $this->assertTrue($meta['searchable']);
        $this->assertTrue($meta['taggable']);
        $this->assertEquals(5, $meta['maxSelections']);
    }

    // ========================================
    // Slug Field Tests
    // ========================================

    public function test_slug_field_creation(): void
    {
        $field = Slug::make('Slug');

        $this->assertEquals('Slug', $field->name);
        $this->assertEquals('slug', $field->attribute);
        $this->assertEquals('SlugField', $field->component);
    }

    public function test_slug_field_with_custom_attribute(): void
    {
        $field = Slug::make('URL Slug', 'url_slug');

        $this->assertEquals('URL Slug', $field->name);
        $this->assertEquals('url_slug', $field->attribute);
    }

    public function test_slug_field_from_method(): void
    {
        $field = Slug::make('Slug')->from('title');

        $this->assertEquals('title', $field->fromAttribute);
    }

    public function test_slug_field_separator(): void
    {
        $field = Slug::make('Slug')->separator('_');

        $this->assertEquals('_', $field->separator);
    }

    public function test_slug_field_max_length(): void
    {
        $field = Slug::make('Slug')->maxLength(50);

        $this->assertEquals(50, $field->maxLength);
    }

    public function test_slug_field_lowercase(): void
    {
        $field = Slug::make('Slug')->lowercase(false);

        $this->assertFalse($field->lowercase);
    }

    public function test_slug_field_unique_validation(): void
    {
        $field = Slug::make('Slug')->unique('posts');

        $this->assertEquals('posts', $field->uniqueTable);
    }

    public function test_slug_field_unique_validation_with_column(): void
    {
        $field = Slug::make('Slug')->unique('posts', 'slug_column');

        $this->assertEquals('posts', $field->uniqueTable);
        $this->assertEquals('slug_column', $field->uniqueColumn);
    }

    public function test_slug_field_generate_slug(): void
    {
        $field = Slug::make('Slug');

        $slug = $field->generateSlug('Hello World Test');

        $this->assertEquals('hello-world-test', $slug);
    }

    public function test_slug_field_generate_slug_with_custom_separator(): void
    {
        $field = Slug::make('Slug')->separator('_');

        $slug = $field->generateSlug('Hello World Test');

        $this->assertEquals('hello_world_test', $slug);
    }

    public function test_slug_field_generate_slug_with_max_length(): void
    {
        $field = Slug::make('Slug')->maxLength(10);

        $slug = $field->generateSlug('This is a very long title that should be truncated');

        $this->assertEquals('this-is-a', $slug);
        $this->assertLessThanOrEqual(10, strlen($slug));
    }

    public function test_slug_field_meta_information(): void
    {
        $field = Slug::make('Slug')
            ->from('title')
            ->separator('_')
            ->maxLength(50)
            ->lowercase(false)
            ->unique('posts', 'slug_column');

        $meta = $field->meta();

        $this->assertArrayHasKey('fromAttribute', $meta);
        $this->assertArrayHasKey('separator', $meta);
        $this->assertArrayHasKey('maxLength', $meta);
        $this->assertArrayHasKey('lowercase', $meta);
        $this->assertArrayHasKey('uniqueTable', $meta);
        $this->assertArrayHasKey('uniqueColumn', $meta);
        $this->assertEquals('title', $meta['fromAttribute']);
        $this->assertEquals('_', $meta['separator']);
        $this->assertEquals(50, $meta['maxLength']);
        $this->assertFalse($meta['lowercase']);
        $this->assertEquals('posts', $meta['uniqueTable']);
        $this->assertEquals('slug_column', $meta['uniqueColumn']);
    }

    // ========================================
    // Timezone Field Tests
    // ========================================

    public function test_timezone_field_creation(): void
    {
        $field = Timezone::make('Timezone');

        $this->assertEquals('Timezone', $field->name);
        $this->assertEquals('timezone', $field->attribute);
        $this->assertEquals('TimezoneField', $field->component);
    }

    public function test_timezone_field_with_custom_attribute(): void
    {
        $field = Timezone::make('User Timezone', 'user_timezone');

        $this->assertEquals('User Timezone', $field->name);
        $this->assertEquals('user_timezone', $field->attribute);
    }

    public function test_timezone_field_searchable(): void
    {
        $field = Timezone::make('Timezone')->searchable();

        $this->assertTrue($field->searchable);
    }

    public function test_timezone_field_searchable_false(): void
    {
        $field = Timezone::make('Timezone')->searchable(false);

        $this->assertFalse($field->searchable);
    }

    public function test_timezone_field_group_by_region(): void
    {
        $field = Timezone::make('Timezone')->groupByRegion();

        $this->assertTrue($field->groupByRegion);
    }

    public function test_timezone_field_group_by_region_false(): void
    {
        $field = Timezone::make('Timezone')->groupByRegion(false);

        $this->assertFalse($field->groupByRegion);
    }

    public function test_timezone_field_only_common(): void
    {
        $field = Timezone::make('Timezone')->onlyCommon();

        $this->assertTrue($field->onlyCommon);
    }

    public function test_timezone_field_only_common_false(): void
    {
        $field = Timezone::make('Timezone')->onlyCommon(false);

        $this->assertFalse($field->onlyCommon);
    }

    public function test_timezone_field_has_timezones(): void
    {
        $field = Timezone::make('Timezone');

        $timezones = $field->getTimezones();

        $this->assertIsArray($timezones);
        $this->assertNotEmpty($timezones);
        // Check for a timezone that should exist in the full list
        $this->assertArrayHasKey('America/New_York', $timezones);
    }

    public function test_timezone_field_common_timezones_only(): void
    {
        $field = Timezone::make('Timezone')->onlyCommon();

        $timezones = $field->getTimezones();

        $this->assertIsArray($timezones);
        $this->assertNotEmpty($timezones);
        // Should have fewer timezones when only common ones are included
        $this->assertLessThan(100, count($timezones));
    }

    public function test_timezone_field_meta_information(): void
    {
        $field = Timezone::make('Timezone')
            ->searchable()
            ->groupByRegion()
            ->onlyCommon();

        $meta = $field->meta();

        $this->assertArrayHasKey('searchable', $meta);
        $this->assertArrayHasKey('groupByRegion', $meta);
        $this->assertArrayHasKey('onlyCommon', $meta);
        $this->assertArrayHasKey('timezones', $meta);
        $this->assertTrue($meta['searchable']);
        $this->assertTrue($meta['groupByRegion']);
        $this->assertTrue($meta['onlyCommon']);
        $this->assertIsArray($meta['timezones']);
    }

    // ========================================
    // Avatar Field Tests
    // ========================================

    public function test_avatar_field_creation(): void
    {
        $field = Avatar::make('Avatar');

        $this->assertEquals('Avatar', $field->name);
        $this->assertEquals('avatar', $field->attribute);
        $this->assertEquals('AvatarField', $field->component);
    }

    public function test_avatar_field_extends_image(): void
    {
        $field = Avatar::make('Avatar');

        $this->assertInstanceOf(Image::class, $field);
    }

    public function test_avatar_field_with_custom_attribute(): void
    {
        $field = Avatar::make('Profile Picture', 'profile_picture');

        $this->assertEquals('Profile Picture', $field->name);
        $this->assertEquals('profile_picture', $field->attribute);
    }

    public function test_avatar_field_squared_by_default(): void
    {
        $field = Avatar::make('Avatar');

        $this->assertTrue($field->squared);
    }

    public function test_avatar_field_rounded(): void
    {
        $field = Avatar::make('Avatar')->rounded();

        $this->assertTrue($field->rounded);
        $this->assertFalse($field->squared);
    }

    public function test_avatar_field_squared_override(): void
    {
        $field = Avatar::make('Avatar')->squared(false);

        $this->assertFalse($field->squared);
    }

    public function test_avatar_field_size(): void
    {
        $field = Avatar::make('Avatar')->size(100);

        $this->assertEquals(100, $field->size);
    }

    public function test_avatar_field_show_in_index(): void
    {
        $field = Avatar::make('Avatar')->showInIndex();

        $this->assertTrue($field->showInIndex);
    }

    public function test_avatar_field_show_in_index_false(): void
    {
        $field = Avatar::make('Avatar')->showInIndex(false);

        $this->assertFalse($field->showInIndex);
    }

    public function test_avatar_field_default_path(): void
    {
        $field = Avatar::make('Avatar');

        $this->assertEquals('avatars', $field->path);
    }

    public function test_avatar_field_meta_information(): void
    {
        $field = Avatar::make('Avatar')
            ->rounded()
            ->size(120)
            ->showInIndex();

        $meta = $field->meta();

        $this->assertArrayHasKey('squared', $meta);
        $this->assertArrayHasKey('rounded', $meta);
        $this->assertArrayHasKey('size', $meta);
        $this->assertArrayHasKey('showInIndex', $meta);
        $this->assertFalse($meta['squared']);
        $this->assertTrue($meta['rounded']);
        $this->assertEquals(120, $meta['size']);
        $this->assertTrue($meta['showInIndex']);
    }

    // ========================================
    // Gravatar Field Tests
    // ========================================

    public function test_gravatar_field_creation(): void
    {
        $field = Gravatar::make('Gravatar');

        $this->assertEquals('Gravatar', $field->name);
        $this->assertEquals('gravatar', $field->attribute);
        $this->assertEquals('GravatarField', $field->component);
    }

    public function test_gravatar_field_with_custom_attribute(): void
    {
        $field = Gravatar::make('Profile Picture', 'profile_picture');

        $this->assertEquals('Profile Picture', $field->name);
        $this->assertEquals('profile_picture', $field->attribute);
    }

    public function test_gravatar_field_from_email(): void
    {
        $field = Gravatar::make('Gravatar')->fromEmail('email');

        $this->assertEquals('email', $field->emailAttribute);
    }

    public function test_gravatar_field_size(): void
    {
        $field = Gravatar::make('Gravatar')->size(200);

        $this->assertEquals(200, $field->size);
    }

    public function test_gravatar_field_default_fallback(): void
    {
        $field = Gravatar::make('Gravatar')->defaultImage('mp');

        $this->assertEquals('mp', $field->defaultFallback);
    }

    public function test_gravatar_field_rating(): void
    {
        $field = Gravatar::make('Gravatar')->rating('pg');

        $this->assertEquals('pg', $field->rating);
    }

    public function test_gravatar_field_squared(): void
    {
        $field = Gravatar::make('Gravatar')->squared();

        $this->assertTrue($field->squared);
    }

    public function test_gravatar_field_rounded(): void
    {
        $field = Gravatar::make('Gravatar')->rounded();

        $this->assertTrue($field->rounded);
        $this->assertFalse($field->squared);
    }

    public function test_gravatar_field_generate_url(): void
    {
        $field = Gravatar::make('Gravatar')->size(100);

        $url = $field->generateGravatarUrl('test@example.com');

        $this->assertStringContainsString('gravatar.com/avatar/', $url);
        $this->assertStringContainsString('s=100', $url);
    }

    public function test_gravatar_field_generate_url_with_options(): void
    {
        $field = Gravatar::make('Gravatar')
            ->size(150)
            ->defaultImage('identicon')
            ->rating('g');

        $url = $field->generateGravatarUrl('test@example.com');

        $this->assertStringContainsString('s=150', $url);
        $this->assertStringContainsString('d=identicon', $url);
        $this->assertStringContainsString('r=g', $url);
    }

    public function test_gravatar_field_meta_information(): void
    {
        $field = Gravatar::make('Gravatar')
            ->fromEmail('email')
            ->size(120)
            ->defaultImage('mp')
            ->rating('pg')
            ->rounded();

        $meta = $field->meta();

        $this->assertArrayHasKey('emailAttribute', $meta);
        $this->assertArrayHasKey('size', $meta);
        $this->assertArrayHasKey('defaultFallback', $meta);
        $this->assertArrayHasKey('rating', $meta);
        $this->assertArrayHasKey('squared', $meta);
        $this->assertArrayHasKey('rounded', $meta);
        $this->assertEquals('email', $meta['emailAttribute']);
        $this->assertEquals(120, $meta['size']);
        $this->assertEquals('mp', $meta['defaultFallback']);
        $this->assertEquals('pg', $meta['rating']);
        $this->assertFalse($meta['squared']);
        $this->assertTrue($meta['rounded']);
    }

    // ========================================
    // Advanced Field Behavior Tests
    // ========================================

    public function test_field_immutable(): void
    {
        $field = Text::make('Name')->immutable();

        $this->assertTrue($field->immutable);
    }

    public function test_field_immutable_false(): void
    {
        $field = Text::make('Name')->immutable(false);

        $this->assertFalse($field->immutable);
    }

    public function test_field_filterable(): void
    {
        $field = Text::make('Name')->filterable();

        $this->assertTrue($field->filterable);
    }

    public function test_field_filterable_false(): void
    {
        $field = Text::make('Name')->filterable(false);

        $this->assertFalse($field->filterable);
    }

    public function test_field_copyable_base_class(): void
    {
        $field = Text::make('Name')->copyable();

        $this->assertTrue($field->copyable);
    }

    public function test_field_copyable_false_base_class(): void
    {
        $field = Text::make('Name')->copyable(false);

        $this->assertFalse($field->copyable);
    }

    public function test_field_as_html(): void
    {
        $field = Text::make('Description')->asHtml();

        $this->assertTrue($field->asHtml);
    }

    public function test_field_as_html_false(): void
    {
        $field = Text::make('Description')->asHtml(false);

        $this->assertFalse($field->asHtml);
    }

    public function test_field_text_align(): void
    {
        $field = Text::make('Amount')->textAlign('right');

        $this->assertEquals('right', $field->textAlign);
    }

    public function test_field_text_align_center(): void
    {
        $field = Text::make('Title')->textAlign('center');

        $this->assertEquals('center', $field->textAlign);
    }

    public function test_field_text_align_left(): void
    {
        $field = Text::make('Name')->textAlign('left');

        $this->assertEquals('left', $field->textAlign);
    }

    public function test_field_stacked(): void
    {
        $field = Text::make('Description')->stacked();

        $this->assertTrue($field->stacked);
    }

    public function test_field_stacked_false(): void
    {
        $field = Text::make('Description')->stacked(false);

        $this->assertFalse($field->stacked);
    }

    public function test_field_full_width(): void
    {
        $field = Text::make('Description')->fullWidth();

        $this->assertTrue($field->fullWidth);
    }

    public function test_field_full_width_false(): void
    {
        $field = Text::make('Description')->fullWidth(false);

        $this->assertFalse($field->fullWidth);
    }

    public function test_field_advanced_behavior_json_serialization(): void
    {
        $field = Text::make('Name')
            ->immutable()
            ->filterable()
            ->copyable()
            ->asHtml()
            ->textAlign('center')
            ->stacked()
            ->fullWidth();

        $json = $field->jsonSerialize();

        $this->assertArrayHasKey('immutable', $json);
        $this->assertArrayHasKey('filterable', $json);
        $this->assertArrayHasKey('copyable', $json);
        $this->assertArrayHasKey('asHtml', $json);
        $this->assertArrayHasKey('textAlign', $json);
        $this->assertArrayHasKey('stacked', $json);
        $this->assertArrayHasKey('fullWidth', $json);
        $this->assertTrue($json['immutable']);
        $this->assertTrue($json['filterable']);
        $this->assertTrue($json['copyable']);
        $this->assertTrue($json['asHtml']);
        $this->assertEquals('center', $json['textAlign']);
        $this->assertTrue($json['stacked']);
        $this->assertTrue($json['fullWidth']);
    }

    // ========================================
    // Text Field Specific Advanced Behavior Tests
    // ========================================

    public function test_text_field_enforce_maxlength(): void
    {
        $field = Text::make('Name')->enforceMaxlength();

        $this->assertTrue($field->enforceMaxlength);
    }

    public function test_text_field_enforce_maxlength_false(): void
    {
        $field = Text::make('Name')->enforceMaxlength(false);

        $this->assertFalse($field->enforceMaxlength);
    }

    public function test_text_field_enforce_maxlength_with_max_length(): void
    {
        $field = Text::make('Name')
            ->maxLength(50)
            ->enforceMaxlength();

        $this->assertEquals(50, $field->maxLength);
        $this->assertTrue($field->enforceMaxlength);
    }

    public function test_text_field_advanced_json_serialization(): void
    {
        $field = Text::make('Name')
            ->maxLength(100)
            ->enforceMaxlength()
            ->suggestions(['John', 'Jane', 'Bob'])
            ->copyable()
            ->textAlign('left');

        $json = $field->jsonSerialize();

        $this->assertArrayHasKey('maxLength', $json);
        $this->assertArrayHasKey('enforceMaxlength', $json);
        $this->assertArrayHasKey('suggestions', $json);
        $this->assertArrayHasKey('copyable', $json);
        $this->assertArrayHasKey('textAlign', $json);
        $this->assertEquals(100, $json['maxLength']);
        $this->assertTrue($json['enforceMaxlength']);
        $this->assertEquals(['John', 'Jane', 'Bob'], $json['suggestions']);
        $this->assertTrue($json['copyable']);
        $this->assertEquals('left', $json['textAlign']);
    }

    // ========================================
    // BelongsTo Relationship Field Tests
    // ========================================

    public function test_belongs_to_field_creation(): void
    {
        $field = BelongsTo::make('User');

        $this->assertEquals('User', $field->name);
        $this->assertEquals('user', $field->attribute);
        $this->assertEquals('BelongsToField', $field->component);
        $this->assertEquals('user', $field->relationshipName);
    }

    public function test_belongs_to_field_with_custom_attribute(): void
    {
        $field = BelongsTo::make('Author', 'author_id');

        $this->assertEquals('Author', $field->name);
        $this->assertEquals('author_id', $field->attribute);
        $this->assertEquals('author_id', $field->relationshipName);
    }

    public function test_belongs_to_field_with_resource_class(): void
    {
        $field = BelongsTo::make('User')->resource('App\\Resources\\UserResource');

        $this->assertEquals('App\\Resources\\UserResource', $field->resourceClass);
    }

    public function test_belongs_to_field_resource_method(): void
    {
        $field = BelongsTo::make('User')->resource('App\\Resources\\UserResource');

        $this->assertEquals('App\\Resources\\UserResource', $field->resourceClass);
    }

    public function test_belongs_to_field_relationship_method(): void
    {
        $field = BelongsTo::make('User')->relationship('author');

        $this->assertEquals('author', $field->relationshipName);
    }

    public function test_belongs_to_field_foreign_key(): void
    {
        $field = BelongsTo::make('User')->foreignKey('author_id');

        $this->assertEquals('author_id', $field->foreignKey);
    }

    public function test_belongs_to_field_owner_key(): void
    {
        $field = BelongsTo::make('User')->ownerKey('uuid');

        $this->assertEquals('uuid', $field->ownerKey);
    }

    public function test_belongs_to_field_searchable(): void
    {
        $field = BelongsTo::make('User')->searchable();

        $this->assertTrue($field->searchable);
    }

    public function test_belongs_to_field_searchable_false(): void
    {
        $field = BelongsTo::make('User')->searchable(false);

        $this->assertFalse($field->searchable);
    }

    public function test_belongs_to_field_show_create_button(): void
    {
        $field = BelongsTo::make('User')->showCreateButton();

        $this->assertTrue($field->showCreateButton);
    }

    public function test_belongs_to_field_show_create_button_false(): void
    {
        $field = BelongsTo::make('User')->showCreateButton(false);

        $this->assertFalse($field->showCreateButton);
    }

    public function test_belongs_to_field_display_callback(): void
    {
        $callback = fn($model) => $model->name;
        $field = BelongsTo::make('User')->display($callback);

        $this->assertEquals($callback, $field->displayCallback);
    }

    public function test_belongs_to_field_query_callback(): void
    {
        $callback = fn($request, $query) => $query->where('active', true);
        $field = BelongsTo::make('User')->query($callback);

        $this->assertEquals($callback, $field->queryCallback);
    }

    public function test_belongs_to_field_guess_resource_class(): void
    {
        $field = BelongsTo::make('User Category', 'user_category');

        $this->assertEquals('App\\AdminPanel\\Resources\\UserCategory', $field->resourceClass);
    }

    public function test_belongs_to_field_meta_information(): void
    {
        $field = BelongsTo::make('User')
            ->resource('App\\Resources\\UserResource')
            ->relationship('author')
            ->foreignKey('author_id')
            ->ownerKey('uuid')
            ->searchable()
            ->showCreateButton();

        $meta = $field->meta();

        $this->assertArrayHasKey('resourceClass', $meta);
        $this->assertArrayHasKey('relationshipName', $meta);
        $this->assertArrayHasKey('foreignKey', $meta);
        $this->assertArrayHasKey('ownerKey', $meta);
        $this->assertArrayHasKey('searchable', $meta);
        $this->assertArrayHasKey('showCreateButton', $meta);
        $this->assertEquals('App\\Resources\\UserResource', $meta['resourceClass']);
        $this->assertEquals('author', $meta['relationshipName']);
        $this->assertEquals('author_id', $meta['foreignKey']);
        $this->assertEquals('uuid', $meta['ownerKey']);
        $this->assertTrue($meta['searchable']);
        $this->assertTrue($meta['showCreateButton']);
    }

    // ========================================
    // HasMany Relationship Field Tests
    // ========================================

    public function test_has_many_field_creation(): void
    {
        $field = HasMany::make('Posts');

        $this->assertEquals('Posts', $field->name);
        $this->assertEquals('posts', $field->attribute);
        $this->assertEquals('HasManyField', $field->component);
        $this->assertEquals('posts', $field->relationshipName);
    }

    public function test_has_many_field_with_custom_attribute(): void
    {
        $field = HasMany::make('Blog Posts', 'blog_posts');

        $this->assertEquals('Blog Posts', $field->name);
        $this->assertEquals('blog_posts', $field->attribute);
        $this->assertEquals('blog_posts', $field->relationshipName);
    }

    public function test_has_many_field_resource_method(): void
    {
        $field = HasMany::make('Posts')->resource('App\\Resources\\PostResource');

        $this->assertEquals('App\\Resources\\PostResource', $field->resourceClass);
    }

    public function test_has_many_field_relationship_method(): void
    {
        $field = HasMany::make('Posts')->relationship('blogPosts');

        $this->assertEquals('blogPosts', $field->relationshipName);
    }

    public function test_has_many_field_foreign_key(): void
    {
        $field = HasMany::make('Posts')->foreignKey('user_id');

        $this->assertEquals('user_id', $field->foreignKey);
    }

    public function test_has_many_field_local_key(): void
    {
        $field = HasMany::make('Posts')->localKey('uuid');

        $this->assertEquals('uuid', $field->localKey);
    }

    public function test_has_many_field_searchable(): void
    {
        $field = HasMany::make('Posts')->searchable();

        $this->assertTrue($field->searchable);
    }

    public function test_has_many_field_searchable_false(): void
    {
        $field = HasMany::make('Posts')->searchable(false);

        $this->assertFalse($field->searchable);
    }

    public function test_has_many_field_show_create_button(): void
    {
        $field = HasMany::make('Posts')->showCreateButton();

        $this->assertTrue($field->showCreateButton);
    }

    public function test_has_many_field_show_create_button_false(): void
    {
        $field = HasMany::make('Posts')->showCreateButton(false);

        $this->assertFalse($field->showCreateButton);
    }

    public function test_has_many_field_show_attach_button(): void
    {
        $field = HasMany::make('Posts')->showAttachButton();

        $this->assertTrue($field->showAttachButton);
    }

    public function test_has_many_field_show_attach_button_false(): void
    {
        $field = HasMany::make('Posts')->showAttachButton(false);

        $this->assertFalse($field->showAttachButton);
    }

    public function test_has_many_field_per_page(): void
    {
        $field = HasMany::make('Posts')->perPage(25);

        $this->assertEquals(25, $field->perPage);
    }

    public function test_has_many_field_display_fields(): void
    {
        $fields = ['title', 'status', 'created_at'];
        $field = HasMany::make('Posts')->displayFields($fields);

        $this->assertEquals($fields, $field->displayFields);
    }

    public function test_has_many_field_query_callback(): void
    {
        $callback = fn($request, $query) => $query->where('published', true);
        $field = HasMany::make('Posts')->query($callback);

        $this->assertEquals($callback, $field->queryCallback);
    }

    public function test_has_many_field_only_on_detail_by_default(): void
    {
        $field = HasMany::make('Posts');

        $this->assertFalse($field->showOnIndex);
        $this->assertTrue($field->showOnDetail);
        $this->assertFalse($field->showOnCreation);
        $this->assertFalse($field->showOnUpdate);
    }

    public function test_has_many_field_guess_resource_class(): void
    {
        $field = HasMany::make('Blog Posts', 'blog_posts');

        $this->assertEquals('App\\AdminPanel\\Resources\\BlogPosts', $field->resourceClass);
    }

    public function test_has_many_field_meta_information(): void
    {
        $field = HasMany::make('Posts')
            ->resource('App\\Resources\\PostResource')
            ->relationship('blogPosts')
            ->foreignKey('user_id')
            ->localKey('uuid')
            ->searchable()
            ->showCreateButton()
            ->showAttachButton()
            ->perPage(25)
            ->displayFields(['title', 'status']);

        $meta = $field->meta();

        $this->assertArrayHasKey('resourceClass', $meta);
        $this->assertArrayHasKey('relationshipName', $meta);
        $this->assertArrayHasKey('foreignKey', $meta);
        $this->assertArrayHasKey('localKey', $meta);
        $this->assertArrayHasKey('searchable', $meta);
        $this->assertArrayHasKey('showCreateButton', $meta);
        $this->assertArrayHasKey('showAttachButton', $meta);
        $this->assertArrayHasKey('perPage', $meta);
        $this->assertArrayHasKey('displayFields', $meta);
        $this->assertEquals('App\\Resources\\PostResource', $meta['resourceClass']);
        $this->assertEquals('blogPosts', $meta['relationshipName']);
        $this->assertEquals('user_id', $meta['foreignKey']);
        $this->assertEquals('uuid', $meta['localKey']);
        $this->assertTrue($meta['searchable']);
        $this->assertTrue($meta['showCreateButton']);
        $this->assertTrue($meta['showAttachButton']);
        $this->assertEquals(25, $meta['perPage']);
        $this->assertEquals(['title', 'status'], $meta['displayFields']);
    }

    // ========================================
    // ManyToMany Relationship Field Tests
    // ========================================

    public function test_many_to_many_field_creation(): void
    {
        $field = ManyToMany::make('Tags');

        $this->assertEquals('Tags', $field->name);
        $this->assertEquals('tags', $field->attribute);
        $this->assertEquals('ManyToManyField', $field->component);
        $this->assertEquals('tags', $field->relationshipName);
    }

    public function test_many_to_many_field_with_custom_attribute(): void
    {
        $field = ManyToMany::make('User Roles', 'user_roles');

        $this->assertEquals('User Roles', $field->name);
        $this->assertEquals('user_roles', $field->attribute);
        $this->assertEquals('user_roles', $field->relationshipName);
    }

    public function test_many_to_many_field_resource_method(): void
    {
        $field = ManyToMany::make('Tags')->resource('App\\Resources\\TagResource');

        $this->assertEquals('App\\Resources\\TagResource', $field->resourceClass);
    }

    public function test_many_to_many_field_relationship_method(): void
    {
        $field = ManyToMany::make('Tags')->relationship('userTags');

        $this->assertEquals('userTags', $field->relationshipName);
    }

    public function test_many_to_many_field_pivot_table(): void
    {
        $field = ManyToMany::make('Tags')->pivotTable('user_tags');

        $this->assertEquals('user_tags', $field->pivotTable);
    }

    public function test_many_to_many_field_foreign_pivot_key(): void
    {
        $field = ManyToMany::make('Tags')->foreignPivotKey('user_id');

        $this->assertEquals('user_id', $field->foreignPivotKey);
    }

    public function test_many_to_many_field_related_pivot_key(): void
    {
        $field = ManyToMany::make('Tags')->relatedPivotKey('tag_id');

        $this->assertEquals('tag_id', $field->relatedPivotKey);
    }

    public function test_many_to_many_field_parent_key(): void
    {
        $field = ManyToMany::make('Tags')->parentKey('uuid');

        $this->assertEquals('uuid', $field->parentKey);
    }

    public function test_many_to_many_field_related_key(): void
    {
        $field = ManyToMany::make('Tags')->relatedKey('uuid');

        $this->assertEquals('uuid', $field->relatedKey);
    }

    public function test_many_to_many_field_searchable(): void
    {
        $field = ManyToMany::make('Tags')->searchable();

        $this->assertTrue($field->searchable);
    }

    public function test_many_to_many_field_searchable_false(): void
    {
        $field = ManyToMany::make('Tags')->searchable(false);

        $this->assertFalse($field->searchable);
    }

    public function test_many_to_many_field_show_attach_button(): void
    {
        $field = ManyToMany::make('Tags')->showAttachButton();

        $this->assertTrue($field->showAttachButton);
    }

    public function test_many_to_many_field_show_attach_button_false(): void
    {
        $field = ManyToMany::make('Tags')->showAttachButton(false);

        $this->assertFalse($field->showAttachButton);
    }

    public function test_many_to_many_field_show_detach_button(): void
    {
        $field = ManyToMany::make('Tags')->showDetachButton();

        $this->assertTrue($field->showDetachButton);
    }

    public function test_many_to_many_field_show_detach_button_false(): void
    {
        $field = ManyToMany::make('Tags')->showDetachButton(false);

        $this->assertFalse($field->showDetachButton);
    }

    public function test_many_to_many_field_pivot_fields(): void
    {
        $fields = ['created_at', 'role', 'permissions'];
        $field = ManyToMany::make('Tags')->pivotFields($fields);

        $this->assertEquals($fields, $field->pivotFields);
    }

    public function test_many_to_many_field_display_callback(): void
    {
        $callback = fn($model) => $model->name;
        $field = ManyToMany::make('Tags')->display($callback);

        $this->assertEquals($callback, $field->displayCallback);
    }

    public function test_many_to_many_field_query_callback(): void
    {
        $callback = fn($request, $query) => $query->where('active', true);
        $field = ManyToMany::make('Tags')->query($callback);

        $this->assertEquals($callback, $field->queryCallback);
    }

    public function test_many_to_many_field_only_on_detail_by_default(): void
    {
        $field = ManyToMany::make('Tags');

        $this->assertFalse($field->showOnIndex);
        $this->assertTrue($field->showOnDetail);
        $this->assertFalse($field->showOnCreation);
        $this->assertFalse($field->showOnUpdate);
    }

    public function test_many_to_many_field_guess_resource_class(): void
    {
        $field = ManyToMany::make('User Roles', 'user_roles');

        $this->assertEquals('App\\AdminPanel\\Resources\\UserRoles', $field->resourceClass);
    }

    public function test_many_to_many_field_meta_information(): void
    {
        $field = ManyToMany::make('Tags')
            ->resource('App\\Resources\\TagResource')
            ->relationship('userTags')
            ->pivotTable('user_tags')
            ->foreignPivotKey('user_id')
            ->relatedPivotKey('tag_id')
            ->parentKey('uuid')
            ->relatedKey('uuid')
            ->searchable()
            ->showAttachButton()
            ->showDetachButton()
            ->pivotFields(['created_at', 'role']);

        $meta = $field->meta();

        $this->assertArrayHasKey('resourceClass', $meta);
        $this->assertArrayHasKey('relationshipName', $meta);
        $this->assertArrayHasKey('pivotTable', $meta);
        $this->assertArrayHasKey('foreignPivotKey', $meta);
        $this->assertArrayHasKey('relatedPivotKey', $meta);
        $this->assertArrayHasKey('parentKey', $meta);
        $this->assertArrayHasKey('relatedKey', $meta);
        $this->assertArrayHasKey('searchable', $meta);
        $this->assertArrayHasKey('showAttachButton', $meta);
        $this->assertArrayHasKey('showDetachButton', $meta);
        $this->assertArrayHasKey('pivotFields', $meta);
        $this->assertEquals('App\\Resources\\TagResource', $meta['resourceClass']);
        $this->assertEquals('userTags', $meta['relationshipName']);
        $this->assertEquals('user_tags', $meta['pivotTable']);
        $this->assertEquals('user_id', $meta['foreignPivotKey']);
        $this->assertEquals('tag_id', $meta['relatedPivotKey']);
        $this->assertEquals('uuid', $meta['parentKey']);
        $this->assertEquals('uuid', $meta['relatedKey']);
        $this->assertTrue($meta['searchable']);
        $this->assertTrue($meta['showAttachButton']);
        $this->assertTrue($meta['showDetachButton']);
        $this->assertEquals(['created_at', 'role'], $meta['pivotFields']);
    }

    // ========================================
    // Field-Level Authorization Tests
    // ========================================

    public function test_field_can_see_callback(): void
    {
        $callback = fn($request, $resource) => true;
        $field = Text::make('Name')->canSee($callback);

        $this->assertEquals($callback, $field->canSeeCallback);
    }

    public function test_field_can_update_callback(): void
    {
        $callback = fn($request, $resource) => true;
        $field = Text::make('Name')->canUpdate($callback);

        $this->assertEquals($callback, $field->canUpdateCallback);
    }

    public function test_field_authorized_to_see_returns_true_by_default(): void
    {
        $field = Text::make('Name');
        $request = new \Illuminate\Http\Request();

        $result = $field->authorizedToSee($request);

        $this->assertTrue($result);
    }

    public function test_field_authorized_to_see_with_callback(): void
    {
        $field = Text::make('Name')->canSee(fn($request, $resource) => false);
        $request = new \Illuminate\Http\Request();

        $result = $field->authorizedToSee($request);

        $this->assertFalse($result);
    }

    public function test_field_authorized_to_update_returns_true_by_default(): void
    {
        $field = Text::make('Name');
        $request = new \Illuminate\Http\Request();

        $result = $field->authorizedToUpdate($request);

        $this->assertTrue($result);
    }

    public function test_field_authorized_to_update_with_callback(): void
    {
        $field = Text::make('Name')->canUpdate(fn($request, $resource) => false);
        $request = new \Illuminate\Http\Request();

        $result = $field->authorizedToUpdate($request);

        $this->assertFalse($result);
    }

    public function test_field_only_for_role(): void
    {
        $field = Text::make('Name')->onlyForRole('admin');
        $request = new \Illuminate\Http\Request();

        // Mock user without role
        $request->setUserResolver(fn() => null);
        $this->assertFalse($field->authorizedToSee($request));

        // Mock user with different role
        $user = new class {
            public string $role = 'user';
            public function hasRole(string $role): bool { return $this->role === $role; }
        };
        $request->setUserResolver(fn() => $user);
        $this->assertFalse($field->authorizedToSee($request));

        // Mock user with admin role
        $user->role = 'admin';
        $this->assertTrue($field->authorizedToSee($request));
    }

    public function test_field_only_for_permission(): void
    {
        $field = Text::make('Name')->onlyForPermission('edit-posts');
        $request = new \Illuminate\Http\Request();

        // Mock user without permission
        $user = new class {
            public function hasPermission(string $permission): bool { return false; }
        };
        $request->setUserResolver(fn() => $user);
        $this->assertFalse($field->authorizedToSee($request));

        // Mock user with permission
        $user = new class {
            public function hasPermission(string $permission): bool { return $permission === 'edit-posts'; }
        };
        $request->setUserResolver(fn() => $user);
        $this->assertTrue($field->authorizedToSee($request));
    }

    public function test_field_only_for_admins(): void
    {
        $field = Text::make('Name')->onlyForAdmins();
        $request = new \Illuminate\Http\Request();

        // Mock user with user role
        $user = new class {
            public string $role = 'user';
            public function hasRole(string $role): bool { return $this->role === $role; }
        };
        $request->setUserResolver(fn() => $user);
        $this->assertFalse($field->authorizedToSee($request));

        // Mock user with admin role
        $user->role = 'admin';
        $this->assertTrue($field->authorizedToSee($request));
    }

    public function test_field_hide_from_index_when(): void
    {
        $field = Text::make('Name')->hideFromIndexWhen(fn($request, $resource) => true);

        $this->assertFalse($field->showOnIndex);
    }

    public function test_field_hide_from_detail_when(): void
    {
        $field = Text::make('Name')->hideFromDetailWhen(fn($request, $resource) => true);

        $this->assertFalse($field->showOnDetail);
    }

    public function test_field_readonly_when(): void
    {
        $field = Text::make('Name')->readonlyWhen(fn($request, $resource) => true);

        $this->assertTrue($field->readonly);
    }
}
