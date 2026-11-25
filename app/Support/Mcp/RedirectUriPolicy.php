<?php

namespace App\Support\Mcp;

/**
 * Política de URLs de retorno (redirect_uri) aceptadas al registrar un cliente OAuth dinámicamente.
 * Evita que un cliente malicioso reciba códigos de autorización en dominios arbitrarios.
 */
class RedirectUriPolicy
{
    private const LOOPBACK_HOSTS = ['127.0.0.1', '[::1]', 'localhost'];

    public static function isAllowed(mixed $uri): bool
    {
        if (! is_string($uri) || $uri === '' || strlen($uri) > 2048) {
            return false;
        }

        // Passport guarda las URLs separadas por coma; los espacios/control se rechazan para evitar ambigüedades.
        if (str_contains($uri, ',') || preg_match('/[\s\x00-\x1F\x7F]/', $uri)) {
            return false;
        }

        $parts = parse_url($uri);

        if (! is_array($parts) || empty($parts['scheme']) || empty($parts['host'])) {
            return false;
        }

        if (isset($parts['user']) || isset($parts['pass']) || isset($parts['fragment'])) {
            return false;
        }

        $scheme = strtolower($parts['scheme']);
        $host = strtolower($parts['host']);

        if ($scheme === 'https') {
            return in_array($host, array_map('strtolower', config('mcp.redirect_hosts', [])), true)
                && (! isset($parts['port']) || (int) $parts['port'] === 443);
        }

        if ($scheme === 'http' && config('mcp.allow_loopback_redirects')) {
            return in_array($host, self::LOOPBACK_HOSTS, true);
        }

        return false;
    }
}
