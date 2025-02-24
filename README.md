# Laravel Possession - User Impersonation Package

[![Latest Version on Packagist](https://img.shields.io/packagist/v/verseles/possession.svg?style=flat-square)](https://packagist.org/packages/verseles/possession)
[![Total Downloads](https://img.shields.io/packagist/dt/verseles/possession.svg?style=flat-square)](https://packagist.org/packages/verseles/possession)

A simple user impersonation package for Laravel with Sanctum compatibility.
Provides a simple way to allow administrators to "possess" (impersonate) other users in your Laravel application.  It's useful for debugging, support, and testing.

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



## Installation

You can install the package via Composer:

```bash
composer require verseles/possession
```

After installation, publish the configuration file:

```bash
php artisan vendor:publish --provider="Verseles\Possession\PossessionServiceProvider" --tag="possession-config"
```

This will create a `config/possession.php` file where you can customize the package's behavior.

## Configuration

The `config/possession.php` file contains the following options:

```php
<?php

return [
    'user_model' => App\Models\User::class, // The User model class

    'session_keys' => [
        'original_user' => 'original_user_id', // Session key to store the original admin's ID
    ],
];
```

*   **`user_model`:**  Specify the fully qualified class name of your User model.  This is usually `App\Models\User` but can be customized.
*   **`session_keys.original_user`:**  This is the key used to store the original administrator's ID in the session. You generally don't need to change this.

## Usage

### 1. Add the Trait

Add the `ImpersonatesUsers` trait to your `User` model:

```php
<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Verseles\Possession\Traits\ImpersonatesUsers;

class User extends Authenticatable
{
    use ImpersonatesUsers;

    // ... your existing User model code ...

    /**
     * Determine if the user can possess other users.
     *
     * @return bool
     */
    public function canPossess(): bool
    {
        // Implement your logic here (e.g., check for an 'admin' role)
        return $this->is_admin; // Example:  Only users with 'is_admin' set to true can possess.
    }

    /**
     * Determine if the user can be possessed by other users.
     *
     * @return bool
     */
    public function canBePossessed(): bool
    {
        // Implement your logic here (e.g., prevent admins from being possessed)
        return !$this->is_admin; // Example: Admins cannot be possessed.
    }
}
```

**Important:**  You *must* implement the `canPossess()` and `canBePossessed()` methods in your `User` model.  These methods control who can possess others and who can be possessed, respectively.  The examples above are basic; adapt them to your specific authorization rules.  The `isPossessed()` method is already implemented in the Trait.

### 2. Use the Global Helper Functions

The package provides two global helper functions: `possess()` and `unpossess()`.

#### `possess($user)`

This function allows an administrator to possess another user.  It accepts the following as the `$user` argument:

*   **User ID (integer):**  `possess(123);`
*   **User Email (string):** `possess('user@example.com');`
*   **User Model Instance:** `possess($user);`

```php
// Example in a controller:

public function possessUser(Request $request, $userId)
{
    try {
        possess($userId);
        return redirect()->route('dashboard'); // Redirect after successful possession
    } catch (\Exception $e) {
        return back()->withErrors(['possession' => $e.getMessage()]); // Handle errors
    }
}
```

#### `unpossess()`

This function returns the administrator to their original account. It takes no arguments.

```php
// Example in a controller:

public function unpossessUser()
{
    try {
        unpossess();
        return redirect()->route('admin.dashboard'); // Redirect back to the admin dashboard
    } catch (\Exception $e) {
        return back()->withErrors(['possession' => $e.getMessage()]);
    }
}
```

### Error Handling

The `possess()` and `unpossess()` functions throw exceptions if:

*   The current user cannot possess the target user.
*   The target user cannot be possessed.
*   The current user and target user are the same.
*   There's no original user to return to (when calling `unpossess()`).
*   The original user cannot be found.
*   The original user (retrieved in `unpossess()`) cannot possess.

You should use `try-catch` blocks to handle these exceptions gracefully in your application.

### Example Workflow

1.  An administrator clicks a "Possess User" button in an admin panel.
2.  The button sends a request to a route that calls the `possess()` function with the target user's ID.
3.  If successful, the administrator is now logged in as the target user.
4.  The administrator performs actions on behalf of the user.
5.  The administrator clicks an "Unpossess" button.
6.  The button sends a request to a route that calls the `unpossess()` function.
7.  The administrator is returned to their original account.

## Important Notes

*   **Security:** Ensure that your `canPossess()` and `canBePossessed()` methods are properly implemented to prevent unauthorized access.  Only trusted users should be allowed to possess others.
*   **Session:** The package uses the session to store the original administrator's ID.  This is essential for returning to the correct account.
*   **Error Handling:** Always wrap calls to `possess()` and `unpossess()` in `try-catch` blocks to handle potential errors.
* **composer dump-autoload:** Remember execute `composer dump-autoload`.

This README provides a complete guide to installing, configuring, and using the Laravel Possession package. Remember to adapt the examples to your specific application's needs.
```


## License

The MIT License (MIT)
