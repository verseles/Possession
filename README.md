# Laravel Possession - User Impersonation Package

[![Latest Version](https://img.shields.io/packagist/v/verseles/possession.svg?style=flat-square)](https://packagist.org/packages/verseles/possession)
[![Tests](https://img.shields.io/github/actions/workflow/status/verseles/possession/tests.yml?label=tests&style=flat-square)](https://github.com/verseles/possession/actions)
[![PHPStan](https://img.shields.io/badge/PHPStan-level%208-brightgreen.svg?style=flat-square)](https://phpstan.org/)

A secure and feature-rich user impersonation package for Laravel with Sanctum compatibility.

## Features

- Secure user impersonation with session regeneration
- Multiple ways to identify users (ID, email, or model instance)
- Built-in routes and controller (configurable)
- Middleware for protecting routes during impersonation
- Laravel events for auditing (PossessionStarted, PossessionEnded)
- Global helper functions (`possess()`, `unpossess()`, `isPossessing()`)
- Facade with full IDE support
- Visual impersonation indicator (Blade component)
- Comprehensive exception handling
- PHPStan level 8 compliant
- Full test coverage

## Requirements

- PHP 8.2+
- Laravel 9.0, 10.0, 11.0, or 12.0

## Installation

1. Install via Composer:
```bash
composer require verseles/possession
```

2. Publish the configuration file (optional):
```bash
php artisan vendor:publish --tag=possession-config
```

3. Add the trait to your User model:
```php
use Verseles\Possession\Traits\ImpersonatesUsers;

class User extends Authenticatable
{
    use ImpersonatesUsers;

    public function canPossess(): bool
    {
        return $this->is_admin; // Your admin check logic
    }

    public function canBePossessed(): bool
    {
        return !$this->is_admin; // Prevent impersonating other admins
    }
}
```

## Usage

### Using the Facade

```php
use Verseles\Possession\Facades\Possession;

// Start impersonation (accepts ID, email, or User model)
Possession::possess($user);
Possession::possess(123);
Possession::possess('user@example.com');

// Stop impersonation
Possession::unpossess();

// Check if currently impersonating
if (Possession::isPossessing()) {
    // ...
}

// Get the original admin user
$admin = Possession::getOriginalUser();
```

### Using Helper Functions

```php
// Start impersonation
possess($user);

// Stop impersonation
unpossess();

// Check status
if (isPossessing()) {
    // Currently impersonating
}
```

### Using the Trait Methods

```php
// Check if the current session is an impersonation
if (Auth::user()->isPossessed()) {
    // or use the alias
}

if (Auth::user()->isImpersonating()) {
    // ...
}
```

### Built-in Routes

The package automatically registers two routes:

| Method | URI | Name | Description |
|--------|-----|------|-------------|
| POST | `/possession/possess` | `possession.possess` | Start impersonating |
| POST | `/possession/leave` | `possession.leave` | Stop impersonating |

To use the possess route, send a POST request with a `user` parameter (ID or email).

**Disable automatic routes** in your config:
```php
'routes' => [
    'enabled' => false, // Define your own routes
],
```

### Blade Component

Include the visual indicator in your layout:
```blade
@include('possession::impersonating')
```

This shows a floating indicator when impersonating with a "Stop Impersonating" button.

## Middleware

The package includes three middleware for protecting routes:

### ForbidDuringPossession

Block access to sensitive routes during impersonation:

```php
Route::middleware('forbid-during-possession')->group(function () {
    Route::post('/user/password', [PasswordController::class, 'update']);
    Route::delete('/user/account', [AccountController::class, 'destroy']);
});
```

### EnsureNotPossessing

Abort with 403 if impersonating (stricter than ForbidDuringPossession):

```php
Route::middleware('ensure-not-possessing')->group(function () {
    Route::post('/billing/subscribe', [BillingController::class, 'subscribe']);
});
```

### SharePossessionState

Share `$isPossessing` and `$originalUser` with all views:

```php
Route::middleware('share-possession-state')->group(function () {
    // All views in this group will have access to:
    // $isPossessing (bool)
    // $originalUser (User|null)
});
```

## Events

The package dispatches events for auditing:

```php
use Verseles\Possession\Events\PossessionStarted;
use Verseles\Possession\Events\PossessionEnded;

// In your EventServiceProvider
protected $listen = [
    PossessionStarted::class => [
        LogImpersonationStarted::class,
    ],
    PossessionEnded::class => [
        LogImpersonationEnded::class,
    ],
];

// In your listener
public function handle(PossessionStarted $event): void
{
    Log::info('Impersonation started', [
        'admin_id' => $event->admin->id,
        'target_id' => $event->target->id,
    ]);
}
```

## Exception Handling

```php
use Verseles\Possession\Exceptions\ImpersonationException;

try {
    Possession::possess($user);
} catch (ImpersonationException $e) {
    // Handle the error
    return back()->with('error', $e->getMessage());
}
```

Exception types (all extend `ImpersonationException`):
- `unauthorizedPossess()` - User cannot impersonate
- `unauthorizedUnpossess()` - Original user lost permission
- `targetCannotBePossessed()` - Target user is protected
- `selfPossession()` - Cannot impersonate yourself
- `noImpersonationActive()` - No active impersonation to end

## Configuration

Full configuration options in `config/possession.php`:

```php
return [
    // User model class
    'user_model' => App\Models\User::class,

    // Authentication guard
    'admin_guard' => 'web',

    // Session keys
    'session_keys' => [
        'original_user' => 'possession.original_user_id',
    ],

    // Routes configuration
    'routes' => [
        'enabled' => true,
        'prefix' => 'possession',
        'middleware' => ['web', 'auth'],
    ],

    // Redirect destinations
    'redirect_after_possess' => '/',
    'redirect_after_unpossess' => '/',
    'forbidden_redirect' => '/',
];
```

## Publishing Assets

```bash
# Publish configuration
php artisan vendor:publish --tag=possession-config

# Publish views (for customization)
php artisan vendor:publish --tag=possession-views

# Publish routes (for customization)
php artisan vendor:publish --tag=possession-routes
```

## Development

```bash
# Run tests
composer test

# Run PHPStan
composer analyse

# Format code with Pint
composer format

# Run all checks
composer check
```

## Security Considerations

- Session is fully regenerated on both possess and unpossess
- CSRF token is regenerated to prevent session fixation
- All session data is flushed during transitions
- Use `forbid-during-possession` middleware on sensitive routes
- Implement proper `canPossess()` and `canBePossessed()` logic
- Consider logging all impersonation events for audit trails
- Never expose impersonation controls to non-administrative users

## License

The MIT License (MIT)
