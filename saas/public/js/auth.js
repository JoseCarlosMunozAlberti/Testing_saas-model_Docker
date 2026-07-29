/**
 * PS Tenant – Autenticación / gestión de sesión
 * Trabaja con localStorage y se apoya en api-client.js.
 */

const CLAVE_TOKEN   = 'saas_token';
const CLAVE_USUARIO = 'saas_usuario';

/* ---------- Funciones de almacenamiento ---------- */

/**
 * Guarda token y datos de usuario tras un login exitoso.
 */
function guardarSesion(token, usuario) {
    localStorage.setItem(CLAVE_TOKEN, token);
    localStorage.setItem(CLAVE_USUARIO, JSON.stringify(usuario));
}

/**
 * Devuelve el token almacenado o null.
 */
function obtenerToken() {
    return localStorage.getItem(CLAVE_TOKEN);
}

/**
 * Devuelve el objeto usuario almacenado o null.
 */
function obtenerUsuarioGuardado() {
    const raw = localStorage.getItem(CLAVE_USUARIO);
    if (!raw) return null;
    try {
        return JSON.parse(raw);
    } catch (_e) {
        return null;
    }
}

/**
 * Elimina toda la información de sesión local.
 */
function eliminarSesion() {
    localStorage.removeItem(CLAVE_TOKEN);
    localStorage.removeItem(CLAVE_USUARIO);
}

/**
 * Comprueba si hay un token guardado (sin validar con la API).
 */
function haySesion() {
    return !!obtenerToken();
}

/* ---------- Funciones con la API ---------- */

/**
 * Valida la sesión consultando GET /api/usuario.
 * Devuelve el objeto usuario si es válida, o false si no lo es.
 */
async function validarSesion() {
    if (!haySesion()) {
        return false;
    }

    try {
        const usuario = await apiRequest('/usuario');
        // Actualizar datos locales con la información más reciente
        localStorage.setItem(CLAVE_USUARIO, JSON.stringify(usuario));
        return usuario;
    } catch (error) {
        if (error.status === 401) {
            eliminarSesion();
        }
        return false;
    }
}

/**
 * Cierra la sesión: llama POST /api/logout y limpia siempre la sesión local.
 */
async function cerrarSesion() {
    try {
        await apiRequest('/logout', { method: 'POST' });
    } catch (_e) {
        // Aunque falle, siempre limpiar
    }
    eliminarSesion();
    window.location.href = '/login';
}
