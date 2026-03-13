<?php

namespace App\Http\Controllers\Contracts;

use App\Models\Contract;

class DeleteContractController
{
    public function __invoke(Contract $contract)
    {
        $contract->delete();

        return redirect()->route('contracts.index')
            ->with('success', 'Contrato eliminado correctamente.');
    }
}
