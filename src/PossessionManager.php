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
  public function possess ( $user ): void
  {
	 $admin = Auth::guard(config('possession.admin_guard'))->user();
	 $user  = $this->resolveUser($user);

	 $this->validateImpersonation($admin, $user);

	 $this->logoutAndDestroySession();

	 Auth::login($user);
	 Session::put(config('possession.session_keys.original_user'), $admin->id);

	 event(new PossessionStarted($admin, $user));
  }

  public function isPossessing (): bool
  {
	 return Session::has(config('possession.session_keys.original_user'));
  }

  public function getOriginalUser (): ?Authenticatable
  {
	 $originalUserId = Session::get(config('possession.session_keys.original_user'));

	 if (!$originalUserId) {
		return null;
	 }

	 return $this->resolveUser($originalUserId);
  }

  public function unpossess (): void
  {
	 $originalUserId = Session::get(config('possession.session_keys.original_user'));

	 if (!$originalUserId) {
		throw ImpersonationException::noImpersonationActive();
	 }

	 $target = Auth::user();
	 $admin  = $this->resolveUser($originalUserId);

	 if (!$admin->canPossess()) {
		throw ImpersonationException::unauthorizedUnpossess();
	 }

	 $this->logoutAndDestroySession();

	 Auth::login($admin);
	 Session::forget(config('possession.session_keys.original_user'));

	 event(new PossessionEnded($admin, $target));
  }

  protected function resolveUser ( $user ): Authenticatable
  {
	 if ($user instanceof Authenticatable) return $user;

	 $model = config('possession.user_model');

	 if (is_numeric($user)) {
		return $model::findOrFail($user);
	 }

	 if (is_string($user) && filter_var($user, FILTER_VALIDATE_EMAIL)) {
		return $model::where('email', $user)->firstOrFail();
	 }

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

	 if ($admin->id === $user->id) {
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
