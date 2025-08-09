# Date & Time Fields

Fields for handling dates, times, and timezones in JTD Admin Panel.

## Date Field

The `Date` field provides a date picker with localization support for date-only values.

### Basic Usage

```php
use JTD\AdminPanel\Fields\Date;

Date::make('Birthday')
```

### Features

- Clean date picker interface
- Localization support
- Proper date formatting and validation
- Integration with Carbon for date manipulation

### Configuration Options

#### Date Format
The Date field automatically handles date formatting based on your application's locale settings.

```php
Date::make('Event Date')
    ->help('Select the date for your event')
```

### Advanced Examples

```php
// Date with validation
Date::make('Start Date')
    ->required()
    ->rules('date', 'after:today')
    ->help('Event must be scheduled for a future date')

// Birthday field
Date::make('Date of Birth')
    ->rules('date', 'before:today', 'after:1900-01-01')
    ->help('Enter your date of birth')

// Nullable date field
Date::make('Completion Date')
    ->nullable()
    ->rules('date', 'after_or_equal:start_date')
    ->help('Leave blank if not yet completed')
```

---

## DateTime Field

The `DateTime` field provides combined date and time input with timezone support, step intervals, and advanced configuration.

### Basic Usage

```php
use JTD\AdminPanel\Fields\DateTime;

DateTime::make('Created At')
```

### Configuration Options

#### Step Intervals
Control the time step intervals:

```php
DateTime::make('Appointment Time')
    ->step(15) // 15-minute intervals
```

#### Timezone Support
Handle timezone-aware datetime values:

```php
DateTime::make('Event Start')
    ->withTimezone() // Enable timezone handling
```

#### Format Configuration
Customize the datetime display format:

```php
DateTime::make('Published At')
    ->format('Y-m-d H:i:s') // Custom format
```

### Advanced Examples

```php
// Complete datetime field with all options
DateTime::make('Event Start Time')
    ->required()
    ->step(30) // 30-minute intervals
    ->withTimezone()
    ->rules('date', 'after:now')
    ->help('Select the start time for your event')

// Meeting scheduler
DateTime::make('Meeting Time')
    ->step(15) // 15-minute intervals
    ->rules([
        'required',
        'date',
        'after:now',
        function ($attribute, $value, $fail) {
            $hour = Carbon::parse($value)->hour;
            if ($hour < 9 || $hour > 17) {
                $fail('Meeting must be scheduled during business hours (9 AM - 5 PM)');
            }
        }
    ])
    ->help('Schedule during business hours (9 AM - 5 PM)')

// Publication datetime
DateTime::make('Publish At')
    ->nullable()
    ->default(now()->addHour())
    ->rules('date', 'after:now')
    ->help('Schedule publication time (leave blank to publish immediately)')
```

### Database Considerations

DateTime fields work with various database column types:

```php
// Migration examples
Schema::table('events', function (Blueprint $table) {
    $table->datetime('start_time');
    $table->timestamp('published_at')->nullable();
    $table->timestampTz('event_datetime'); // With timezone
});
```

### Model Casting

Cast datetime attributes appropriately in your models:

```php
// In your Eloquent model
protected $casts = [
    'start_time' => 'datetime',
    'published_at' => 'datetime',
    'event_datetime' => 'datetime',
];

protected $dates = [
    'start_time',
    'published_at',
    'event_datetime',
];
```

---

## Timezone Field

The `Timezone` field provides a comprehensive timezone selection dropdown with world coverage and regional grouping.

### Basic Usage

```php
use JTD\AdminPanel\Fields\Timezone;

Timezone::make('Timezone')
```

### Features

- Searchable timezone dropdown with world coverage
- Regional timezone grouping for better organization
- Integration with PHP timezone database
- Support for timezone abbreviations and full names

### Configuration Options

#### Searchable Interface
Enable search functionality for easy timezone discovery:

```php
Timezone::make('User Timezone')
    ->searchable() // Enable search within timezones
```

#### Default Values
Set default timezone values:

```php
Timezone::make('Timezone')
    ->default('America/New_York')
    ->help('Select your local timezone')
```

### Advanced Examples

```php
// User profile timezone
Timezone::make('Timezone')
    ->required()
    ->default(config('app.timezone'))
    ->searchable()
    ->help('This will be used for displaying dates and times')

// Event timezone
Timezone::make('Event Timezone')
    ->required()
    ->searchable()
    ->rules('required', 'timezone')
    ->help('Timezone for the event location')

// System timezone setting
Timezone::make('System Timezone')
    ->default('UTC')
    ->help('Default timezone for system operations')
```

### Common Timezone Values

```php
Timezone::make('Timezone')
    ->default(function () {
        // Common defaults based on user location or system settings
        return auth()->user()->timezone ?? config('app.timezone');
    })
```

### Validation

```php
Timezone::make('Timezone')
    ->rules('required', 'timezone')
    ->help('Must be a valid timezone identifier')
```

---

## Field Combinations

Date and time fields work well together for comprehensive temporal data management:

```php
public function fields(): array
{
    return [
        Date::make('Event Date')
            ->required()
            ->rules('date', 'after:today'),
            
        DateTime::make('Start Time')
            ->required()
            ->step(15)
            ->rules('date', 'after:event_date'),
            
        DateTime::make('End Time')
            ->required()
            ->step(15)
            ->rules('date', 'after:start_time'),
            
        Timezone::make('Event Timezone')
            ->required()
            ->searchable()
            ->default('America/New_York'),
    ];
}
```

---

## Validation Examples

### Date Validation
```php
Date::make('Start Date')
    ->rules([
        'required',
        'date',
        'after:today',
        'before:' . now()->addYear()->format('Y-m-d')
    ])
```

### DateTime Validation
```php
DateTime::make('Appointment')
    ->rules([
        'required',
        'date',
        'after:now',
        function ($attribute, $value, $fail) {
            $dayOfWeek = Carbon::parse($value)->dayOfWeek;
            if (in_array($dayOfWeek, [0, 6])) { // Sunday = 0, Saturday = 6
                $fail('Appointments cannot be scheduled on weekends');
            }
        }
    ])
```

### Timezone Validation
```php
Timezone::make('Timezone')
    ->rules([
        'required',
        'timezone',
        'in:' . implode(',', timezone_identifiers_list())
    ])
```

---

## Working with Carbon

All date/time fields integrate seamlessly with Carbon:

```php
// Custom date formatting
Date::make('Event Date')
    ->displayUsing(function ($value) {
        return $value ? Carbon::parse($value)->format('F j, Y') : null;
    })

// Relative time display
DateTime::make('Created At')
    ->displayUsing(function ($value) {
        return $value ? Carbon::parse($value)->diffForHumans() : null;
    })

// Timezone conversion
DateTime::make('Event Time')
    ->resolveUsing(function ($value) {
        if ($value && $this->timezone) {
            return Carbon::parse($value)->setTimezone($this->timezone);
        }
        return $value;
    })
```

---

## Localization Support

Date and time fields respect your application's locale settings:

```php
// In your AppServiceProvider
public function boot()
{
    Carbon::setLocale(config('app.locale'));
}

// Date field with localized display
Date::make('Event Date')
    ->displayUsing(function ($value) {
        return $value ? Carbon::parse($value)->translatedFormat('F j, Y') : null;
    })
```

---

## Performance Considerations

### Database Indexing
```php
// Migration with proper indexing
Schema::table('events', function (Blueprint $table) {
    $table->datetime('start_time')->index();
    $table->datetime('end_time')->index();
    $table->string('timezone')->index();
});
```

### Query Optimization
```php
// Efficient date range queries
DateTime::make('Created At')
    ->filterable(function ($request, $query, $value, $attribute) {
        if (is_array($value) && count($value) === 2) {
            $query->whereBetween($attribute, $value);
        }
    })
```

---

## Next Steps

- Explore [File & Media Fields](./file-media-fields.md) for uploads
- Learn about [Display & Formatting Fields](./display-formatting-fields.md)
- Review [Field Validation](../validation.md) patterns
- Understand [Resource](../resources.md) integration
