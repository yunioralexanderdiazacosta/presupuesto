<?php

namespace App\Http\Controllers;

use App\Models\CreditDebitNote;
use App\Models\Invoice;
use App\Models\Outflow;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class OutflowsDashboardController extends Controller
{
    public function index(Request $request)
    {
        $season_id = session('season_id');
        $team_id = Auth::user()->team_id;

        // Validar que exista season_id en sesión
        // Nota: La ruta 'select.budget' es donde el usuario selecciona la temporada (season)
        // El nombre es histórico y se mantiene por compatibilidad
        if (!$season_id) {
            return redirect()->route('select.budget');
        }

        return Inertia::render('OutflowsDashboard', [
            'summary' => $this->getSummary($season_id, $team_id),
            'investments' => $this->getInvestmentsTotal($season_id, $team_id),
            'expenses' => $this->getExpensesTotal($season_id, $team_id),
            'invoices' => $this->getInvoicesTotal($season_id, $team_id),
            'creditNotes' => $this->getCreditNotesTotal($season_id, $team_id),
            'debitNotes' => $this->getDebitNotesTotal($season_id, $team_id),
        ]);
    }

    private function getSummary($season_id, $team_id)
    {
        try {
            // Obtener todos los outflows con sus relaciones
            $outflows = Outflow::where('season_id', $season_id)
                ->where('team_id', $team_id)
                ->with(['invoiceProduct', 'creditDebitNoteItem'])
                ->get();

            $totalCount = $outflows->count();

            // Calcular el total sumando quantity × unit_price de cada outflow
            $totalAmount = $outflows->sum(function($outflow) {
                // Si viene de invoice_product
                if ($outflow->invoice_product_id && $outflow->invoiceProduct) {
                    return $outflow->quantity * $outflow->invoiceProduct->unit_price;
                }
                // Si viene de credit_debit_note_item
                if ($outflow->credit_debit_note_item_id && $outflow->creditDebitNoteItem) {
                    return $outflow->quantity * $outflow->creditDebitNoteItem->unit_price;
                }
                return 0;
            });

            return [
                'total_amount' => floatval($totalAmount ?? 0),
                'total_count' => intval($totalCount ?? 0),
                'avg_per_outflow' => $totalCount > 0 ? floatval($totalAmount / $totalCount) : 0,
            ];
        } catch (\Exception $e) {
            // En caso de error, retornar valores por defecto
            Log::error('Error en OutflowsDashboard getSummary: ' . $e->getMessage());
            return [
                'total_amount' => 0,
                'total_count' => 0,
                'avg_per_outflow' => 0,
            ];
        }
    }

    private function getInvestmentsTotal($season_id, $team_id)
    {
        try {
            // Obtener outflows que tienen operación "inversion" (case-insensitive)
            $outflows = Outflow::where('season_id', $season_id)
                ->where('team_id', $team_id)
                ->whereHas('operation', function($query) {
                    $query->whereRaw('LOWER(name) LIKE ?', ['%inversion%']);
                })
                ->with(['invoiceProduct', 'creditDebitNoteItem', 'operation'])
                ->get();

            $totalCount = $outflows->count();

            // Calcular el total sumando quantity × unit_price
            $totalAmount = $outflows->sum(function($outflow) {
                if ($outflow->invoice_product_id && $outflow->invoiceProduct) {
                    return $outflow->quantity * $outflow->invoiceProduct->unit_price;
                }
                if ($outflow->credit_debit_note_item_id && $outflow->creditDebitNoteItem) {
                    return $outflow->quantity * $outflow->creditDebitNoteItem->unit_price;
                }
                return 0;
            });

            return [
                'total' => floatval($totalAmount ?? 0),
                'count' => intval($totalCount ?? 0),
            ];
        } catch (\Exception $e) {
            Log::error('Error en OutflowsDashboard getInvestmentsTotal: ' . $e->getMessage());
            return [
                'total' => 0,
                'count' => 0,
            ];
        }
    }

    private function getExpensesTotal($season_id, $team_id)
    {
        try {
            // Obtener outflows que tienen operación "gasto" (case-insensitive)
            $outflows = Outflow::where('season_id', $season_id)
                ->where('team_id', $team_id)
                ->whereHas('operation', function($query) {
                    $query->whereRaw('LOWER(name) LIKE ?', ['%gasto%']);
                })
                ->with(['invoiceProduct', 'creditDebitNoteItem', 'operation'])
                ->get();

            $totalCount = $outflows->count();

            // Calcular el total sumando quantity × unit_price
            $totalAmount = $outflows->sum(function($outflow) {
                if ($outflow->invoice_product_id && $outflow->invoiceProduct) {
                    return $outflow->quantity * $outflow->invoiceProduct->unit_price;
                }
                if ($outflow->credit_debit_note_item_id && $outflow->creditDebitNoteItem) {
                    return $outflow->quantity * $outflow->creditDebitNoteItem->unit_price;
                }
                return 0;
            });

            return [
                'total' => floatval($totalAmount ?? 0),
                'count' => intval($totalCount ?? 0),
            ];
        } catch (\Exception $e) {
            Log::error('Error en OutflowsDashboard getExpensesTotal: ' . $e->getMessage());
            return [
                'total' => 0,
                'count' => 0,
            ];
        }
    }

    private function getInvoicesTotal($season_id, $team_id)
    {
        try {
            // Obtener todas las facturas con sus productos
            $invoices = Invoice::where('season_id', $season_id)
                ->where('team_id', $team_id)
                ->with('invoiceProducts')
                ->get();

            $totalCount = $invoices->count();

            // Calcular el total sumando unit_price × amount de cada producto
            $totalAmount = $invoices->sum(function($invoice) {
                return $invoice->invoiceProducts->sum(function($product) {
                    return $product->unit_price * $product->amount;
                });
            });

            return [
                'total' => floatval($totalAmount ?? 0),
                'count' => intval($totalCount ?? 0),
            ];
        } catch (\Exception $e) {
            Log::error('Error en OutflowsDashboard getInvoicesTotal: ' . $e->getMessage());
            return [
                'total' => 0,
                'count' => 0,
            ];
        }
    }

    private function getCreditNotesTotal($season_id, $team_id)
    {
        try {
            // Obtener todas las notas de crédito con sus items
            $notes = CreditDebitNote::where('season_id', $season_id)
                ->where('team_id', $team_id)
                ->where('type', 'credito')
                ->with('items')
                ->get();

            $totalCount = $notes->count();

            // Calcular el total sumando quantity × unit_price de cada item
            $totalAmount = $notes->sum(function($note) {
                return $note->items->sum(function($item) {
                    return $item->quantity * $item->unit_price;
                });
            });

            return [
                'total' => floatval($totalAmount ?? 0),
                'count' => intval($totalCount ?? 0),
            ];
        } catch (\Exception $e) {
            Log::error('Error en OutflowsDashboard getCreditNotesTotal: ' . $e->getMessage());
            return [
                'total' => 0,
                'count' => 0,
            ];
        }
    }

    private function getDebitNotesTotal($season_id, $team_id)
    {
        try {
            // Obtener todas las notas de débito con sus items
            $notes = CreditDebitNote::where('season_id', $season_id)
                ->where('team_id', $team_id)
                ->where('type', 'debito')
                ->with('items')
                ->get();

            $totalCount = $notes->count();

            // Calcular el total sumando quantity × unit_price de cada item
            $totalAmount = $notes->sum(function($note) {
                return $note->items->sum(function($item) {
                    return $item->quantity * $item->unit_price;
                });
            });

            return [
                'total' => floatval($totalAmount ?? 0),
                'count' => intval($totalCount ?? 0),
            ];
        } catch (\Exception $e) {
            Log::error('Error en OutflowsDashboard getDebitNotesTotal: ' . $e->getMessage());
            return [
                'total' => 0,
                'count' => 0,
            ];
        }
    }
}
