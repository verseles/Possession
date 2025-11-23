# Migration Guide

This document describes breaking changes and how to migrate from previous versions.

---

## Migrating to v2.0.0

Version 2.0.0 introduces significant improvements including automatic route registration, middleware, events, and better code quality tooling. Some changes may require updates to your existing code.

### Table of Contents

- [Breaking Changes](#breaking-changes)
- [New Features](#new-features)
- [Configuration Changes](#configuration-changes)
- [Code Changes Required](#code-changes-required)
- [Deprecations](#deprecations)

---

## Breaking Changes

### 1. Automatic Route Registration

**What changed:** Routes are now automatically registered by the package.

**Impact:** If you had custom routes with the same names (`possession.possess`, `possession.leave`), they will conflict.

**How to migrate:**

Option A - Disable automatic routes:
```php
// config/possession.php
'routes' => [
    'enabled' => false, // Disable automatic routes
],
```

Option B - Remove your custom routes and use the built-in ones:
```php
// Remove from routes/web.php:
Route::post('/possession/impersonate/{user}', ...);
Route::post('/possession/leave', ...);

// The package now provides:
// POST /possession/possess (name: possession.possess)
// POST /possession/leave (name: possession.leave)
```

### 2. Route Parameter Change

**What changed:** The possess route now expects a `user` form parameter instead of a route parameter.

**Before (v1.x):**
```blade
<form action="{{ route('possession.impersonate', $user->id) }}" method="POST">
```

**After (v2.x):**
```blade
<form action="{{ route('possession.possess') }}" method="POST">
    @csrf
    <input type="hidden" name="user" value="{{ $user->id }}">
    <button type="submit">Impersonate</button>
</form>
```

### 3. Exception Handling Behavior

**What changed:** `possess()` now throws `ImpersonationException` when no user is logged in (previously might have caused unexpected behavior).

**How to migrate:**
```php
use Verseles\Possession\Exceptions\ImpersonationException;

try {
    Possession::possess($user);
} catch (ImpersonationException $e) {
    // Handle all impersonation errors here
    return back()->with('error', $e->getMessage());
}
```

### 4. Helper Functions Now Use Facade

**What changed:** The `possess()`, `unpossess()`, and new `isPossessing()` helper functions now delegate to `PossessionManager` instead of having their own implementation.

**Impact:**
- Error messages are now consistent with the Facade
- Exceptions thrown are now `ImpersonationException` instead of generic `Exception`

**Before (v1.x):**
```php
try {
    possess($user);
} catch (\Exception $e) {
    // Generic exception
}
```

**After (v2.x):**
```php
use Verseles\Possession\Exceptions\ImpersonationException;

try {
    possess($user);
} catch (ImpersonationException $e) {
    // Typed exception
}
```

---

## New Features

### 1. Middleware

Three new middleware are available:

```php
// Protect routes during impersonation (redirects with error)
Route::middleware('forbid-during-possession')->group(function () {
    Route::post('/user/password', [PasswordController::class, 'update']);
    Route::delete('/user/account', [AccountController::class, 'destroy']);
});

// Strict protection (returns 403)
Route::middleware('ensure-not-possessing')->group(function () {
    Route::post('/billing', [BillingController::class, 'update']);
});

// Share state with all views
Route::middleware('share-possession-state')->group(function () {
    // Views get $isPossessing (bool) and $originalUser (User|null)
});
```

### 2. Laravel Events

Events are now dispatched for auditing:

```php
use Verseles\Possession\Events\PossessionStarted;
use Verseles\Possession\Events\PossessionEnded;

// Register listeners in EventServiceProvider
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
        'admin_email' => $event->admin->email,
        'target_id' => $event->target->id,
        'target_email' => $event->target->email,
        'ip' => request()->ip(),
    ]);
}
```

### 3. New Methods

**Facade/Manager:**
```php
// Check if currently impersonating
Possession::isPossessing(); // bool

// Get the original admin user
Possession::getOriginalUser(); // User|null
```

**Helper:**
```php
// Check if currently impersonating
isPossessing(); // bool
```

**Trait (alias):**
```php
// Both methods now work (isImpersonating is an alias)
Auth::user()->isPossessed();
Auth::user()->isImpersonating(); // NEW - alias
```

### 4. Email Resolution

You can now possess users by email:

```php
Possession::possess('user@example.com');
possess('user@example.com');
```

---

## Configuration Changes

The configuration file has been expanded. Publish the new config:

```bash
php artisan vendor:publish --tag=possession-config --force
```

### New Configuration Options

```php
return [
    // Existing options...
    'user_model' => App\Models\User::class,
    'admin_guard' => 'web',
    'session_keys' => [
        'original_user' => 'possession.original_user_id',
    ],

    // NEW: Routes configuration
    'routes' => [
        'enabled' => true,        // Set to false to disable automatic routes
        'prefix' => 'possession', // URL prefix
        'middleware' => ['web', 'auth'], // Middleware applied to routes
    ],

    // NEW: Redirect destinations
    'redirect_after_possess' => '/',   // Where to redirect after starting impersonation
    'redirect_after_unpossess' => '/', // Where to redirect after stopping impersonation
    'forbidden_redirect' => '/',       // Where to redirect when ForbidDuringPossession blocks
];
```

---

## Code Changes Required

### Update Blade Templates

**Before:**
```blade
@if(auth()->check() && auth()->user()->isPossessed())
    <form action="{{ route('possession.leave') }}" method="POST">
        @csrf
        <button>Stop</button>
    </form>
@endif
```

**After (recommended - use the built-in component):**
```blade
@include('possession::impersonating')
```

Or if you have a custom template, both methods now work:
```blade
@if(Auth::check() && Auth::user()->isImpersonating())
    {{-- Your custom UI --}}
@endif
```

### Update Exception Handling

If you were catching generic exceptions, update to typed exceptions:

```php
// Before
try {
    possess($user);
} catch (\Exception $e) {
    // ...
}

// After
use Verseles\Possession\Exceptions\ImpersonationException;

try {
    possess($user);
} catch (ImpersonationException $e) {
    // ...
}
```

### Update Custom Routes

If you had custom impersonation routes, either remove them (use built-in) or disable automatic routes:

```php
// config/possession.php
'routes' => [
    'enabled' => false,
],
```

---

## Deprecations

The following are deprecated but still work in v2.x:

| Deprecated | Use Instead |
|------------|-------------|
| Custom route names other than `possession.possess` / `possession.leave` | Use the standard route names |
| Catching generic `\Exception` from helpers | Catch `ImpersonationException` |

---

## New Development Tools

The package now includes development tools:

```bash
# Run tests
composer test

# Run static analysis (PHPStan level 8)
composer analyse

# Format code (Laravel Pint)
composer format

# Run all checks
composer check
```

---

## Publishable Assets

New publishable assets are available:

```bash
# Publish configuration
php artisan vendor:publish --tag=possession-config

# Publish views for customization
php artisan vendor:publish --tag=possession-views

# Publish routes for customization
php artisan vendor:publish --tag=possession-routes
```

---

## Full Changelog

### Added
- Automatic route registration with configurable prefix and middleware
- `PossessionController` for handling possess/unpossess actions
- `ForbidDuringPossession` middleware
- `EnsureNotPossessing` middleware
- `SharePossessionState` middleware
- `PossessionStarted` event
- `PossessionEnded` event
- `isPossessing()` method on Facade/Manager
- `getOriginalUser()` method on Facade/Manager
- `isPossessing()` helper function
- `isImpersonating()` alias on trait
- Email resolution support in `resolveUser()`
- PHPStan level 8 static analysis
- Laravel Pint code formatting
- Comprehensive test suite
- MIGRATION.md documentation

### Changed
- Helper functions now delegate to `PossessionManager` (DRY)
- `PossessionManager::possess()` now has proper type hints
- Better null checking in `possess()` and `unpossess()`
- Updated Blade template with improved styling
- Expanded configuration options
- Updated README with comprehensive documentation

### Fixed
- `isImpersonating()` method now exists (was missing, only `isPossessed()` existed)
- Session regeneration is now properly documented
- Type safety improvements throughout

---

## Questions?

If you encounter issues during migration, please [open an issue](https://github.com/verseles/possession/issues) on GitHub.
