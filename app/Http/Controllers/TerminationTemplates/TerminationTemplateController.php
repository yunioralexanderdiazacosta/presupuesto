<?php

namespace App\Http\Controllers\TerminationTemplates;

use App\Http\Controllers\Controller;
use App\Models\Termination;
use App\Models\TerminationTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class TerminationTemplateController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $templates = TerminationTemplate::where('team_id', $user->team_id)
            ->latest()
            ->get();

        // Historial de términos del equipo para generar documentos
        $terminations = Termination::with(['employee', 'contract.companyReason', 'contract.afp', 'contract.healthPlan', 'contract.city', 'contract.paymentMethod', 'contract.bank', 'contract.accountType', 'causalTermino'])
            ->where('team_id', $user->team_id)
            ->latest()
            ->get()
            ->map(fn($t) => [
                'value'         => $t->id,
                'label'         => $t->employee->paternal_surname . ' ' . ($t->employee->maternal_surname ?? '') . ', ' . $t->employee->first_name . ' (' . $t->employee->rut . ')',
                'fecha_termino' => optional($t->fecha_termino)->format('Y-m-d'),
                'causal'        => $t->causalTermino->articulo . ' - ' . $t->causalTermino->nombre,
            ]);

        // Campos disponibles para las plantillas Word
        $availableFields = [
            // Datos del empleado
            ['field' => '${nombre_empleado}',   'description' => 'Nombre completo del empleado'],
            ['field' => '${primer_nombre}',      'description' => 'Primer nombre'],
            ['field' => '${segundo_nombre}',     'description' => 'Segundo nombre'],
            ['field' => '${apellido_paterno}',   'description' => 'Apellido paterno'],
            ['field' => '${apellido_materno}',   'description' => 'Apellido materno'],
            ['field' => '${rut}',                'description' => 'RUT del empleado'],
            ['field' => '${fecha_nacimiento}',   'description' => 'Fecha de nacimiento'],
            ['field' => '${nacionalidad}',       'description' => 'Nacionalidad'],
            ['field' => '${estado_civil}',       'description' => 'Estado civil'],
            ['field' => '${direccion}',          'description' => 'Dirección del empleado'],
            ['field' => '${ciudad}',             'description' => 'Ciudad'],
            ['field' => '${telefono}',           'description' => 'Teléfono'],
            ['field' => '${email}',              'description' => 'Email'],
            // Datos del contrato
            ['field' => '${cargo}',              'description' => 'Cargo del contrato'],
            ['field' => '${labor}',              'description' => 'Labor a realizar'],
            ['field' => '${tipo_contrato}',      'description' => 'Tipo de contrato'],
            ['field' => '${fecha_contrato}',     'description' => 'Fecha de inicio del contrato'],
            ['field' => '${sueldo_base}',        'description' => 'Sueldo base (formateado)'],
            ['field' => '${sueldo_liquido}',     'description' => 'Sueldo líquido (formateado)'],
            ['field' => '${afp}',                'description' => 'AFP'],
            ['field' => '${salud}',              'description' => 'Plan de salud'],
            // Datos bancarios
            ['field' => '${forma_pago}',         'description' => 'Forma de pago'],
            ['field' => '${transferencia}',      'description' => 'Banco / Institución para transferencia'],
            ['field' => '${tipo_cuenta}',        'description' => 'Tipo de cuenta bancaria'],
            ['field' => '${numero_cuenta}',      'description' => 'N° de cuenta bancaria'],
            // Datos del término
            ['field' => '${causal_articulo}',    'description' => 'Artículo de la causal (ej: Art. 159 N°1)'],
            ['field' => '${causal_nombre}',      'description' => 'Nombre/descripción de la causal'],
            ['field' => '${fecha_termino}',      'description' => 'Fecha de término del contrato'],
            ['field' => '${finiquito}',          'description' => 'Monto del finiquito (formateado)'],
            ['field' => '${indemnizacion}',      'description' => 'Indemnización (formateado)'],
            ['field' => '${mes_aviso}',          'description' => 'Mes de aviso (formateado)'],
            ['field' => '${descuento_afc}',      'description' => 'Descuento AFC (formateado)'],
            ['field' => '${dias_vacaciones}',    'description' => 'Días de vacaciones pendientes'],
            ['field' => '${monto_vacaciones}',   'description' => 'Monto por vacaciones (formateado)'],
            ['field' => '${anos_servicio}',      'description' => 'Años de servicio'],
            ['field' => '${notas}',              'description' => 'Notas del término'],
            // Datos de la empresa
            ['field' => '${razon_social}',       'description' => 'Razón social de la empresa'],
            ['field' => '${rut_empresa}',        'description' => 'RUT de la empresa'],
            ['field' => '${representante_legal}','description' => 'Representante legal'],
            ['field' => '${rut_representante}',  'description' => 'RUT del representante'],
            ['field' => '${direccion_empresa}',  'description' => 'Dirección de la empresa'],
            // General
            ['field' => '${fecha_actual}',       'description' => 'Fecha de generación del documento'],
        ];

        return Inertia::render('TerminationTemplates/Index', [
            'templates'       => $templates,
            'terminations'    => $terminations,
            'availableFields' => $availableFields,
        ]);
    }
}
