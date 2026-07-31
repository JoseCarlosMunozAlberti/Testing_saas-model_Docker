<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\VentaController;
use App\Http\Controllers\PagoController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function (): void {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/usuario', [AuthController::class, 'usuarioActual']);
    Route::get('/dashboard/resumen', [DashboardController::class, 'resumen']);
    Route::post('/productos', [ProductoController::class, 'store'])->middleware('role:admin,superuser');
    Route::apiResource('productos', ProductoController::class)->except(['store']);

    // Rutas de Ventas
    Route::get('/ventas', [VentaController::class, 'index']);
    Route::post('/ventas', [VentaController::class, 'store']);
    Route::get('/ventas/{id}', [VentaController::class, 'show']);

    // Rutas de Pagos y QR
    Route::post('/ventas/{id}/qr', [PagoController::class, 'generarQr']);
    Route::post('/pagos/confirmar', [PagoController::class, 'confirmar']);
});