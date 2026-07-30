/**
 * PS Tenant – Dashboard reactivo
 * Estado central + funciones de renderizado.
 * Usa apiRequest() de api-client.js — nunca fetch() directo.
 */

/* ============================================================
   Estado central
   ============================================================ */

const dashboardState = {
    cargando: true,
    error: null,
    datos: null,
};

/* ============================================================
   Inicialización
   ============================================================ */

/**
 * Punto de entrada: valida sesión, muestra usuario y carga datos.
 */
async function inicializarDashboard() {
    // 1. Validar sesión
    const usuario = await validarSesion();
    if (!usuario) {
        window.location.href = '/login';
        return;
    }

    // 2. Datos de usuario en la vista
    const nombre = usuario.name || '—';
    const email  = usuario.email || '—';
    const tenant = usuario.tenant?.nombre_comercial || '—';

    document.getElementById('dashboard-bienvenida').textContent =
        `Bienvenido, ${nombre}`;

    document.getElementById('dashboard-usuario-nombre').textContent = nombre;
    document.getElementById('dashboard-usuario-email').textContent  = email;
    document.getElementById('dashboard-tenant-nombre').textContent  = tenant;

    // 3. Navbar
    const navNombre = document.getElementById('navbar-nombre-usuario');
    const navTenant = document.getElementById('navbar-nombre-tenant');
    if (navNombre) navNombre.textContent = nombre;
    if (navTenant) navTenant.textContent = tenant;

    // 4. Logout
    const btnLogout = document.getElementById('btn-logout');
    if (btnLogout) {
        btnLogout.addEventListener('click', () => cerrarSesion());
    }

    // 5. Botón Actualizar
    const btnActualizar = document.getElementById('btn-actualizar-dashboard');
    if (btnActualizar) {
        btnActualizar.addEventListener('click', async () => {
            btnActualizar.disabled = true;
            btnActualizar.querySelector('.ps-btn-texto').classList.add('d-none');
            btnActualizar.querySelector('.ps-btn-spinner').classList.remove('d-none');

            await cargarResumenDashboard();

            if (!dashboardState.error) {
                mostrarToast('Datos actualizados correctamente.', 'success');
            }

            btnActualizar.disabled = false;
            btnActualizar.querySelector('.ps-btn-texto').classList.remove('d-none');
            btnActualizar.querySelector('.ps-btn-spinner').classList.add('d-none');
        });
    }

    // 6. Primera carga de datos
    await cargarResumenDashboard();
}

/* ============================================================
   Carga de datos
   ============================================================ */

/**
 * Consulta GET /api/dashboard/resumen y actualiza el estado.
 */
async function cargarResumenDashboard() {
    actualizarEstadoDashboard({ cargando: true, error: null });

    try {
        const datos = await apiRequest('/dashboard/resumen');
        actualizarEstadoDashboard({ cargando: false, error: null, datos });
    } catch (error) {
        // 401 ya fue manejado por api-client.js (redirige a login)
        if (error.status === 401) return;

        const mensaje = error.message || 'No se pudieron cargar los datos del dashboard.';
        actualizarEstadoDashboard({ cargando: false, error: mensaje, datos: null });
        mostrarToast(mensaje, 'danger');
    }
}

/* ============================================================
   Gestión de estado
   ============================================================ */

/**
 * Actualiza el estado central y dispara el renderizado.
 */
function actualizarEstadoDashboard(nuevoEstado) {
    Object.assign(dashboardState, nuevoEstado);
    renderizarDashboard();
}

/* ============================================================
   Renderizado principal
   ============================================================ */

/**
 * Decide qué renderizar según el estado actual.
 */
function renderizarDashboard() {
    const zonaIndicadores = document.getElementById('zona-indicadores');
    const zonaRecientes   = document.getElementById('zona-productos-recientes');

    if (dashboardState.cargando) {
        mostrarEstadoCarga();
        return;
    }

    if (dashboardState.error) {
        mostrarEstadoError(dashboardState.error);
        return;
    }

    if (dashboardState.datos) {
        // Mostrar las zonas
        zonaIndicadores.classList.remove('d-none');
        zonaRecientes.classList.remove('d-none');
        document.getElementById('zona-carga').classList.add('d-none');
        document.getElementById('zona-error').classList.add('d-none');

        renderizarIndicadores(dashboardState.datos);
        renderizarProductosRecientes(dashboardState.datos.productos_recientes || []);
    }
}

/* ============================================================
   Renderizado de indicadores
   ============================================================ */

function renderizarIndicadores(datos) {
    document.getElementById('kpi-total-productos').textContent =
        datos.total_productos ?? 0;

    document.getElementById('kpi-stock-total').textContent =
        new Intl.NumberFormat('es-BO').format(datos.stock_total ?? 0);

    document.getElementById('kpi-valor-inventario').textContent =
        formatearMoneda(datos.valor_inventario ?? 0);

    document.getElementById('kpi-stock-bajo').textContent =
        datos.productos_stock_bajo ?? 0;

    document.getElementById('kpi-sin-stock').textContent =
        datos.productos_sin_stock ?? 0;
}

/* ============================================================
   Renderizado de productos recientes
   ============================================================ */

function renderizarProductosRecientes(productos) {
    const tbody = document.getElementById('tabla-recientes-body');
    const tablaWrapper = document.getElementById('tabla-recientes-wrapper');
    const estadoVacio  = document.getElementById('estado-vacio-recientes');

    if (!productos || productos.length === 0) {
        tablaWrapper.classList.add('d-none');
        estadoVacio.classList.remove('d-none');
        return;
    }

    estadoVacio.classList.add('d-none');
    tablaWrapper.classList.remove('d-none');

    tbody.innerHTML = productos.map(p => {
        const estadoStock = obtenerEstadoStock(p.stock);
        return `
            <tr>
                <td class="text-muted">${p.id}</td>
                <td class="fw-medium">${escapeHtml(p.nombre)}</td>
                <td>${formatearMoneda(parseFloat(p.precio))}</td>
                <td>
                    <span class="badge bg-${estadoStock.clase}">${estadoStock.texto}</span>
                    <span class="ms-1 text-muted small">${p.stock}</span>
                </td>
                <td class="text-muted small">${formatearFecha(p.created_at)}</td>
            </tr>
        `;
    }).join('');
}

/* ============================================================
   Estados de interfaz
   ============================================================ */

function mostrarEstadoCarga() {
    document.getElementById('zona-carga').classList.remove('d-none');
    document.getElementById('zona-indicadores').classList.add('d-none');
    document.getElementById('zona-productos-recientes').classList.add('d-none');
    document.getElementById('zona-error').classList.add('d-none');
}

function mostrarEstadoError(mensaje) {
    document.getElementById('zona-carga').classList.add('d-none');
    document.getElementById('zona-indicadores').classList.add('d-none');
    document.getElementById('zona-productos-recientes').classList.add('d-none');

    const zonaError = document.getElementById('zona-error');
    zonaError.classList.remove('d-none');
    document.getElementById('error-mensaje').textContent = mensaje;

    // Botón reintentar
    const btnReintentar = document.getElementById('btn-reintentar');
    // Evitar acumular listeners
    const nuevoBtn = btnReintentar.cloneNode(true);
    btnReintentar.parentNode.replaceChild(nuevoBtn, btnReintentar);
    nuevoBtn.addEventListener('click', () => cargarResumenDashboard());
}

/* ============================================================
   Utilidades de formato
   ============================================================ */

/**
 * Formatea un número como moneda boliviana: Bs 18.500,50
 */
function formatearMoneda(valor) {
    const formateado = new Intl.NumberFormat('es-BO', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(valor);
    return `Bs ${formateado}`;
}

/**
 * Formatea una fecha ISO a formato legible: 29/7/2026 14:20
 */
function formatearFecha(fecha) {
    if (!fecha) return '—';
    try {
        return new Intl.DateTimeFormat('es-BO', {
            day: 'numeric',
            month: 'numeric',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        }).format(new Date(fecha));
    } catch (_e) {
        return '—';
    }
}

/**
 * Devuelve clase Bootstrap y texto según nivel de stock.
 */
function obtenerEstadoStock(stock) {
    if (stock === 0) {
        return { clase: 'danger', texto: 'Sin stock' };
    }
    if (stock >= 1 && stock <= 5) {
        return { clase: 'warning text-dark', texto: 'Stock bajo' };
    }
    return { clase: 'success', texto: 'Disponible' };
}

/**
 * Escapa HTML para prevenir XSS al insertar datos dinámicos.
 */
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

/* ============================================================
   Arranque
   ============================================================ */

document.addEventListener('DOMContentLoaded', () => {
    inicializarDashboard();
});
