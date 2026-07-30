@extends('layouts.app')

@section('title', 'Productos – PS Tenant')

@section('content')
<div class="container py-4">

    {{-- ===== Encabezado ===== --}}
    <div class="ps-page-header d-flex flex-wrap justify-content-between align-items-start gap-2 mb-4">
        <div>
            <h2><i class="bi bi-box-seam me-2"></i>Productos</h2>
            <p class="text-muted mb-0">Gestión y control del catálogo de productos de tu empresa</p>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-outline-secondary btn-sm" id="btn-actualizar-productos" type="button">
                <span class="ps-btn-texto">
                    <i class="bi bi-arrow-clockwise me-1"></i> Actualizar
                </span>
                <span class="ps-btn-spinner d-none">
                    <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                    Actualizando…
                </span>
            </button>
            <button class="btn btn-ps-primary btn-sm" type="button" disabled title="Próximamente">
                <i class="bi bi-plus-lg me-1"></i> Nuevo producto
            </button>
        </div>
    </div>

    {{-- ===== Barra de Herramientas (Búsqueda y Por página) ===== --}}
    <div class="ps-card card mb-4">
        <div class="card-body">
            <div class="row g-3 align-items-center justify-content-between">
                {{-- Búsqueda --}}
                <div class="col-12 col-md-6 col-lg-5">
                    <div class="input-group">
                        <span class="input-group-text bg-white text-muted"><i class="bi bi-search"></i></span>
                        <input type="text"
                               class="form-control"
                               id="input-busqueda"
                               placeholder="Buscar por nombre..."
                               aria-label="Buscar por nombre">
                        <button class="btn btn-outline-secondary" type="button" id="btn-limpiar-busqueda" title="Limpiar búsqueda">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>
                </div>

                {{-- Selector por página --}}
                <div class="col-12 col-md-auto d-flex align-items-center justify-content-md-end gap-2">
                    <label for="select-per-page" class="form-label mb-0 text-nowrap small text-muted">Mostrar:</label>
                    <select class="form-select form-select-sm w-auto" id="select-per-page" aria-label="Cantidad por página">
                        <option value="5">5</option>
                        <option value="10" selected>10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                    </select>
                    <span class="small text-muted">por pág.</span>
                </div>
            </div>
        </div>
    </div>

    {{-- ===== Estado de Carga ===== --}}
    <div id="zona-carga-productos">
        <div class="ps-card card">
            <div class="card-body text-center py-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Cargando…</span>
                </div>
                <p class="text-muted mt-3 mb-0">Cargando catálogo de productos…</p>
            </div>
        </div>
    </div>

    {{-- ===== Estado de Error ===== --}}
    <div id="zona-error-productos" class="d-none">
        <div class="ps-card card border-danger">
            <div class="card-body text-center py-5">
                <i class="bi bi-exclamation-triangle-fill text-danger" style="font-size: 2.5rem;"></i>
                <h5 class="mt-3">Error al cargar productos</h5>
                <p class="text-muted mb-3" id="error-mensaje-productos">Ocurrió un error inesperado al conectar con el servidor.</p>
                <button class="btn btn-ps-primary btn-sm" id="btn-reintentar-productos" type="button">
                    <i class="bi bi-arrow-clockwise me-1"></i> Reintentar
                </button>
            </div>
        </div>
    </div>

    {{-- ===== Zona de Contenido (Tabla + Paginación) ===== --}}
    <div id="zona-contenido-productos" class="d-none">
        <div class="ps-card card">
            <div class="card-body p-0">

                {{-- Tabla Responsive --}}
                <div id="tabla-productos-wrapper" class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th scope="col" class="ps-sortable-th" data-sort="id" style="cursor: pointer;">
                                    ID <i class="bi bi-arrow-down-up ps-sort-icon ms-1 text-muted opacity-50"></i>
                                </th>
                                <th scope="col" class="ps-sortable-th" data-sort="nombre" style="cursor: pointer;">
                                    Nombre <i class="bi bi-arrow-down-up ps-sort-icon ms-1 text-muted opacity-50"></i>
                                </th>
                                <th scope="col" class="ps-sortable-th" data-sort="precio" style="cursor: pointer;">
                                    Precio <i class="bi bi-arrow-down-up ps-sort-icon ms-1 text-muted opacity-50"></i>
                                </th>
                                <th scope="col" class="ps-sortable-th" data-sort="stock" style="cursor: pointer;">
                                    Stock <i class="bi bi-arrow-down-up ps-sort-icon ms-1 text-muted opacity-50"></i>
                                </th>
                                <th scope="col">Estado</th>
                                <th scope="col" class="ps-sortable-th" data-sort="created_at" style="cursor: pointer;">
                                    Fecha de registro <i class="bi bi-arrow-down-up ps-sort-icon ms-1 text-muted opacity-50"></i>
                                </th>
                                <th scope="col">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="tabla-productos-body"></tbody>
                    </table>
                </div>

                {{-- Estado Vacío (Tenant sin productos) --}}
                <div id="estado-vacio-productos" class="d-none text-center py-5">
                    <i class="bi bi-inbox text-muted" style="font-size: 3rem;"></i>
                    <h5 class="mt-3">No hay productos registrados</h5>
                    <p class="text-muted mb-0">Tu empresa aún no tiene productos agregados en el sistema.</p>
                </div>

                {{-- Estado Sin Resultados (Búsqueda vacía) --}}
                <div id="estado-sin-resultados-productos" class="d-none text-center py-5">
                    <i class="bi bi-search text-muted" style="font-size: 3rem;"></i>
                    <h5 class="mt-3">Sin resultados</h5>
                    <p class="text-muted mb-0">No se encontraron productos que coincidan con "<strong id="texto-busqueda-fallida"></strong>".</p>
                </div>

            </div>

            {{-- Pie de tarjeta: Resumen y Paginación --}}
            <div class="card-footer bg-white border-top-0 d-flex flex-wrap justify-content-between align-items-center gap-3 py-3">
                <div class="small text-muted" id="texto-resumen-resultados">
                    Cargando resumen…
                </div>
                <nav aria-label="Navegación de productos">
                    <ul class="pagination pagination-sm mb-0" id="ul-paginacion-productos"></ul>
                </nav>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script src="{{ asset('js/productos.js') }}"></script>
@endpush
