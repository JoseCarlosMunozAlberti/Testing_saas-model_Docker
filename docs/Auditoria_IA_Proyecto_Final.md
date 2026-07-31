# Documento de Auditoría de IA y Decisiones Técnicas

**Proyecto Capstone Final:** SaaS Multitenant con Laravel 13, Blade, Bootstrap 5.3, QR y Salesforce  
**Fecha:** Julio 2026  
**Integrantes:**
- José Carlos Muñoz Alberti
- Leonardo David Vargas Monasterio

---

## Declaración de Honestidad Académica

Este documento registra las decisiones arquitectónicas y técnicas adoptadas durante la construcción del proyecto final SaaS multitenant. Documenta la interacción con herramientas de IA asistida (Gemini, Claude, Antigravity) especificando el criterio de evaluación y aceptación por parte de cada integrante del equipo.

> **Instrucciones para los integrantes:**  
> Cada integrante debe revisar y validar personalmente los apartados asignados con su nombre antes de la entrega final.

---

## Decisiones Técnicas y de Arquitectura Evaluadas

### 1. Modelo de Aislamiento Multitenant con Base Compartida
- **Tarea realizada con IA:** Generación del Trait de filtrado e intersección en Eloquent.
- **Herramienta utilizada:** Antigravity / Claude.
- **Instrucción o propósito:** Generar un mecanismo automático para aplicar `WHERE tenant_id = ?` en todas las consultas Eloquent de la aplicación.
- **Propuesta generada:** Aplicar un Global Scope para filtrar las consultas Eloquent por tenant_id y utilizar eventos del modelo para asignar automáticamente el tenant_id durante la creación de registros.
- **Archivo afectado:** `saas/app/Traits/Multitenant.php`
- **Decisión humana:**
  - **Revisado por:** José Carlos Muñoz Alberti.
  - **Lo aceptado:** Se aceptó el Trait `Multitenant` ya que desacopla la lógica de filtrado de los controladores.
  - **Lo modificado:** Se aseguró que al registrar ventas y productos, el `tenant_id` se asigne en el backend directamente del usuario autenticado vía `Auth::user()->tenant_id` sin confiar en ningún valor enviado desde el navegador.
  - **Lo descartado:** Se descartó el modelo de bases de datos físicas separadas por tenant para mantener una arquitectura ligera.
  - **Resultado:** Aislamiento multitenant implementado en el backend y validado mediante pruebas automatizadas.
  - **Riesgo detectado:** Posibilidad de inyección de `tenant_id` en formularios HTML.
  - **Corrección aplicada:** Eliminación total de inputs `tenant_id` en vistas Blade y desestimación del campo si se recibe en el payload del frontend.
  - **Evidencia de validación:** Pruebas de Feature automatizadas `ProductoMultitenantTest` y `VentaMultitenantTest` ejecutadas con éxito en entorno de prueba aislado.

---

### 2. Frontend con Laravel Blade y JavaScript Moderno en Lugar de Framework SPA
- **Tarea realizada con IA:** Generación de módulos de estado JavaScript y componentes accesibles Blade.
- **Herramienta utilizada:** Antigravity / Claude.
- **Instrucción o propósito:** Crear una interfaz interactiva y reactiva sin dependencias de frameworks pesados (React, Angular, Vue).
- **Propuesta generada:** Usar Laravel Blade complementado con Bootstrap 5.3 por CDN y módulos de JavaScript vanila con estado centralizado (`productosState`, `ventasState`).
- **Archivos afectados:** `saas/resources/views/layouts/app.blade.php`, `saas/public/js/productos.js`, `saas/public/js/ventas.js`
- **Decisión humana:**
  - **Revisado por:** Leonardo David Vargas Monasterio.
  - **Lo aceptado:** La estructura basada en un layout maestro (`app.blade.php`), clientes de API encapsulados (`api-client.js`) y módulos JS por vista.
  - **Lo modificado:** Se añadió sanitización dinámica con `escapeHtml()` para prevenir vulnerabilidades XSS en concatenaciones HTML e indicadores accesibles con atributos WAI-ARIA (`aria-current`, `aria-sort`).
  - **Lo descartado:** Se descartó el uso de Angular, React o Vue y compilación Vite/NPM para esta fase.
  - **Resultado:** Carga ultrarrápida y experiencia reactiva sin bundlers complejos.
  - **Riesgo detectado:** Vulnerabilidades XSS por renderizado dinámico con `innerHTML`.
  - **Corrección aplicada:** Implementación de la función utilitaria `escapeHtml()` para sanear todo valor recibido de la API antes de insertarlo en el DOM.
  - **Evidencia de validación:** Validación estática `node --check` sobre todos los archivos `.js` públicos.

---

### 3. Flujo Transaccional de Venta y Pago QR Idempotente
- **Tarea realizada con IA:** Diseño de los controladores `VentaController` y `PagoController` con transacciones de base de datos.
- **Herramienta utilizada:** Antigravity / Claude.
- **Instrucción o propósito:** Implementar cobro QR con confirmación bancaria simulada y descuento único de inventario.
- **Propuesta generada:** Usar `DB::transaction()` con bloqueo pesimista `lockForUpdate()` en dos fases: creación de venta pendiente sin afectar inventario, y confirmación de pago por referencia única que descuenta stock una sola vez.
- **Archivos afectados:** `saas/app/Http/Controllers/VentaController.php`, `saas/app/Http/Controllers/PagoController.php`
- **Decisión humana:**
  - **Revisado por:** José Carlos Muñoz Alberti.
  - **Lo aceptado:** La separación del flujo en 2 pasos: `POST /api/ventas` (creación pendiente) y `POST /api/pagos/confirmar` (pago e inventario).
  - **Lo modificado:** Se añadió lógica de idempotencia explícita: si una venta ya cuenta con estado `pagada`, la confirmación responde HTTP 200 con la bandera `idempotente: true` sin reejecutar el descuento de stock.
  - **Lo descartado:** Descontar stock durante la creación inicial de la venta.
  - **Resultado:** Transacciones atómicas seguras e idénticas ante reintentos.
  - **Riesgo detectado:** Doble descuento de inventario si se presiona "Confirmar" múltiples veces.
  - **Corrección aplicada:** Verificación de estado `pagada` dentro del bloque pesimista `lockForUpdate()`.
  - **Evidencia de validación:** Test unitario `test_generar_qr_y_confirmar_pago_descuenta_stock` en `VentaMultitenantTest`.

---

### 4. Integración Tolerante a Fallos con Salesforce (OAuth 2.0 Client Credentials)
- **Tarea realizada con IA:** Migración de `SalesforceService` de OAuth Password Grant a Client Credentials Grant.
- **Herramienta utilizada:** Antigravity / Claude.
- **Instrucción o propósito:** Sincronizar las ventas pagadas como `Opportunity` en Salesforce usando la External Client App sin exponer credenciales de usuario.
- **Propuesta generada:** Inicialmente se propuso OAuth Password Grant (`username`/`password`). Posteriormente se actualizó a OAuth 2.0 Client Credentials Grant (`grant_type=client_credentials`) leyendo la configuración desde `config('services.salesforce')`.
- **Archivos afectados:** `saas/app/Services/SalesforceService.php`, `saas/config/services.php`
- **Decisión humana:**
  - **Revisado por:** Pendiente de validación humana final por José Carlos Muñoz Alberti y Leonardo David Vargas Monasterio.
  - **Lo aceptado:** La eliminación completa del flujo Password Grant y de las propiedades `$username`/`$password`, así como la asignación de estados `sincronizada`, `fallida` y `deshabilitada`.
  - **Lo modificado:** Se verificó manualmente via Postman y con la External Client App de Salesforce la respuesta 200 OK y la creación exitosa de la Opportunity. La sincronización con Salesforce se ejecuta después del commit de la transacción local. Los errores de Salesforce no revierten el pago ni el descuento de stock.
  - **Lo descartado:** Invocar la API de Salesforce dentro de la transacción `DB::transaction()` local.
  - **Resultado:** Integración M2M (Machine-to-Machine) tolerante a fallos que no revierte la venta local en caso de caídas externas.
  - **Riesgo detectado:** Bloqueo del cobro de venta en caso de timeout de red con Salesforce si estuviera dentro del bloque transaccional.
  - **Corrección aplicada:** Ejecución de `SalesforceService::sincronizarVenta()` en un bloque `try/catch` posterior al commit de la base de datos local.
  - **Evidencia de validación:** Test `test_salesforce_mock_cuando_sf_enabled_true` con `Http::fake()` y aserciones en `VentaMultitenantTest`.

---

### 5. Arquitectura de Despliegue con Docker Compose (5 Servicios)
- **Tarea realizada with IA:** Definición del archivo `docker-compose.yml` y configuración del Reverse Proxy Nginx.
- **Herramienta utilizada:** Antigravity / Claude.
- **Instrucción o propósito:** Configurar un entorno reproducible de 5 servicios (`app`, `db`, `redis`, `frontend`, `localstack`).
- **Propuesta generada:** Configurar 5 servicios en `docker-compose.yml`: `app` (PHP 8.5/Laravel), `db` (MySQL 8), `redis` (Redis 7), `frontend` (Nginx Reverse Proxy en 4200) y `localstack` (LocalStack 4.14.0 en 4566).
- **Archivos afectados:** `docker-compose.yml`, `frontend/Dockerfile`, `frontend/nginx.conf`
- **Decisión humana:**
  - **Revisado por:** Pendiente de validación humana final por José Carlos Muñoz Alberti y Leonardo David Vargas Monasterio.
  - **Lo aceptado:** La especificación de los 5 contenedores con healthchecks explícitos (`service_healthy`).
  - **Lo modificado:** Se corrigieron los encabezados de Nginx (`proxy_set_header Host $http_host;`) para preservar el origen `localhost:4200` y evitar redirecciones indeseadas al puerto 8000.
  - **Lo descartado:** Levantar un contenedor de Angular o Node para la capa frontend.
  - **Resultado:** Entorno multi-contenedor reproducible y funcional en ambos puertos (4200 y 8000).
  - **Riesgo detectado:** Pérdida de cabecera `Authorization` o redirecciones HTTP 302 incorrectas en el puerto 4200.
  - **Corrección aplicada:** Ajuste fino en `frontend/nginx.conf` con `proxy_set_header X-Forwarded-Host $http_host;` y `proxy_redirect off;`.
  - **Evidencia de validación:** Ejecución exitosa de `docker compose config` y `docker compose ps` reportando los 5 servicios activos.

---

## Verificación por los Integrantes

- [ ] **Pendiente de validación humana final por José Carlos Muñoz Alberti** — Revisión de aislamiento multitenant, transacciones DB de ventas/pagos y suite de pruebas.
- [ ] **Pendiente de validación humana final por Leonardo David Vargas Monasterio** — Revisión del frontend Blade/JS, consumo de API, SalesforceService y entorno Docker.
