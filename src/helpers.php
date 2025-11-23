<?php

use Verseles\Possession\Facades\Possession;

if (!function_exists('possess')) {
    /**
     * Possess the specified user.
     *
     * @param  mixed  $user  The user's ID, email, or model instance.
     *
     * @throws \Verseles\Possession\Exceptions\ImpersonationException
     */
    function possess($user): void
    {
        Possession::possess($user);
    }
}

if (!function_exists('unpossess')) {
    /**
     * Stop possessing the current user and return to the original admin user.
     *
     *
     * @throws \Verseles\Possession\Exceptions\ImpersonationException
     */
    function unpossess(): void
    {
        Possession::unpossess();
    }
}

if (!function_exists('isPossessing')) {
    /**
     * Check if currently possessing another user.
     */
    function isPossessing(): bool
    {
        return Possession::isPossessing();
    }
}
