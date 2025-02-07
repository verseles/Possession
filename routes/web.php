<?php

use Illuminate\Support\Facades\Route;
use Verseles\Possession\Http\Controllers\ImpersonationController;

Route::middleware(['web', 'auth:'.config('possession.admin_guard')])->group(function () {
    Route::post('/possession/impersonate', [ImpersonationController::class, 'impersonate'])
        ->name('possession.impersonate');
    
    Route::post('/possession/leave', [ImpersonationController::class, 'leave'])
        ->name('possession.leave');
});