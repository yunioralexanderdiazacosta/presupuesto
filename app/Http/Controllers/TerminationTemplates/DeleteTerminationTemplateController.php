<?php

namespace App\Http\Controllers\TerminationTemplates;

use App\Models\TerminationTemplate;
use Illuminate\Support\Facades\Storage;

class DeleteTerminationTemplateController
{
    public function __invoke(TerminationTemplate $terminationTemplate)
    {
        Storage::disk('local')->delete($terminationTemplate->file_path);

        $terminationTemplate->delete();

        return redirect()->route('termination-templates.index')
            ->with('success', 'Plantilla eliminada correctamente.');
    }
}
