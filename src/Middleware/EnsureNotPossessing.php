<?php

namespace Verseles\Possession\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Verseles\Possession\Facades\Possession;

/**
 * Middleware to ensure the current user is NOT being impersonated.
 *
 * Use this on routes that should only be accessible to the "real" user,
 * not during impersonation.
 */
class EnsureNotPossessing
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Possession::isPossessing()) {
            abort(403, 'Access denied during impersonation.');
        }

        return $next($request);
    }
}
