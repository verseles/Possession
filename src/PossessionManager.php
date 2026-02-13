<?php

namespace Verseles\Possession;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Verseles\Possession\Exceptions\ImpersonationException;

class PossessionManager
{
  public function possess ( $user ): void
  {
	 if (Session::has(config('possession.session_keys.original_user'))) {
		throw ImpersonationException::alreadyImpersonating();
	 }

	 $admin = Auth::guard(config('possession.admin_guard'))->user();

	 if (!$admin) {
		throw ImpersonationException::notAuthenticated();
	 }

	 $user  = $this->resolveUser($user);

	 $this->validateImpersonation($admin, $user);

	 $this->logoutAndDestroySession();

	 Auth::login($user);
	 Session::put(config('possession.session_keys.original_user'), $admin->id);
  }

  public function unpossess (): void
  {
	 $originalUserId = Session::get(config('possession.session_keys.original_user'));

	 if (!$originalUserId) {
		throw ImpersonationException::noImpersonationActive();
	 }

	 $guard = config('possession.admin_guard');
	 $admin = Auth::guard($guard)->getProvider()->retrieveById($originalUserId);

	 if (!$admin) {
		throw ImpersonationException::adminNotFound();
	 }

	 if (!$admin->canPossess()) {
		throw ImpersonationException::unauthorizedUnpossess();
	 }

	 $this->logoutAndDestroySession();

	 Auth::guard($guard)->login($admin);
	 Session::forget(config('possession.session_keys.original_user'));
  }

  protected function resolveUser ( $user ): Authenticatable
  {
	 if ($user instanceof Authenticatable) return $user;

	 $model = config('possession.user_model');

	 return $model::findOrFail($user);
  }

  protected function validateImpersonation ( $admin, $user ): void
  {
	 if (!$admin->canPossess()) {
		throw ImpersonationException::unauthorizedPossess();
	 }

	 if (!$user->canBePossessed()) {
		throw ImpersonationException::targetCannotBePossessed();
	 }

	 if ($admin->is($user)) {
		throw ImpersonationException::selfPossession();
	 }
  }

  protected function logoutAndDestroySession (): void
  {
	 Auth::logout();
	 Session::invalidate();
	 Session::regenerateToken();
	 Session::flush();
  }
}
