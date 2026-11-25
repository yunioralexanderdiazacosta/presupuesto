<?php

namespace App\Http\Controllers\Mcp;

use App\Http\Controllers\Controller;
use App\Support\Mcp\RedirectUriPolicy;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Passport\Client;
use Laravel\Passport\ClientRepository;

/**
 * RFC 7591: registro dinámico de clientes OAuth. Solo se crean clientes públicos (sin secreto) que usan
 * authorization_code + PKCE, con URLs de retorno restringidas por RedirectUriPolicy.
 */
class RegisterOAuthClientController extends Controller
{
    public function __invoke(Request $request, ClientRepository $clients): JsonResponse
    {
        $uris = $request->input('redirect_uris');

        if (! is_array($uris) || $uris === [] || count($uris) > config('mcp.max_redirect_uris')) {
            return $this->error('invalid_redirect_uri', 'Debe indicar entre 1 y ' . config('mcp.max_redirect_uris') . ' redirect_uris.');
        }

        foreach ($uris as $uri) {
            if (! RedirectUriPolicy::isAllowed($uri)) {
                return $this->error('invalid_redirect_uri', 'Una de las redirect_uris no está permitida.');
            }
        }

        $authMethod = $request->input('token_endpoint_auth_method', 'none');
        if ($authMethod !== 'none') {
            return $this->error('invalid_client_metadata', 'Solo se admiten clientes públicos (token_endpoint_auth_method=none).');
        }

        $grantTypes = $request->input('grant_types', ['authorization_code', 'refresh_token']);
        if (! is_array($grantTypes) || array_diff($grantTypes, ['authorization_code', 'refresh_token']) !== []) {
            return $this->error('invalid_client_metadata', 'Solo se admiten los grant_types authorization_code y refresh_token.');
        }

        if (array_diff((array) $request->input('response_types', ['code']), ['code']) !== []) {
            return $this->error('invalid_client_metadata', 'Solo se admite response_types=code.');
        }

        if (Client::query()->whereNull('user_id')->where('revoked', false)->count() >= config('mcp.max_dynamic_clients')) {
            return response()->json([
                'error' => 'temporarily_unavailable',
                'error_description' => 'Registro de clientes no disponible por el momento.',
            ], 503);
        }

        $uris = array_values(array_unique($uris));
        $client = $clients->create(null, $this->clientName($request->input('client_name')), implode(',', $uris), null, false, false, false);

        Log::channel('mcp')->info('mcp.client_registered', [
            'client_id' => (string) $client->getKey(),
            'client_name' => $client->name,
            'redirect_hosts' => array_map(fn ($u) => parse_url($u, PHP_URL_HOST), $uris),
            'ip' => $request->ip(),
        ]);

        return response()->json([
            'client_id' => (string) $client->getKey(),
            'client_name' => $client->name,
            'client_id_issued_at' => $client->created_at->timestamp,
            'redirect_uris' => $uris,
            'grant_types' => ['authorization_code', 'refresh_token'],
            'response_types' => ['code'],
            'token_endpoint_auth_method' => 'none',
            'scope' => config('mcp.scope'),
        ], 201)->header('Cache-Control', 'no-store');
    }

    /** El nombre lo envía el cliente (no es confiable): se limpia antes de mostrarlo en la pantalla de consentimiento. */
    private function clientName(mixed $name): string
    {
        $name = is_string($name) ? trim(preg_replace('/\s+/u', ' ', strip_tags($name))) : '';
        $name = preg_replace('/[^\p{L}\p{N} ._\-()\/+]/u', '', $name);

        return Str::limit($name !== '' ? $name : 'Asistente de IA', 100, '');
    }

    private function error(string $error, string $description): JsonResponse
    {
        return response()->json(['error' => $error, 'error_description' => $description], 400);
    }
}
