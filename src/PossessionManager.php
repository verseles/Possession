<?php

namespace Verseles\Possession;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Verseles\Possession\Exceptions\ImpersonationException;

class PossessionManager
{
  public function possess ( $user, $guard = null ): void
  {
	 if (Session::has(config('possession.session_keys.original_user'))) {
		throw ImpersonationException::alreadyImpersonating();
	 }

	 $admin = Auth::guard(config('possession.admin_guard'))->user();
	 $user  = $this->resolveUser($user, $guard);

	 $this->validateImpersonation($admin, $user);

	 $this->logoutAndDestroySession(config('possession.admin_guard'));

	 if ($guard) {
		Auth::guard($guard)->login($user);
	 } else {
		Auth::login($user);
	 }

	 Session::put(config('possession.session_keys.original_user'), $admin->id);

	 if ($guard) {
		Session::put(config('possession.session_keys.impersonated_guard'), $guard);
	 }
  }

  public function unpossess (): void
  {
	 $originalUserId = Session::get(config('possession.session_keys.original_user'));
	 $impersonatedGuard = Session::get(config('possession.session_keys.impersonated_guard'));

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

	 $this->logoutAndDestroySession($impersonatedGuard);

	 Auth::guard($guard)->login($admin);
	 Session::forget(config('possession.session_keys.original_user'));
	 Session::forget(config('possession.session_keys.impersonated_guard'));
  }

  protected function resolveUser ( $user, $guard = null ): Authenticatable
  {
	 if ($user instanceof Authenticatable) return $user;

	 if ($guard) {
		$provider = Auth::guard($guard)->getProvider();

		if ($provider) {
		  $model = $provider->retrieveById($user);

		  if ($model) {
			 return $model;
		  }
		}
	 }

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

  protected function logoutAndDestroySession ( $guard = null ): void
  {
	 if ($guard) {
		Auth::guard($guard)->logout();
	 } else {
		Auth::logout();
	 }

	 Session::invalidate();
	 Session::regenerateToken();
	 Session::flush();
  }
}
