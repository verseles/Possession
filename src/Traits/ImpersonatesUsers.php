<?php

namespace Verseles\Possession\Traits;

trait ImpersonatesUsers
{
    public function canImpersonate()
    {
        return false;
    }

    public function canBeImpersonated()
    {
        return true;
    }

    public function isImpersonating()
    {
        return session()->has(config('possession.session_keys.original_user'));
    }
}