<?php

namespace App\Http\Controllers\ContractTemplates;

use App\Models\ContractTemplate;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\TemplateProcessor;
use ZipArchive;

class GenerateContractController
{
    public function __invoke(Request $request, ContractTemplate $contractTemplate)
    {
        $request->validate([
            'employee_ids' => 'required|array|min:1',
            'employee_ids.*' => 'exists:employees,id',
        ]);

        $user = Auth::user();
        $templatePath = Storage::disk('local')->path($contractTemplate->file_path);

        if (!file_exists($templatePath)) {
            return back()->with('error', 'El archivo de plantilla no fue encontrado.');
        }

        $employees = Employee::with(['activeContract.companyReason', 'activeContract.schedule', 'activeContract.afp', 'activeContract.healthPlan', 'activeContract.city'])
            ->where('team_id', $user->team_id)
            ->whereIn('id', $request->employee_ids)
            ->get();

        // Si es un solo empleado, generar y descargar directo
        if ($employees->count() === 1) {
            $employee = $employees->first();
            $outputPath = $this->generateForEmployee($templatePath, $employee);
            $fileName = 'Contrato_' . str_replace(' ', '_', $employee->full_name) . '.docx';

            return response()->download($outputPath, $fileName)->deleteFileAfterSend(true);
        }

        // Múltiples empleados: combinar en un solo .docx con saltos de página
        $tempFiles = [];
        foreach ($employees as $employee) {
            $tempFiles[] = $this->generateForEmployee($templatePath, $employee);
        }

        $outputPath = $this->mergeDocx($tempFiles);

        // Limpiar temporales
        foreach ($tempFiles as $f) {
            @unlink($f);
        }

        $fileName = 'Contratos_' . date('Y-m-d') . '.docx';

        return response()->download($outputPath, $fileName)->deleteFileAfterSend(true);
    }

    /**
     * Combina múltiples .docx en uno solo manipulando el XML interno.
     * Inserta un salto de página entre cada documento.
     */
    private function mergeDocx(array $docxFiles): string
    {
        // Usar el primer archivo como base
        $basePath = tempnam(sys_get_temp_dir(), 'merged_') . '.docx';
        copy($docxFiles[0], $basePath);

        $zip = new ZipArchive();
        $zip->open($basePath);
        $baseXml = $zip->getFromName('word/document.xml');
        $zip->close();

        // Extraer el body del documento base (sin el tag </w:body></w:document> final)
        $baseXml = preg_replace('/<\/w:body>\s*<\/w:document>\s*$/', '', $baseXml);

        // Agregar los demás documentos con salto de página
        for ($i = 1; $i < count($docxFiles); $i++) {
            $docZip = new ZipArchive();
            $docZip->open($docxFiles[$i]);
            $docXml = $docZip->getFromName('word/document.xml');
            $docZip->close();

            // Extraer solo el contenido dentro de <w:body>...</w:body>
            if (preg_match('/<w:body>(.*)<\/w:body>/s', $docXml, $matches)) {
                $bodyContent = $matches[1];

                // Insertar salto de página antes del contenido
                $pageBreak = '<w:p><w:r><w:br w:type="page"/></w:r></w:p>';
                $baseXml .= $pageBreak . $bodyContent;
            }
        }

        // Cerrar el XML
        $baseXml .= '</w:body></w:document>';

        // Escribir el XML combinado de vuelta al docx
        $zip = new ZipArchive();
        $zip->open($basePath);
        $zip->addFromString('word/document.xml', $baseXml);
        $zip->close();

        return $basePath;
    }

    private function generateForEmployee(string $templatePath, Employee $employee): string
    {
        $template = new TemplateProcessor($templatePath);
        $contract = $employee->activeContract;

        $template->setValue('nombre_empleado', $employee->full_name ?? '');
        $template->setValue('primer_nombre', $employee->first_name ?? '');
        $template->setValue('segundo_nombre', $employee->second_name ?? '');
        $template->setValue('apellido_paterno', $employee->paternal_surname ?? '');
        $template->setValue('apellido_materno', $employee->maternal_surname ?? '');
        $template->setValue('rut', $employee->rut ?? '');
        $template->setValue('fecha_nacimiento', optional($employee->birth_date)->format('d/m/Y') ?? '');
        $template->setValue('nacionalidad', $employee->nationality ?? '');

        // Datos del contrato
        $template->setValue('cargo', $contract->position ?? '');
        $template->setValue('labor', $contract->labor ?? '');
        $template->setValue('tipo_contrato', $contract->contract_type ?? '');
        $template->setValue('fecha_contrato', optional($contract?->contract_date)->format('d/m/Y') ?? '');
        $template->setValue('fecha_termino', optional($contract?->end_date)->format('d/m/Y') ?? '');
        $template->setValue('sueldo_base', number_format($contract->base_salary ?? 0, 0, ',', '.'));
        $template->setValue('sueldo_liquido', number_format($contract->net_salary ?? 0, 0, ',', '.'));
        $template->setValue('direccion', $contract->address ?? '');
        $template->setValue('telefono', $contract->phone ?? '');
        $template->setValue('email', $contract->email ?? '');
        $template->setValue('estado_civil', $contract->marital_status ?? '');
        $template->setValue('ciudad', $contract->city?->name ?? '');

        // Datos de la empresa
        $template->setValue('razon_social', $contract?->companyReason?->name ?? '');
        $template->setValue('rut_empresa', $contract?->companyReason?->rut ?? '');
        $template->setValue('representante_legal', $contract?->companyReason?->legal_representative ?? '');
        $template->setValue('rut_representante', $contract?->companyReason?->rut_representative ?? '');
        $template->setValue('direccion_empresa', $contract?->companyReason?->address ?? '');

        // Otros
        $template->setValue('jornada', $contract?->schedule?->name ?? '');
        $template->setValue('afp', $contract?->afp?->name ?? '');
        $template->setValue('salud', $contract?->healthPlan?->name ?? '');
        $template->setValue('fecha_actual', now()->format('d/m/Y'));

        $outputPath = tempnam(sys_get_temp_dir(), 'contract_') . '.docx';
        $template->saveAs($outputPath);

        return $outputPath;
    }
}
