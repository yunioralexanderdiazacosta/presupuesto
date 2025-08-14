<?php

namespace App\Imports;

use App\Models\CostCenter;
use App\Models\Fruit;
use App\Models\Variety;
use App\Models\Parcel;
use App\Models\DevelopmentState;
use App\Models\CompanyReason;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class CostCentersImport implements ToModel, WithHeadingRow, WithValidation
{
    public function model(array $row)
    {
        $user = Auth::user();
        $season_id = session('season_id');

        // Buscar IDs por nombre
        $fruit_id = Fruit::where('name', $row['frutal'] ?? '')->where('team_id', $user->team_id)->value('id');
        $variety_id = Variety::where('name', $row['variedad'] ?? '')->where('team_id', $user->team_id)->value('id');
        $parcel_id = Parcel::where('name', $row['parcela'] ?? '')->where('team_id', $user->team_id)->value('id');
        $development_state_id = DevelopmentState::where('name', $row['estado_de_desarrollo'] ?? '')->value('id');
        $company_reason_id = CompanyReason::where('name', $row['razon_social'] ?? '')->where('team_id', $user->team_id)->value('id');

        return new CostCenter([
            'name' => $row['nombre_de_cc'] ?? '',
            'surface' => $row['superficie'] ?? 0,
            'observations' => $row['observaciones'] ?? '',
            'fruit_id' => $fruit_id,
            'variety_id' => $variety_id,
            'parcel_id' => $parcel_id,
            'development_state_id' => $development_state_id,
            'year_plantation' => $row['ano_de_plantacion'] ?? null,
            'company_reason_id' => $company_reason_id,
            'season_id' => $season_id,
        ]);
    }
    /**
     * Definir reglas de validación por fila. Si fallan, la importación se detiene.
     * @return array
     */
    public function rules(): array
    {
        $teamId = Auth::user()->team_id;
        $seasonId = session('season_id');
        return [
            // No repetir nombre de centro de costo
            'nombre_de_cc' => ['required', function($attribute, $value, $fail) use ($teamId, $seasonId) {
                if (CostCenter::where('name', $value)
                    ->where('season_id', $seasonId)
                    ->whereHas('season', fn($q) => $q->where('team_id', $teamId))
                    ->exists()) {
                    $fail("El centro de costo '{$value}' ya existe para esta temporada.");
                }
            }],
            'frutal' => ['required', function($attribute, $value, $fail) use ($teamId) {
                if (!Fruit::where('name', $value)->where('team_id', $teamId)->exists()) {
                    $fail("El frutal '{$value}' no existe.");
                }
            }],
            'variedad' => ['required', function($attribute, $value, $fail) use ($teamId) {
                if (!Variety::where('name', $value)->where('team_id', $teamId)->exists()) {
                    $fail("La variedad '{$value}' no existe.");
                }
            }],
            'parcela' => ['required', function($attribute, $value, $fail) use ($teamId) {
                if (!Parcel::where('name', $value)->where('team_id', $teamId)->exists()) {
                    $fail("La parcela '{$value}' no existe.");
                }
            }],
            'estado_de_desarrollo' => ['required', function($attribute, $value, $fail) {
                if (!DevelopmentState::where('name', $value)->exists()) {
                    $fail("El estado de desarrollo '{$value}' no existe.");
                }
            }],
            'razon_social' => ['required', function($attribute, $value, $fail) use ($teamId) {
                if (!CompanyReason::where('name', $value)->where('team_id', $teamId)->exists()) {
                    $fail("La razón social '{$value}' no existe.");
                }
            }],
        ];
    }
}
