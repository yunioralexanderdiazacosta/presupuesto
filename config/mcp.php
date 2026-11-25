<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Conexión con asistentes de IA (servidor MCP de solo lectura)
    |--------------------------------------------------------------------------
    |
    | Permite que Claude, ChatGPT, Copilot, etc. consulten datos del sistema con
    | los permisos del usuario que autoriza la conexión (OAuth 2.1 + PKCE).
    |
    */

    'enabled' => env('MCP_ENABLED', true),

    'name' => 'Alisoft',

    'scope' => 'mcp:read',

    // Duración de los tokens: el asistente renueva el acceso solo mientras la conexión siga autorizada.
    'access_token_ttl_minutes' => (int) env('MCP_ACCESS_TOKEN_TTL_MINUTES', 60),
    'refresh_token_ttl_days' => (int) env('MCP_REFRESH_TOKEN_TTL_DAYS', 30),

    // Solicitudes por minuto por usuario hacia /mcp.
    'rate_limit_per_minute' => (int) env('MCP_RATE_LIMIT_PER_MINUTE', 60),

    // Máximo de filas que una herramienta puede devolver en una sola llamada.
    'max_results' => 100,
    'default_results' => 25,

    /*
    |--------------------------------------------------------------------------
    | Registro dinámico de clientes (RFC 7591)
    |--------------------------------------------------------------------------
    |
    | Solo se aceptan URLs de retorno HTTPS de estos dominios (o loopback para
    | aplicaciones de escritorio). Se pueden ampliar con MCP_REDIRECT_HOSTS
    | (lista separada por comas).
    |
    */

    'redirect_hosts' => array_values(array_unique(array_filter(array_merge([
        'claude.ai',
        'claude.com',
        'chatgpt.com',
        'chat.openai.com',
        'vscode.dev',
        'insiders.vscode.dev',
        'global.consent.azure-apim.net',
    ], array_map('trim', explode(',', (string) env('MCP_REDIRECT_HOSTS', ''))))))),

    'allow_loopback_redirects' => true,

    'max_redirect_uris' => 5,

    // Tope de clientes registrados dinámicamente que aún no han sido depurados.
    'max_dynamic_clients' => 2000,

    // Los clientes registrados que nunca obtuvieron un token se eliminan pasadas estas horas.
    'prune_unused_clients_after_hours' => 24,
];
