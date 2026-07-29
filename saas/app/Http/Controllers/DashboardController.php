<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function resumen(): JsonResponse
    {
        $totalProductos = Producto::query()->count();

        $stockTotal = (int) Producto::query()
            ->sum('stock');

        $valorInventario = (float) Producto::query()
            ->selectRaw(
                'COALESCE(SUM(precio * stock), 0) AS total'
            )
            ->value('total');

        $productosStockBajo = Producto::query()
            ->whereBetween('stock', [1, 5])
            ->count();

        $productosSinStock = Producto::query()
            ->where('stock', 0)
            ->count();

        $productosRecientes = Producto::query()
            ->latest('created_at')
            ->limit(5)
            ->get([
                'id',
                'nombre',
                'precio',
                'stock',
                'created_at',
            ]);

        return response()->json([
            'total_productos' => $totalProductos,
            'stock_total' => $stockTotal,
            'valor_inventario' => round($valorInventario, 2),
            'productos_stock_bajo' => $productosStockBajo,
            'productos_sin_stock' => $productosSinStock,
            'productos_recientes' => $productosRecientes,
        ]);
    }
}