<?php

namespace App\Http\Controllers\ContractTemplates;

use App\Models\ContractTemplate;
use Illuminate\Support\Facades\Storage;

class DeleteContractTemplateController
{
    public function __invoke(ContractTemplate $contractTemplate)
    {
        Storage::disk('local')->delete($contractTemplate->file_path);

        $contractTemplate->delete();

        return redirect()->route('contract-templates.index')
            ->with('success', 'Plantilla eliminada correctamente.');
    }
}
