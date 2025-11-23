<?php

namespace Verseles\Possession\Traits;

trait ImpersonatesUsers
{
    public function canPossess(): bool
    {
        return false;
    }

    public function canBePossessed(): bool
    {
        return true;
    }

    public function isPossessed(): bool
    {
        return session()->has(config('possession.session_keys.original_user'));
    }

    /**
     * Alias for isPossessed() - checks if the current session is impersonating this user.
     */
    public function isImpersonating(): bool
    {
        return $this->isPossessed();
    }
}