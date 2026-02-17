<?php

use App\Http\Controllers\Api\SyncController;
use App\Http\Controllers\Auth\ApiLoginController;
use App\Http\Middleware\RestrictToHousehold;
use Illuminate\Support\Facades\Route;

Route::post('login', ApiLoginController::class)->middleware('throttle:api-login');

Route::middleware(['auth:sanctum', RestrictToHousehold::class])->group(function () {
    Route::get('user', fn () => request()->user()->only('id', 'name', 'email', 'avatar'));

    Route::post('sync/push', [SyncController::class, 'push']);
    Route::get('sync/pull', [SyncController::class, 'pull']);
    Route::post('sync/full', [SyncController::class, 'full']);
});
