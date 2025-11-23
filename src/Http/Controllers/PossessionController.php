<?php

namespace Verseles\Possession\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Verseles\Possession\Exceptions\ImpersonationException;
use Verseles\Possession\Facades\Possession;

class PossessionController extends Controller
{
    /**
     * Start impersonating a user.
     */
    public function possess(Request $request): RedirectResponse
    {
        $request->validate([
            'user' => 'required',
        ]);

        try {
            Possession::possess($request->input('user'));

            return redirect()
                ->intended(config('possession.redirect_after_possess', '/'))
                ->with('success', 'Now impersonating user.');
        } catch (ImpersonationException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Stop impersonating and return to original user.
     */
    public function unpossess(): RedirectResponse
    {
        try {
            Possession::unpossess();

            return redirect()
                ->to(config('possession.redirect_after_unpossess', '/'))
                ->with('success', 'Stopped impersonating.');
        } catch (ImpersonationException $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
