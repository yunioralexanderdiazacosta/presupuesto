<?php

namespace App\Providers;

// use Illuminate\Support\Facades\Gate;
use Carbon\CarbonInterval;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Inertia\Inertia;
use Laravel\Passport\Passport;
use App\Support\Mcp\McpUrls;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        //
    ];

    /**
     * Debe ejecutarse en register(): Passport decide si registra sus rutas durante su propio boot().
     */
    public function register(): void
    {
        Passport::ignoreRoutes();
    }

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->configureOAuthForAiAssistants();
    }

    /**
     * OAuth 2.1 (Passport) usado únicamente por las conexiones con asistentes de IA (MCP).
     * Las rutas se registran a mano en routes/mcp.php: no se exponen las que trae Passport por defecto
     * (clientes, tokens personales, etc.).
     */
    protected function configureOAuthForAiAssistants(): void
    {
        $scope = config('mcp.scope');

        Passport::tokensCan([
            $scope => 'Consultar facturas y pagos en modo solo lectura',
        ]);
        Passport::setDefaultScope($scope);

        Passport::tokensExpireIn(CarbonInterval::minutes(config('mcp.access_token_ttl_minutes')));
        Passport::refreshTokensExpireIn(CarbonInterval::days(config('mcp.refresh_token_ttl_days')));

        Passport::authorizationView(function (array $parameters) {
            $client = $parameters['client'];
            $user = $parameters['user'];

            return Inertia::render('Mcp/Authorize', [
                'client' => [
                    'id' => (string) $client->getKey(),
                    'name' => $client->name,
                    'redirect_hosts' => McpUrls::redirectHosts($client->redirect),
                ],
                'scopes' => collect($parameters['scopes'])->map(fn ($s) => [
                    'id' => $s->id,
                    'description' => $s->description,
                ])->values(),
                'authToken' => $parameters['authToken'],
                'state' => $parameters['request']->input('state'),
                'userName' => $user->name,
                'teamName' => $user->team?->name,
            ]);
        });
    }
}
