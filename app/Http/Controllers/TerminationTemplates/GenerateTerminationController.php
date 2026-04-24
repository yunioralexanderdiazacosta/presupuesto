<?php

namespace App\Http\Controllers\TerminationTemplates;

use App\Models\Termination;
use App\Models\TerminationTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\TemplateProcessor;
use ZipArchive;

class GenerateTerminationController
{
    public function __invoke(Request $request, TerminationTemplate $terminationTemplate)
    {
        $request->validate([
            'termination_ids'   => 'required|array|min:1',
            'termination_ids.*' => 'exists:terminations,id',
        ]);

        $user = Auth::user();
        $templatePath = Storage::disk('local')->path($terminationTemplate->file_path);

        if (!file_exists($templatePath)) {
            return back()->with('error', 'El archivo de plantilla no fue encontrado.');
        }

        $terminations = Termination::with([
            'employee',
            'contract.companyReason',
            'contract.afp',
            'contract.healthPlan',
            'contract.city',
            'contract.paymentMethod',
            'contract.bank',
            'contract.accountType',
            'causalTermino',
        ])
            ->where('team_id', $user->team_id)
            ->whereIn('id', $request->termination_ids)
            ->get();

        // Un solo término: descargar directo
        if ($terminations->count() === 1) {
            $termination = $terminations->first();
            $outputPath = $this->generateForTermination($templatePath, $termination);
            $fileName = 'Finiquito_' . str_replace(' ', '_', $termination->employee->full_name) . '.docx';

            return response()->download($outputPath, $fileName)->deleteFileAfterSend(true);
        }

        // Múltiples: combinar en un solo .docx con saltos de página
        $tempFiles = [];
        foreach ($terminations as $termination) {
            $tempFiles[] = $this->generateForTermination($templatePath, $termination);
        }

        $outputPath = $this->mergeDocx($tempFiles);

        foreach ($tempFiles as $f) {
            @unlink($f);
        }

        $fileName = 'Finiquitos_' . date('Y-m-d') . '.docx';

        return response()->download($outputPath, $fileName)->deleteFileAfterSend(true);
    }

    private function mergeDocx(array $docxFiles): string
    {
        $basePath = tempnam(sys_get_temp_dir(), 'merged_') . '.docx';
        copy($docxFiles[0], $basePath);

        $zip = new ZipArchive();
        $zip->open($basePath);
        $baseXml = $zip->getFromName('word/document.xml');
        $zip->close();

        $baseXml = preg_replace('/<\/w:body>\s*<\/w:document>\s*$/', '', $baseXml);

        for ($i = 1; $i < count($docxFiles); $i++) {
            $docZip = new ZipArchive();
            $docZip->open($docxFiles[$i]);
            $docXml = $docZip->getFromName('word/document.xml');
            $docZip->close();

            if (preg_match('/<w:body>(.*)<\/w:body>/s', $docXml, $matches)) {
                $bodyContent = $matches[1];
                $pageBreak = '<w:p><w:r><w:br w:type="page"/></w:r></w:p>';
                $baseXml .= $pageBreak . $bodyContent;
            }
        }

        $baseXml .= '</w:body></w:document>';

        $zip = new ZipArchive();
        $zip->open($basePath);
        $zip->addFromString('word/document.xml', $baseXml);
        $zip->close();

        return $basePath;
    }

    private function generateForTermination(string $templatePath, Termination $termination): string
    {
        $template  = new TemplateProcessor($templatePath);
        $employee  = $termination->employee;
        $contract  = $termination->contract;
        $causal    = $termination->causalTermino;

        // Datos del empleado
        $template->setValue('nombre_empleado',    $employee->full_name ?? '');
        $template->setValue('primer_nombre',      $employee->first_name ?? '');
        $template->setValue('segundo_nombre',     $employee->second_name ?? '');
        $template->setValue('apellido_paterno',   $employee->paternal_surname ?? '');
        $template->setValue('apellido_materno',   $employee->maternal_surname ?? '');
        $template->setValue('rut',                $employee->rut ?? '');
        $template->setValue('fecha_nacimiento',   optional($employee->birth_date)->format('d/m/Y') ?? '');
        $template->setValue('nacionalidad',       $employee->nationality ?? '');

        // Datos del contrato
        $template->setValue('cargo',              $contract?->position ?? '');
        $template->setValue('labor',              $contract?->labor ?? '');
        $template->setValue('tipo_contrato',      $contract?->contract_type ?? '');
        $template->setValue('fecha_contrato',     optional($contract?->contract_date)->format('d/m/Y') ?? '');
        $template->setValue('sueldo_base',        number_format($contract?->base_salary ?? 0, 0, ',', '.'));
        $template->setValue('sueldo_liquido',     number_format($contract?->net_salary ?? 0, 0, ',', '.'));
        $template->setValue('direccion',          $contract?->address ?? '');
        $template->setValue('telefono',           $contract?->phone ?? '');
        $template->setValue('email',              $contract?->email ?? '');
        $template->setValue('estado_civil',       $contract?->marital_status ?? '');
        $template->setValue('ciudad',             $contract?->city?->name ?? '');
        $template->setValue('afp',                $contract?->afp?->name ?? '');
        $template->setValue('salud',              $contract?->healthPlan?->name ?? '');

        // Datos bancarios
        $template->setValue('forma_pago',         $contract?->paymentMethod?->name ?? '');
        $template->setValue('transferencia',      $contract?->bank?->name ?? '');
        $template->setValue('tipo_cuenta',        $contract?->accountType?->name ?? '');
        $template->setValue('numero_cuenta',      $contract?->account_number ?? '');

        // Datos del término
        $template->setValue('causal_articulo',    $causal?->articulo ?? '');
        $template->setValue('causal_nombre',      $causal?->nombre ?? '');
        $template->setValue('fecha_termino',      optional($termination->fecha_termino)->format('d/m/Y') ?? '');
        $template->setValue('finiquito',          $termination->settlement !== null ? number_format($termination->settlement, 0, ',', '.') : '');
        $template->setValue('notas',              $termination->notas ?? '');

        // Datos de la empresa
        $template->setValue('razon_social',       $contract?->companyReason?->name ?? '');
        $template->setValue('rut_empresa',        $contract?->companyReason?->rut ?? '');
        $template->setValue('representante_legal',$contract?->companyReason?->legal_representative ?? '');
        $template->setValue('rut_representante',  $contract?->companyReason?->rut_representative ?? '');
        $template->setValue('direccion_empresa',  $contract?->companyReason?->address ?? '');

        // General
        $template->setValue('fecha_actual',       now()->format('d/m/Y'));

        $outputPath = tempnam(sys_get_temp_dir(), 'termination_') . '.docx';
        $template->saveAs($outputPath);

        return $outputPath;
    }
}
