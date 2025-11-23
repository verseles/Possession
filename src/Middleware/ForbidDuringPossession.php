<?php

namespace Verseles\Possession\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Verseles\Possession\Facades\Possession;

/**
 * Middleware to block certain routes/actions while impersonating a user.
 *
 * Use this middleware on routes that should not be accessible during impersonation,
 * such as: password change, account deletion, billing, etc.
 */
class ForbidDuringPossession
{
    public function handle(Request $request, Closure $next, ?string $redirectTo = null): Response
    {
        if (Possession::isPossessing()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'This action is not allowed while impersonating.',
                ], 403);
            }

            $redirectUrl = $redirectTo ?? config('possession.forbidden_redirect', '/');

            return redirect()->to($redirectUrl)
                ->with('error', 'This action is not allowed while impersonating.');
        }

        return $next($request);
    }
}
