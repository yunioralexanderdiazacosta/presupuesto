<?php

namespace App\Services\Mcp;

use App\Models\Season;
use App\Models\User;
use InvalidArgumentException;

/**
 * Usuario que autorizó la conexión. Toda consulta MCP se acota a su empresa (team_id) y a una
 * temporada (season_id) de esa empresa, igual que el resto del sistema.
 */
class McpContext
{
    public function __construct(public readonly User $user)
    {
    }

    public function teamId(): int
    {
        return (int) $this->user->team_id;
    }

    /** Temporada pedida (debe pertenecer a la empresa del usuario) o, si no se indica, la predeterminada. */
    public function season(?int $seasonId = null): Season
    {
        $query = Season::query()->where('team_id', $this->teamId());

        if ($seasonId !== null) {
            $season = $query->whereKey($seasonId)->first();

            if (! $season) {
                throw new InvalidArgumentException('La temporada indicada no existe. Use list_seasons para ver las disponibles.');
            }

            return $season;
        }

        $season = $query->orderByDesc('is_default')->orderByDesc('id')->first();

        if (! $season) {
            throw new InvalidArgumentException('La empresa no tiene temporadas configuradas.');
        }

        return $season;
    }

    public function limit(mixed $requested): int
    {
        $max = (int) config('mcp.max_results');

        return max(1, min($max, (int) ($requested ?: config('mcp.default_results'))));
    }
}
