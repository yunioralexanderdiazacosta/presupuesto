<?php

namespace App\Http\Controllers\ContractTemplates;

use App\Http\Controllers\Controller;
use App\Models\ContractTemplate;
use App\Models\Employee;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ContractTemplateController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $templates = ContractTemplate::where('team_id', $user->team_id)
            ->latest()
            ->get();

        $employees = Employee::with('activeContract')
            ->where('team_id', $user->team_id)
            ->where('is_active', true)
            ->orderBy('paternal_surname')
            ->get()
            ->map(fn($e) => [
                'value' => $e->id,
                'label' => $e->full_name . ' (' . $e->rut . ')',
                'contract_date' => optional($e->activeContract?->contract_date)->format('Y-m-d'),
                'labor' => $e->activeContract?->labor ?? '',
                'contract_type' => $e->activeContract?->contract_type ?? '',
            ]);

        // Campos disponibles para las plantillas Word
        $availableFields = [
            ['field' => '${nombre_empleado}', 'description' => 'Nombre completo del empleado'],
            ['field' => '${primer_nombre}', 'description' => 'Primer nombre'],
            ['field' => '${segundo_nombre}', 'description' => 'Segundo nombre'],
            ['field' => '${apellido_paterno}', 'description' => 'Apellido paterno'],
            ['field' => '${apellido_materno}', 'description' => 'Apellido materno'],
            ['field' => '${rut}', 'description' => 'RUT del empleado'],
            ['field' => '${fecha_nacimiento}', 'description' => 'Fecha de nacimiento'],
            ['field' => '${nacionalidad}', 'description' => 'Nacionalidad'],
            ['field' => '${cargo}', 'description' => 'Cargo del contrato'],
            ['field' => '${labor}', 'description' => 'Labor a realizar'],
            ['field' => '${tipo_contrato}', 'description' => 'Tipo de contrato'],
            ['field' => '${fecha_contrato}', 'description' => 'Fecha del contrato'],
            ['field' => '${fecha_termino}', 'description' => 'Fecha de término'],
            ['field' => '${sueldo_base}', 'description' => 'Sueldo base (formateado)'],
            ['field' => '${sueldo_liquido}', 'description' => 'Sueldo líquido (formateado)'],
            ['field' => '${direccion}', 'description' => 'Dirección del empleado'],
            ['field' => '${telefono}', 'description' => 'Teléfono'],
            ['field' => '${email}', 'description' => 'Email'],
            ['field' => '${estado_civil}', 'description' => 'Estado civil'],
            ['field' => '${ciudad}', 'description' => 'Ciudad'],
            ['field' => '${razon_social}', 'description' => 'Razón social de la empresa'],
            ['field' => '${rut_empresa}', 'description' => 'RUT de la empresa'],
            ['field' => '${representante_legal}', 'description' => 'Representante legal'],
            ['field' => '${rut_representante}', 'description' => 'RUT del representante'],
            ['field' => '${direccion_empresa}', 'description' => 'Dirección de la empresa'],
            ['field' => '${jornada}', 'description' => 'Jornada/horario'],
            ['field' => '${afp}', 'description' => 'AFP'],
            ['field' => '${salud}', 'description' => 'Plan de salud'],
            ['field' => '${fecha_actual}', 'description' => 'Fecha de generación del documento'],
            // Datos bancarios
            ['field' => '${forma_pago}', 'description' => 'Forma de pago'],
            ['field' => '${transferencia}', 'description' => 'Banco / Institución para transferencia'],
            ['field' => '${tipo_cuenta}', 'description' => 'Tipo de cuenta bancaria'],
            ['field' => '${numero_cuenta}', 'description' => 'N° de cuenta bancaria'],
        ];

        return Inertia::render('ContractTemplates/Index', [
            'templates' => $templates,
            'employees' => $employees,
            'availableFields' => $availableFields,
        ]);
    }
}
