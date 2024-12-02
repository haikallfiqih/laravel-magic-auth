<?php

use Illuminate\Support\Facades\Route;
use LaravelLinkAuth\MagicAuth\Http\Controllers\MagicAuthController;

Route::post('/magic-link', [MagicAuthController::class, 'sendMagicLink'])
    ->name('magic-auth.send')
    ->middleware('throttle:' . config('magic-auth.throttle.max_attempts,magic-auth.throttle.decay_minutes'));

Route::get('/auth/verify', [MagicAuthController::class, 'verify'])
    ->name('magic-auth.verify')
    ->middleware('signed');
