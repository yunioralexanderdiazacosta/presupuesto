<?php

namespace App\Imports;

use App\Models\Employee;
use App\Http\Requests\Employees\StoreEmployeeRequest;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Carbon\Carbon;

class EmployeesImport implements ToModel, WithHeadingRow, WithValidation
{
    public function model(array $row)
    {
        $user = Auth::user();

        $rut = preg_replace('/[.\-]/', '', strtoupper(trim($row['rut'] ?? '')));
        // Reformatear a formato limpio para almacenar
        $rutFormatted = trim($row['rut'] ?? '');

        // Si ya existe en este team, saltar la fila
        if (Employee::where('rut', $rutFormatted)->where('team_id', $user->team_id)->exists()) {
            return null;
        }

        // Parsear fecha
        $birthDate = null;
        if (!empty($row['fecha_nacimiento'])) {
            $rawDate = $row['fecha_nacimiento'];
            try {
                // Si Excel envía número serial (ej. 44927)
                if (is_numeric($rawDate)) {
                    $birthDate = Date::excelToDateTimeObject($rawDate)->format('Y-m-d');
                } else {
                    $rawDate = trim($rawDate);
                    // Intentar formatos comunes: d/m/Y, d-m-Y, d.m.Y
                    $formats = ['d/m/Y', 'd-m-Y', 'd.m.Y', 'Y-m-d', 'Y/m/d'];
                    $parsed = false;
                    foreach ($formats as $fmt) {
                        try {
                            $birthDate = Carbon::createFromFormat($fmt, $rawDate)->format('Y-m-d');
                            $parsed = true;
                            break;
                        } catch (\Exception $e) {
                            continue;
                        }
                    }
                    if (!$parsed) {
                        $birthDate = Carbon::parse($rawDate)->format('Y-m-d');
                    }
                }
            } catch (\Exception $e) {
                $birthDate = null;
            }
        }

        // Determinar estado
        $estado = strtolower(trim($row['estado'] ?? 'activo'));
        $isActive = !in_array($estado, ['inactivo', 'no', '0', 'false']);

        return new Employee([
            'team_id' => $user->team_id,
            'first_name' => trim($row['primer_nombre'] ?? ''),
            'second_name' => trim($row['segundo_nombre'] ?? '') ?: null,
            'paternal_surname' => trim($row['apellido_paterno'] ?? ''),
            'maternal_surname' => trim($row['apellido_materno'] ?? '') ?: null,
            'rut' => $rutFormatted,
            'birth_date' => $birthDate,
            'nationality' => trim($row['nacionalidad'] ?? '') ?: 'Chilena',
            'is_active' => $isActive,
        ]);
    }

    public function rules(): array
    {
        $teamId = Auth::user()->team_id;

        return [
            'rut' => ['required', 'string', 'max:12', function ($attribute, $value, $fail) {
                if (!StoreEmployeeRequest::validarRut($value)) {
                    $fail("El RUT '{$value}' no es válido.");
                }
            }],
            'primer_nombre' => 'required|string|max:100',
            'segundo_nombre' => 'nullable|string|max:100',
            'apellido_paterno' => 'required|string|max:100',
            'apellido_materno' => 'nullable|string|max:100',
            'fecha_nacimiento' => 'nullable|string',
            'nacionalidad' => 'nullable|string|max:60',
            'estado' => 'nullable|string',
        ];
    }
}
