<?php

namespace App\Http\Middleware;

use App\Party\PartyEvent;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Le mini-site de la fête a sa propre vue racine (police pixel, noindex).
     */
    public function rootView(Request $request): string
    {
        return $this->isPartyRequest($request) ? 'party' : $this->rootView;
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        if ($this->isPartyRequest($request)) {
            return $this->sharePartyProps($request);
        }

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $request->user(),
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'syncApiUrl' => config('cocon.sync_api_url'),
            'flash' => [
                'api_token' => $request->session()->get('api_token'),
                'sync_token' => $request->session()->get('sync_token'),
                'logged_out' => $request->session()->get('logged_out'),
            ],
            'appVersionCode' => (int) config('nativephp.version_code'),
            'isNativePHP' => function_exists('nativephp_call'),
        ];
    }

    /**
     * Props partagées du mini-site : aucune donnée Cocoon n'y est exposée.
     *
     * @return array<string, mixed>
     */
    private function sharePartyProps(Request $request): array
    {
        return [
            ...parent::share($request),
            'party' => [
                'title' => config('party.title'),
                'isClosed' => PartyEvent::isClosed(),
                'player' => ($player = EnsurePartyPlayer::resolve($request)) ? ['pseudo' => $player->pseudo] : null,
            ],
        ];
    }

    private function isPartyRequest(Request $request): bool
    {
        return $request->routeIs('party.*');
    }
}
