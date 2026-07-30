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
            <button class="btn btn-ps-primary btn-sm" id="btn-nuevo-producto" type="button">
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

{{-- ===== Modal Reutilizable para Crear / Editar Producto ===== --}}
<div class="modal fade" id="modal-producto" tabindex="-1" aria-labelledby="modal-producto-titulo" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modal-producto-titulo">Nuevo producto</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <form id="form-producto" novalidate>
                <div class="modal-body">
                    <input type="hidden" id="producto-id" value="">

                    {{-- Campo Nombre --}}
                    <div class="mb-3">
                        <label for="producto-nombre" class="form-label">Nombre del producto <span class="text-danger">*</span></label>
                        <input type="text"
                               class="form-control"
                               id="producto-nombre"
                               name="nombre"
                               placeholder="Ej. Cemento Viacha"
                               required>
                        <div class="invalid-feedback"></div>
                    </div>

                    {{-- Campo Precio --}}
                    <div class="mb-3">
                        <label for="producto-precio" class="form-label">Precio (Bs) <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text">Bs</span>
                            <input type="number"
                                   step="0.01"
                                   min="0"
                                   class="form-control"
                                   id="producto-precio"
                                   name="precio"
                                   placeholder="0.00"
                                   required>
                        </div>
                        <div class="invalid-feedback"></div>
                    </div>

                    {{-- Campo Stock --}}
                    <div class="mb-3">
                        <label for="producto-stock" class="form-label">Stock inicial / Unidades <span class="text-danger">*</span></label>
                        <input type="number"
                               step="1"
                               min="0"
                               class="form-control"
                               id="producto-stock"
                               name="stock"
                               placeholder="0"
                               required>
                        <div class="invalid-feedback"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-ps-primary" id="btn-guardar-producto">
                        <span id="btn-guardar-texto">Crear producto</span>
                        <span id="btn-guardar-spinner" class="d-none">
                            <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                            Guardando…
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ===== Modal de Confirmación para Eliminar Producto ===== --}}
<div class="modal fade" id="modal-eliminar-producto" tabindex="-1" aria-labelledby="modal-eliminar-titulo" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title text-danger" id="modal-eliminar-titulo">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>Eliminar producto
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body py-3">
                <p class="mb-2">¿Estás seguro de que deseas eliminar el producto <strong id="eliminar-producto-nombre"></strong>?</p>
                <p class="text-muted small mb-0">El producto dejará de mostrarse en el sistema y en el catálogo activo de tu empresa.</p>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" id="btn-confirmar-eliminar">
                    <span id="btn-eliminar-texto">Eliminar producto</span>
                    <span id="btn-eliminar-spinner" class="d-none">
                        <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                        Eliminando…
                    </span>
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/productos.js') }}"></script>
@endpush
