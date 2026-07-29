<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProductoController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function (): void {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/usuario', [AuthController::class, 'usuarioActual']);
Route::get(
    '/dashboard/resumen',
    [DashboardController::class, 'resumen']
);
    Route::apiResource('productos', ProductoController::class);
    
});
