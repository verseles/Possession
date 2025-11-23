<?php

namespace Verseles\Possession\Tests\Fixtures;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Verseles\Possession\Traits\ImpersonatesUsers;

class User extends Authenticatable
{
    use ImpersonatesUsers;

    protected $guarded = [];

    protected bool $canPossessOverride = false;

    protected bool $canBePossessedOverride = true;

    public function setCanPossess(bool $value): self
    {
        $this->canPossessOverride = $value;

        return $this;
    }

    public function setCanBePossessed(bool $value): self
    {
        $this->canBePossessedOverride = $value;

        return $this;
    }

    public function canPossess(): bool
    {
        return $this->canPossessOverride;
    }

    public function canBePossessed(): bool
    {
        return $this->canBePossessedOverride;
    }
}
