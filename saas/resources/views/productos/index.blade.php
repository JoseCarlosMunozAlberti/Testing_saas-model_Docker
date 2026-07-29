@extends('layouts.app')

@section('title', 'Productos – PS Tenant')

@section('content')
<div class="container py-4">
    {{-- Encabezado de página --}}
    <div class="ps-page-header">
        <h2><i class="bi bi-box-seam me-2"></i>Productos</h2>
        <p>Gestión del catálogo de productos</p>
    </div>

    {{-- Tabla provisional --}}
    <div class="ps-placeholder-box">
        <i class="bi bi-table"></i>
        <h5>Tabla interactiva de productos</h5>
        <p class="mb-0">La tabla con búsqueda, paginación y operaciones CRUD se implementará en el siguiente paso.</p>
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

    // Navbar
    const nombre = usuario.name || '—';
    const tenant = usuario.tenant?.nombre_comercial || '—';

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
