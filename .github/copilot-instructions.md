# Instrucciones para Agentes de IA

## Arquitectura del Proyecto

Sistema de gestión presupuestaria agrícola construido con:
- **Backend**: Laravel 10 + Inertia.js + Jetstream + Spatie Permissions
- **Frontend**: Vue 3 + PrimeVue + TailwindCSS + Bootstrap 5 (modales) + Chart.js
- **Stack**: PHP 8.1+, MySQL, Vite
- **Estructura**: `app/Http/Controllers/` (lógica), `app/Models/` (Eloquent), `resources/js/Pages/` (vistas), `resources/js/Components/` (componentes reutilizables)
- **Ubicación**: Chile - Usar moneda **CLP** (Pesos Chilenos) con símbolo **$**
- **JavaScript**: NO se usa jQuery. Usar Bootstrap 5 nativo y JavaScript vanilla.

## Contexto de Negocio

**Filtrado por temporada y equipo**: Toda entidad (inversiones, presupuestos, servicios, agroquímicos, etc.) DEBE filtrarse por `season_id` (sesión) y `team_id` (usuario). El usuario selecciona su temporada activa al inicio de sesión (mal llamado "select-budget"). Esta regla es CRÍTICA.

**Jerarquía de niveles**: `Level1` → `Level2` → `Level3` → `Level4`. Solo `Level1` tiene `team_id`. Para filtrar entidades en niveles inferiores, usar relaciones:

```php
// ❌ INCORRECTO: Level3 no tiene team_id directamente
$level3s = Level3::where('team_id', $teamId)->get();

// ✅ CORRECTO: Filtrar a través de las relaciones
$level3s = Level3::whereHas('level2.level1', function($query) use ($teamId) {
    $query->where('team_id', $teamId);
})->get();
```

**Productos**: Los productos SÍ tienen `team_id` directo además de `level3_id`.

**Combustibles**: Para obtener productos de combustible del equipo:
1. Buscar `Level3` con nombre 'combustible' que pertenezcan al team (vía level2.level1)
2. Filtrar productos por esos level3_ids Y por team_id del usuario

## Patrones de Controladores

**Controladores de acción única**: Cada entidad principal tiene su carpeta con un controlador por acción:
- `app/Http/Controllers/FuelOutflows/StoreFuelOutflowController.php`
- `app/Http/Controllers/FuelOutflows/UpdateFuelOutflowController.php`
- `app/Http/Controllers/FuelOutflows/DeleteFuelOutflowController.php`
- Patrón: `CreateEntidadController.php`, `StoreEntidadController.php`, `EditEntidadController.php`, `UpdateEntidadController.php`, `DeleteEntidadController.php`, `ShowEntidadController.php`

**Controlador principal**: `FuelOutflowController@index` orquesta vistas principales, catálogos, endpoints agregados y reportes. NO duplica lógica CRUD. Sirve para carga de vistas, lógica especial y coordinación.

**FormRequests dedicados**: Cada flujo debe tener su FormRequest para centralizar validaciones (ej. `FormFuelOutflowRequest.php`). Usa `StoreXRequest` y `UpdateXRequest` para acciones específicas.

## Convenciones de Rutas (web.php)

**Sintaxis moderna (REQUERIDA)**: Usar controladores de acción única sin arrays:

```php
// ✅ CORRECTO: Sintaxis moderna limpia
use App\Http\Controllers\InvoicePayments\InvoicePaymentController;
use App\Http\Controllers\InvoicePayments\StoreInvoicePaymentController;

Route::get('/invoice-payments', InvoicePaymentController::class)->name('invoice-payments.index');
Route::post('/invoice-payments', StoreInvoicePaymentController::class)->name('invoice-payments.store');

// ❌ INCORRECTO: Sintaxis antigua con arrays
Route::get('/invoice-payments', [InvoicePaymentController::class, 'index'])->name('invoice-payments.index');

// ❌ INCORRECTO: Namespaces completos
Route::post('/invoice-payments', \App\Http\Controllers\InvoicePayments\StoreInvoicePaymentController::class);
```

**Reglas obligatorias**:
1. **Importar TODOS los controladores** en la parte superior de web.php
2. **NO usar namespaces completos** en las rutas (ej. `\App\Http\Controllers\...`)
3. **Sintaxis según tipo de controlador**:
   - **Controladores de acción única** (con `__invoke()`): usar `Controller::class` sin array
   - **Controladores principales/orquestadores** (con método `index()`): usar `[Controller::class, 'index']`
   - **Métodos auxiliares específicos**: usar `[Controller::class, 'metodo']` (ej. `'import'`, `'template'`, `'searchInvoices'`)
4. **Nombres consistentes**: usar `.delete` en lugar de `.destroy` para consistencia

**Ejemplos**:
```php
// ✅ Controlador de acción única (tiene __invoke)
Route::post('/invoice-payments', StoreInvoicePaymentController::class);

// ✅ Controlador principal (tiene método index)
Route::get('/outflows-dashboard', [OutflowsDashboardController::class, 'index']);

// ✅ Método auxiliar específico
Route::get('/api/invoices/search', [InvoicePaymentController::class, 'searchInvoices']);
```

**Organización de imports**: Agrupar imports por módulo con comentario descriptivo:
```php
// Rutas para Invoice Payments
use App\Http\Controllers\InvoicePayments\InvoicePaymentController;
use App\Http\Controllers\InvoicePayments\InvoicePaymentDashboardController;
use App\Http\Controllers\InvoicePayments\StoreInvoicePaymentController;
use App\Http\Controllers\InvoicePayments\UpdateInvoicePaymentController;
use App\Http\Controllers\InvoicePayments\DeleteInvoicePaymentController;
```

## Convenciones Frontend

**Selects**: Usar SIEMPRE `@vueform/multiselect`, nunca selects nativos ni otros plugins. Ejemplo:
```vue
import Multiselect from '@vueform/multiselect';
```

**Modales y formularios**: Los modales (ej. `CreateFuelOutflowModal.vue`, `EditXModal.vue`) solo manejan header/footer/slots. El formulario real (inputs, selects, lógica de negocio) va en componente separado (ej. `XForm.vue`). El modal siempre debe estar montado y abrirse/cerrarse con Bootstrap 5 nativo:

```javascript
// Abrir modal
const modalElement = document.getElementById('modalId');
const modal = new bootstrap.Modal(modalElement);
modal.show();

// Cerrar modal
const modalInstance = bootstrap.Modal.getInstance(modalElement);
if (modalInstance) {
    modalInstance.hide();
}
```

Esto permite máxima escalabilidad, reutilización y consistencia entre módulos.

**Confirmaciones**: Usar SweetAlert (`Swal.fire`) en TODA operación de guardado/edición, siguiendo el estándar visual del sistema.

**Formato Visual de Vistas Principales**: TODAS las vistas principales (Index) deben seguir el patrón estándar del sistema:

```vue
<template>
    <AppLayout title="[Título]">
        <div class="card my-3">
            <div class="card-header">
                <div class="row flex-between-center">
                    <div class="col-6 col-sm-auto d-flex align-items-center pe-0">
                        <h5 class="fs-9 mb-0 text-nowrap py-2 py-xl-0">
                            <i class="fas fa-[icono] me-2"></i>[Título]
                        </h5>
                    </div>
                    <div class="col-6 col-sm-auto ms-auto text-end ps-0">
                        <div class="d-flex align-items-center gap-2">
                            <!-- Botones de acción -->
                            <ExportExcelButton 
                                class="btn btn-falcon-default btn-sm"
                            />
                            <button class="btn btn-falcon-default btn-sm">
                                <span class="fas fa-plus" data-fa-transform="shrink-3 down-2"></span>
                                <span class="d-none d-sm-inline-block ms-1">Nuevo</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-body bg-body-tertiary">
                <!-- Contenido: tablas, pestañas, etc. -->
            </div>
        </div>
    </AppLayout>
</template>
```

**Reglas del formato:**
- ✅ Título y botones DENTRO del `card-header`
- ✅ Todos los botones usan `btn-falcon-default` (sin colores)
- ✅ Estructura: `card` > `card-header` + `card-body bg-body-tertiary`
- ✅ Iconos en el título con clase `me-2`
- ✅ Botones con iconos usando `data-fa-transform="shrink-3 down-2"`
- ❌ NO usar colores en botones (no `btn-primary`, `btn-danger`, etc.)
- ❌ NO poner título fuera del card
- ❌ NO usar `container-fluid` con rows para el layout principal

**Exportación Excel**: Al usar `ExportExcelButton`, exportar números puros (sin `toLocaleString`). Excel aplica formato automático con separador de miles.

```javascript
// ✅ CORRECTO: Exportar números puros
const excelData = computed(() => {
php artisan test         # Ejecutar tests PHPUnit
```

**Testing**: PHPUnit configurado (`phpunit.xml`). Tests en `tests/Feature/` y `tests/Unit/`.

**Migraciones**: Mantener claras y reversibles. Seeders en `database/seeders/`.

**Flujo típico de usuario**:
1. Usuario inicia sesión y selecciona temporada activa
2. Navega por menú lateral (Inversiones, Presupuestos, etc.)
3. CRUD de recursos dentro del contexto de su equipo y temporada
```

**En tabla HTML**: Usar `toLocaleString('es-ES')` para formatear con punto de miles y coma decimal.

## Workflows Desarrollo

**Comandos clave**:
```bash
npm run dev              # Vite dev server
php artisan serve        # Laravel dev server
switch-env.bat           # Cambiar entre local/producción (Windows)
php artisan config:cache # Cachear config en producción
```

**Testing**: PHPUnit configurado (`phpunit.xml` - Exportaciones masivas
- **PDF**: DomPDF (config en `config/dompdf.php`) - Generación de reportes
- **OCR**: Mindee API para procesamiento de facturas
- **Auth**: Jetstream + Sanctum + Fortify
- **Permisos**: Spatie Laravel Permission (roles y permisos)
- **Comunicación Backend/Frontend**: Inertia.js (híbrido SPA)

## Buenas Prácticas

- **Escalabilidad**: Los módulos deben seguir el patrón de "servicios o invoices" para facilitar mantenimiento
- **Robustez**: Los componentes compartidos deben ser robustos ante props faltantes
- **UI consistente**: Mantener claridad y consistencia visual
- **Código limpio**: Comentar lógica especial, mantener código legible
- **Documentación**: Actualizar CONTEXT.md con nuevas reglas/flujos

## Referencias

Consultar:
- [CONTEXT.md](CONTEXT.md) - Reglas de negocio detalladas y lógica del sistema
- [GUIA_AMBIENTES.md](GUIA_AMBIENTES.md) - Configuración de ambientes (desarrollo/producción)
- [DEPLOY_PRODUCTION.md](DEPLOY_PRODUCTION.md) - Proceso de despliegue
- **PDF**: DomPDF (config en `config/dompdf.php`)
- **OCR**: Mindee API para facturas
- **Auth**: Jetstream + Sanctum
- **Permisos**: Spatie Laravel Permission

Consultar [CONTEXT.md](CONTEXT.md) para reglas de negocio detalladas y [GUIA_AMBIENTES.md](GUIA_AMBIENTES.md) para configuración de ambientes.
