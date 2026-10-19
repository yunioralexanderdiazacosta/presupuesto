<?php

namespace App\Http\Controllers\FertilizerOutflows;

use App\Http\Controllers\Controller;
use App\Models\FertilizerOutflow;
use App\Models\Outflow;
use App\Models\InvoiceProduct;
use App\Models\CreditDebitNoteItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Services\InventoryAvailabilityService;
use App\Traits\CheckSeasonLocked;

class UpdateFertilizerOutflowController extends Controller
{
    use CheckSeasonLocked;
    public function __invoke(Request $request, FertilizerOutflow $fertilizerOutflow)
    {
        $this->abortIfSeasonLocked();
        $user = Auth::user();
        $teamId = $user->team_id;
        $seasonId = session('season_id');

        // Verificar que pertenezca al team del usuario
        if ($fertilizerOutflow->team_id !== $teamId) {
            abort(403, 'No autorizado');
        }

        $request->validate([
            'date' => 'required|date',
            'invoice_product_id' => 'required|exists:invoice_products,id',
            'quantity' => 'required|numeric|min:0.01',
            'observations' => 'nullable|string',
        ]);

        // Validar stock disponible
        $invoiceProductId = $request->invoice_product_id;
        $quantity = $request->quantity;
        $currentOutflow = DB::table('outflows')->where('fertilizer_outflow_id', $fertilizerOutflow->id)->first(['id', 'invoice_product_id', 'quantity']);
        $currentOutflowId = $currentOutflow?->id;
        $previousByLine = $currentOutflow?->invoice_product_id
            ? [(int) $currentOutflow->invoice_product_id => (float) $currentOutflow->quantity]
            : [];

        DB::beginTransaction();

        try {
            // Validar stock disponible excluyendo el consumo que se está editando
            InventoryAvailabilityService::assertCanConsume(
                [(int) $invoiceProductId => (float) $quantity],
                [],
                array_filter([$currentOutflowId]),
                'quantity',
                $previousByLine
            );

            // Actualizar fertilizer outflow
            $fertilizerOutflow->update([
                'date' => $request->date,
                'invoice_product_id' => $invoiceProductId,
                'quantity' => $quantity,
                'observations' => $request->observations,
            ]);

            // Actualizar outflow relacionado
            $outflow = Outflow::where('fertilizer_outflow_id', $fertilizerOutflow->id)->first();
            if ($outflow) {
                $outflow->update([
                    'date' => $request->date,
                    'invoice_product_id' => $invoiceProductId,
                    'quantity' => $quantity,
                    'notes' => "Aplicación fertilizante - Orden #{$fertilizerOutflow->fertilizer_order_id}" . ($request->observations ? " - {$request->observations}" : ''),
                ]);
            }

            DB::commit();

            return back()->with('success', 'Aplicación actualizada correctamente');

        } catch (ValidationException $e) {
            DB::rollBack();
            return back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Error al actualizar: ' . $e->getMessage()]);
        }
    }
}
