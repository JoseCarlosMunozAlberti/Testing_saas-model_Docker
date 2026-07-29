@extends('layouts.app')

@section('title', 'Dashboard – PS Tenant')

@section('content')
<div class="container py-4">
    {{-- Encabezado de página --}}
    <div class="ps-page-header">
        <h2><i class="bi bi-speedometer2 me-2"></i>Dashboard</h2>
        <p id="dashboard-bienvenida">Cargando información…</p>
    </div>

    {{-- Tarjeta de bienvenida --}}
    <div class="row mb-4">
        <div class="col-lg-6">
            <div class="ps-card card">
                <div class="card-body">
                    <h5 class="card-title mb-3">
                        <i class="bi bi-person-circle me-2 text-primary"></i>Información de la sesión
                    </h5>
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2">
                            <strong>Usuario:</strong>
                            <span id="dashboard-usuario-nombre">—</span>
                        </li>
                        <li class="mb-2">
                            <strong>Correo:</strong>
                            <span id="dashboard-usuario-email">—</span>
                        </li>
                        <li>
                            <strong>Empresa:</strong>
                            <span id="dashboard-tenant-nombre">—</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    {{-- Indicadores provisionales --}}
    <div class="ps-placeholder-box">
        <i class="bi bi-bar-chart-line"></i>
        <h5>Indicadores del Dashboard</h5>
        <p class="mb-0">Las tarjetas con el resumen de inventario se incorporarán en el siguiente paso.</p>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', async () => {
    // Validar sesión
    const usuario = await validarSesion();
    if (!usuario) {
        window.location.href = '/login';
        return;
    }

    // Mostrar datos del usuario
    const nombre = usuario.name || '—';
    const email = usuario.email || '—';
    const tenant = usuario.tenant?.nombre_comercial || '—';

    document.getElementById('dashboard-bienvenida').textContent =
        `Bienvenido, ${nombre}`;

    document.getElementById('dashboard-usuario-nombre').textContent = nombre;
    document.getElementById('dashboard-usuario-email').textContent = email;
    document.getElementById('dashboard-tenant-nombre').textContent = tenant;

    // Navbar
    const navNombre = document.getElementById('navbar-nombre-usuario');
    const navTenant = document.getElementById('navbar-nombre-tenant');
    if (navNombre) navNombre.textContent = nombre;
    if (navTenant) navTenant.textContent = tenant;

    // Botón logout
    const btnLogout = document.getElementById('btn-logout');
    if (btnLogout) {
        btnLogout.addEventListener('click', () => cerrarSesion());
    }
});
</script>
@endpush
