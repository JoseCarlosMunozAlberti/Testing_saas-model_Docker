<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use App\Models\Venta;
use App\Models\DetalleVenta;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class VentaController extends Controller
{
    /**
     * Listar ventas del tenant autenticado.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->get('per_page', 10);
        $perPage = in_array($perPage, [5, 10, 25, 50]) ? $perPage : 10;

        $ventas = Venta::with(['cliente:id,nombre', 'detalles.producto:id,nombre'])
            ->latest('created_at')
            ->paginate($perPage);

        return response()->json($ventas);
    }

    /**
     * Ver una venta específica del tenant autenticado.
     */
    public function show(int $id): JsonResponse
    {
        $venta = Venta::with(['cliente:id,nombre,ci', 'detalles.producto:id,nombre,precio', 'usuario:id,name'])
            ->findOrFail($id);

        return response()->json($venta);
    }

    /**
     * Registrar una nueva venta en estado 'pendiente'.
     * Los precios y el total se calculan estrictamente en el backend.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'cliente_id' => 'nullable|integer',
            'items' => 'required|array|min:1',
            'items.*.producto_id' => 'required|integer',
            'items.*.cantidad' => 'required|integer|min:1',
        ]);

        $user = $request->user();
        $items = $request->input('items');

        // Extraer los IDs de los productos a comprar
        $productoIds = array_column($items, 'producto_id');

        // Consultar los productos asegurando que pertenecen al tenant autenticado (vía Global Scope)
        $productos = Producto::whereIn('id', $productoIds)->get()->keyBy('id');

        // Validar que todos los productos existen y pertenecen al tenant
        foreach ($items as $item) {
            $pId = $item['producto_id'];
            $cantidad = $item['cantidad'];

            if (!$productos->has($pId)) {
                throw ValidationException::withMessages([
                    'items' => ["El producto ID {$pId} no está disponible o no pertenece a su empresa."],
                ]);
            }

            $producto = $productos->get($pId);

            if ($producto->stock < $cantidad) {
                throw ValidationException::withMessages([
                    'items' => ["Stock insuficiente para '{$producto->nombre}'. Disponible: {$producto->stock}, solicitado: {$cantidad}."],
                ]);
            }
        }

        // Crear la venta y sus detalles en una transacción DB
        $venta = DB::transaction(function () use ($user, $request, $items, $productos) {
            $totalCalculado = 0;

            foreach ($items as $item) {
                $producto = $productos->get($item['producto_id']);
                $totalCalculado += ((float) $producto->precio) * $item['cantidad'];
            }

            // Crear Venta (tenant_id y usuario_id se asignan automáticamente por el backend)
            $nuevaVenta = Venta::create([
                'cliente_id' => $request->input('cliente_id'),
                'usuario_id' => $user->id,
                'total' => round($totalCalculado, 2),
                'estado' => 'pendiente',
            ]);

            // Crear DetalleVenta
            foreach ($items as $item) {
                $producto = $productos->get($item['producto_id']);
                DetalleVenta::create([
                    'venta_id' => $nuevaVenta->id,
                    'producto_id' => $producto->id,
                    'cantidad' => $item['cantidad'],
                    'precio_unitario' => $producto->precio,
                ]);
            }

            return $nuevaVenta;
        });

        return response()->json(
            $venta->load(['detalles.producto:id,nombre']),
            201
        );
    }
}
