/**
 * PS Tenant – API Client
 * Punto único para todas las peticiones a la API REST.
 * No duplicar Fetch en otros archivos.
 */

const API_BASE = '/api';

/**
 * Realiza una petición a la API.
 *
 * @param {string}  endpoint  – ruta relativa (ej. '/login', '/productos')
 * @param {object}  opciones
 * @param {string}  [opciones.method='GET']
 * @param {object}  [opciones.body]         – se serializa a JSON automáticamente
 * @param {boolean} [opciones.esLogin=false] – true solo al ejecutar POST /api/login
 * @returns {Promise<any>} datos de respuesta
 */
async function apiRequest(endpoint, opciones = {}) {
    const { method = 'GET', body = null, esLogin = false } = opciones;

    // --- Cabeceras ---
    const headers = {
        'Accept': 'application/json',
    };

    if (body) {
        headers['Content-Type'] = 'application/json';
    }

    const token = obtenerToken();
    if (token) {
        headers['Authorization'] = `Bearer ${token}`;
    }

    // --- Petición ---
    const config = {
        method,
        headers,
    };

    if (body) {
        config.body = JSON.stringify(body);
    }

    let respuesta;
    try {
        respuesta = await fetch(`${API_BASE}${endpoint}`, config);
    } catch (_error) {
        throw {
            status: 0,
            message: 'No se pudo conectar con el servidor. Verifica tu conexión.',
            errors: {},
            data: null,
        };
    }

    // --- 204 No Content ---
    if (respuesta.status === 204) {
        return null;
    }

    // --- Parsear JSON (si hay contenido) ---
    let datos = null;
    const contentType = respuesta.headers.get('Content-Type') || '';
    if (contentType.includes('application/json')) {
        try {
            datos = await respuesta.json();
        } catch (_e) {
            datos = null;
        }
    }

    // --- 401 No autorizado ---
    if (respuesta.status === 401 && !esLogin) {
        eliminarSesion();
        window.location.href = '/login';
        throw {
            status: 401,
            message: 'Sesión expirada. Inicia sesión nuevamente.',
            errors: {},
            data: datos,
        };
    }

    // --- Error genérico ---
    if (!respuesta.ok) {
        throw {
            status: respuesta.status,
            message: datos?.message || `Error ${respuesta.status}`,
            errors: datos?.errors || {},
            data: datos,
        };
    }

    return datos;
}
