# Laravel Possession - User Impersonation Package

[![Latest Version](https://img.shields.io/packagist/v/verseles/possession.svg?style=flat-square)](https://packagist.org/packages/verseles/possession)

A simple user impersonation package for Laravel with Sanctum compatibility.

## Features

- Secure user impersonation system
- Session-based impersonation
- Sanctum compatibility
- Simple administration controls
- Visual impersonation indicator
- Easy to integrate

## Warning

- This package is still in development, so use it with caution.
- It wasn't tested with all Laravel versions, only with Laravel 11.
- It is designed to be used with Sanctum and separated by subdomains (API and Web).

## Installation

1. Install via Composer:
```bash
composer require verseles/possession
```

2.Publish the configuration file (optional):
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

4. After possession, you can check if a user is possessed:
```php
if(auth()->user()->isPossessed()) {
    // Do something
}
```

## Usage

### Possess a user

```blade
@if(auth()->check() && auth()->user()->canImpersonate())
<form action="{{ route('possession.impersonate') }}" method="POST">
    @csrf
    <input type="hidden" name="user_id" value="{{ $user->id }}">
    <button type="submit">Impersonate User</button>
</form>
@endif
```

### Stop possessing

The package automatically adds a floating button when possessing a user.
```blade
@include('possession::impersonating')
```

## Configuration

Edit `config/possession.php` after publishing.

```php
return [
    'user_model' => App\Models\User::class,
    'admin_guard' => 'web',
    'session_keys' => [
        'original_user' => 'possession.original_user_id',
    ],
];
```

## License

The MIT License (MIT)
