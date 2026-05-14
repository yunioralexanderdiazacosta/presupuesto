<?php

namespace App\Imports;

use App\Models\Supplier;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class SuppliersImport implements ToModel, WithHeadingRow, WithValidation
{
    public function model(array $row)
    {
        $user = Auth::user();

        return new Supplier([
            'team_id' => $user->team_id,
            'name'    => $row['nombre']   ?? '',
            'rut'     => $row['rut']      ?? '',
            'contact' => $row['contacto'] ?? null,
            'email'   => $row['email']    ?? null,
            'phone'   => $row['telefono'] ?? null,
        ]);
    }

    public function rules(): array
    {
        $teamId = Auth::user()->team_id;

        return [
            'nombre' => [
                'required',
                function ($attribute, $value, $fail) use ($teamId) {
                    if (Supplier::where('name', $value)->where('team_id', $teamId)->exists()) {
                        $fail("El proveedor '{$value}' ya existe.");
                    }
                },
            ],
            'rut' => [
                'required',
                function ($attribute, $value, $fail) use ($teamId) {
                    if (Supplier::where('rut', $value)->where('team_id', $teamId)->exists()) {
                        $fail("El RUT '{$value}' ya está registrado.");
                    }
                },
            ],
            'email' => ['nullable', 'email'],
        ];
    }
}
