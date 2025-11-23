<?php

namespace Verseles\Possession\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Verseles\Possession\Exceptions\ImpersonationException;

class ExceptionTest extends TestCase
{
    public function test_unauthorized_possess_exception(): void
    {
        $exception = ImpersonationException::unauthorizedPossess();

        $this->assertInstanceOf(ImpersonationException::class, $exception);
        $this->assertStringContainsString('not authorized', $exception->getMessage());
    }

    public function test_unauthorized_unpossess_exception(): void
    {
        $exception = ImpersonationException::unauthorizedUnpossess();

        $this->assertInstanceOf(ImpersonationException::class, $exception);
        $this->assertStringContainsString('no longer has permission', $exception->getMessage());
    }

    public function test_target_cannot_be_possessed_exception(): void
    {
        $exception = ImpersonationException::targetCannotBePossessed();

        $this->assertInstanceOf(ImpersonationException::class, $exception);
        $this->assertStringContainsString('cannot be possessed', $exception->getMessage());
    }

    public function test_self_possession_exception(): void
    {
        $exception = ImpersonationException::selfPossession();

        $this->assertInstanceOf(ImpersonationException::class, $exception);
        $this->assertStringContainsString('yourself', $exception->getMessage());
    }

    public function test_no_impersonation_active_exception(): void
    {
        $exception = ImpersonationException::noImpersonationActive();

        $this->assertInstanceOf(ImpersonationException::class, $exception);
        $this->assertStringContainsString('No active impersonation', $exception->getMessage());
    }
}
