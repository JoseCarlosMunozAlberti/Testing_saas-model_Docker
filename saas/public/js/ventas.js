/**
 * PS Tenant – Módulo Frontend de Ventas y Pago QR
 * Carrito interactivo, registro de venta y simulación de pago QR bancario.
 * Utiliza exclusivamente apiRequest() de api-client.js.
 */

const ventasState = {
    cargandoProductos: true,
    cargandoVentas: true,
    productos: [],
    carrito: [], // array de { producto, cantidad }
    ventas: [],
    ventaActivaId: null,
    referenciaActiva: null,
    montoActivo: 0,
    registrando: false,
    confirmando: false,
};

let bsModalPagoQr = null;

/* ============================================================
   Inicialización
   ============================================================ */

async function inicializarVentas() {
    // 1. Validar Sesión
    const usuario = await validarSesion();
    if (!usuario) {
        window.location.href = '/login';
        return;
    }

    // 2. Mostrar usuario/tenant en Navbar
    const nombre = usuario.name || '—';
    const tenant = usuario.tenant?.nombre_comercial || '—';

    const navNombre = document.getElementById('navbar-nombre-usuario');
    const navTenant = document.getElementById('navbar-nombre-tenant');
    if (navNombre) navNombre.textContent = nombre;
    if (navTenant) navTenant.textContent = tenant;

    // 3. Configurar Logout
    const btnLogout = document.getElementById('btn-logout');
    if (btnLogout) {
        btnLogout.addEventListener('click', () => cerrarSesion());
    }

    // 4. Modal de Pago QR
    const modalQrEl = document.getElementById('modal-pago-qr');
    if (modalQrEl) {
        bsModalPagoQr = new bootstrap.Modal(modalQrEl);
    }

    // 5. Configurar Eventos
    configurarEventosVentas();

    // 6. Cargar datos iniciales
    await Promise.all([
        cargarProductosVenta(),
        cargarHistorialVentas()
    ]);
}

/* ============================================================
   Eventos y Listeners
   ============================================================ */

function configurarEventosVentas() {
    // Buscador rápido de productos POS
    const inputBuscar = document.getElementById('input-buscar-producto');
    if (inputBuscar) {
        inputBuscar.addEventListener('input', (e) => {
            filtrarProductosPos(e.target.value.toLowerCase().trim());
        });
    }

    // Vaciar carrito
    const btnVaciar = document.getElementById('btn-vaciar-carrito');
    if (btnVaciar) {
        btnVaciar.addEventListener('click', () => {
            ventasState.carrito = [];
            renderizarCarrito();
        });
    }

    // Registrar Venta
    const btnRegistrar = document.getElementById('btn-registrar-venta');
    if (btnRegistrar) {
        btnRegistrar.addEventListener('click', () => {
            registrarVentaPos();
        });
    }

    // Botón Actualizar
    const btnActualizar = document.getElementById('btn-actualizar-ventas');
    if (btnActualizar) {
        btnActualizar.addEventListener('click', async () => {
            btnActualizar.disabled = true;
            await Promise.all([cargarProductosVenta(), cargarHistorialVentas()]);
            mostrarToast('Datos de ventas actualizados.', 'success');
            btnActualizar.disabled = false;
        });
    }

    // Simular Confirmación Bancaria
    const btnSimular = document.getElementById('btn-simular-confirmacion');
    if (btnSimular) {
        btnSimular.addEventListener('click', () => {
            simularConfirmacionBancaria();
        });
    }

    // Delegación en tabla de carrito para cambiar cantidades o eliminar
    const tbodyCarrito = document.getElementById('tbody-carrito');
    if (tbodyCarrito) {
        tbodyCarrito.addEventListener('change', (e) => {
            if (e.target.classList.contains('input-cant-carrito')) {
                const pId = parseInt(e.target.getAttribute('data-id'), 10);
                const nuevaCant = parseInt(e.target.value, 10);
                actualizarCantidadCarrito(pId, nuevaCant);
            }
        });

        tbodyCarrito.addEventListener('click', (e) => {
            const btnQuitar = e.target.closest('.btn-quitar-item');
            if (btnQuitar) {
                const pId = parseInt(btnQuitar.getAttribute('data-id'), 10);
                quitarItemCarrito(pId);
            }
        });
    }

    // Delegación en tabla de historial para ver/pagar QR
    const tbodyHistorial = document.getElementById('tbody-historial-ventas');
    if (tbodyHistorial) {
        tbodyHistorial.addEventListener('click', (e) => {
            const btnCobrar = e.target.closest('.btn-cobrar-qr');
            if (btnCobrar) {
                const vId = parseInt(btnCobrar.getAttribute('data-id'), 10);
                abrirModalCobroQr(vId);
            }
        });
    }
}

/* ============================================================
   Carga de Datos (API)
   ============================================================ */

async function cargarProductosVenta() {
    try {
        const res = await apiRequest('/productos?per_page=50');
        ventasState.productos = res.data || [];
        ventasState.cargandoProductos = false;
        renderizarProductosPos(ventasState.productos);
    } catch (error) {
        if (error.status !== 401) {
            mostrarToast(error.message || 'Error al cargar productos para venta.', 'danger');
        }
    }
}

async function cargarHistorialVentas() {
    try {
        const res = await apiRequest('/ventas?per_page=10');
        ventasState.ventas = res.data || [];
        ventasState.cargandoVentas = false;
        renderizarHistorialVentas(ventasState.ventas);
    } catch (error) {
        if (error.status !== 401) {
            mostrarToast(error.message || 'Error al cargar historial de ventas.', 'danger');
        }
    }
}

/* ============================================================
   Lógica del Carrito
   ============================================================ */

function agregarAlCarrito(producto) {
    if (producto.stock <= 0) {
        mostrarToast(`El producto '${producto.nombre}' no tiene stock disponible.`, 'warning');
        return;
    }

    const existe = ventasState.carrito.find(item => item.producto.id === producto.id);
    if (existe) {
        if (existe.cantidad + 1 > producto.stock) {
            mostrarToast(`No puedes superar el stock disponible (${producto.stock} unidades).`, 'warning');
            return;
        }
        existe.cantidad += 1;
    } else {
        ventasState.carrito.push({ producto, cantidad: 1 });
    }

    renderizarCarrito();
}

function actualizarCantidadCarrito(productoId, cantidad) {
    const item = ventasState.carrito.find(i => i.producto.id === productoId);
    if (!item) return;

    if (isNaN(cantidad) || cantidad < 1) {
        cantidad = 1;
    }

    if (cantidad > item.producto.stock) {
        mostrarToast(`Stock máximo disponible: ${item.producto.stock}`, 'warning');
        cantidad = item.producto.stock;
    }

    item.cantidad = cantidad;
    renderizarCarrito();
}

function quitarItemCarrito(productoId) {
    ventasState.carrito = ventasState.carrito.filter(i => i.producto.id !== productoId);
    renderizarCarrito();
}

function calcularTotalCarrito() {
    return ventasState.carrito.reduce((sum, item) => sum + (parseFloat(item.producto.precio) * item.cantidad), 0);
}

/* ============================================================
   Renderizado de UI
   ============================================================ */

function renderizarProductosPos(productos) {
    const cont = document.getElementById('contenedor-productos-pos');
    if (!cont) return;

    if (productos.length === 0) {
        cont.innerHTML = `<div class="text-center py-4 text-muted">No hay productos disponibles.</div>`;
        return;
    }

    cont.innerHTML = productos.map(p => `
        <div class="col-sm-6 col-md-4">
            <div class="card h-100 ps-card ps-item-pos ${p.stock <= 0 ? 'opacity-50' : ''}" 
                 style="cursor: ${p.stock > 0 ? 'pointer' : 'not-allowed'};" 
                 onclick="${p.stock > 0 ? `agregarAlCarritoById(${p.id})` : ''}">
                <div class="card-body p-2 text-center">
                    <div class="fw-semibold text-truncate small">${escapeHtml(p.nombre)}</div>
                    <div class="text-primary fw-bold">${formatearMoneda(parseFloat(p.precio))}</div>
                    <small class="text-muted" style="font-size: 0.75rem;">Stock: ${p.stock}</small>
                </div>
            </div>
        </div>
    `).join('');
}

function agregarAlCarritoById(id) {
    const prod = ventasState.productos.find(p => p.id === id);
    if (prod) agregarAlCarrito(prod);
}

function filtrarProductosPos(termino) {
    if (!termino) {
        renderizarProductosPos(ventasState.productos);
        return;
    }
    const filtrados = ventasState.productos.filter(p => p.nombre.toLowerCase().includes(termino));
    renderizarProductosPos(filtrados);
}

function renderizarCarrito() {
    const tbody = document.getElementById('tbody-carrito');
    const txtTotal = document.getElementById('txt-total-venta');
    const btnRegistrar = document.getElementById('btn-registrar-venta');

    if (!tbody || !txtTotal || !btnRegistrar) return;

    if (ventasState.carrito.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="5" class="text-center py-4 text-muted">El carrito está vacío. Haz clic en los productos para agregarlos.</td>
            </tr>
        `;
        txtTotal.textContent = 'Bs 0,00';
        btnRegistrar.disabled = true;
        return;
    }

    tbody.innerHTML = ventasState.carrito.map(item => {
        const subtotal = parseFloat(item.producto.precio) * item.cantidad;
        return `
            <tr>
                <td><small class="fw-medium">${escapeHtml(item.producto.nombre)}</small></td>
                <td>
                    <input type="number" class="form-control form-control-sm input-cant-carrito" 
                           data-id="${item.producto.id}" min="1" max="${item.producto.stock}" value="${item.cantidad}">
                </td>
                <td><small>${formatearMoneda(parseFloat(item.producto.precio))}</small></td>
                <td class="fw-bold"><small>${formatearMoneda(subtotal)}</small></td>
                <td>
                    <button type="button" class="btn btn-link text-danger p-0 btn-quitar-item" data-id="${item.producto.id}">
                        <i class="bi bi-x-circle-fill"></i>
                    </button>
                </td>
            </tr>
        `;
    }).join('');

    const total = calcularTotalCarrito();
    txtTotal.textContent = formatearMoneda(total);
    btnRegistrar.disabled = false;
}

function renderizarHistorialVentas(ventas) {
    const tbody = document.getElementById('tbody-historial-ventas');
    if (!tbody) return;

    if (ventas.length === 0) {
        tbody.innerHTML = `<tr><td colspan="4" class="text-center py-4 text-muted">No existen ventas registradas.</td></tr>`;
        return;
    }

    tbody.innerHTML = ventas.map(v => {
        const badgeClase = v.estado === 'pagada' ? 'bg-success' : (v.estado === 'pendiente' ? 'bg-warning text-dark' : 'bg-secondary');
        const esPendiente = v.estado === 'pendiente';

        return `
            <tr>
                <td class="fw-bold">#${v.id}</td>
                <td class="fw-semibold">${formatearMoneda(parseFloat(v.total))}</td>
                <td><span class="badge ${badgeClase}">${v.estado.toUpperCase()}</span></td>
                <td>
                    ${esPendiente ? `
                        <button type="button" class="btn btn-primary btn-sm btn-cobrar-qr" data-id="${v.id}">
                            <i class="bi bi-qr-code"></i> Cobrar
                        </button>
                    ` : `
                        <span class="text-success small"><i class="bi bi-check-all"></i> Pagado</span>
                    `}
                </td>
            </tr>
        `;
    }).join('');
}

/* ============================================================
   Registro de Venta y Pago QR
   ============================================================ */

async function registrarVentaPos() {
    if (ventasState.carrito.length === 0) return;

    const payload = {
        items: ventasState.carrito.map(item => ({
            producto_id: item.producto.id,
            cantidad: item.cantidad
        }))
    };

    const btnRegistrar = document.getElementById('btn-registrar-venta');
    if (btnRegistrar) btnRegistrar.disabled = true;

    try {
        const venta = await apiRequest('/ventas', {
            method: 'POST',
            body: payload
        });

        mostrarToast(`Venta #${venta.id} creada en estado PENDIENTE.`, 'success');
        
        // Vaciar carrito
        ventasState.carrito = [];
        renderizarCarrito();

        // Actualizar historial y abrir cobro QR automáticamente
        await cargarHistorialVentas();
        abrirModalCobroQr(venta.id);

    } catch (error) {
        if (error.status !== 401) {
            mostrarToast(error.message || 'Error al registrar la venta.', 'danger');
        }
    } finally {
        if (btnRegistrar && ventasState.carrito.length > 0) btnRegistrar.disabled = false;
    }
}

async function abrirModalCobroQr(ventaId) {
    try {
        const resQr = await apiRequest(`/ventas/${ventaId}/qr`, { method: 'POST' });

        ventasState.ventaActivaId = resQr.venta_id;
        ventasState.referenciaActiva = resQr.referencia;
        ventasState.montoActivo = resQr.monto;

        document.getElementById('qr-modal-monto').textContent = formatearMoneda(resQr.monto);
        document.getElementById('qr-modal-referencia').textContent = resQr.referencia;

        // Dibujar QR visual seguro en canvas usando API nativa de patrones
        dibujarQrVisualCanvas(resQr.qr_texto);

        if (bsModalPagoQr) {
            bsModalPagoQr.show();
        }
    } catch (error) {
        if (error.status !== 401) {
            mostrarToast(error.message || 'Error al obtener el código QR de cobro.', 'danger');
        }
    }
}

async function simularConfirmacionBancaria() {
    if (!ventasState.referenciaActiva) return;

    const btnSimular = document.getElementById('btn-simular-confirmacion');
    if (btnSimular) btnSimular.disabled = true;

    try {
        const resConfirm = await apiRequest('/pagos/confirmar', {
            method: 'POST',
            body: { referencia: ventasState.referenciaActiva }
        });

        mostrarToast(resConfirm.message || '¡Pago confirmado exitosamente!', 'success');

        if (bsModalPagoQr) {
            bsModalPagoQr.hide();
        }

        // Recargar productos (para reflejar descuento de stock) y ventas
        await Promise.all([cargarProductosVenta(), cargarHistorialVentas()]);

    } catch (error) {
        if (error.status !== 401) {
            mostrarToast(error.message || 'Error al confirmar el pago.', 'danger');
        }
    } finally {
        if (btnSimular) btnSimular.disabled = false;
    }
}

/**
 * Dibuja un QR visual local sin depender de APIs de terceros externamente expuestas.
 */
function dibujarQrVisualCanvas(texto) {
    const canvas = document.getElementById('qr-canvas');
    if (!canvas) return;
    const ctx = canvas.getContext('2d');
    
    // Limpiar canvas
    ctx.fillStyle = '#FFFFFF';
    ctx.fillRect(0, 0, canvas.width, canvas.height);

    // Dibujar patrón pseudo-QR representativo
    ctx.fillStyle = '#1E293B';
    const gridSize = 10;
    const cellSize = canvas.width / gridSize;

    // Generar patrón determinístico basado en la referencia
    let hash = 0;
    for (let i = 0; i < texto.length; i++) {
        hash = (hash << 5) - hash + texto.charCodeAt(i);
        hash |= 0;
    }

    for (let row = 0; row < gridSize; row++) {
        for (let col = 0; col < gridSize; col++) {
            // Posicionar esquinas estándar de QR (Finder patterns)
            const esEsquinaSupIzq = row < 3 && col < 3;
            const esEsquinaSupDer = row < 3 && col >= gridSize - 3;
            const esEsquinaInfIzq = row >= gridSize - 3 && col < 3;

            if (esEsquinaSupIzq || esEsquinaSupDer || esEsquinaInfIzq) {
                if (row === 1 && col === 1 || row === 1 && col === gridSize - 2 || row === gridSize - 2 && col === 1) {
                    ctx.fillStyle = '#FFFFFF';
                } else {
                    ctx.fillStyle = '#4361EE';
                }
                ctx.fillRect(col * cellSize, row * cellSize, cellSize, cellSize);
            } else {
                const val = (row * gridSize + col + hash) % 3;
                if (val === 0) {
                    ctx.fillStyle = '#1E293B';
                    ctx.fillRect(col * cellSize, row * cellSize, cellSize, cellSize);
                }
            }
        }
    }
}

/* ============================================================
   Utilidades de Formato y Seguridad
   ============================================================ */

function formatearMoneda(valor) {
    const formateado = new Intl.NumberFormat('es-BO', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(valor);
    return `Bs ${formateado}`;
}

function escapeHtml(str) {
    if (!str) return '';
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

/* ============================================================
   Arranque único
   ============================================================ */

document.addEventListener('DOMContentLoaded', () => {
    inicializarVentas();
});
