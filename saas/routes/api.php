<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProductoController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function (): void {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/usuario', [AuthController::class, 'usuarioActual']);

    Route::apiResource('productos', ProductoController::class);
});
