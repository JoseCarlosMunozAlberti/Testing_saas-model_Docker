<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\User;
use App\Models\Cliente;
use App\Models\Producto;
use App\Models\Venta;
use App\Models\DetalleVenta;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DatosDemostracionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Obtener o crear Tenants
        $tenantSalqui = Tenant::updateOrCreate(
            ['nombre_comercial' => 'SALQUI S.R.L.'],
            ['estado' => 'activo']
        );

        $tenantPalacio = Tenant::updateOrCreate(
            ['nombre_comercial' => 'Gran Palacio de la Industria'],
            ['estado' => 'activo']
        );

        // 2. Obtener o crear Usuarios Admin
        $userSalqui = User::updateOrCreate(
            ['email' => 'admin@salqui.com'],
            [
                'tenant_id' => $tenantSalqui->id,
                'name' => 'Admin Salqui',
                'password' => Hash::make('Clave12345'),
                'rol' => 'admin',
            ]
        );

        $userPalacio = User::updateOrCreate(
            ['email' => 'admin@palacio.com'],
            [
                'tenant_id' => $tenantPalacio->id,
                'name' => 'Admin Palacio',
                'password' => Hash::make('Clave12345'),
                'rol' => 'admin',
            ]
        );

        // 3. Crear Clientes para cada Tenant (al menos 6)
        $clientesSalquiData = [
            ['ci' => '1000001', 'nombre' => 'Juan Carlos Ramirez', 'telefono' => '71111111'],
            ['ci' => '1000002', 'nombre' => 'Sofia Escalera', 'telefono' => '72222222'],
            ['ci' => '1000003', 'nombre' => 'Roberto Rojas', 'telefono' => '73333333'],
            ['ci' => '1000004', 'nombre' => 'Elena Vasquez', 'telefono' => '74444444'],
            ['ci' => '1000005', 'nombre' => 'Mauricio Lora', 'telefono' => '75555555'],
            ['ci' => '1000006', 'nombre' => 'Gabriela Mendez', 'telefono' => '76666666'],
        ];

        $clientesPalacioData = [
            ['ci' => '2000001', 'nombre' => 'Alberto Durán', 'telefono' => '61111111'],
            ['ci' => '2000002', 'nombre' => 'Beatriz Carrasco', 'telefono' => '62222222'],
            ['ci' => '2000003', 'nombre' => 'Carlos Fuentes', 'telefono' => '63333333'],
            ['ci' => '2000004', 'nombre' => 'Diana Lopez', 'telefono' => '64444444'],
            ['ci' => '2000005', 'nombre' => 'Eduardo Montenegro', 'telefono' => '65555555'],
            ['ci' => '2000006', 'nombre' => 'Flavia Perez', 'telefono' => '66666666'],
        ];

        $clientesSalqui = [];
        foreach ($clientesSalquiData as $c) {
            $clientesSalqui[] = Cliente::updateOrCreate(
                ['tenant_id' => $tenantSalqui->id, 'ci' => $c['ci']],
                ['nombre' => $c['nombre'], 'telefono' => $c['telefono']]
            );
        }

        $clientesPalacio = [];
        foreach ($clientesPalacioData as $c) {
            $clientesPalacio[] = Cliente::updateOrCreate(
                ['tenant_id' => $tenantPalacio->id, 'ci' => $c['ci']],
                ['nombre' => $c['nombre'], 'telefono' => $c['telefono']]
            );
        }

        // 4. Crear Productos para cada Tenant (al menos 10)
        $productosSalquiData = [
            ['nombre' => 'Cemento IP-40 50kg', 'precio' => 55.00],
            ['nombre' => 'Pintura Látex Blanca 20L', 'precio' => 280.00],
            ['nombre' => 'Martillo de Uña 16oz', 'precio' => 45.00],
            ['nombre' => 'Caja Tornillos 2 pulgadas x100', 'precio' => 35.00],
            ['nombre' => 'Cable Cobre AWG 12 100m', 'precio' => 180.00],
            ['nombre' => 'Foco LED 12W Pack x6', 'precio' => 65.00],
            ['nombre' => 'Tubo PVC 2 pulgadas 6m', 'precio' => 28.00],
            ['nombre' => 'Carretilla Metálica 80L', 'precio' => 220.00],
            ['nombre' => 'Pala de Punta Templada', 'precio' => 60.00],
            ['nombre' => 'Disco Corte Metal 4.5 pulgadas', 'precio' => 12.00],
        ];

        $productosPalacioData = [
            ['nombre' => 'Cemento F-30 Especial', 'precio' => 52.00],
            ['nombre' => 'Pintura Anticorrosiva Roja 4L', 'precio' => 110.00],
            ['nombre' => 'Juego Llaves Combinadas x12', 'precio' => 150.00],
            ['nombre' => 'Caja Clavos de Acero 3 pulgadas', 'precio' => 40.00],
            ['nombre' => 'Cinta Aislante 20m Pack x10', 'precio' => 50.00],
            ['nombre' => 'Reflector LED 50W Exterior', 'precio' => 130.00],
            ['nombre' => 'Tubo Galvanizado 1 pulgada 6m', 'precio' => 140.00],
            ['nombre' => 'Amoladora Angular 800W', 'precio' => 380.00],
            ['nombre' => 'Taladro Percutor 600W', 'precio' => 450.00],
            ['nombre' => 'Lentes de Seguridad Claros', 'precio' => 15.00],
        ];

        $productosSalqui = [];
        foreach ($productosSalquiData as $p) {
            $productosSalqui[] = Producto::updateOrCreate(
                ['tenant_id' => $tenantSalqui->id, 'nombre' => $p['nombre']],
                ['precio' => $p['precio'], 'stock' => 500] // stock inicial suficiente para no quedar negativo
            );
        }

        $productosPalacio = [];
        foreach ($productosPalacioData as $p) {
            $productosPalacio[] = Producto::updateOrCreate(
                ['tenant_id' => $tenantPalacio->id, 'nombre' => $p['nombre']],
                ['precio' => $p['precio'], 'stock' => 500] // stock inicial suficiente
            );
        }

        // 5. Crear Ventas e Historial (Al menos 40 en total)
        // Estrategia idempotente para las ventas: si ya hay ventas sembradas, no duplicamos.
        if (Venta::count() >= 40) {
            $this->command->info('Las ventas de demostración ya existen. Se omitió la creación de nuevas ventas.');
            return;
        }

        // Generaremos 22 ventas para SALQUI y 22 para Palacio (Total 44)
        $this->seedVentasForTenant($tenantSalqui, $userSalqui, $clientesSalqui, $productosSalqui);
        $this->seedVentasForTenant($tenantPalacio, $userPalacio, $clientesPalacio, $productosPalacio);

        $this->command->info('Se crearon las ventas y detalles de demostración correctamente.');
    }

    /**
     * Seed ventas for a specific tenant.
     */
    private function seedVentasForTenant(Tenant $tenant, User $user, array $clientes, array $productos): void
    {
        // Necesitamos cumplir:
        // - Al menos 4 ventas de hoy con total > 200 y estado != anulada (por cada tenant, para sumar 8+ en total)
        // - Al menos 2 ventas de hoy con total > 200 y estado = anulada (por cada tenant, para sumar 3+ en total)
        // - Ventas en días anteriores (últimos 30 días)
        
        $today = Carbon::today();

        // 1. Ventas de HOY válidas (> 200, no anuladas)
        for ($i = 0; $i < 5; $i++) {
            $fecha = (clone $today)->addHours(8 + $i); // Distintas horas de hoy
            $this->crearVentaConDetalles(
                $tenant->id,
                $user->id,
                $clientes[array_rand($clientes)],
                $productos,
                'pagada',
                $fecha,
                true // forzar total > 200
            );
        }

        // 2. Ventas de HOY anuladas (> 200)
        for ($i = 0; $i < 2; $i++) {
            $fecha = (clone $today)->addHours(14 + $i);
            $this->crearVentaConDetalles(
                $tenant->id,
                $user->id,
                $clientes[array_rand($clientes)],
                $productos,
                'anulada',
                $fecha,
                true // forzar total > 200
            );
        }

        // 3. Ventas de días anteriores (últimos 30 días)
        for ($i = 1; $i <= 15; $i++) {
            $fecha = (clone $today)->subDays($i)->addHours(rand(9, 18))->addMinutes(rand(0, 59));
            $estado = (rand(1, 10) <= 2) ? 'anulada' : ((rand(1, 2) === 1) ? 'pagada' : 'pendiente');
            
            $this->crearVentaConDetalles(
                $tenant->id,
                $user->id,
                $clientes[array_rand($clientes)],
                $productos,
                $estado,
                $fecha,
                false // aleatorio
            );
        }
    }

    /**
     * Crea una venta y sus detalles bajo una transacción.
     */
    private function crearVentaConDetalles(
        int $tenantId,
        int $usuarioId,
        Cliente $cliente,
        array $productos,
        string $estado,
        Carbon $fecha,
        bool $forzarMayorA200
    ): void {
        DB::transaction(function () use ($tenantId, $usuarioId, $cliente, $productos, $estado, $fecha, $forzarMayorA200) {
            // Decidir la cantidad de productos distintos para esta venta (entre 1 y 5)
            $cantProductosDistintos = rand(1, 5);
            $productosSeleccionados = array_rand($productos, $cantProductosDistintos);
            if (!is_array($productosSeleccionados)) {
                $productosSeleccionados = [$productosSeleccionados];
            }

            // Si forzarMayorA200 es true, aseguramos que la primera línea de producto sea costosa o de gran cantidad
            $detalles = [];
            $totalCalculado = 0.0;

            foreach ($productosSeleccionados as $index => $key) {
                $producto = $productos[$key];
                
                // Cantidad aleatoria de 1 a 10
                $cantidad = rand(1, 10);

                if ($forzarMayorA200 && $index === 0) {
                    // Nos aseguramos que (cantidad * precio) sea mayor a 200
                    if ($producto->precio < 50.00) {
                        $cantidad = (int) ceil(210 / $producto->precio);
                    } else {
                        $cantidad = rand(5, 10);
                    }
                }

                $precioUnitario = $producto->precio;
                $subtotal = $cantidad * $precioUnitario;

                $detalles[] = [
                    'producto' => $producto,
                    'cantidad' => $cantidad,
                    'precio_unitario' => $precioUnitario,
                    'subtotal' => $subtotal
                ];

                $totalCalculado += $subtotal;
            }

            // Crear la Venta
            $venta = new Venta();
            $venta->tenant_id = $tenantId;
            $venta->cliente_id = $cliente->id;
            $venta->usuario_id = $usuarioId;
            $venta->total = $totalCalculado;
            $venta->estado = $estado;
            $venta->created_at = $fecha;
            $venta->updated_at = $fecha;
            $venta->save();

            // Crear DetalleVentas y restar Stock (si el estado no es anulado)
            foreach ($detalles as $d) {
                $prod = $d['producto'];

                $detalle = new DetalleVenta();
                $detalle->tenant_id = $tenantId;
                $detalle->venta_id = $venta->id;
                $detalle->producto_id = $prod->id;
                $detalle->cantidad = $d['cantidad'];
                $detalle->precio_unitario = $d['precio_unitario'];
                $detalle->save();

                // Restar del stock solo si la venta no se anula
                if ($estado !== 'anulada') {
                    $prod->decrement('stock', $d['cantidad']);
                }
            }
        });
    }
}
