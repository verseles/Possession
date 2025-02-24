# Laravel Possession - User Impersonation Package

[![Latest Version](https://img.shields.io/packagist/v/verseles/possession.svg?style=flat-square)](https://packagist.org/packages/verseles/possession)

A simple user impersonation package for Laravel with Sanctum compatibility.

## Features

- Secure user impersonation system
- Global `possess()` and `unpossess()` methods
- Comprehensive exception handling
- Session-based impersonation
- Sanctum compatibility
- Simple administration controls
- Visual impersonation indicator
- Easy to integrate

## Warning

- This package is still in development, so use it with caution.
- It wasn't tested with all Laravel versions, only with Laravel 11.

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
    
    // Add your possession logic
    public function canPossess()
    {
        return $this->is_admin; // Your admin check logic
    }
    
    public function canBePossessed()
    {
        return !$this->is_admin; // Example restriction
    }
}
```

## Usage

### Global Methods

Use the facade for direct impersonation control:

```php
use Verseles\Possession\Facades\Possession;

// Possess a user (accepts ID, email, or User model instance)
try {
    Possession::possess($targetUser);
} catch (\Verseles\Possession\Exceptions\ImpersonationException $e) {
    // Handle exception
    return redirect()->back()->withErrors(['impersonation' => $e->getMessage()]);
}

// Stop possessing
try {
    Possession::unpossess();
} catch (\Verseles\Possession\Exceptions\ImpersonationException $e) {
    // Handle exception
    return redirect()->back()->withErrors(['impersonation' => $e->getMessage()]);
}
```

### Web Routes Example

```php
// routes/web.php
use Verseles\Possession\Facades\Possession;

Route::middleware(['web', 'auth:'.config('possession.admin_guard')])->group(function () {
    Route::post('/possession/impersonate/{user}', function ($user) {
        try {
            Possession::possess($user);
            return redirect()->route('dashboard');
        } catch (\Verseles\Possession\Exceptions\ImpersonationException $e) {
            return back()->withErrors(['impersonation' => $e->getMessage()]);
        }
    });
    
    Route::post('/possession/leave', function () {
        try {
            Possession::unpossess();
            return redirect()->route('admin.dashboard');
        } catch (\Verseles\Possession\Exceptions\ImpersonationException $e) {
            return back()->withErrors(['impersonation' => $e->getMessage()]);
        }
    });
});
```

### Blade Templates

**Start Impersonation:**
```blade
@if(auth()->check() && auth()->user()->canImpersonate())
<form action="{{ route('possession.impersonate', $user->id) }}" method="POST">
    @csrf
    <button type="submit">Impersonate User</button>
</form>
@endif
```

**Stop Impersonation:**
```blade
@include('possession::impersonating')
```

## Error Handling

The package throws specific exceptions you can catch:

```php
use Verseles\Possession\Exceptions\ImpersonationException;

try {
    Possession::possess($user);
} catch (ImpersonationException $e) {
    // Handle specific error types:
    if ($e->getCode() === 403) {
        // Authorization error
    }
    
    // General error handling
    return back()->withErrors(['impersonation' => $e->getMessage()]);
}
```

Available exceptions:
- `UnauthorizedPossessException`
- `UnauthorizedUnpossessException`
- `TargetCannotBePossessedException`
- `SelfPossessionException`
- `NoImpersonationActiveException`

## Configuration

Edit `config/possession.php` after publishing:

```php
return [
    'user_model' => App\Models\User::class,
    'admin_guard' => 'web',
    'session_keys' => [
        'original_user' => 'possession.original_user_id',
    ],
];
```

## Security Considerations

- Always protect impersonation routes with middleware
- Use separate guards for admin and regular users
- Regularly review your `canPossess()` and `canBePossessed()` logic
- Never expose impersonation controls to non-administrative users

## License

The MIT License (MIT)
