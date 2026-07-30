@extends('layouts.app')

@section('title', 'Dashboard – PS Tenant')

@section('content')
<div class="container py-4">

    {{-- ===== Encabezado ===== --}}
    <div class="ps-page-header d-flex flex-wrap justify-content-between align-items-start gap-2">
        <div>
            <h2><i class="bi bi-speedometer2 me-2"></i>Dashboard</h2>
            <p id="dashboard-bienvenida">Cargando información…</p>
        </div>
        <button class="btn btn-ps-primary btn-sm" id="btn-actualizar-dashboard" type="button">
            <span class="ps-btn-texto">
                <i class="bi bi-arrow-clockwise me-1"></i> Actualizar
            </span>
            <span class="ps-btn-spinner d-none">
                <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                Actualizando…
            </span>
        </button>
    </div>

    {{-- ===== Tarjeta de sesión ===== --}}
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

    {{-- ===== Estado de carga ===== --}}
    <div id="zona-carga">
        <div class="row g-3 mb-4">
            @for ($i = 0; $i < 5; $i++)
            <div class="col-sm-6 col-xl">
                <div class="ps-card card ps-kpi-card">
                    <div class="card-body">
                        <div class="placeholder-glow">
                            <span class="placeholder col-4 mb-2"></span>
                            <span class="placeholder placeholder-lg col-6 d-block mb-2"></span>
                            <span class="placeholder col-8"></span>
                        </div>
                    </div>
                </div>
            </div>
            @endfor
        </div>
        <div class="ps-card card">
            <div class="card-body text-center py-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Cargando…</span>
                </div>
                <p class="text-muted mt-3 mb-0">Cargando datos del inventario…</p>
            </div>
        </div>
    </div>

    {{-- ===== Estado de error ===== --}}
    <div id="zona-error" class="d-none">
        <div class="ps-card card border-danger">
            <div class="card-body text-center py-5">
                <i class="bi bi-exclamation-triangle-fill text-danger" style="font-size: 2.5rem;"></i>
                <h5 class="mt-3">Error al cargar los datos</h5>
                <p class="text-muted mb-3" id="error-mensaje">Ocurrió un error inesperado.</p>
                <button class="btn btn-ps-primary btn-sm" id="btn-reintentar" type="button">
                    <i class="bi bi-arrow-clockwise me-1"></i> Reintentar
                </button>
            </div>
        </div>
    </div>

    {{-- ===== Indicadores KPI ===== --}}
    <div id="zona-indicadores" class="d-none">
        <div class="row g-3 mb-4">
            {{-- Total productos --}}
            <div class="col-sm-6 col-xl">
                <div class="ps-card card ps-kpi-card">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-2">
                            <div class="ps-kpi-icon bg-primary bg-opacity-10 text-primary">
                                <i class="bi bi-box-seam"></i>
                            </div>
                            <span class="ps-kpi-title ms-2">Productos</span>
                        </div>
                        <div class="ps-kpi-valor" id="kpi-total-productos">0</div>
                        <small class="text-muted">Total registrados</small>
                    </div>
                </div>
            </div>

            {{-- Unidades en inventario --}}
            <div class="col-sm-6 col-xl">
                <div class="ps-card card ps-kpi-card">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-2">
                            <div class="ps-kpi-icon bg-success bg-opacity-10 text-success">
                                <i class="bi bi-stack"></i>
                            </div>
                            <span class="ps-kpi-title ms-2">Inventario</span>
                        </div>
                        <div class="ps-kpi-valor" id="kpi-stock-total">0</div>
                        <small class="text-muted">Unidades totales</small>
                    </div>
                </div>
            </div>

            {{-- Valor del inventario --}}
            <div class="col-sm-6 col-xl">
                <div class="ps-card card ps-kpi-card">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-2">
                            <div class="ps-kpi-icon bg-info bg-opacity-10 text-info">
                                <i class="bi bi-currency-dollar"></i>
                            </div>
                            <span class="ps-kpi-title ms-2">Valor total</span>
                        </div>
                        <div class="ps-kpi-valor" id="kpi-valor-inventario">Bs 0,00</div>
                        <small class="text-muted">Valor del inventario</small>
                    </div>
                </div>
            </div>

            {{-- Stock bajo --}}
            <div class="col-sm-6 col-xl">
                <div class="ps-card card ps-kpi-card">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-2">
                            <div class="ps-kpi-icon bg-warning bg-opacity-10 text-warning">
                                <i class="bi bi-exclamation-triangle"></i>
                            </div>
                            <span class="ps-kpi-title ms-2">Stock bajo</span>
                        </div>
                        <div class="ps-kpi-valor" id="kpi-stock-bajo">0</div>
                        <small class="text-muted">Entre 1 y 5 unidades</small>
                    </div>
                </div>
            </div>

            {{-- Sin stock --}}
            <div class="col-sm-6 col-xl">
                <div class="ps-card card ps-kpi-card">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-2">
                            <div class="ps-kpi-icon bg-danger bg-opacity-10 text-danger">
                                <i class="bi bi-x-circle"></i>
                            </div>
                            <span class="ps-kpi-title ms-2">Sin stock</span>
                        </div>
                        <div class="ps-kpi-valor" id="kpi-sin-stock">0</div>
                        <small class="text-muted">Agotados</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ===== Productos recientes ===== --}}
    <div id="zona-productos-recientes" class="d-none">
        <div class="ps-card card">
            <div class="card-body">
                <h5 class="card-title mb-3">
                    <i class="bi bi-clock-history me-2 text-primary"></i>Productos recientes
                </h5>

                {{-- Tabla --}}
                <div id="tabla-recientes-wrapper" class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Nombre</th>
                                <th>Precio</th>
                                <th>Stock</th>
                                <th>Fecha de registro</th>
                            </tr>
                        </thead>
                        <tbody id="tabla-recientes-body"></tbody>
                    </table>
                </div>

                {{-- Estado vacío --}}
                <div id="estado-vacio-recientes" class="d-none text-center py-4">
                    <i class="bi bi-inbox text-muted" style="font-size: 2rem;"></i>
                    <p class="text-muted mt-2 mb-0">No hay productos recientes para mostrar.</p>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script src="{{ asset('js/dashboard.js') }}"></script>
@endpush
