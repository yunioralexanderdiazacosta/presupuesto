<?php

namespace App\Http\Controllers\PaymentRequests;

use App\Http\Controllers\Controller;
use App\Models\CostCenter;
use App\Models\Grouping;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;

class PaymentRequestController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $teamId = $user->team_id;
        $seasonId = session('season_id');

        $requests = \App\Models\PaymentRequest::where('team_id', $teamId)
            ->where('season_id', $seasonId)
            ->with(['user:id,name', 'resolvedBy:id,name', 'costCenters:id,name', 'recipients:id,name', 'files'])
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($pr) use ($user) {
                return [
                    'id' => $pr->id,
                    'number' => $pr->number,
                    'date' => $pr->date->format('Y-m-d'),
                    'date_formatted' => $pr->date->format('d/m/Y'),
                    'character' => $pr->character,
                    'character_label' => $pr->character_label,
                    'character_color' => $pr->character_color,
                    'concept_observations' => $pr->concept_observations,
                    'files' => $pr->files->map(fn($f) => ['id' => $f->id, 'file_path' => $f->file_path, 'original_name' => $f->original_name])->values(),
                    'status' => $pr->status,
                    'status_label' => $pr->status_label,
                    'status_color' => $pr->status_color,
                    'user_name' => $pr->user->name ?? '',
                    'user_id' => $pr->user_id,
                    'resolved_by_name' => $pr->resolvedBy->name ?? null,
                    'resolved_at' => $pr->resolved_at?->format('d/m/Y H:i'),
                    'cost_centers' => $pr->costCenters->pluck('name')->values(),
                    'recipients' => $pr->recipients->map(fn($u) => ['id' => $u->id, 'name' => $u->name])->values(),
                    'is_recipient' => $pr->recipients->contains('id', $user->id),
                    'is_owner' => $pr->user_id === $user->id,
                    'created_at' => $pr->created_at->format('d/m/Y H:i'),
                ];
            });

        $costCenters = CostCenter::where('season_id', $seasonId)
            ->whereHas('season', fn($q) => $q->where('team_id', $teamId))
            ->get(['id', 'name'])
            ->map(fn($cc) => ['value' => $cc->id, 'label' => $cc->name]);

        $groupings = Grouping::with(['costCenters' => function ($q) use ($seasonId) {
                $q->select('cost_centers.id', 'cost_centers.name')->where('season_id', $seasonId);
            }])
            ->where('season_id', $seasonId)
            ->whereHas('season.team', fn($q) => $q->where('team_id', $teamId))
            ->get()
            ->map(fn($g) => [
                'id' => $g->id,
                'name' => $g->name,
                'cost_centers' => $g->costCenters->map(fn($cc) => ['id' => $cc->id, 'name' => $cc->name])->values(),
            ]);

        $executors = collect([]);
        if (Role::where('name', 'Ejecutor de Pagos')->exists()) {
            $executors = User::role('Ejecutor de Pagos')
                ->where('team_id', $teamId)
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn($u) => ['value' => $u->id, 'label' => $u->name]);
        }

        return Inertia::render('PaymentRequests/Index', [
            'requests' => $requests,
            'costCenters' => $costCenters,
            'groupings' => $groupings,
            'executors' => $executors,
            'authUserId' => $user->id,
        ]);
    }
}
