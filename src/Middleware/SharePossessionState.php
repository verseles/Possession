<?php

namespace Verseles\Possession\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;
use Verseles\Possession\Facades\Possession;

/**
 * Middleware to share possession state with all views.
 *
 * This makes `$isPossessing` and `$originalUser` available in all Blade templates.
 */
class SharePossessionState
{
    public function handle(Request $request, Closure $next): Response
    {
        $isPossessing = Possession::isPossessing();

        View::share('isPossessing', $isPossessing);
        View::share('originalUser', $isPossessing ? Possession::getOriginalUser() : null);

        return $next($request);
    }
}
