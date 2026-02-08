<?php

use App\Http\Controllers\Auth\ApiLoginController;
use App\Http\Middleware\RestrictToHousehold;
use Illuminate\Support\Facades\Route;

Route::post('login', ApiLoginController::class)->middleware('throttle:api-login');

Route::middleware(['auth:sanctum', RestrictToHousehold::class])->group(function () {
    Route::get('user', fn () => request()->user()->only('id', 'name', 'email', 'avatar'));
});
