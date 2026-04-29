<?php

namespace Verseles\Possession\Exceptions;

use Exception;

class ImpersonationException extends Exception
{
  public static function unauthorizedPossess (): self
  {
	 return new self('Current user is not authorized to impersonate others', 403);
  }

  public static function unauthorizedUnpossess (): self
  {
	 return new self('Original user no longer has permission to possess', 403);
  }

  public static function targetCannotBePossessed (): self
  {
	 return new self('Target user cannot be possessed', 403);
  }

  public static function selfPossession (): self
  {
	 return new self('Cannot impersonate yourself', 403);
  }

  public static function noImpersonationActive (): self
  {
	 return new self('No active impersonation session', 400);
  }

  public static function alreadyImpersonating (): self
  {
	 return new self('Cannot impersonate while already impersonating', 400);
  }

  public static function adminNotFound (): self
  {
	 return new self('Original admin user not found', 404);
  }

  public static function notAuthenticated (): self
  {
	 return new self('No authenticated user found', 401);
  }
}
