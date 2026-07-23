<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductoController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(Producto::all());
    }

    public function store(Request $request): JsonResponse
    {
        $datosValidados = $request->validate([
            'nombre' => 'required|string|min:3|max:150',
            'precio' => 'required|numeric|min:0.01',
            'stock' => 'sometimes|integer|min:0',
        ]);

        $producto = Producto::create($datosValidados);

        return response()->json($producto, 201);
    }

    public function show(Producto $producto): JsonResponse
    {
        return response()->json($producto);
    }

    public function update(Request $request, Producto $producto): JsonResponse
    {
        $datosValidados = $request->validate([
            'nombre' => 'sometimes|string|min:3|max:150',
            'precio' => 'sometimes|numeric|min:0.01',
            'stock' => 'sometimes|integer|min:0',
        ]);

        $producto->update($datosValidados);

        return response()->json($producto);
    }

    public function destroy(Producto $producto): JsonResponse
    {
        $producto->delete();

        return response()->json(null, 204);
    }
}
