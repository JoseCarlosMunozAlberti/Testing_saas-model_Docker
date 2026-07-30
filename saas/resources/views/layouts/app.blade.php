<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'PS Tenant')</title>

    {{-- Bootstrap 5.3 CSS --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet">

    {{-- Bootstrap Icons --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
          rel="stylesheet">

    {{-- Google Fonts: Inter --}}
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap"
          rel="stylesheet">

    {{-- CSS del proyecto --}}
    <link href="{{ asset('css/frontend.css') }}" rel="stylesheet">
</head>
<body>

    {{-- ===== Navegación autenticada ===== --}}
    @if($mostrarNavegacion ?? false)
    <nav class="navbar navbar-expand-lg ps-navbar" id="navbar-principal">
        <div class="container-fluid">
            <a class="navbar-brand" href="/dashboard">
                <i class="bi bi-building me-1"></i> PS Tenant
            </a>

            <button class="navbar-toggler border-0" type="button"
                    data-bs-toggle="collapse" data-bs-target="#navbarContenido"
                    aria-controls="navbarContenido" aria-expanded="false"
                    aria-label="Menú">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarContenido">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link {{ request()->is('dashboard') ? 'active' : '' }}"
                           href="/dashboard" id="nav-dashboard">
                            <i class="bi bi-speedometer2 me-1"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->is('productos') ? 'active' : '' }}"
                           href="/productos" id="nav-productos">
                            <i class="bi bi-box-seam me-1"></i> Productos
                        </a>
                    </li>
                </ul>

                <div class="d-flex align-items-center gap-3">
                    <div class="ps-user-info d-none d-md-block text-end" id="navbar-usuario-info">
                        <div><strong id="navbar-nombre-usuario">—</strong></div>
                        <small id="navbar-nombre-tenant">—</small>
                    </div>
                    <button class="btn btn-outline-light btn-sm" id="btn-logout" type="button">
                        <i class="bi bi-box-arrow-right me-1"></i> Cerrar sesión
                    </button>
                </div>
            </div>
        </div>
    </nav>
    @endif

    {{-- ===== Contenido principal ===== --}}
    @yield('content')

    {{-- ===== Contenedor global de Toasts ===== --}}
    <div class="toast-container position-fixed bottom-0 end-0 p-3 ps-toast-container"
         id="ps-toast-container"></div>

    {{-- ===== Scripts ===== --}}
    {{-- Bootstrap Bundle (Popper incluido) --}}
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    {{-- Módulos del proyecto (orden importa) --}}
    <script src="{{ asset('js/api-client.js') }}"></script>
    <script src="{{ asset('js/auth.js') }}"></script>
    <script src="{{ asset('js/ui.js') }}"></script>

    @stack('scripts')
</body>
</html>
