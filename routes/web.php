<?php

use Illuminate\Support\Facades\Route;
use Verseles\Possession\Http\Controllers\PossessionController;

Route::middleware(config('possession.routes.middleware', ['web', 'auth']))
    ->prefix(config('possession.routes.prefix', 'possession'))
    ->name('possession.')
    ->group(function () {
        Route::post('/possess', [PossessionController::class, 'possess'])->name('possess');
        Route::post('/leave', [PossessionController::class, 'unpossess'])->name('leave');
    });
