<?php

namespace Verseles\Possession\Facades;

use Illuminate\Support\Facades\Facade;

class Possession extends Facade
{
  protected static function getFacadeAccessor()
  {
	 return \Verseles\Possession\PossessionManager::class;
  }
}
