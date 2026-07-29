<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use App\Models\Producto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProductoMultitenantTest extends TestCase
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
        $this->productoSalqui->nombre = 'Producto exclusivo SALQUI';
        $this->productoSalqui->precio = 100.00;
        $this->productoSalqui->stock = 10;
        $this->productoSalqui->tenant_id = $this->tenantSalqui->id;
        $this->productoSalqui->save();

        $this->productoGranPalacio = new Producto();
        $this->productoGranPalacio->nombre = 'Producto exclusivo Gran Palacio';
        $this->productoGranPalacio->precio = 200.00;
        $this->productoGranPalacio->stock = 20;
        $this->productoGranPalacio->tenant_id = $this->tenantGranPalacio->id;
        $this->productoGranPalacio->save();
    }

    public function test_usuario_no_autenticado_no_puede_acceder_a_productos(): void
    {
        $response = $this->getJson('/api/productos');

        $response->assertStatus(401);
    }

  public function test_cada_tenant_solo_lista_sus_productos(): void
{
    Sanctum::actingAs($this->userSalqui);

    $response = $this->getJson('/api/productos');

    $response->assertStatus(200)
        ->assertJsonCount(1, 'data')
        ->assertJsonFragment([
            'nombre' => 'Producto exclusivo SALQUI',
        ])
        ->assertJsonMissing([
            'nombre' => 'Producto exclusivo Gran Palacio',
        ]);

    Sanctum::actingAs($this->userGranPalacio);

    $response = $this->getJson('/api/productos');

    $response->assertStatus(200)
        ->assertJsonCount(1, 'data')
        ->assertJsonFragment([
            'nombre' => 'Producto exclusivo Gran Palacio',
        ])
        ->assertJsonMissing([
            'nombre' => 'Producto exclusivo SALQUI',
        ]);
}

    public function test_tenant_id_se_asigna_automaticamente_al_crear_producto(): void
    {
        Sanctum::actingAs($this->userSalqui);

        $response = $this->postJson('/api/productos', [
            'nombre' => 'Nuevo producto SALQUI',
            'precio' => 350.50,
            'stock' => 5,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('tenant_id', $this->tenantSalqui->id);

        $this->assertDatabaseHas('productos', [
            'nombre' => 'Nuevo producto SALQUI',
            'tenant_id' => $this->tenantSalqui->id,
        ]);
    }

    public function test_tenant_no_puede_ver_producto_de_otro_tenant(): void
    {
        Sanctum::actingAs($this->userSalqui);

        $response = $this->getJson("/api/productos/{$this->productoGranPalacio->id}");

        $response->assertStatus(404);

        Sanctum::actingAs($this->userGranPalacio);

        $response = $this->getJson("/api/productos/{$this->productoSalqui->id}");

        $response->assertStatus(404);
    }

    public function test_tenant_no_puede_actualizar_producto_de_otro_tenant(): void
    {
        Sanctum::actingAs($this->userSalqui);

        $response = $this->patchJson("/api/productos/{$this->productoGranPalacio->id}", [
            'nombre' => 'Intento de modificacion',
        ]);

        $response->assertStatus(404);

        $this->assertDatabaseHas('productos', [
            'id' => $this->productoGranPalacio->id,
            'nombre' => 'Producto exclusivo Gran Palacio',
        ]);
    }

    public function test_tenant_no_puede_eliminar_producto_de_otro_tenant(): void
    {
        Sanctum::actingAs($this->userSalqui);

        $response = $this->deleteJson("/api/productos/{$this->productoGranPalacio->id}");

        $response->assertStatus(404);

        $this->assertDatabaseHas('productos', [
            'id' => $this->productoGranPalacio->id,
        ]);
    }

    public function test_tenant_puede_actualizar_y_eliminar_su_propio_producto(): void
    {
        Sanctum::actingAs($this->userSalqui);

        $response = $this->patchJson("/api/productos/{$this->productoSalqui->id}", [
            'nombre' => 'Producto SALQUI Modificado',
            'precio' => 150.00,
            'stock' => 15,
        ]);

        $response->assertStatus(200)
            ->assertJsonFragment([
                'nombre' => 'Producto SALQUI Modificado',
                'precio' => '150.00',
                'stock' => 15,
            ]);

        $response = $this->deleteJson("/api/productos/{$this->productoSalqui->id}");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('productos', [
            'id' => $this->productoSalqui->id,
        ]);
    }
    public function test_listado_de_productos_esta_paginado_por_tenant(): void
{
    Sanctum::actingAs($this->userSalqui);

    for ($i = 1; $i <= 6; $i++) {
        Producto::create([
            'nombre' => "Producto paginado {$i}",
            'precio' => 20 + $i,
            'stock' => 10 + $i,
        ]);
    }

    $this->getJson('/api/productos?page=1&per_page=5')
        ->assertStatus(200)
        ->assertJsonCount(5, 'data')
        ->assertJsonPath('current_page', 1)
        ->assertJsonPath('per_page', 5)
        ->assertJsonPath('total', 7)
        ->assertJsonPath('last_page', 2);
}
public function test_busqueda_de_productos_respeta_el_tenant(): void
{
    Sanctum::actingAs($this->userSalqui);

    Producto::create([
        'nombre' => 'Cemento especial SALQUI',
        'precio' => 80,
        'stock' => 25,
    ]);

    Sanctum::actingAs($this->userGranPalacio);

    Producto::create([
        'nombre' => 'Cemento secreto Palacio',
        'precio' => 90,
        'stock' => 30,
    ]);

    Sanctum::actingAs($this->userSalqui);

    $this->getJson('/api/productos?search=cemento&per_page=10')
        ->assertStatus(200)
        ->assertJsonCount(1, 'data')
        ->assertJsonFragment([
            'nombre' => 'Cemento especial SALQUI',
        ])
        ->assertJsonMissing([
            'nombre' => 'Cemento secreto Palacio',
        ]);
}
public function test_productos_pueden_ordenarse_por_stock(): void
{
    Sanctum::actingAs($this->userSalqui);

    Producto::create([
        'nombre' => 'Producto stock 5',
        'precio' => 50,
        'stock' => 5,
    ]);

    Producto::create([
        'nombre' => 'Producto stock 25',
        'precio' => 60,
        'stock' => 25,
    ]);

    Producto::create([
        'nombre' => 'Producto stock 15',
        'precio' => 70,
        'stock' => 15,
    ]);

    $this->getJson(
        '/api/productos?sort_by=stock&sort_dir=desc&per_page=10'
    )
        ->assertStatus(200)
        ->assertJsonPath('data.0.nombre', 'Producto stock 25')
        ->assertJsonPath('data.0.stock', 25)
        ->assertJsonPath('data.1.stock', 15)
        ->assertJsonPath('data.2.stock', 10)
        ->assertJsonPath('data.3.stock', 5);
}
}
