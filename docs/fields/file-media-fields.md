# File & Media Fields

Fields for file uploads and media management in JTD Admin Panel.

## File Field

The `File` field provides complete file upload functionality with disk configuration, type restrictions, and multiple file support.

### Basic Usage

```php
use JTD\AdminPanel\Fields\File;

File::make('Document')
```

### Configuration Options

#### Storage Disk
Specify which disk to store files on:

```php
File::make('Document')
    ->disk('public') // Store on public disk
```

#### File Type Restrictions
Limit allowed file types:

```php
File::make('Document')
    ->acceptedTypes('.pdf,.doc,.docx')
```

#### File Size Limits
Control maximum file size:

```php
File::make('Document')
    ->rules('file', 'max:10240') // 10MB max
```

### Advanced Examples

```php
// Complete file upload field
File::make('Contract Document')
    ->disk('documents')
    ->acceptedTypes('.pdf,.doc,.docx')
    ->rules('required', 'file', 'max:5120', 'mimes:pdf,doc,docx')
    ->help('Upload contract document (PDF, DOC, or DOCX, max 5MB)')

// Multiple file upload
File::make('Attachments')
    ->disk('attachments')
    ->multiple() // Allow multiple files
    ->acceptedTypes('.pdf,.jpg,.png,.doc')
    ->rules('array', 'max:5') // Max 5 files
    ->help('Upload up to 5 attachments')

// Resume upload
File::make('Resume')
    ->disk('resumes')
    ->acceptedTypes('.pdf')
    ->rules('required', 'file', 'mimes:pdf', 'max:2048')
    ->help('Upload your resume in PDF format (max 2MB)')
```

### Storage Configuration

Configure storage disks in your `config/filesystems.php`:

```php
'disks' => [
    'documents' => [
        'driver' => 'local',
        'root' => storage_path('app/documents'),
        'url' => env('APP_URL').'/storage/documents',
        'visibility' => 'private',
    ],
    
    'public_uploads' => [
        'driver' => 'local',
        'root' => storage_path('app/public/uploads'),
        'url' => env('APP_URL').'/storage/uploads',
        'visibility' => 'public',
    ],
],
```

---

## Image Field

The `Image` field provides image upload with preview, thumbnails, dimensions control, and quality settings.

### Basic Usage

```php
use JTD\AdminPanel\Fields\Image;

Image::make('Profile Picture')
```

### Configuration Options

#### Storage Disk
Specify storage location:

```php
Image::make('Profile Picture')
    ->disk('public')
```

#### Image Dimensions
Control image size and quality:

```php
Image::make('Banner Image')
    ->dimensions(1200, 600) // Width x Height
    ->quality(85) // JPEG quality
```

#### Thumbnail Generation
Configure thumbnail display:

```php
Image::make('Product Image')
    ->thumbnail(150, 150) // Thumbnail size
    ->preview() // Show preview on forms
```

### Advanced Examples

```php
// Complete image field with all options
Image::make('Product Image')
    ->disk('products')
    ->dimensions(800, 600)
    ->quality(90)
    ->thumbnail(200, 200)
    ->preview()
    ->rules('required', 'image', 'max:2048', 'dimensions:min_width=400,min_height=300')
    ->help('Upload product image (min 400x300px, max 2MB)')

// Avatar image
Image::make('Avatar')
    ->disk('avatars')
    ->dimensions(300, 300)
    ->thumbnail(100, 100)
    ->rules('image', 'max:1024', 'dimensions:ratio=1/1')
    ->help('Square avatar image (max 1MB)')

// Gallery images
Image::make('Gallery Images')
    ->disk('gallery')
    ->multiple() // Multiple images
    ->thumbnail(150, 150)
    ->rules('array', 'max:10') // Max 10 images
    ->help('Upload up to 10 gallery images')
```

### Image Processing

The Image field can handle various image processing tasks:

```php
Image::make('Featured Image')
    ->disk('featured')
    ->dimensions(1200, 630) // Social media optimized
    ->quality(85)
    ->rules('required', 'image', 'dimensions:min_width=1200,min_height=630')
    ->help('Featured image for social sharing (1200x630px)')
```

---

## Avatar Field

The `Avatar` field extends the Image field with avatar-specific features and enhanced display options.

### Basic Usage

```php
use JTD\AdminPanel\Fields\Avatar;

Avatar::make('Avatar')
```

### Features

- Enhanced image field with avatar-specific features
- Display in search results next to resource titles
- squared() and rounded() display methods
- Optimized for user profile management
- Integration with existing image upload system

### Configuration Options

#### Display Styles
Control how avatars are displayed:

```php
Avatar::make('Profile Picture')
    ->squared() // Square edges

Avatar::make('Profile Picture')
    ->rounded() // Fully rounded edges (default for avatars)
```

#### Storage and Processing
```php
Avatar::make('Avatar')
    ->disk('avatars')
    ->dimensions(200, 200)
    ->thumbnail(50, 50)
    ->rules('image', 'max:1024', 'dimensions:ratio=1/1')
```

### Advanced Examples

```php
// Complete avatar field
Avatar::make('Profile Avatar')
    ->disk('avatars')
    ->dimensions(300, 300)
    ->thumbnail(75, 75)
    ->rounded()
    ->rules('image', 'max:2048', 'dimensions:min_width=100,min_height=100,ratio=1/1')
    ->help('Square profile picture (min 100x100px, max 2MB)')

// Team member avatar
Avatar::make('Team Photo')
    ->disk('team')
    ->dimensions(400, 400)
    ->thumbnail(100, 100)
    ->squared()
    ->rules('required', 'image', 'dimensions:ratio=1/1')
    ->help('Professional headshot (square format required)')
```

### Search Results Integration

Avatar fields automatically appear in search results:

```php
// In your Resource
public function title()
{
    return $this->name;
}

// Avatar will automatically appear next to the name in search results
Avatar::make('Avatar')
    ->disk('avatars')
    ->thumbnail(40, 40)
```

---

## Gravatar Field

The `Gravatar` field provides email-based avatar integration with automatic generation and fallback options.

### Basic Usage

```php
use JTD\AdminPanel\Fields\Gravatar;

Gravatar::make('Avatar')
```

### Features

- Automatic Gravatar generation from email addresses
- Configurable fallback options and sizing
- Support for Gravatar rating and default image settings
- Seamless integration with user management workflows

### Configuration Options

#### Email Source
Specify which field contains the email:

```php
Gravatar::make('Avatar')
    ->fromEmail('email') // Use 'email' field for Gravatar lookup
```

#### Size and Fallback
Configure Gravatar size and fallback behavior:

```php
Gravatar::make('Avatar')
    ->size(200) // Gravatar size in pixels
    ->default('mp') // Fallback to mystery person
```

#### Rating
Set appropriate content rating:

```php
Gravatar::make('Avatar')
    ->rating('pg') // G, PG, R, or X rating
```

### Advanced Examples

```php
// Complete Gravatar field
Gravatar::make('Profile Picture')
    ->fromEmail('email')
    ->size(150)
    ->default('identicon') // Generate unique pattern if no Gravatar
    ->rating('g') // Family-friendly content only
    ->help('Avatar generated from your email address via Gravatar')

// User profile Gravatar
Gravatar::make('Avatar')
    ->fromEmail('email_address')
    ->size(100)
    ->default('mp') // Mystery person fallback
    ->rating('pg')
    ->squared() // Square display
    ->help('We use Gravatar for profile pictures')

// Team member Gravatar
Gravatar::make('Team Avatar')
    ->fromEmail('work_email')
    ->size(200)
    ->default('retro') // Retro pattern fallback
    ->rating('g')
    ->help('Professional avatar from work email')
```

### Gravatar Options

#### Default Images
- `404` - Return 404 if no image
- `mp` - Mystery person silhouette
- `identicon` - Geometric pattern based on email
- `monsterid` - Monster face
- `wavatar` - Cartoon face
- `retro` - 8-bit arcade style
- `robohash` - Robot face
- `blank` - Transparent PNG

#### Ratings
- `g` - Suitable for all audiences
- `pg` - May contain rude gestures, provocatively dressed individuals
- `r` - May contain harsh profanity, intense violence, nudity
- `x` - May contain hardcore sexual imagery or extremely disturbing violence

---

## File Validation Examples

### File Type Validation
```php
File::make('Document')
    ->rules([
        'required',
        'file',
        'mimes:pdf,doc,docx,txt',
        'max:5120' // 5MB
    ])
```

### Image Validation
```php
Image::make('Photo')
    ->rules([
        'required',
        'image',
        'mimes:jpeg,png,jpg,gif',
        'max:2048',
        'dimensions:min_width=300,min_height=300,max_width=2000,max_height=2000'
    ])
```

### Multiple Files Validation
```php
File::make('Attachments')
    ->multiple()
    ->rules([
        'array',
        'max:5', // Max 5 files
        '*.file',
        '*.max:1024' // Each file max 1MB
    ])
```

---

## Storage Best Practices

### Security Considerations
```php
File::make('Upload')
    ->disk('secure_uploads') // Private disk
    ->rules('required', 'file', 'mimes:pdf', 'max:5120')
    ->acceptedTypes('.pdf') // Client-side restriction
```

### Performance Optimization
```php
Image::make('Gallery Image')
    ->disk('images')
    ->dimensions(1200, 800) // Resize on upload
    ->quality(85) // Optimize file size
    ->thumbnail(300, 200) // Generate thumbnail
```

### Organization
```php
File::make('Document')
    ->disk('documents')
    ->path(function ($request) {
        return 'documents/' . date('Y/m');
    }) // Organize by year/month
```

---

## Next Steps

- Learn about [Display & Formatting Fields](./display-formatting-fields.md)
- Explore [Relationship Fields](./relationship-fields.md)
- Review [Field Validation](../validation.md) patterns
- Understand [Resource](../resources.md) integration
