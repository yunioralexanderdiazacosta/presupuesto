<?php

namespace App\Support\Mcp;

/**
 * URLs públicas del servidor MCP y del servidor de autorización OAuth.
 * Se derivan de APP_URL (no del header Host) para que los metadatos publicados sean deterministas.
 */
class McpUrls
{
    public static function base(): string
    {
        return rtrim((string) (config('app.url') ?: url('/')), '/');
    }

    public static function server(): string
    {
        return self::base() . '/mcp';
    }

    public static function resourceMetadata(): string
    {
        return self::base() . '/.well-known/oauth-protected-resource/mcp';
    }

    public static function authorizationEndpoint(): string
    {
        return self::base() . '/oauth/authorize';
    }

    public static function tokenEndpoint(): string
    {
        return self::base() . '/oauth/token';
    }

    public static function registrationEndpoint(): string
    {
        return self::base() . '/oauth/register';
    }

    /** Dominios (host o host:puerto) de las URLs de retorno registradas para un cliente. */
    public static function redirectHosts(?string $redirect): array
    {
        return collect(explode(',', (string) $redirect))
            ->map(fn ($uri) => parse_url(trim($uri)))
            ->filter(fn ($parts) => is_array($parts) && ! empty($parts['host']))
            ->map(fn ($parts) => $parts['host'] . (isset($parts['port']) ? ':' . $parts['port'] : ''))
            ->unique()
            ->values()
            ->all();
    }
}
