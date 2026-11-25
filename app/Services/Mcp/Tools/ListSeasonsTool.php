<?php

namespace App\Services\Mcp\Tools;

use App\Models\Season;
use App\Services\Mcp\McpContext;
use App\Services\Mcp\McpTool;

class ListSeasonsTool extends McpTool
{
    public function name(): string
    {
        return 'list_seasons';
    }

    public function title(): string
    {
        return 'Listar temporadas';
    }

    public function description(): string
    {
        return 'Lista las temporadas de la empresa del usuario e indica cuál se usa por defecto cuando otra herramienta no recibe season_id.';
    }

    protected function properties(): array
    {
        return [];
    }

    protected function rules(): array
    {
        return [];
    }

    protected function execute(McpContext $context, array $arguments): array
    {
        $default = $context->season();

        return [
            'default_season_id' => $default->id,
            'seasons' => Season::query()
                ->where('team_id', $context->teamId())
                ->orderByDesc('id')
                ->get(['id', 'name', 'is_default', 'is_locked'])
                ->map(fn (Season $s) => [
                    'id' => $s->id,
                    'name' => $s->name,
                    'is_default' => $s->is_default,
                    'is_locked' => $s->is_locked,
                ])
                ->values()
                ->all(),
        ];
    }
}
