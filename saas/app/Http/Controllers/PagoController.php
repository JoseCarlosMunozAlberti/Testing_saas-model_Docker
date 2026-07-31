<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use App\Models\Venta;
use App\Services\SalesforceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PagoController extends Controller
{
    /**
     * Genera la referencia y el texto QR para una venta pendiente.
     */
    public function generarQr(int $ventaId, Request $request): JsonResponse
    {
        $venta = Venta::findOrFail($ventaId);

        if ($venta->estado !== 'pendiente') {
            return response()->json([
                'message' => "La venta #{$venta->id} no está en estado pendiente (estado actual: {$venta->estado}).",
            ], 422);
        }

        // Si no tiene referencia asignada, generar una única
        if (!$venta->referencia_pago) {
            $referencia = 'QR-' . strtoupper(Str::random(8)) . '-' . $venta->id;
            $venta->update([
                'referencia_pago' => $referencia,
            ]);
        }

        $referencia = $venta->referencia_pago;
        $montoFormatted = number_format((float) $venta->total, 2, '.', '');
        $qrTexto = "PAGO-QR|REF:{$referencia}|MONTO:{$montoFormatted}|BENEFICIARIO:PS-TENANT";

        return response()->json([
            'venta_id' => $venta->id,
            'referencia' => $referencia,
            'monto' => (float) $venta->total,
            'qr_texto' => $qrTexto,
            'estado' => $venta->estado,
        ]);
    }

    /**
     * Confirma el pago mediante la referencia única.
     * Descuenta stock en transacción DB. Es idempotente ante confirmaciones repetidas.
     * Tras confirmar en DB, dispara la sincronización no bloqueante a Salesforce.
     */
    public function confirmar(Request $request, SalesforceService $sfService): JsonResponse
    {
        $request->validate([
            'referencia' => 'required|string',
        ]);

        $referencia = $request->input('referencia');

        // Buscar venta por referencia (dentro de la transacción usaremos lockForUpdate)
        $ventaExistente = Venta::where('referencia_pago', $referencia)->first();

        if (!$ventaExistente) {
            return response()->json([
                'message' => 'No se encontró ninguna venta asociada a la referencia de pago proporcionada.',
            ], 404);
        }

        // Si ya está pagada (idempotencia)
        if ($ventaExistente->estado === 'pagada') {
            return response()->json([
                'message' => 'El pago para esta venta ya fue confirmado anteriormente.',
                'venta' => $ventaExistente->fresh(['detalles.producto:id,nombre']),
                'idempotente' => true,
            ], 200);
        }

        if ($ventaExistente->estado !== 'pendiente') {
            return response()->json([
                'message' => "La venta #{$ventaExistente->id} se encuentra en estado '{$ventaExistente->estado}' y no puede ser pagada.",
            ], 422);
        }

        // Transacción con bloqueo para actualización y descuento de stock
        $ventaPagada = DB::transaction(function () use ($referencia) {
            /** @var Venta $venta */
            $venta = Venta::where('referencia_pago', $referencia)
                ->lockForUpdate()
                ->firstOrFail();

            // Verificación idempotente adicional dentro del lock
            if ($venta->estado === 'pagada') {
                return $venta;
            }

            // Cargar los detalles con sus productos
            $venta->load('detalles');

            // Verificar disponibilidad de stock para todos los productos
            foreach ($venta->detalles as $detalle) {
                $producto = Producto::where('id', $detalle->producto_id)
                    ->lockForUpdate()
                    ->first();

                if (!$producto || $producto->stock < $detalle->cantidad) {
                    $nombreProd = $producto ? $producto->nombre : "ID {$detalle->producto_id}";
                    $disponible = $producto ? $producto->stock : 0;
                    throw new \Exception("Stock insuficiente para '{$nombreProd}'. Disponible: {$disponible}, requerido: {$detalle->cantidad}.");
                }
            }

            // Descontar stock
            foreach ($venta->detalles as $detalle) {
                $producto = Producto::where('id', $detalle->producto_id)->first();
                $producto->decrement('stock', $detalle->cantidad);
            }

            // Marcar como pagada
            $venta->update([
                'estado' => 'pagada',
                'pagada_at' => now(),
            ]);

            return $venta;
        });

        // NUNCA ejecutar la sincronización dentro de la transacción de DB.
        // Se ejecuta POSTERIORMENTE al commit y si falla NO revierte el pago.
        try {
            $sfService->sincronizarVenta($ventaPagada);
        } catch (\Throwable $e) {
            // El log de error ya se captura dentro del servicio
        }

        return response()->json([
            'message' => 'Pago confirmado exitosamente y stock descontado.',
            'venta' => $ventaPagada->fresh(['detalles.producto:id,nombre']),
            'idempotente' => false,
        ]);
    }
}
