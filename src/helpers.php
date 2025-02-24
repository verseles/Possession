<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

if (!function_exists('possess')) {
    /**
     * Possess the specified user.
     *
     * @param  mixed  $user  The user's ID, model instance, or identifier.
     *
     * @return void
     *
     * @throws \Exception If the current user cannot possess the target user.
     * @throws \Exception If the target user cannot be possessed.
     * @throws \Exception If the current user and the target user are the same.
     */
    function possess($user)
    {
        $admin = Auth::user();
        $request = request();

        if (is_numeric($user)) {
            $user = config('possession.user_model')::findOrFail($user);
        } elseif (is_string($user)) {
            $user = config('possession.user_model')::where('email', $user)->firstOrFail(); // Example: find by email
        }
        // No 'else if' is needed, as it already is a model.

        if (!$admin->canPossess() || !$user->canBePossessed() || $admin->id === $user->id) {
            throw new \Exception("User cannot possess the selected user.");
        }

        auth()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        $request->session()->flush();

        Auth::login($user);
        session()->put(config('possession.session_keys.original_user'), $admin->id);
    }
}

if (!function_exists('unpossess')) {
    /**
     * Stop possessing the current user and return to the original admin user.
     *
     * @return void
     *
     * @throws \Exception If there is no original user in the session.
     * @throws \Exception If the original user cannot be found.
     */
    function unpossess()
    {
        $request = request();
        $adminId = session()->get(config('possession.session_keys.original_user'));

        if (!$adminId) {
            throw new \Exception("No user to return to.");
        }

        $admin = config('possession.user_model')::find($adminId);

        if (!$admin) {
            throw new \Exception("Original user not found.");
        }

        if (!$admin->canPossess()) {
            throw new \Exception("Original user cannot possess.");
        }

        auth()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        $request->session()->flush();

        Auth::login($admin);
        session()->forget(config('possession.session_keys.original_user'));
    }
}
