/**
 * PS Tenant – Módulo de Productos
 * Tabla interactiva, búsqueda con debounce, ordenamiento y paginación.
 * Utiliza apiRequest() de api-client.js exclusivamente.
 */

/* ============================================================
   Estado central
   ============================================================ */

const productosState = {
    cargando: true,
    error: null,
    productos: [],
    paginaActual: 1,
    ultimaPagina: 1,
    porPagina: 10,
    total: 0,
    desde: 0,
    hasta: 0,
    busqueda: '',
    ordenarPor: 'id',
    direccion: 'asc'
};

let debounceTimer = null;

/* ============================================================
   Inicialización
   ============================================================ */

/**
 * Inicialización principal de la vista de productos.
 */
async function inicializarProductos() {
    // 1. Validar sesión
    const usuario = await validarSesion();
    if (!usuario) {
        window.location.href = '/login';
        return;
    }

    // 2. Mostrar datos del usuario en navbar
    const nombre = usuario.name || '—';
    const tenant = usuario.tenant?.nombre_comercial || '—';

    const navNombre = document.getElementById('navbar-nombre-usuario');
    const navTenant = document.getElementById('navbar-nombre-tenant');
    if (navNombre) navNombre.textContent = nombre;
    if (navTenant) navTenant.textContent = tenant;

    // 3. Configurar evento Logout
    const btnLogout = document.getElementById('btn-logout');
    if (btnLogout) {
        btnLogout.addEventListener('click', () => cerrarSesion());
    }

    // 4. Configurar eventos de controles e interfaz
    configurarEventosProductos();

    // 5. Cargar primera lista de productos
    await cargarProductos();
}

/* ============================================================
   Eventos y Listeners
   ============================================================ */

/**
 * Asigna los event listeners a los controles de la página.
 */
function configurarEventosProductos() {
    // Búsqueda con debounce
    const inputBusqueda = document.getElementById('input-busqueda');
    if (inputBusqueda) {
        inputBusqueda.addEventListener('input', (e) => {
            configurarBusquedaConDebounce(e.target.value);
        });
    }

    // Botón Limpiar búsqueda
    const btnLimpiar = document.getElementById('btn-limpiar-busqueda');
    if (btnLimpiar) {
        btnLimpiar.addEventListener('click', () => {
            limpiarBusqueda();
        });
    }

    // Selector por página
    const selectPorPagina = document.getElementById('select-per-page');
    if (selectPorPagina) {
        selectPorPagina.addEventListener('change', (e) => {
            cambiarCantidadPorPagina(parseInt(e.target.value, 10));
        });
    }

    // Botón Actualizar
    const btnActualizar = document.getElementById('btn-actualizar-productos');
    if (btnActualizar) {
        btnActualizar.addEventListener('click', async () => {
            btnActualizar.disabled = true;
            btnActualizar.querySelector('.ps-btn-texto').classList.add('d-none');
            btnActualizar.querySelector('.ps-btn-spinner').classList.remove('d-none');

            await cargarProductos(true);

            btnActualizar.disabled = false;
            btnActualizar.querySelector('.ps-btn-texto').classList.remove('d-none');
            btnActualizar.querySelector('.ps-btn-spinner').classList.add('d-none');
        });
    }

    // Ordenamiento por encabezados de tabla
    const thsSortables = document.querySelectorAll('th[data-sort]');
    thsSortables.forEach(th => {
        th.addEventListener('click', () => {
            const columna = th.getAttribute('data-sort');
            if (columna) {
                cambiarOrdenamiento(columna);
            }
        });
    });
}

/**
 * Maneja la búsqueda en tiempo real con debounce (~400ms).
 */
function configurarBusquedaConDebounce(valor) {
    if (debounceTimer) {
        clearTimeout(debounceTimer);
    }

    debounceTimer = setTimeout(() => {
        productosState.busqueda = valor.trim();
        productosState.paginaActual = 1;
        cargarProductos();
    }, 400);
}

/**
 * Limpia el campo de búsqueda y restaura el listado completo.
 */
function limpiarBusqueda() {
    const inputBusqueda = document.getElementById('input-busqueda');
    if (inputBusqueda) {
        inputBusqueda.value = '';
    }
    productosState.busqueda = '';
    productosState.paginaActual = 1;
    cargarProductos();
}

/**
 * Cambia el ordenamiento por la columna especificada.
 */
function cambiarOrdenamiento(columna) {
    const columnasPermitidas = ['id', 'nombre', 'precio', 'stock', 'created_at'];
    if (!columnasPermitidas.includes(columna)) return;

    if (productosState.ordenarPor === columna) {
        productosState.direccion = productosState.direccion === 'asc' ? 'desc' : 'asc';
    } else {
        productosState.ordenarPor = columna;
        productosState.direccion = 'asc';
    }

    productosState.paginaActual = 1;
    cargarProductos();
}

/**
 * Cambia la página actual.
 */
function cambiarPagina(pagina) {
    if (pagina < 1 || pagina > productosState.ultimaPagina || pagina === productosState.paginaActual) {
        return;
    }
    productosState.paginaActual = pagina;
    cargarProductos();
}

/**
 * Cambia la cantidad de elementos mostrados por página.
 */
function cambiarCantidadPorPagina(cantidad) {
    const opcionesPermitidas = [5, 10, 25, 50];
    if (!opcionesPermitidas.includes(cantidad)) return;

    productosState.porPagina = cantidad;
    productosState.paginaActual = 1;
    cargarProductos();
}

/* ============================================================
   Carga de Datos (API)
   ============================================================ */

/**
 * Realiza la consulta GET /api/productos utilizando apiRequest().
 */
async function cargarProductos(esManual = false) {
    actualizarEstadoProductos({ cargando: true, error: null });

    const queryParams = new URLSearchParams();
    queryParams.set('page', productosState.paginaActual);
    queryParams.set('per_page', productosState.porPagina);
    queryParams.set('sort_by', productosState.ordenarPor);
    queryParams.set('sort_dir', productosState.direccion);

    if (productosState.busqueda) {
        queryParams.set('search', productosState.busqueda);
    }

    try {
        const respuesta = await apiRequest(`/productos?${queryParams.toString()}`);
        
        actualizarEstadoProductos({
            cargando: false,
            error: null,
            productos: respuesta.data || [],
            paginaActual: respuesta.current_page || 1,
            ultimaPagina: respuesta.last_page || 1,
            porPagina: respuesta.per_page || productosState.porPagina,
            total: respuesta.total || 0,
            desde: respuesta.from || 0,
            hasta: respuesta.to || 0
        });

        if (esManual) {
            mostrarToast('Productos actualizados correctamente.', 'success');
        }
    } catch (error) {
        if (error.status === 401) return;

        const mensaje = error.message || 'No se pudieron cargar los productos.';
        actualizarEstadoProductos({
            cargando: false,
            error: mensaje,
            productos: [],
            total: 0,
            desde: 0,
            hasta: 0
        });

        mostrarToast(mensaje, 'danger');
    }
}

/* ============================================================
   Gestión de Estado Central
   ============================================================ */

/**
 * Actualiza el estado central y dispara el renderizado.
 */
function actualizarEstadoProductos(nuevoEstado) {
    Object.assign(productosState, nuevoEstado);
    renderizarProductos();
}

/* ============================================================
   Renderizado Principal
   ============================================================ */

/**
 * Renderiza la vista de productos basándose en productosState.
 */
function renderizarProductos() {
    const zonaCarga = document.getElementById('zona-carga-productos');
    const zonaError = document.getElementById('zona-error-productos');
    const zonaContenido = document.getElementById('zona-contenido-productos');

    if (productosState.cargando) {
        mostrarEstadoCarga();
        return;
    }

    if (productosState.error) {
        mostrarEstadoError(productosState.error);
        return;
    }

    // Ocultar carga y error
    if (zonaCarga) zonaCarga.classList.add('d-none');
    if (zonaError) zonaError.classList.add('d-none');
    if (zonaContenido) zonaContenido.classList.remove('d-none');

    // Renderizar subcomponentes
    renderizarTablaProductos(productosState.productos);
    renderizarResumenResultados();
    renderizarPaginacion();
    actualizarIconosOrdenamiento();
}

/* ============================================================
   Subcomponentes de Renderizado
   ============================================================ */

/**
 * Renderiza el cuerpo de la tabla o los estados sin resultados / vacío.
 */
function renderizarTablaProductos(productos) {
    const tbody = document.getElementById('tabla-productos-body');
    const wrapperTabla = document.getElementById('tabla-productos-wrapper');
    const estadoVacio = document.getElementById('estado-vacio-productos');
    const estadoSinResultados = document.getElementById('estado-sin-resultados-productos');

    if (!tbody || !wrapperTabla || !estadoVacio || !estadoSinResultados) return;

    if (productos.length === 0) {
        wrapperTabla.classList.add('d-none');
        if (productosState.busqueda) {
            estadoVacio.classList.add('d-none');
            estadoSinResultados.classList.remove('d-none');
            const txtBusqueda = document.getElementById('texto-busqueda-fallida');
            if (txtBusqueda) txtBusqueda.textContent = productosState.busqueda;
        } else {
            estadoSinResultados.classList.add('d-none');
            estadoVacio.classList.remove('d-none');
        }
        return;
    }

    // Ocultar estados vacíos y mostrar tabla
    estadoVacio.classList.add('d-none');
    estadoSinResultados.classList.add('d-none');
    wrapperTabla.classList.remove('d-none');

    tbody.innerHTML = productos.map(p => {
        const estadoStock = obtenerEstadoStock(p.stock);
        return `
            <tr>
                <td class="text-muted fw-bold">${p.id}</td>
                <td class="fw-medium">${escapeHtml(p.nombre)}</td>
                <td class="fw-semibold text-dark">${formatearMoneda(parseFloat(p.precio))}</td>
                <td>${p.stock}</td>
                <td>
                    <span class="badge bg-${estadoStock.clase}">${estadoStock.texto}</span>
                </td>
                <td class="text-muted small">${formatearFecha(p.created_at)}</td>
                <td>
                    <div class="btn-group btn-group-sm" role="group">
                        <button type="button" class="btn btn-outline-secondary" disabled title="Editar (Próximamente)">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button type="button" class="btn btn-outline-danger" disabled title="Eliminar (Próximamente)">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
    }).join('');
}

/**
 * Renderiza el texto de resumen de resultados.
 */
function renderizarResumenResultados() {
    const elResumen = document.getElementById('texto-resumen-resultados');
    if (!elResumen) return;

    if (productosState.total === 0) {
        elResumen.textContent = 'Mostrando 0 resultados';
        return;
    }

    elResumen.textContent = `Mostrando ${productosState.desde} a ${productosState.hasta} de ${productosState.total} productos`;
}

/**
 * Renderiza los botones de paginación numéricos y controles Anterior/Siguiente.
 */
function renderizarPaginacion() {
    const elPaginacion = document.getElementById('ul-paginacion-productos');
    if (!elPaginacion) return;

    if (productosState.total === 0 || productosState.ultimaPagina <= 1) {
        elPaginacion.innerHTML = '';
        return;
    }

    const { paginaActual, ultimaPagina } = productosState;
    let html = '';

    // Botón Anterior
    const esPrimeroDeshabilitado = paginaActual === 1 ? 'disabled' : '';
    html += `
        <li class="page-item ${esPrimeroDeshabilitado}">
            <button class="page-link" onclick="cambiarPagina(${paginaActual - 1})" ${paginaActual === 1 ? 'tabindex="-1" aria-disabled="true"' : ''}>
                <i class="bi bi-chevron-left"></i> Anterior
            </button>
        </li>
    `;

    // Ventana de páginas (máximo 5 botones centrados en la actual)
    let inicio = Math.max(1, paginaActual - 2);
    let fin = Math.min(ultimaPagina, paginaActual + 2);

    if (paginaActual <= 3) {
        fin = Math.min(ultimaPagina, 5);
    }
    if (paginaActual > ultimaPagina - 3) {
        inicio = Math.max(1, ultimaPagina - 4);
    }

    if (inicio > 1) {
        html += `<li class="page-item"><button class="page-link" onclick="cambiarPagina(1)">1</button></li>`;
        if (inicio > 2) {
            html += `<li class="page-item disabled"><span class="page-link">…</span></li>`;
        }
    }

    for (let i = inicio; i <= fin; i++) {
        const esActiva = i === paginaActual ? 'active' : '';
        html += `
            <li class="page-item ${esActiva}">
                <button class="page-link" onclick="cambiarPagina(${i})">${i}</button>
            </li>
        `;
    }

    if (fin < ultimaPagina) {
        if (fin < ultimaPagina - 1) {
            html += `<li class="page-item disabled"><span class="page-link">…</span></li>`;
        }
        html += `<li class="page-item"><button class="page-link" onclick="cambiarPagina(${ultimaPagina})">${ultimaPagina}</button></li>`;
    }

    // Botón Siguiente
    const esUltimoDeshabilitado = paginaActual === ultimaPagina ? 'disabled' : '';
    html += `
        <li class="page-item ${esUltimoDeshabilitado}">
            <button class="page-link" onclick="cambiarPagina(${paginaActual + 1})" ${paginaActual === ultimaPagina ? 'tabindex="-1" aria-disabled="true"' : ''}>
                Siguiente <i class="bi bi-chevron-right"></i>
            </button>
        </li>
    `;

    elPaginacion.innerHTML = html;
}

/**
 * Actualiza los iconos indicador de dirección de orden en las columnas de la tabla.
 */
function actualizarIconosOrdenamiento() {
    const ths = document.querySelectorAll('th[data-sort]');
    ths.forEach(th => {
        const columna = th.getAttribute('data-sort');
        const icono = th.querySelector('.ps-sort-icon');
        if (!icono) return;

        if (columna === productosState.ordenarPor) {
            th.classList.add('table-active');
            icono.className = `bi ps-sort-icon ms-1 ${productosState.direccion === 'asc' ? 'bi-arrow-up' : 'bi-arrow-down'}`;
        } else {
            th.classList.remove('table-active');
            icono.className = 'bi bi-arrow-down-up ps-sort-icon ms-1 text-muted opacity-50';
        }
    });
}

/* ============================================================
   Estados de Interfaz
   ============================================================ */

function mostrarEstadoCarga() {
    const zonaCarga = document.getElementById('zona-carga-productos');
    const zonaError = document.getElementById('zona-error-productos');
    const zonaContenido = document.getElementById('zona-contenido-productos');

    if (zonaCarga) zonaCarga.classList.remove('d-none');
    if (zonaError) zonaError.classList.add('d-none');
    if (zonaContenido) zonaContenido.classList.add('d-none');
}

function mostrarEstadoError(mensaje) {
    const zonaCarga = document.getElementById('zona-carga-productos');
    const zonaError = document.getElementById('zona-error-productos');
    const zonaContenido = document.getElementById('zona-contenido-productos');

    if (zonaCarga) zonaCarga.classList.add('d-none');
    if (zonaContenido) zonaContenido.classList.add('d-none');
    if (zonaError) {
        zonaError.classList.remove('d-none');
        const msgEl = document.getElementById('error-mensaje-productos');
        if (msgEl) msgEl.textContent = mensaje;
    }

    // Botón reintentar
    const btnReintentar = document.getElementById('btn-reintentar-productos');
    if (btnReintentar) {
        const nuevoBtn = btnReintentar.cloneNode(true);
        btnReintentar.parentNode.replaceChild(nuevoBtn, btnReintentar);
        nuevoBtn.addEventListener('click', () => cargarProductos());
    }
}

/* ============================================================
   Utilidades de Formato y Seguridad
   ============================================================ */

/**
 * Formatea un valor numérico a Moneda Boliviana (Bs 72,50).
 */
function formatearMoneda(valor) {
    const formateado = new Intl.NumberFormat('es-BO', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(valor);
    return `Bs ${formateado}`;
}

/**
 * Formatea una fecha ISO a representación legible.
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
 * Devuelve estado e indicación de badge para el stock.
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
 * Escapa strings dinámicos para evitar ataques XSS al usar innerHTML.
 */
function escapeHtml(str) {
    if (!str) return '';
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

/* ============================================================
   Arranque único al cargar DOM
   ============================================================ */

document.addEventListener('DOMContentLoaded', () => {
    inicializarProductos();
});
