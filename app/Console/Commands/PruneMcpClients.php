<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Laravel\Passport\Passport;

class PruneMcpClients extends Command
{
    protected $signature = 'mcp:prune-clients';

    protected $description = 'Elimina los clientes OAuth registrados dinámicamente por asistentes de IA que nunca obtuvieron un token';

    public function handle(): int
    {
        $deleted = Passport::client()->newQuery()
            ->whereNull('user_id')
            ->where('created_at', '<', now()->subHours(config('mcp.prune_unused_clients_after_hours')))
            ->whereNotIn('id', Passport::token()->newQuery()->select('client_id'))
            ->whereNotIn('id', Passport::authCode()->newQuery()->where('expires_at', '>', now())->select('client_id'))
            ->delete();

        $this->info("Clientes sin uso eliminados: {$deleted}");

        return self::SUCCESS;
    }
}
