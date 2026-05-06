<?php

namespace Verseles\Possession\Tests;

use Verseles\Possession\Exceptions\ImpersonationException;
use PHPUnit\Framework\TestCase;

class ImpersonationExceptionTest extends TestCase
{
    public function test_exception_codes()
    {
        $this->assertEquals(403, ImpersonationException::unauthorizedPossess()->getCode());
        $this->assertEquals(403, ImpersonationException::unauthorizedUnpossess()->getCode());
        $this->assertEquals(403, ImpersonationException::targetCannotBePossessed()->getCode());
        $this->assertEquals(400, ImpersonationException::selfPossession()->getCode());
        $this->assertEquals(400, ImpersonationException::noImpersonationActive()->getCode());
        $this->assertEquals(403, ImpersonationException::alreadyImpersonating()->getCode());
        $this->assertEquals(404, ImpersonationException::adminNotFound()->getCode());
        $this->assertEquals(401, ImpersonationException::notAuthenticated()->getCode());
    }
}
