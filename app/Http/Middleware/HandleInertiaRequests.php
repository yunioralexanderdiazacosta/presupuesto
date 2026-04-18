<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use App\Models\Season;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     * @param  \Illuminate\Http\Request  $request
     * @return string|null
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Defines the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function share(Request $request): array
    {
        // API desactivada temporalmente para desarrollo
        $price = session('price');
          if ($price !== null && $price !== '') {
            $price = number_format((float)$price, 1, '.', '');
        }

        // Validar y limpiar season_id inválido
        $season = \App\Models\Season::find(session('season_id'));
        if (!session('season_id') || !$season) {
            session()->forget('season_id');
        }

        return array_merge(parent::share($request), [
            'public_path' => config('app.url'),
            'price'       => $price,
            'temporada'   => $season ? strtoupper($season->name) : '',
            'seasonColor' => $season ? $season->color : null,
            'auth' => fn () => [
                'user' => Auth::user() ? Auth::user()->load('team') : null,
            ],
            'gates' => function() {
                $user = Auth::user();
                return $user ? [
                    'roles' => $user->getRoleNames(),
                    'permissions' => $user->getAllPermissions()->pluck('name')
                ] : null;
            },
            'flash' => [
                'error' => fn () => $request->session()->get('error'),
                'success' => fn () => $request->session()->get('success'),
                'info' => fn () => $request->session()->get('info'),
                'copy_warnings' => fn () => $request->session()->get('copy_warnings'),
            ],
            'lifetime' => env('SESSION_LIFETIME'),
            'appVersion' => config('app.version'),
        ]);
    }
}
