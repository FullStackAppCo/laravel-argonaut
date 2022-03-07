> This package is currently in development
> # Laravel Argonaut

Store simple, arbitrary, hierarchical values in one or more JSON Files persisted to the filesystem.
Retrieve and store values using Laravel's convenient dot syntax. Cached for performance.

## Installation
```
composer require fsac/laravel-argonaut
```

Publish the configuration file
```
php artisan vendor:publish --class='FullStackAppCo\Argonaut\ServiceProvider'
```


## Usage
Add a store by providing a `stores` configuration in your `config/argonaut.php` file. You must provide
the name of a disk configured in your `config/filesystems.php` and a path on that disk where Argonaut should 
persist the store. Stores should be keyed with a unique name which will be used to retrieve them.
```php
[
    'stores => [
        'theme' => [
            'path' => 'settings/theme.json',
            'disk' => 'local,
        ],
    ],
]
```

The `Argonaut` facade can be used to retrieve a store which has been configured in `config/argonaut.php`
```php
use FullStackAppCo\Argonaut\Facades\Argonaut;

Argonaut::store('theme');
```

Manipulating values:
```php
$store = Argonaut::store('theme');

// Store a value...
$store->put('color', '#bada55');

// Retrieve a value...
$store->get('color');
// => '#bada55'

// Store a nested value...
$store->put('colors.primary', '#bada55');

$store->get('colors');
// => ['primary' => '#bada55']
// Retrieve a nested value...
$store->get('colors.primary');
// => '#bada55'
```

You **must** call the save method to persist your store to disk:
```
$store = Argonaut::store('theme');
$store->put('color', '#bada55');

// Persist to disk...
$store->save();

// Methods may be chained for convenience...
Argonaut::store('theme')->put('colors.primary', '#bada55')->save();

```

## Testing
During testing an array driver is used so that data is not persisted to disk.