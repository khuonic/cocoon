<?php

namespace App\Providers;

use App\Party\GameRegistry;
use App\Party\PartyEvent;
use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(GameRegistry::class, fn () => new GameRegistry(config('party.games', [])));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureParty();
    }

    /**
     * Mini-site de la fête (/fete) : binding {game} et rate limiting.
     *
     * Le binding doit vivre ici (et non dans routes/party.php) pour survivre au route:cache.
     */
    protected function configureParty(): void
    {
        Route::bind('game', fn (string $slug) => $this->app->make(GameRegistry::class)->findOrFail($slug));

        RateLimiter::for('party-password', fn (Request $request) => Limit::perMinute(10)->by('party-password|'.$request->ip()));

        RateLimiter::for('party', fn (Request $request) => Limit::perMinute(60)->by(
            'party|'.($request->cookie(PartyEvent::PLAYER_COOKIE) ?: $request->ip())
        ));
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null
        );
    }
}
