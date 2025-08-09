# Text & Content Fields

Fields for rich text and content management in JTD Admin Panel.

## Textarea Field

The `Textarea` field provides multi-line text input with configurable height, content management, and validation.

### Basic Usage

```php
use JTD\AdminPanel\Fields\Textarea;

Textarea::make('Description')
```

### Configuration Options

#### Textarea Height
Control the height of the textarea:

```php
Textarea::make('Content')
    ->rows(5) // Set number of rows
```

#### Content Display
Control how content is displayed on detail pages:

```php
Textarea::make('Biography')
    ->alwaysShow() // Always display content (default: hidden behind "Show Content" link)
```

#### Character Limits
Set maximum character length with optional enforcement:

```php
Textarea::make('Excerpt')
    ->maxlength(500)
    ->enforceMaxlength() // Client-side enforcement
```

### Advanced Examples

```php
// Complete textarea with all options
Textarea::make('Article Content')
    ->rows(10)
    ->maxlength(5000)
    ->enforceMaxlength()
    ->alwaysShow()
    ->required()
    ->searchable()
    ->help('Write your article content here (max 5000 characters)')

// Short excerpt field
Textarea::make('Meta Description')
    ->rows(3)
    ->maxlength(160)
    ->enforceMaxlength()
    ->placeholder('Brief description for search engines')
    ->help('Keep under 160 characters for optimal SEO')
```

### Searchable Content

Textarea fields support full-text search:

```php
Textarea::make('Content')
    ->searchable() // Enables content-based filtering
    ->alwaysShow()
```

---

## Code Field

The `Code` field provides a syntax highlighting editor with support for 30+ programming languages, themes, and advanced features.

### Basic Usage

```php
use JTD\AdminPanel\Fields\Code;

Code::make('Source Code')
```

### Language Support

Specify the programming language for proper syntax highlighting:

```php
Code::make('PHP Code')
    ->language('php')

Code::make('JavaScript')
    ->language('javascript')

Code::make('SQL Query')
    ->language('sql')
```

#### Supported Languages
- `dockerfile`
- `htmlmixed`
- `javascript`
- `markdown`
- `nginx`
- `php`
- `ruby`
- `sass`
- `shell`
- `sql`
- `twig`
- `vim`
- `vue`
- `xml`
- `yaml-frontmatter`
- `yaml`

### JSON Support

Handle JSON data with automatic formatting:

```php
Code::make('Configuration')
    ->json() // Enables JSON mode with validation
```

### Advanced Examples

```php
// PHP code editor
Code::make('Custom Function')
    ->language('php')
    ->help('Write your custom PHP function here')
    ->rules('required')

// Configuration editor
Code::make('App Config')
    ->json()
    ->rules('required', 'json')
    ->help('Enter valid JSON configuration')

// SQL query editor
Code::make('Database Query')
    ->language('sql')
    ->placeholder('SELECT * FROM users WHERE...')
    ->help('Write your SQL query')
```

### Database Storage

Code fields work well with TEXT columns:

```php
// Migration
Schema::table('snippets', function (Blueprint $table) {
    $table->text('code');
    $table->json('config'); // For JSON code fields
});
```

### Model Casting

For JSON code fields, cast the attribute appropriately:

```php
// In your Eloquent model
protected $casts = [
    'config' => 'array',
    'metadata' => 'json',
];
```

---

## Slug Field

The `Slug` field provides URL-friendly slug generation with auto-updating from other fields and manual editing support.

### Basic Usage

```php
use JTD\AdminPanel\Fields\Slug;

Slug::make('Slug')
```

### Auto-Generation

Generate slugs automatically from other fields:

```php
Slug::make('Slug')
    ->from('title') // Auto-generate from title field
```

### Configuration Options

#### Source Field
Specify which field to generate the slug from:

```php
Slug::make('URL Slug')
    ->from('name') // Generate from 'name' field
```

#### Manual Editing
Allow manual slug editing with validation:

```php
Slug::make('Slug')
    ->from('title')
    ->rules('required', 'alpha_dash', 'unique:posts,slug')
```

### Advanced Examples

```php
// Complete slug field with validation
Slug::make('Slug')
    ->from('title')
    ->rules([
        'required',
        'alpha_dash',
        'max:100',
        'unique:articles,slug,' . $this->id
    ])
    ->help('URL-friendly version of the title')

// Custom slug generation
Slug::make('Slug')
    ->from('title')
    ->fillUsing(function ($request, $model, $attribute) {
        if (!$request->filled($attribute) && $request->filled('title')) {
            $model->{$attribute} = Str::slug($request->input('title'));
        } elseif ($request->filled($attribute)) {
            $model->{$attribute} = Str::slug($request->input($attribute));
        }
    })
```

### Uniqueness Validation

Ensure slug uniqueness across your application:

```php
Slug::make('Slug')
    ->from('title')
    ->rules([
        'required',
        'alpha_dash',
        Rule::unique('posts', 'slug')->ignore($this->id),
    ])
```

### SEO Considerations

```php
Slug::make('SEO Slug')
    ->from('title')
    ->rules('required', 'alpha_dash', 'max:60')
    ->help('Keep under 60 characters for optimal SEO')
    ->placeholder('auto-generated-from-title')
```

---

## Field Combinations

Text and content fields work well together for content management:

```php
public function fields(): array
{
    return [
        Text::make('Title')
            ->required()
            ->maxlength(100)
            ->searchable(),
            
        Slug::make('Slug')
            ->from('title')
            ->rules('required', 'unique:articles,slug'),
            
        Textarea::make('Excerpt')
            ->rows(3)
            ->maxlength(300)
            ->enforceMaxlength()
            ->help('Brief summary (max 300 characters)'),
            
        Textarea::make('Content')
            ->rows(15)
            ->required()
            ->searchable()
            ->alwaysShow(),
            
        Code::make('Custom CSS')
            ->language('css')
            ->nullable()
            ->help('Optional custom styling'),
    ];
}
```

---

## Content Management Best Practices

### SEO-Friendly Content

```php
Text::make('Title')
    ->maxlength(60)
    ->enforceMaxlength()
    ->help('Keep under 60 characters for SEO'),

Textarea::make('Meta Description')
    ->rows(2)
    ->maxlength(160)
    ->enforceMaxlength()
    ->help('Search engine description (160 chars max)'),

Slug::make('Slug')
    ->from('title')
    ->rules('unique:posts,slug')
```

### Content Validation

```php
Textarea::make('Content')
    ->required()
    ->rules('min:100')
    ->help('Minimum 100 characters required'),

Code::make('Schema Markup')
    ->json()
    ->nullable()
    ->rules('json')
    ->help('Optional JSON-LD schema markup')
```

### User Experience

```php
Textarea::make('Content')
    ->rows(10)
    ->alwaysShow() // Don't hide behind "Show Content" link
    ->placeholder('Start writing your content...')
    ->help('Use markdown for formatting')
```

---

## Searchability & Filtering

Enable search and filtering for content fields:

```php
Textarea::make('Content')
    ->searchable() // Full-text search
    ->alwaysShow(),

Code::make('Code Snippet')
    ->language('php')
    ->searchable() // Search within code

Slug::make('Slug')
    ->filterable() // Enable slug filtering
```

---

## Next Steps

- Learn about [Date & Time Fields](./date-time-fields.md) for temporal data
- Explore [File & Media Fields](./file-media-fields.md) for uploads
- Review [Field Validation](../validation.md) patterns
- Understand [Resource](../resources.md) integration
