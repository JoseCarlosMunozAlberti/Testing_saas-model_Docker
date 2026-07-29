/**
 * PS Tenant – Utilidades de UI
 * Funciones compartidas de interfaz (toasts, etc.).
 */

/**
 * Muestra un Toast de Bootstrap dentro del contenedor global.
 *
 * @param {string} mensaje – texto a mostrar
 * @param {'success'|'danger'|'warning'|'info'} tipo – variante de Bootstrap
 */
function mostrarToast(mensaje, tipo = 'info') {
    const contenedor = document.getElementById('ps-toast-container');
    if (!contenedor) return;

    // Iconos según tipo
    const iconos = {
        success: 'bi-check-circle-fill',
        danger:  'bi-exclamation-triangle-fill',
        warning: 'bi-exclamation-circle-fill',
        info:    'bi-info-circle-fill',
    };

    const icono = iconos[tipo] || iconos.info;

    const id = 'toast-' + Date.now();

    const html = `
        <div id="${id}" class="toast align-items-center text-bg-${tipo} border-0" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body">
                    <i class="bi ${icono} me-2"></i>${mensaje}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Cerrar"></button>
            </div>
        </div>
    `;

    contenedor.insertAdjacentHTML('beforeend', html);

    const toastEl = document.getElementById(id);
    const bsToast = new bootstrap.Toast(toastEl, { delay: 4000 });
    bsToast.show();

    // Limpiar del DOM al ocultarse
    toastEl.addEventListener('hidden.bs.toast', () => toastEl.remove());
}
