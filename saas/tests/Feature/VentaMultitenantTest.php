<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use App\Models\Producto;
use App\Models\Venta;
use App\Models\DetalleVenta;
use App\Services\SalesforceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class VentaMultitenantTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenantSalqui;
    private Tenant $tenantGranPalacio;
    private User $userSalqui;
    private User $userGranPalacio;
    private Producto $productoSalqui;
    private Producto $productoGranPalacio;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenantSalqui = Tenant::create([
            'nombre_comercial' => 'SALQUI S.R.L.',
            'estado' => 'activo',
        ]);

        $this->tenantGranPalacio = Tenant::create([
            'nombre_comercial' => 'Gran Palacio de la Industria',
            'estado' => 'activo',
        ]);

        $this->userSalqui = User::create([
            'tenant_id' => $this->tenantSalqui->id,
            'name' => 'User Salqui',
            'email' => 'salqui@example.com',
            'password' => Hash::make('password'),
        ]);

        $this->userGranPalacio = User::create([
            'tenant_id' => $this->tenantGranPalacio->id,
            'name' => 'User Gran Palacio',
            'email' => 'granpalacio@example.com',
            'password' => Hash::make('password'),
        ]);

        $this->productoSalqui = new Producto();
        $this->productoSalqui->nombre = 'Producto SALQUI';
        $this->productoSalqui->precio = 50.00;
        $this->productoSalqui->stock = 20;
        $this->productoSalqui->tenant_id = $this->tenantSalqui->id;
        $this->productoSalqui->save();

        $this->productoGranPalacio = new Producto();
        $this->productoGranPalacio->nombre = 'Producto Gran Palacio';
        $this->productoGranPalacio->precio = 100.00;
        $this->productoGranPalacio->stock = 10;
        $this->productoGranPalacio->tenant_id = $this->tenantGranPalacio->id;
        $this->productoGranPalacio->save();
    }

    public function test_crear_venta_requiere_autenticacion(): void
    {
        $response = $this->postJson('/api/ventas', [
            'items' => [
                ['producto_id' => $this->productoSalqui->id, 'cantidad' => 1],
            ],
        ]);

        $response->assertStatus(401);
    }

    public function test_crear_venta_asigna_tenant_y_calcula_total_en_backend(): void
    {
        Sanctum::actingAs($this->userSalqui);

        $response = $this->postJson('/api/ventas', [
            'items' => [
                ['producto_id' => $this->productoSalqui->id, 'cantidad' => 2],
            ],
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('estado', 'pendiente')
            ->assertJsonPath('total', '100.00');

        $this->assertDatabaseHas('ventas', [
            'id' => $response->json('id'),
            'tenant_id' => $this->tenantSalqui->id,
            'usuario_id' => $this->userSalqui->id,
            'estado' => 'pendiente',
        ]);

        // Crear la venta NO debe descontar el stock todavía
        $this->assertEquals(20, $this->productoSalqui->fresh()->stock);
    }

    public function test_no_permite_producto_de_otro_tenant(): void
    {
        Sanctum::actingAs($this->userSalqui);

        $response = $this->postJson('/api/ventas', [
            'items' => [
                ['producto_id' => $this->productoGranPalacio->id, 'cantidad' => 1],
            ],
        ]);

        $response->assertStatus(422);
    }

    public function test_no_permite_stock_insuficiente(): void
    {
        Sanctum::actingAs($this->userSalqui);

        $response = $this->postJson('/api/ventas', [
            'items' => [
                ['producto_id' => $this->productoSalqui->id, 'cantidad' => 999],
            ],
        ]);

        $response->assertStatus(422);
    }

    public function test_generar_qr_y_confirmar_pago_descuenta_stock(): void
    {
        Sanctum::actingAs($this->userSalqui);

        $ventaRes = $this->postJson('/api/ventas', [
            'items' => [
                ['producto_id' => $this->productoSalqui->id, 'cantidad' => 5],
            ],
        ]);

        $ventaId = $ventaRes->json('id');

        // Generar QR
        $qrRes = $this->postJson("/api/ventas/{$ventaId}/qr");
        $qrRes->assertStatus(200)
            ->assertJsonStructure(['referencia', 'monto', 'qr_texto', 'estado']);

        $referencia = $qrRes->json('referencia');

        // Confirmar Pago
        $confirmRes = $this->postJson('/api/pagos/confirmar', [
            'referencia' => $referencia,
        ]);

        $confirmRes->assertStatus(200)
            ->assertJsonPath('venta.estado', 'pagada');

        // Stock descontado de 20 a 15
        $this->assertEquals(15, $this->productoSalqui->fresh()->stock);

        // Confirmación duplicada debe ser idempotente y no volver a descontar stock
        $confirmDuplicada = $this->postJson('/api/pagos/confirmar', [
            'referencia' => $referencia,
        ]);

        $confirmDuplicada->assertStatus(200)
            ->assertJsonPath('idempotente', true);

        // El stock sigue siendo 15
        $this->assertEquals(15, $this->productoSalqui->fresh()->stock);
    }

    public function test_salesforce_mock_cuando_sf_enabled_true(): void
    {
        config(['services.salesforce.enabled' => true]);
        config(['services.salesforce.login_url' => 'https://login.salesforce.com']);
        config(['services.salesforce.client_id' => 'mock_client_id']);
        config(['services.salesforce.client_secret' => 'mock_client_secret']);

        Http::fake([
            'https://login.salesforce.com/services/oauth2/token' => Http::response([
                'access_token' => 'mock_access_token',
                'instance_url' => 'https://mock.salesforce.com',
            ], 200),
            'https://mock.salesforce.com/services/data/v58.0/sobjects/Opportunity/' => Http::response([
                'id' => '006000000000001AAA',
                'success' => true,
            ], 201),
        ]);

        Sanctum::actingAs($this->userSalqui);

        $ventaRes = $this->postJson('/api/ventas', [
            'items' => [
                ['producto_id' => $this->productoSalqui->id, 'cantidad' => 2],
            ],
        ]);
        $ventaId = $ventaRes->json('id');
        $qrRes = $this->postJson("/api/ventas/{$ventaId}/qr");

        $confirmRes = $this->postJson('/api/pagos/confirmar', [
            'referencia' => $qrRes->json('referencia'),
        ]);

        $confirmRes->assertStatus(200);

        $this->assertDatabaseHas('ventas', [
            'id' => $ventaId,
            'salesforce_id' => '006000000000001AAA',
            'salesforce_sync_status' => 'synced',
        ]);
    }
}
