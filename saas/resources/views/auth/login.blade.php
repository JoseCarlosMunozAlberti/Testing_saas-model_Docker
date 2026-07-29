@extends('layouts.app')

@section('title', 'Iniciar sesión – PS Tenant')

@section('content')
<div class="ps-login-wrapper">
    <div class="ps-login-card card">
        <div class="card-body">
            {{-- Encabezado --}}
            <div class="ps-login-header">
                <div class="ps-login-logo">
                    <i class="bi bi-building"></i>
                </div>
                <h1>PS Tenant</h1>
                <p>Inicia sesión para continuar</p>
            </div>

            {{-- Formulario --}}
            <form id="form-login" novalidate>
                {{-- Email --}}
                <div class="mb-3">
                    <label for="login-email" class="form-label">Correo electrónico</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                        <input type="email"
                               class="form-control"
                               id="login-email"
                               name="email"
                               autocomplete="email"
                               placeholder="usuario@ejemplo.com"
                               required>
                    </div>
                    <div class="invalid-feedback"></div>
                </div>

                {{-- Contraseña --}}
                <div class="mb-4">
                    <label for="login-password" class="form-label">Contraseña</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                        <input type="password"
                               class="form-control"
                               id="login-password"
                               name="password"
                               autocomplete="current-password"
                               placeholder="••••••••"
                               required>
                    </div>
                    <div class="invalid-feedback"></div>
                </div>

                {{-- Botón --}}
                <div class="d-grid">
                    <button type="submit" class="btn btn-ps-primary" id="btn-login">
                        <span id="btn-login-texto">
                            <i class="bi bi-box-arrow-in-right me-1"></i> Iniciar sesión
                        </span>
                        <span id="btn-login-spinner" class="d-none">
                            <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                            Ingresando…
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/login.js') }}"></script>
@endpush
