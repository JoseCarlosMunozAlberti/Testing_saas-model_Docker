/**
 * PS Tenant – Lógica de Login
 * Controla el formulario de inicio de sesión.
 */

document.addEventListener('DOMContentLoaded', async () => {
    // --- Si ya hay sesión válida, redirigir ---
    if (haySesion()) {
        const usuario = await validarSesion();
        if (usuario) {
            window.location.href = '/dashboard';
            return;
        }
    }

    // --- Referencias del DOM ---
    const formulario = document.getElementById('form-login');
    const inputEmail = document.getElementById('login-email');
    const inputPassword = document.getElementById('login-password');
    const btnSubmit = document.getElementById('btn-login');
    const btnTexto = document.getElementById('btn-login-texto');
    const btnSpinner = document.getElementById('btn-login-spinner');

    // --- Envío del formulario ---
    formulario.addEventListener('submit', async (e) => {
        e.preventDefault();

        // Limpiar errores anteriores
        limpiarErrores();

        // Deshabilitar botón y mostrar carga
        btnSubmit.disabled = true;
        btnTexto.classList.add('d-none');
        btnSpinner.classList.remove('d-none');

        try {
            const datos = await apiRequest('/login', {
                method: 'POST',
                body: {
                    email: inputEmail.value.trim(),
                    password: inputPassword.value,
                },
                esLogin: true,
            });

            // Guardar sesión (nunca la contraseña)
            guardarSesion(datos.token, datos.usuario);

            mostrarToast('Inicio de sesión exitoso', 'success');

            // Pequeña espera para que se vea el toast
            setTimeout(() => {
                window.location.href = '/dashboard';
            }, 500);

        } catch (error) {
            if (error.status === 422 && error.errors) {
                mostrarErroresValidacion(error.errors);
                mostrarToast(error.message || 'Revisa los campos del formulario.', 'danger');
            } else {
                mostrarToast(error.message || 'Ocurrió un error inesperado.', 'danger');
            }

            // Restaurar botón
            btnSubmit.disabled = false;
            btnTexto.classList.remove('d-none');
            btnSpinner.classList.add('d-none');
        }
    });
});

/* ---------- Helpers de validación ---------- */

/**
 * Muestra errores de validación debajo de los campos correspondientes.
 */
function mostrarErroresValidacion(errors) {
    for (const [campo, mensajes] of Object.entries(errors)) {
        const input = document.getElementById(`login-${campo}`);
        if (input) {
            input.classList.add('is-invalid');

            // El feedback está en el contenedor padre (.mb-3 / .mb-4), no dentro del input-group
            const contenedor = input.closest('.mb-3, .mb-4');
            if (contenedor) {
                let feedback = contenedor.querySelector('.invalid-feedback');
                if (!feedback) {
                    feedback = document.createElement('div');
                    feedback.className = 'invalid-feedback';
                    contenedor.appendChild(feedback);
                }
                feedback.textContent = Array.isArray(mensajes) ? mensajes[0] : mensajes;
                feedback.style.display = 'block';
            }
        }
    }
}

/**
 * Limpia todos los errores de validación del formulario.
 */
function limpiarErrores() {
    document.querySelectorAll('#form-login .is-invalid').forEach(el => {
        el.classList.remove('is-invalid');
    });
    document.querySelectorAll('#form-login .invalid-feedback').forEach(el => {
        el.textContent = '';
        el.style.display = '';
    });
}
