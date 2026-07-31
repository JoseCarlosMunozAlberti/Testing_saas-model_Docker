@extends('layouts.app')

@section('title', 'Ventas – PS Tenant')

@section('content')
<div class="container py-4">

    {{-- ===== Encabezado ===== --}}
    <div class="ps-page-header d-flex flex-wrap justify-content-between align-items-start gap-2 mb-4">
        <div>
            <h2><i class="bi bi-cart-check me-2"></i>Ventas y Pago QR</h2>
            <p class="text-muted mb-0">Registro de nuevas ventas, generación de cobros QR y confirmación bancaria</p>
        </div>
        <button class="btn btn-outline-secondary btn-sm" id="btn-actualizar-ventas" type="button">
            <span class="ps-btn-texto">
                <i class="bi bi-arrow-clockwise me-1"></i> Actualizar
            </span>
            <span class="ps-btn-spinner d-none">
                <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                Actualizando…
            </span>
        </button>
    </div>

    <div class="row g-4">
        {{-- ===== Panel Izquierdo: Selección de Productos & Carrito ===== --}}
        <div class="col-lg-7">
            <div class="ps-card card mb-4">
                <div class="card-body">
                    <h5 class="card-title mb-3">
                        <i class="bi bi-box-seam me-2 text-primary"></i>Catálogo de productos disponibles
                    </h5>
                    
                    {{-- Buscador rápido --}}
                    <div class="mb-3">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-white text-muted"><i class="bi bi-search"></i></span>
                            <input type="text" class="form-control" id="input-buscar-producto" placeholder="Buscar producto por nombre...">
                        </div>
                    </div>

                    {{-- Lista de Selección --}}
                    <div id="contenedor-productos-pos" class="row g-2 overflow-auto" style="max-height: 320px;">
                        <div class="text-center py-4 text-muted">Cargando productos…</div>
                    </div>
                </div>
            </div>

            {{-- Carrito de Compras --}}
            <div class="ps-card card">
                <div class="card-body">
                    <h5 class="card-title mb-3 d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-cart3 me-2 text-success"></i>Detalle de la Venta</span>
                        <button class="btn btn-outline-danger btn-sm" id="btn-vaciar-carrito" type="button">
                            <i class="bi bi-trash me-1"></i> Vaciar
                        </button>
                    </h5>

                    <div class="table-responsive mb-3">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Producto</th>
                                    <th style="width: 110px;">Cant.</th>
                                    <th>Precio</th>
                                    <th>Subtotal</th>
                                    <th style="width: 40px;"></th>
                                </tr>
                            </thead>
                            <tbody id="tbody-carrito">
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">El carrito está vacío. Haz clic en los productos para agregarlos.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                        <h4 class="mb-0 fw-bold">Total:</h4>
                        <h3 class="mb-0 fw-bold text-primary" id="txt-total-venta">Bs 0,00</h3>
                    </div>

                    <div class="d-grid mt-3">
                        <button class="btn btn-ps-primary btn-lg" id="btn-registrar-venta" disabled type="button">
                            <i class="bi bi-check-circle me-1"></i> Registrar Venta (Pendiente de Pago)
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- ===== Panel Derecho: Historial de Ventas ===== --}}
        <div class="col-lg-5">
            <div class="ps-card card h-100">
                <div class="card-body">
                    <h5 class="card-title mb-3">
                        <i class="bi bi-receipt me-2 text-info"></i>Historial de ventas del tenant
                    </h5>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Total</th>
                                    <th>Estado</th>
                                    <th>Acción</th>
                                </tr>
                            </thead>
                            <tbody id="tbody-historial-ventas">
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-muted">Cargando ventas…</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

{{-- ===== Modal para Cobro y Confirmación Bancaria QR ===== --}}
<div class="modal fade" id="modal-pago-qr" tabindex="-1" aria-labelledby="modal-qr-titulo" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content text-center">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" id="modal-qr-titulo">Cobro mediante QR Bancario</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body py-4">
                <p class="text-muted mb-2">Escanea el código QR desde tu aplicación bancaria o pulsa simular confirmación.</p>
                <h4 class="fw-bold text-primary mb-3" id="qr-modal-monto">Bs 0,00</h4>

                {{-- Representación Visual Segura Local del QR (Canvas / SVG) --}}
                <div class="d-flex justify-content-center mb-3">
                    <div id="contenedor-qr-visual" class="p-3 bg-white border rounded shadow-sm" style="width: 220px; height: 220px;">
                        <canvas id="qr-canvas" width="188" height="188"></canvas>
                    </div>
                </div>

                <div class="small text-muted mb-1">Referencia única de pago:</div>
                <div class="badge bg-light text-dark border fs-6 font-monospace mb-3" id="qr-modal-referencia">REF-XXX</div>

                <div id="qr-estado-alerta" class="alert alert-info py-2 small d-none" role="alert">
                    Esperando confirmación de la pasarela de pagos…
                </div>
            </div>
            <div class="modal-footer border-0 pt-0 justify-content-center gap-2">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-success" id="btn-simular-confirmacion">
                    <i class="bi bi-qr-code-scan me-1"></i> Simular Confirmación Bancaria
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/ventas.js') }}"></script>
@endpush
