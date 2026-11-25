<?php

namespace Tests\Feature\Mcp;

use App\Models\CompanyReason;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\InvoiceProduct;
use App\Models\Product;
use App\Models\Season;
use App\Models\Supplier;
use App\Models\Team;
use App\Models\TeamDisabledModule;
use App\Models\TypeDocument;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Laravel\Passport\Passport;
use Tests\TestCase;

/**
 * Se ejecuta contra una base de datos "*_test" (con DatabaseTransactions: nada queda guardado).
 * Ejemplo: $env:DB_DATABASE='presupuesto_test'; php artisan test --filter=McpServerTest
 */
class McpServerTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        if (! str_ends_with(DB::getDatabaseName(), '_test')) {
            $this->markTestSkipped('Las pruebas MCP solo corren contra una base de datos *_test.');
        }
    }

    /** @return array{user:User, season:Season, invoice:Invoice} */
    private function tenant(string $name, float $unitPrice = 1000): array
    {
        $user = User::create([
            'name' => "Usuario {$name}", 'username' => strtolower($name) . uniqid(),
            'email' => uniqid() . '@example.test', 'password' => bcrypt('secret'), 'status' => 1,
        ]);
        $team = Team::create(['name' => $name, 'user_id' => $user->id]);
        $user->update(['team_id' => $team->id]);
        $season = Season::create(['name' => "Temporada {$name}", 'team_id' => $team->id, 'is_default' => true]);
        $supplier = Supplier::create(['team_id' => $team->id, 'name' => "Proveedor {$name}", 'rut' => '11111111-1']);
        $reason = CompanyReason::create([
            'team_id' => $team->id, 'name' => "Razon {$name}", 'rut' => '22222222-2',
            'legal_representative' => 'Representante', 'rut_representative' => '3-3', 'address' => 'Calle 1',
        ]);
        $type = TypeDocument::firstOrCreate(['name' => 'FACTURA']);
        $product = Product::create(['team_id' => $team->id, 'name' => 'Producto', 'unit_id' => Unit::firstOrCreate(['name' => 'kg'])->id]);

        $invoice = Invoice::create([
            'payment_term' => 30, 'payment_type' => 1, 'team_id' => $team->id, 'supplier_id' => $supplier->id,
            'company_reason_id' => $reason->id, 'type_document_id' => $type->id, 'number_document' => 'F-' . $team->id,
            'date' => now()->subDays(60)->toDateString(), 'due_date' => now()->subDays(30)->toDateString(), 'season_id' => $season->id,
        ]);
        InvoiceProduct::create(['invoice_id' => $invoice->id, 'product_id' => $product->id, 'unit_price' => $unitPrice, 'amount' => 1]);

        return compact('user', 'season', 'invoice');
    }

    private function rpc(string $method, array $params = [], int $id = 1): array
    {
        return ['jsonrpc' => '2.0', 'id' => $id, 'method' => $method, 'params' => $params];
    }

    private function callTool(string $name, array $arguments = []): array
    {
        $response = $this->postJson('/mcp', $this->rpc('tools/call', ['name' => $name, 'arguments' => $arguments]))->assertOk();

        return [$response->json('result.isError'), json_decode($response->json('result.content.0.text'), true)];
    }

    public function test_requires_a_token_and_points_to_the_resource_metadata(): void
    {
        $response = $this->postJson('/mcp', $this->rpc('ping'))->assertUnauthorized();

        $this->assertStringContainsString('/.well-known/oauth-protected-resource/mcp', $response->headers->get('WWW-Authenticate'));
    }

    public function test_rejects_tokens_without_the_read_scope(): void
    {
        ['user' => $user] = $this->tenant('Alfa');
        Passport::actingAs($user, ['otro:scope'], 'api');

        $this->postJson('/mcp', $this->rpc('ping'))->assertForbidden();
    }

    public function test_initialize_and_tools_list(): void
    {
        ['user' => $user] = $this->tenant('Alfa');
        Passport::actingAs($user, ['mcp:read'], 'api');

        $this->postJson('/mcp', $this->rpc('initialize', ['protocolVersion' => '2025-03-26']))
            ->assertOk()
            ->assertJsonPath('result.protocolVersion', '2025-03-26')
            ->assertJsonPath('result.capabilities.tools.listChanged', false);

        $tools = $this->postJson('/mcp', $this->rpc('tools/list'))->assertOk()->json('result.tools');
        $this->assertContains('get_payment_summary', array_column($tools, 'name'));
        $this->assertTrue(collect($tools)->every(fn ($t) => $t['annotations']['readOnlyHint'] === true));

        $this->postJson('/mcp', ['jsonrpc' => '2.0', 'method' => 'notifications/initialized'])->assertStatus(202);
        $this->postJson('/mcp', $this->rpc('nope'))->assertJsonPath('error.code', -32601);
    }

    public function test_only_returns_data_from_the_users_team(): void
    {
        ['user' => $alfa, 'season' => $alfaSeason, 'invoice' => $alfaInvoice] = $this->tenant('Alfa', 1000);
        ['season' => $betaSeason, 'invoice' => $betaInvoice] = $this->tenant('Beta', 5000);

        Passport::actingAs($alfa, ['mcp:read'], 'api');

        [$isError, $summary] = $this->callTool('get_payment_summary');
        $this->assertFalse($isError);
        $this->assertSame($alfaSeason->id, $summary['season']['id']);
        // 1000 neto + 19% IVA, vencida y sin pagos
        $this->assertSame(1190, (int) $summary['outstanding_balance']);
        $this->assertSame(1, $summary['summary']['overdue']['count']);

        [, $invoices] = $this->callTool('list_invoices', ['status' => 'unpaid']);
        $this->assertSame([$alfaInvoice->id], array_column($invoices['invoices'], 'id'));

        // Temporada y factura de otra empresa: no existen para este usuario.
        [$isError, $error] = $this->callTool('get_payment_summary', ['season_id' => $betaSeason->id]);
        $this->assertTrue($isError);
        $this->assertStringContainsString('temporada', $error['error']);

        [$isError] = $this->callTool('get_invoice', ['invoice_id' => $betaInvoice->id]);
        $this->assertTrue($isError);
    }

    public function test_payments_reduce_the_balance(): void
    {
        ['user' => $user, 'season' => $season, 'invoice' => $invoice] = $this->tenant('Alfa', 1000);
        InvoicePayment::create([
            'invoice_id' => $invoice->id, 'team_id' => $user->team_id, 'season_id' => $season->id, 'user_id' => $user->id,
            'payment_date' => now()->toDateString(), 'amount' => 190, 'payment_method' => 1,
        ]);

        Passport::actingAs($user, ['mcp:read'], 'api');

        [, $detail] = $this->callTool('get_invoice', ['invoice_id' => $invoice->id]);
        $this->assertSame(1000, (int) $detail['invoice']['balance']);
        $this->assertSame('partial', $detail['invoice']['status']);

        [, $payments] = $this->callTool('list_payments');
        $this->assertSame(190, (int) $payments['total_amount']);

        [, $debt] = $this->callTool('get_debt_by_supplier');
        $this->assertSame(1000, (int) $debt['total_balance']);
    }

    public function test_invalid_arguments_are_reported_as_tool_errors(): void
    {
        ['user' => $user] = $this->tenant('Alfa');
        Passport::actingAs($user, ['mcp:read'], 'api');

        [$isError, $result] = $this->callTool('list_invoices', ['status' => 'cualquiera', 'date_from' => '31/12/2026']);

        $this->assertTrue($isError);
        $this->assertArrayHasKey('error', $result);
        $this->postJson('/mcp', $this->rpc('tools/call', ['name' => 'drop_database']))->assertJsonPath('error.code', -32602);
    }

    public function test_hides_tools_when_the_module_is_disabled_for_the_team(): void
    {
        ['user' => $user] = $this->tenant('Alfa');
        TeamDisabledModule::create(['team_id' => $user->team_id, 'module_key' => 'invoice-payments.index']);
        Passport::actingAs($user, ['mcp:read'], 'api');

        $this->postJson('/mcp', $this->rpc('tools/list'))->assertJsonPath('result.tools', []);

        [$isError] = $this->callTool('get_payment_summary');
        $this->assertTrue($isError);
    }

    public function test_suspended_users_lose_access(): void
    {
        ['user' => $user] = $this->tenant('Alfa');
        $user->update(['status' => 0]);
        Passport::actingAs($user, ['mcp:read'], 'api');

        $this->postJson('/mcp', $this->rpc('ping'))->assertUnauthorized();
    }

    public function test_discovery_metadata_advertises_pkce_and_dynamic_registration(): void
    {
        $this->getJson('/.well-known/oauth-authorization-server')
            ->assertOk()
            ->assertJsonPath('code_challenge_methods_supported', ['S256'])
            ->assertJsonPath('token_endpoint_auth_methods_supported', ['none'])
            ->assertJsonStructure(['issuer', 'authorization_endpoint', 'token_endpoint', 'registration_endpoint']);

        $this->getJson('/.well-known/oauth-protected-resource/mcp')
            ->assertOk()
            ->assertJsonPath('scopes_supported', ['mcp:read']);

        $this->getJson('/mcp')->assertStatus(405)->assertHeader('Allow', 'POST');
    }

    public function test_dynamic_registration_only_accepts_allowed_redirects(): void
    {
        foreach (['https://evil.example.com/cb', 'http://claude.ai/cb', 'https://claude.ai.evil.com/cb', 'javascript:alert(1)'] as $uri) {
            $this->postJson('/oauth/register', ['client_name' => 'X', 'redirect_uris' => [$uri]])
                ->assertStatus(400)
                ->assertJsonPath('error', 'invalid_redirect_uri');
        }

        $this->postJson('/oauth/register', ['redirect_uris' => ['https://claude.ai/api/mcp/auth_callback'], 'token_endpoint_auth_method' => 'client_secret_post'])
            ->assertStatus(400)
            ->assertJsonPath('error', 'invalid_client_metadata');

        $this->postJson('/oauth/register', ['client_name' => '<b>Claude</b>', 'redirect_uris' => ['https://claude.ai/api/mcp/auth_callback', 'http://127.0.0.1:33418/callback']])
            ->assertCreated()
            ->assertJsonPath('client_name', 'Claude')
            ->assertJsonPath('token_endpoint_auth_method', 'none');
    }

    public function test_full_oauth_flow_with_pkce_then_revocation(): void
    {
        ['user' => $user] = $this->tenant('Alfa', 2000);

        $redirect = 'https://claude.ai/api/mcp/auth_callback';
        $clientId = $this->postJson('/oauth/register', ['client_name' => 'Claude', 'redirect_uris' => [$redirect]])->json('client_id');

        $verifier = bin2hex(random_bytes(32));
        $challenge = rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');
        $query = http_build_query([
            'client_id' => $clientId, 'redirect_uri' => $redirect, 'response_type' => 'code', 'scope' => 'mcp:read',
            'state' => 'xyz', 'code_challenge' => $challenge, 'code_challenge_method' => 'S256',
        ]);

        // Sin PKCE el servidor no entrega códigos a clientes públicos.
        $this->actingAs($user)
            ->get('/oauth/authorize?' . http_build_query(['client_id' => $clientId, 'redirect_uri' => $redirect, 'response_type' => 'code', 'state' => 'x']))
            ->assertStatus(400);

        $page = $this->actingAs($user)->get('/oauth/authorize?' . $query, ['X-Inertia' => 'true', 'X-Inertia-Version' => Inertia::getVersion()])
            ->assertOk()
            ->assertJsonPath('component', 'Mcp/Authorize')
            ->assertJsonPath('props.client.name', 'Claude')
            ->assertJsonPath('props.client.redirect_hosts', ['claude.ai']);

        $approval = $this->actingAs($user)->post('/oauth/authorize', [
            'state' => 'xyz', 'client_id' => $clientId, 'auth_token' => $page->json('props.authToken'),
        ]);
        $approval->assertRedirect();
        parse_str(parse_url($approval->headers->get('Location'), PHP_URL_QUERY), $callback);
        $this->assertSame('xyz', $callback['state']);

        $token = $this->postJson('/oauth/token', [
            'grant_type' => 'authorization_code', 'client_id' => $clientId, 'redirect_uri' => $redirect,
            'code' => $callback['code'], 'code_verifier' => $verifier,
        ])->assertOk()->assertJsonPath('token_type', 'Bearer');

        $this->app['auth']->forgetGuards();

        $this->withToken($token->json('access_token'))->postJson('/mcp', $this->rpc('tools/list'))->assertOk();

        $refreshed = $this->postJson('/oauth/token', [
            'grant_type' => 'refresh_token', 'client_id' => $clientId, 'refresh_token' => $token->json('refresh_token'),
        ])->assertOk();

        $this->actingAs($user)->getJson('/mcp/connections')->assertOk()->assertJsonPath('0.name', 'Claude');
        $this->actingAs($user)->deleteJson("/mcp/connections/{$clientId}")->assertOk();
        $this->actingAs($user)->getJson('/mcp/connections')->assertOk()->assertJson([]);

        $this->app['auth']->forgetGuards();
        $this->withToken($refreshed->json('access_token'))->postJson('/mcp', $this->rpc('ping'))->assertUnauthorized();
        $this->postJson('/oauth/token', [
            'grant_type' => 'refresh_token', 'client_id' => $clientId, 'refresh_token' => $refreshed->json('refresh_token'),
        ])->assertStatus(401);
    }
}
