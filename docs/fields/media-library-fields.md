# Media Library Fields

Professional file and image management fields with Spatie Media Library integration for JTD Admin Panel.

## Overview

Media Library fields provide advanced file management capabilities including:
- **Collections**: Organize media into logical groups
- **Conversions**: Automatic image resizing and format conversion
- **Responsive Images**: Generate multiple sizes for different screen resolutions
- **Metadata**: Store and display file information
- **Validation**: File type, size, and dimension validation
- **Vue Components**: Modern drag-and-drop interfaces with progress indicators

## MediaLibraryFile Field

The `MediaLibraryFile` field provides professional file upload and management with collections, metadata, and download functionality.

### Basic Usage

```php
use JTD\AdminPanel\Fields\MediaLibraryFile;

MediaLibraryFile::make('Document')
    ->collection('documents')
    ->rules('required');
```

### Configuration Options

#### Collections
Organize files into logical collections:

```php
MediaLibraryFile::make('Contract')
    ->collection('contracts') // Store in 'contracts' collection
```

#### File Type Restrictions
Control which file types are accepted:

```php
MediaLibraryFile::make('Document')
    ->acceptsMimeTypes([
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'text/plain'
    ])
```

#### File Size Limits
Set maximum file size in KB:

```php
MediaLibraryFile::make('Document')
    ->maxFileSize(10240) // 10MB limit
```

#### Multiple Files
Allow multiple file uploads:

```php
MediaLibraryFile::make('Attachments')
    ->multiple()
    ->collection('attachments')
```

#### Storage Configuration
Specify storage disk:

```php
MediaLibraryFile::make('Document')
    ->disk('s3') // Use S3 storage
    ->collection('documents')
```

### Advanced Examples

```php
// Complete document upload field
MediaLibraryFile::make('Legal Documents')
    ->collection('legal-docs')
    ->disk('secure')
    ->acceptsMimeTypes(['application/pdf'])
    ->maxFileSize(20480) // 20MB
    ->multiple()
    ->rules('required', 'array', 'min:1')
    ->help('Upload PDF documents only. Maximum 20MB per file.')

// Single contract field
MediaLibraryFile::make('Contract')
    ->collection('contracts')
    ->singleFile()
    ->acceptsMimeTypes(['application/pdf'])
    ->rules('required')
    ->help('Upload the signed contract in PDF format')
```

---

## MediaLibraryImage Field

The `MediaLibraryImage` field provides advanced image management with automatic conversions, responsive images, and gallery functionality.

### Basic Usage

```php
use JTD\AdminPanel\Fields\MediaLibraryImage;

MediaLibraryImage::make('Featured Image')
    ->collection('images')
    ->rules('required');
```

### Configuration Options

#### Image Conversions
Define automatic image conversions:

```php
MediaLibraryImage::make('Product Images')
    ->conversions([
        'thumb' => ['width' => 150, 'height' => 150, 'fit' => 'crop'],
        'medium' => ['width' => 500, 'height' => 500, 'fit' => 'contain'],
        'large' => ['width' => 1200, 'height' => 1200, 'quality' => 90],
    ])
```

#### Responsive Images
Enable responsive image generation:

```php
MediaLibraryImage::make('Hero Image')
    ->responsiveImages() // Generate responsive variants
    ->conversions([
        'mobile' => ['width' => 640, 'quality' => 85],
        'tablet' => ['width' => 1024, 'quality' => 90],
        'desktop' => ['width' => 1920, 'quality' => 95],
    ])
```

#### Multiple Images with Limits
Allow multiple images with quantity limits:

```php
MediaLibraryImage::make('Product Gallery')
    ->multiple()
    ->limit(10) // Maximum 10 images
    ->collection('product-gallery')
```

#### Cropping Interface
Enable image cropping:

```php
MediaLibraryImage::make('Banner Image')
    ->enableCropping()
    ->conversions([
        'banner' => ['width' => 1200, 'height' => 400, 'fit' => 'crop']
    ])
```

#### Image Information Display
Show image dimensions and metadata:

```php
MediaLibraryImage::make('Photos')
    ->showImageDimensions()
    ->multiple()
    ->collection('photos')
```

### Advanced Examples

```php
// Complete product gallery
MediaLibraryImage::make('Product Images')
    ->collection('product-gallery')
    ->multiple()
    ->limit(8)
    ->conversions([
        'thumb' => ['width' => 200, 'height' => 200, 'fit' => 'crop'],
        'medium' => ['width' => 600, 'height' => 600, 'fit' => 'contain'],
        'large' => ['width' => 1200, 'height' => 1200, 'quality' => 90],
        'webp' => ['width' => 800, 'height' => 800, 'format' => 'webp']
    ])
    ->responsiveImages()
    ->enableCropping()
    ->showImageDimensions()
    ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp'])
    ->maxFileSize(5120) // 5MB
    ->rules('required', 'array', 'min:1', 'max:8')
    ->help('Upload 1-8 product images. JPEG, PNG, or WebP formats. Max 5MB each.')

// Hero banner with specific dimensions
MediaLibraryImage::make('Hero Banner')
    ->collection('banners')
    ->singleFile()
    ->conversions([
        'desktop' => ['width' => 1920, 'height' => 600, 'fit' => 'crop'],
        'tablet' => ['width' => 1024, 'height' => 400, 'fit' => 'crop'],
        'mobile' => ['width' => 640, 'height' => 300, 'fit' => 'crop']
    ])
    ->enableCropping()
    ->rules('required', 'image')
    ->help('Upload a banner image. Will be automatically resized for different devices.')
```

---

## MediaLibraryAvatar Field

The `MediaLibraryAvatar` field provides specialized avatar management with square cropping, fallback support, and size variants.

### Basic Usage

```php
use JTD\AdminPanel\Fields\MediaLibraryAvatar;

MediaLibraryAvatar::make('Profile Picture')
    ->collection('avatars')
    ->rules('nullable');
```

### Configuration Options

#### Fallback Images
Set default avatar when no image is uploaded:

```php
MediaLibraryAvatar::make('Avatar')
    ->fallbackUrl('/images/default-avatar.png')
    ->fallbackPath(public_path('images/default-avatar.png'))
```

#### Aspect Ratio Enforcement
Enforce square aspect ratio for avatars:

```php
MediaLibraryAvatar::make('Profile Picture')
    ->cropAspectRatio('1:1') // Square avatars
    ->enableCropping()
```

#### Size Variants
Define avatar size variants:

```php
MediaLibraryAvatar::make('Avatar')
    ->conversions([
        'thumb' => ['width' => 64, 'height' => 64, 'fit' => 'crop'],
        'medium' => ['width' => 150, 'height' => 150, 'fit' => 'crop'],
        'large' => ['width' => 400, 'height' => 400, 'fit' => 'crop']
    ])
```

### Advanced Examples

```php
// Complete user avatar field
MediaLibraryAvatar::make('Profile Picture')
    ->collection('user-avatars')
    ->cropAspectRatio('1:1')
    ->fallbackUrl('/images/default-user-avatar.png')
    ->conversions([
        'thumb' => ['width' => 64, 'height' => 64, 'fit' => 'crop', 'quality' => 85],
        'medium' => ['width' => 150, 'height' => 150, 'fit' => 'crop', 'quality' => 90],
        'large' => ['width' => 400, 'height' => 400, 'fit' => 'crop', 'quality' => 95]
    ])
    ->enableCropping()
    ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp'])
    ->maxFileSize(2048) // 2MB
    ->rules('nullable', 'image')
    ->help('Upload a square profile picture. Will be automatically cropped to fit.')

// Team member avatar with specific requirements
MediaLibraryAvatar::make('Team Photo')
    ->collection('team-avatars')
    ->cropAspectRatio('1:1')
    ->fallbackUrl('/images/team-placeholder.png')
    ->conversions([
        'card' => ['width' => 200, 'height' => 200, 'fit' => 'crop'],
        'detail' => ['width' => 300, 'height' => 300, 'fit' => 'crop']
    ])
    ->rules('required', 'image')
    ->help('Professional headshot required. Square format preferred.')
```

---

## Configuration

### Global Configuration

Media Library fields use configuration from `config/admin-panel.php`:

```php
'media_library' => [
    'default_disk' => 'public',
    'auto_cleanup' => true,
    'file_size_limits' => [
        'file' => 10240,  // 10MB
        'image' => 5120,  // 5MB
        'avatar' => 2048, // 2MB
    ],
    'default_conversions' => [
        'thumb' => ['width' => 150, 'height' => 150, 'fit' => 'crop'],
        'medium' => ['width' => 500, 'height' => 500, 'fit' => 'contain'],
        'large' => ['width' => 1200, 'height' => 1200, 'quality' => 90],
    ],
    'accepted_mime_types' => [
        'file' => ['application/pdf', 'text/plain', /* ... */],
        'image' => ['image/jpeg', 'image/png', 'image/webp'],
        'avatar' => ['image/jpeg', 'image/png', 'image/webp'],
    ],
],
```

### Environment Variables

Configure via environment variables:

```env
ADMIN_PANEL_MEDIA_DISK=s3
ADMIN_PANEL_MEDIA_AUTO_CLEANUP=true
ADMIN_PANEL_MAX_FILE_SIZE=10240
ADMIN_PANEL_MAX_IMAGE_SIZE=5120
ADMIN_PANEL_MAX_AVATAR_SIZE=2048
ADMIN_PANEL_RESPONSIVE_IMAGES=true
```

---

## Model Integration

### HasMedia Trait

Your models must implement the `HasMedia` interface and use the `InteractsWithMedia` trait:

```php
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Product extends Model implements HasMedia
{
    use InteractsWithMedia;

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('images')
            ->acceptsMimeTypes(['image/jpeg', 'image/png']);

        $this->addMediaCollection('documents')
            ->acceptsMimeTypes(['application/pdf']);
    }

    public function registerMediaConversions(Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(150)
            ->height(150)
            ->sharpen(10);
    }
}
```

### Database Migration

Publish and run the Media Library migration:

```bash
php artisan vendor:publish --provider="Spatie\MediaLibrary\MediaLibraryServiceProvider" --tag="migrations"
php artisan migrate
```

---

## Vue Components

### Features

All Media Library fields include modern Vue.js components with:

- **Drag-and-Drop Upload**: Intuitive file dropping interface
- **Progress Indicators**: Real-time upload progress
- **Image Preview**: Thumbnail and lightbox preview
- **File Management**: Remove, reorder, and download files
- **Validation Feedback**: Client-side validation with error messages
- **Dark Theme Support**: Automatic theme adaptation

### Component Names

- `MediaLibraryFileField.vue` - File upload component
- `MediaLibraryImageField.vue` - Image gallery component  
- `MediaLibraryAvatarField.vue` - Avatar upload component

---

## Best Practices

### Performance

1. **Use appropriate image sizes**: Don't generate conversions you won't use
2. **Optimize storage**: Use cloud storage (S3) for production
3. **Limit file sizes**: Set reasonable limits based on your use case
4. **Use WebP format**: For better compression and performance

### Security

1. **Validate file types**: Always restrict accepted MIME types
2. **Scan uploads**: Consider virus scanning for user uploads
3. **Limit file sizes**: Prevent abuse with reasonable size limits
4. **Use secure storage**: Store sensitive files on private disks

### User Experience

1. **Provide clear help text**: Explain file requirements
2. **Use fallback images**: Provide defaults for missing avatars
3. **Show progress**: Keep users informed during uploads
4. **Enable cropping**: Let users control image framing

---

For more information about Spatie Media Library, see the [official documentation](https://spatie.be/docs/laravel-medialibrary).
