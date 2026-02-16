<?php

namespace App\Http\Controllers\PurchaseOrders;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\CostCenter;
use App\Models\Product;
use App\Models\Unit;
use App\Models\User;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;

class PurchaseOrderController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $season_id = session('season_id');

        if (!$season_id) {
            return redirect()->route('dashboard')->with('error', 'Debe seleccionar una campaña activa.');
        }

        $term = $request->term ?? '';
        $status = $request->status ?? '';
        $supplierId = $request->supplier_id ?? '';
        $dateFrom = $request->date_from ?? '';
        $dateTo = $request->date_to ?? '';

        // Obtener órdenes de compra con búsqueda y filtros
        $purchaseOrders = PurchaseOrder::with(['supplier', 'costCenters', 'requestedBy', 'approvedBy', 'items.product', 'items.unit'])
            ->where('team_id', $user->team_id)
            ->where('season_id', $season_id)
            ->when($term, function ($query, $search) {
                $query->where(function($q) use ($search) {
                    $q->where('order_number', 'like', '%'.$search.'%')
                      ->orWhere('notes', 'like', '%'.$search.'%')
                      ->orWhereHas('supplier', function($query) use ($search){
                          $query->where('name', 'like', '%'.$search.'%');
                      });
                });
            })
            ->when($status, function($query, $s) {
                $query->where('status', $s);
            })
            ->when($supplierId, function($query, $id) {
                $query->where('supplier_id', $id);
            })
            ->when($dateFrom, function($query, $date) {
                $query->whereDate('order_date', '>=', $date);
            })
            ->when($dateTo, function($query, $date) {
                $query->whereDate('order_date', '<=', $date);
            })
            ->latest('order_date')
            ->paginate(50);

        // Obtener proveedores del equipo
        $suppliers = Supplier::where('team_id', $user->team_id)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn($s) => ['value' => $s->id, 'label' => $s->name]);

        // Obtener centros de costo de la temporada (que pertenecen al equipo)
        $costCenters = CostCenter::where('season_id', $season_id)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn($c) => ['value' => $c->id, 'label' => $c->name]);

        // Obtener agrupaciones con sus centros de costo
        $groupings = \App\Models\Grouping::with(['costCenters' => function($q) use ($season_id) {
            $q->select('cost_centers.id', 'cost_centers.name')->where('season_id', $season_id);
        }])
        ->where('season_id', $season_id)
        ->whereHas('season.team', fn($q) => $q->where('team_id', $user->team_id))
        ->get()
        ->map(fn($g) => [
            'id' => $g->id,
            'name' => $g->name,
            'cost_centers' => $g->costCenters->map(fn($cc) => [
                'id' => $cc->id,
                'name' => $cc->name
            ])->values(),
        ]);

        // Obtener productos del equipo
        $products = Product::where('team_id', $user->team_id)
            ->with('unit')
            ->orderBy('name')
            ->get(['id', 'name', 'unit_id'])
            ->map(fn($p) => [
                'value' => $p->id,
                'label' => $p->name,
                'unit_id' => $p->unit_id,
                'unit_name' => $p->unit->name ?? ''
            ]);

        // Obtener unidades
        $units = Unit::orderBy('name')
            ->get(['id', 'name'])
            ->map(fn($u) => ['value' => $u->id, 'label' => $u->name]);

        // Obtener usuarios aprobadores del equipo (verificar si el rol existe)
        $approvers = collect([]);
        if (Role::where('name', 'Aprobador Compras')->exists()) {
            $approvers = User::role('Aprobador Compras')
                ->where('team_id', $user->team_id)
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn($a) => ['value' => $a->id, 'label' => $a->name]);
        }

        return Inertia::render('PurchaseOrders/Index', [
            'purchaseOrders' => $purchaseOrders,
            'suppliers' => $suppliers,
            'costCenters' => $costCenters,
            'groupings' => $groupings,
            'products' => $products,
            'units' => $units,
            'approvers' => $approvers,
            'filters' => [
                'term' => $term,
                'status' => $status,
                'supplier_id' => $supplierId,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ],
        ]);
    }
}
