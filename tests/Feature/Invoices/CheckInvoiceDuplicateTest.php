<?php

namespace Tests\Feature\Invoices;

use App\Models\CompanyReason;
use App\Models\Invoice;
use App\Models\Season;
use App\Models\Supplier;
use App\Models\Team;
use App\Models\TypeDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Corre solo contra una base "*_test" (ver tests/Feature/Mcp/McpServerTest.php).
 */
class CheckInvoiceDuplicateTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        if (! str_ends_with(DB::getDatabaseName(), '_test')) {
            $this->markTestSkipped('Solo corre contra una base de datos *_test.');
        }
    }

    private function tenant(string $name): array
    {
        $user = User::create([
            'name' => "Usuario {$name}", 'username' => strtolower($name) . uniqid(),
            'email' => uniqid() . '@example.test', 'password' => bcrypt('secret'), 'status' => 1,
            'email_verified_at' => now(),
        ]);
        $team = Team::create(['name' => $name, 'user_id' => $user->id]);
        $user->update(['team_id' => $team->id]);
        $season = Season::create(['name' => "Temporada {$name}", 'team_id' => $team->id]);
        $supplier = Supplier::create(['team_id' => $team->id, 'name' => "Proveedor {$name}", 'rut' => '1-9']);
        $reason = CompanyReason::create([
            'team_id' => $team->id, 'name' => "Razon {$name}", 'rut' => '2-7',
            'legal_representative' => 'Rep', 'rut_representative' => '3-5', 'address' => 'Calle 1',
        ]);

        $invoice = Invoice::create([
            'payment_term' => 30, 'payment_type' => 1, 'team_id' => $team->id, 'supplier_id' => $supplier->id,
            'company_reason_id' => $reason->id, 'type_document_id' => TypeDocument::firstOrCreate(['name' => 'FACTURA'])->id,
            'number_document' => '1234', 'date' => '2026-11-01', 'due_date' => '2026-12-01', 'season_id' => $season->id,
        ]);

        return compact('user', 'supplier', 'invoice');
    }

    public function test_detects_existing_invoice_for_same_supplier_and_number(): void
    {
        ['user' => $user, 'supplier' => $supplier, 'invoice' => $invoice] = $this->tenant('Alfa');
        $query = ['supplier_id' => $supplier->id, 'number_document' => ' 1234 '];

        $this->actingAs($user)->getJson(route('invoices.check-duplicate', $query))
            ->assertOk()
            ->assertJsonPath('exists', true)
            ->assertJsonPath('invoice.id', $invoice->id);

        // Al editar la propia factura no cuenta como duplicado
        $this->actingAs($user)->getJson(route('invoices.check-duplicate', $query + ['exclude_id' => $invoice->id]))
            ->assertOk()->assertJsonPath('exists', false);

        $this->actingAs($user)->getJson(route('invoices.check-duplicate', ['supplier_id' => $supplier->id, 'number_document' => '9999']))
            ->assertOk()->assertJsonPath('exists', false);
    }

    public function test_does_not_see_invoices_from_other_teams(): void
    {
        ['user' => $alfa] = $this->tenant('Alfa');
        ['supplier' => $betaSupplier] = $this->tenant('Beta');

        $this->actingAs($alfa)->getJson(route('invoices.check-duplicate', ['supplier_id' => $betaSupplier->id, 'number_document' => '1234']))
            ->assertOk()->assertJsonPath('exists', false);
    }

    public function test_requires_supplier_and_number(): void
    {
        ['user' => $user] = $this->tenant('Alfa');

        $this->actingAs($user)->getJson(route('invoices.check-duplicate'))->assertStatus(422);
    }
}
