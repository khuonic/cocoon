<?php

use App\Http\Controllers\Party\PartyAccessController;
use App\Http\Controllers\Party\PartyGameController;
use App\Http\Controllers\Party\PartyHomeController;
use App\Http\Controllers\Party\PartyLeaderboardController;
use App\Http\Controllers\Party\PartyPlayerController;
use App\Http\Controllers\Party\PartyRunController;
use App\Http\Middleware\EnsurePartyAccess;
use App\Http\Middleware\EnsurePartyEnabled;
use App\Http\Middleware\EnsurePartyPlayer;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Mini-site de la fête (/fete)
|--------------------------------------------------------------------------
|
| Public (sans auth Cocoon) mais réservé aux invités : clé QR ou mot de passe.
|
*/

Route::middleware(EnsurePartyEnabled::class)
    ->prefix('fete')
    ->name('party.')
    ->group(function () {
        Route::get('entrer', [PartyAccessController::class, 'create'])->name('enter');
        Route::post('entrer', [PartyAccessController::class, 'store'])
            ->middleware('throttle:party-password')
            ->name('enter.store');

        Route::middleware(EnsurePartyAccess::class)->group(function () {
            Route::get('pseudo', [PartyPlayerController::class, 'edit'])->name('player.edit');
            Route::post('pseudo', [PartyPlayerController::class, 'store'])
                ->middleware('throttle:party')
                ->name('player.store');

            Route::middleware(EnsurePartyPlayer::class)->group(function () {
                Route::get('/', PartyHomeController::class)->name('home');
                Route::get('classement', PartyLeaderboardController::class)->name('leaderboard');
                Route::get('jeux/{game}', [PartyGameController::class, 'show'])->name('games.show');
                Route::get('parties/{run:uuid}', [PartyRunController::class, 'show'])->name('runs.show');

                Route::middleware('throttle:party')->group(function () {
                    Route::post('jeux/{game}/parties', [PartyRunController::class, 'store'])->name('runs.store');
                    Route::post('parties/{run:uuid}/reponses', [PartyRunController::class, 'answer'])->name('runs.answer');
                });
            });
        });
    });
