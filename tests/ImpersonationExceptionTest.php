<?php

namespace Verseles\Possession\Tests;

use Verseles\Possession\Exceptions\ImpersonationException;

class ImpersonationExceptionTest extends TestCase
{
    public function test_unauthorized_possess_has_correct_code()
    {
        $exception = ImpersonationException::unauthorizedPossess();
        $this->assertEquals(403, $exception->getCode());
        $this->assertEquals('Current user is not authorized to impersonate others', $exception->getMessage());
    }

    public function test_unauthorized_unpossess_has_correct_code()
    {
        $exception = ImpersonationException::unauthorizedUnpossess();
        $this->assertEquals(403, $exception->getCode());
    }

    public function test_target_cannot_be_possessed_has_correct_code()
    {
        $exception = ImpersonationException::targetCannotBePossessed();
        $this->assertEquals(403, $exception->getCode());
    }

    public function test_self_possession_has_correct_code()
    {
        $exception = ImpersonationException::selfPossession();
        $this->assertEquals(400, $exception->getCode());
    }

    public function test_no_impersonation_active_has_correct_code()
    {
        $exception = ImpersonationException::noImpersonationActive();
        $this->assertEquals(400, $exception->getCode());
    }

    public function test_already_impersonating_has_correct_code()
    {
        $exception = ImpersonationException::alreadyImpersonating();
        $this->assertEquals(400, $exception->getCode());
    }

    public function test_admin_not_found_has_correct_code()
    {
        $exception = ImpersonationException::adminNotFound();
        $this->assertEquals(404, $exception->getCode());
    }

    public function test_not_authenticated_has_correct_code()
    {
        $exception = ImpersonationException::notAuthenticated();
        $this->assertEquals(401, $exception->getCode());
    }
}
