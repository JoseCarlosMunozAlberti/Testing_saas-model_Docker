<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/login');
});

Route::get('/login', function () {
    return view('auth.login');
})->name('login');

Route::get('/dashboard', function () {
    return view('dashboard.index', ['mostrarNavegacion' => true]);
})->name('dashboard');

Route::get('/productos', function () {
    return view('productos.index', ['mostrarNavegacion' => true]);
})->name('productos.index');

Route::get('/ventas', function () {
    return view('ventas.index', ['mostrarNavegacion' => true]);
})->name('ventas.index');
