<?php

namespace Verseles\Possession\Exceptions;

use Exception;

class ImpersonationException extends Exception
{
  public static function unauthorizedPossess (): self
  {
	 return new self('Current user is not authorized to impersonate others');
  }

  public static function unauthorizedUnpossess (): self
  {
	 return new self('Original user no longer has permission to possess');
  }

  public static function targetCannotBePossessed (): self
  {
	 return new self('Target user cannot be possessed');
  }

  public static function selfPossession (): self
  {
	 return new self('Cannot impersonate yourself');
  }

  public static function noImpersonationActive (): self
  {
	 return new self('No active impersonation session');
  }

  public static function alreadyImpersonating (): self
  {
	 return new self('Cannot impersonate while already impersonating');
  }

  public static function adminNotFound (): self
  {
	 return new self('Original admin user not found');
  }

  public static function notAuthenticated (): self
  {
	 return new self('No authenticated user found');
  }
}
