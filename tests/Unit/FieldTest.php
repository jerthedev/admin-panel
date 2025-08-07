<?php

declare(strict_types=1);

namespace JTD\AdminPanel\Tests\Unit;

use JTD\AdminPanel\Fields\Badge;
use JTD\AdminPanel\Fields\Boolean;
use JTD\AdminPanel\Fields\Code;
use JTD\AdminPanel\Fields\Color;
use JTD\AdminPanel\Fields\Currency;
use JTD\AdminPanel\Fields\DateTime;
use JTD\AdminPanel\Fields\Email;
use JTD\AdminPanel\Fields\File;
use JTD\AdminPanel\Fields\Hidden;
use JTD\AdminPanel\Fields\Image;
use JTD\AdminPanel\Fields\Number;
use JTD\AdminPanel\Fields\Password;
use JTD\AdminPanel\Fields\Select;
use JTD\AdminPanel\Fields\Textarea;
use JTD\AdminPanel\Fields\Text;
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
}
