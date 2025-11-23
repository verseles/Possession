<?php

namespace Verseles\Possession;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Verseles\Possession\Events\PossessionEnded;
use Verseles\Possession\Events\PossessionStarted;
use Verseles\Possession\Exceptions\ImpersonationException;

class PossessionManager
{
    public function possess(Authenticatable|int|string $user): void
    {
        $admin = Auth::guard(config('possession.admin_guard'))->user();

        if (!$admin) {
            throw ImpersonationException::unauthorizedPossess();
        }

        $target = $this->resolveUser($user);

        $this->validateImpersonation($admin, $target);

        $this->logoutAndDestroySession();

        Auth::login($target);
        Session::put(config('possession.session_keys.original_user'), $admin->id);

        event(new PossessionStarted($admin, $target));
    }

    public function isPossessing(): bool
    {
        return Session::has(config('possession.session_keys.original_user'));
    }

    public function getOriginalUser(): ?Authenticatable
    {
        $originalUserId = Session::get(config('possession.session_keys.original_user'));

        if (!$originalUserId) {
            return null;
        }

        return $this->resolveUser($originalUserId);
    }

    public function unpossess(): void
    {
        $originalUserId = Session::get(config('possession.session_keys.original_user'));

        if (!$originalUserId) {
            throw ImpersonationException::noImpersonationActive();
        }

        $target = Auth::user();

        if (!$target) {
            throw ImpersonationException::noImpersonationActive();
        }

        $admin = $this->resolveUser($originalUserId);

        /** @phpstan-ignore-next-line */
        if (!$admin->canPossess()) {
            throw ImpersonationException::unauthorizedUnpossess();
        }

        $this->logoutAndDestroySession();

        Auth::login($admin);
        Session::forget(config('possession.session_keys.original_user'));

        event(new PossessionEnded($admin, $target));
    }

    protected function resolveUser(Authenticatable|int|string $user): Authenticatable
    {
        if ($user instanceof Authenticatable) {
            return $user;
        }

        /** @var class-string<Authenticatable> $model */
        $model = config('possession.user_model');

        if (is_numeric($user)) {
            return $model::findOrFail($user);
        }

        if (filter_var($user, FILTER_VALIDATE_EMAIL)) {
            return $model::where('email', $user)->firstOrFail();
        }

        return $model::findOrFail($user);
    }

    protected function validateImpersonation(Authenticatable $admin, Authenticatable $user): void
    {
        /** @phpstan-ignore-next-line */
        if (!$admin->canPossess()) {
            throw ImpersonationException::unauthorizedPossess();
        }

        /** @phpstan-ignore-next-line */
        if (!$user->canBePossessed()) {
            throw ImpersonationException::targetCannotBePossessed();
        }

        if ($admin->id === $user->id) {
            throw ImpersonationException::selfPossession();
        }
    }

    protected function logoutAndDestroySession(): void
    {
        Auth::logout();
        Session::invalidate();
        Session::regenerateToken();
        Session::flush();
    }
}
