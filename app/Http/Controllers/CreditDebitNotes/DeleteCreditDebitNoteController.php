<?php

namespace App\Http\Controllers\CreditDebitNotes;

use App\Http\Controllers\Controller;
use App\Models\CreditDebitNote;

class DeleteCreditDebitNoteController extends Controller
{
    public function __invoke(CreditDebitNote $note)
    {
        $note->delete();
    }
}
