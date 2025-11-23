<?php

namespace Verseles\Possession\Events;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PossessionStarted
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public Authenticatable $admin,
        public Authenticatable $target
    ) {}
}
