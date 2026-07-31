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
- **Problema/Requisito:** Implementar aislamiento estricto de datos entre empresas (SALQUI y Gran Palacio) previniendo fuga de información en un modelo SaaS.
- **Propuesta de la IA:** Aplicar `Global Scope` en Eloquent acoplado a un `Trait Multitenant` reutilizable en los modelos que intercepte automáticamente todas las consultas `SELECT`, `INSERT`, `UPDATE` y `DELETE`.
- **Revisión del equipo:**
  - **Revisado por:** José Carlos Muñoz Alberti.
  - **Lo aceptado:** Se aceptó el Trait `Multitenant` ya que desacopla la lógica de filtrado de los controladores y garantiza que las consultas Eloquent agreguen implícitamente `WHERE tenant_id = ?`.
  - **Lo modificado:** Se aseguró que al registrar ventas y productos, el `tenant_id` se asigne en el backend directamente del usuario autenticado vía `Auth::user()->tenant_id` sin confiar en ningún valor enviado desde el navegador.
  - **Lo descartado:** Se descartó el modelo de bases de datos físicas separadas por tenant para mantener una arquitectura ligera en esta fase.
  - **Justificación técnica:** Menor costo de infraestructura y menor complejidad operativa.
  - **Evidencia de validación:** Pruebas de Feature automatizadas `ProductoMultitenantTest` y `VentaMultitenantTest` ejecutadas con éxito en entorno de prueba aislado.

---

### 2. Frontend con Laravel Blade y JavaScript Moderno en Lugar de Framework SPA
- **Problema/Requisito:** Crear un frontend interactivo, reactivo y moderno cumpliendo los requerimientos del proyecto sin introducir sobrecargas de compilación.
- **Propuesta de la IA:** Usar Laravel Blade complementado con Bootstrap 5.3 por CDN y módulos de JavaScript vanila con estado centralizado (`productosState`, `ventasState`).
- **Revisión del equipo:**
  - **Revisado por:** Leonardo David Vargas Monasterio.
  - **Lo aceptado:** La estructura basada en un layout maestro (`app.blade.php`), clientes de API encapsulados (`api-client.js`) y módulos JS por vista.
  - **Lo modificado:** Se añadió sanitización dinámica con `escapeHtml()` para prevenar vulnerabilidades XSS en concatenaciones HTML e indicadores accesibles con atributos WAI-ARIA (`aria-current`, `aria-sort`).
  - **Lo descartado:** Se descartó el uso de Angular, React o Vue y compilación Vite/NPM para esta fase.
  - **Justificación técnica:** Menor complejidad de empaquetado, tiempo de carga ultrarrápido y compatibilidad directa con las directivas de Blade.

---

### 3. Flujo Transaccional de Venta y Pago QR Idempotente
- **Problema/Requisito:** Registrar ventas en estado pendiente y confirmar el cobro descontando el stock sin generar dobles descuentos por solicitudes duplicadas.
- **Propuesta de la IA:** Usar `DB::transaction()` con bloqueo pesimista `lockForUpdate()` en dos fases: creación de venta pendiente sin afectar inventario, y confirmación de pago por referencia única que descuenta stock una sola vez.
- **Revisión del equipo:**
  - **Revisado por:** José Carlos Muñoz Alberti.
  - **Lo aceptado:** La separación del flujo en 2 pasos: `POST /api/ventas` (creación pendiente) y `POST /api/pagos/confirmar` (pago e inventario).
  - **Lo modificado:** Se añadió lógica de idempotencia explícita: si una venta ya cuenta con estado `pagada`, la confirmación responde HTTP 200 con la bandera `idempotente: true` sin reejecutar el descuento de stock.
  - **Justificación técnica:** Previene condiciones de carrera (race conditions) y garantiza consistencia del inventario en entornos de alta concurrencia.

---

### 4. Integración Asíncrona / No Bloqueante con Salesforce (OAuth 2.0 Client Credentials)
- **Problema/Requisito:** Enviar la venta pagada a Salesforce como `Opportunity` (`Closed Won`) mediante OAuth 2.0 Client Credentials Grant (versión API `v67.0`) sin requerir credenciales personales de usuario (`username`/`password`) ni bloquear la transacción de pago local.
- **Propuesta de la IA:** Actualizar `SalesforceService` para usar exclusivamente `config('services.salesforce')`, realizar la autenticación con `grant_type=client_credentials` enviando `client_id` y `client_secret`, y crear la `Opportunity` usando el Bearer Token obtenido.
- **Revisión del equipo:**
  - **Revisado por:** Pendiente de revisión humana final por José Carlos Muñoz Alberti y Leonardo David Vargas Monasterio.
  - **Lo aceptado:** La eliminación completa del flujo Password Grant y de las propiedades `$username`/`$password`, así como la asignación de estados `sincronizada`, `fallida` y `deshabilitada`.
  - **Lo modificado:** Se incorporaron aserciones estrictas en `VentaMultitenantTest` mediante `Http::assertSent()` para verificar que la petición enviada contenga únicamente `grant_type=client_credentials` y no exponga contraseñas en logs ni payloads.
  - **Justificación técnica:** Sigue los estándares modernos de integración M2M (Machine-to-Machine) recomendados por Salesforce para External Client Apps, eliminando la dependencia de usuarios individuales.

---

### 5. Arquitectura de Despliegue con Docker Compose (5 Servicios)
- **Problema/Requisito:** Definir un entorno multi-contenedor estándar para desarrollo e integración continua.
- **Propuesta de la IA:** Configurar 5 servicios en `docker-compose.yml`: `app` (PHP 8.5/Laravel), `db` (MySQL 8), `redis` (Redis 7), `frontend` (Nginx Reverse Proxy en 4200) y `localstack` (servicios de AWS simulados).
- **Revisión del equipo:**
  - **Revisado por:** Ambos integrantes.
  - **Lo aceptado:** La especificación de los 5 contenedores con healthchecks explícitos (`service_healthy`).
  - **Lo modificado:** El servicio `frontend` se configuró con un `Dockerfile` ligero de Nginx que redirige las peticiones al contenedor `app` manteniendo el mismo origen.
  - **Justificación técnica:** Permite probar el acceso por el puerto 4200 y el puerto 8000 en un entorno reproducible sin dependencias locales adicionales.

---

## Verificación por los Integrantes

- [ ] **Validado por José Carlos Muñoz Alberti** — Revisión de aislamiento multitenant, transacciones DB de ventas/pagos y suite de pruebas.
- [ ] **Validado por Leonardo David Vargas Monasterio** — Revisión del frontend Blade/JS, consumo de API, SalesforceService y entorno Docker.
