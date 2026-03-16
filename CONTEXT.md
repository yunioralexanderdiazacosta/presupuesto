# CONTEXT.md

## Descripción general
Este proyecto es un sistema de gestión presupuestaria agrícola desarrollado en Laravel + Vue (Inertia.js). Permite administrar presupuestos, inversiones, temporadas, equipos y otros recursos relacionados con la gestión agrícola.

## Estructura principal
- **app/Http/Controllers/**: Lógica de backend y endpoints.
- **app/Models/**: Modelos Eloquent.
- **resources/js/Pages/**: Vistas principales de Inertia/Vue.
- **resources/js/Components/**: Componentes reutilizables Vue.
- **database/migrations/**: Migraciones de base de datos.
- **routes/web.php**: Rutas web principales.

## Lógica y reglas de negocio clave
- Todas las entidades  (inversiones, presupuestos, servicios, agroquimicos etc.) deben estar filtradas por temporada (`season`) y equipo (`team`).
- El usuario solo puede ver y operar sobre los datos de su equipo y temporada activa (eliegida siemprea al incio de la sesion en el mal llamado "select-budget").
- Los módulos deben ser escalables y seguir el patrón de "servicios o invoices" para facilitar el mantenimiento.
- Los componentes compartidos deben ser robustos ante props faltantes.
- Todos los selects en formularios deben implementarse usando el componente Multiselect de Vue (`@vueform/multiselect`), nunca el nativo ni otros plugins.
- Al guardar o editar cualquier entidad mediante formularios, siempre se debe mostrar un mensaje de confirmación usando SweetAlert (Swal.fire), siguiendo el estándar visual del sistema.

### Jerarquía de niveles y filtrado por equipo
- **Estructura jerárquica**: El sistema usa una jerarquía de 4 niveles: `Level1` -> `Level2` -> `Level3` -> `Level4`
- **Relación con team**: El `team_id` se encuentra en `Level1`, no en los niveles inferiores
- **Filtrado correcto**: Para filtrar entidades por equipo en niveles inferiores, usar relaciones:
  ```php
  // ❌ INCORRECTO: Level3 no tiene team_id directamente
  $level3s = Level3::where('team_id', $teamId)->get();
  
  // ✅ CORRECTO: Filtrar a través de las relaciones
  $level3s = Level3::whereHas('level2.level1', function($query) use ($teamId) {
      $query->where('team_id', $teamId);
  })->get();
  ```
- **Productos**: Los productos SÍ tienen `team_id` directo, además de `level3_id`
- **Combustibles**: Para obtener productos de combustible del equipo:
  1. Buscar `Level3` con nombre 'combustible' que pertenezcan al team (vía level2.level1)
  2. Filtrar productos por esos level3_ids Y por team_id del usuario

## Flujo típico de usuario
1. El usuario inicia sesión y selecciona una temporada activa.
2. Navega por el menú lateral para acceder a módulos como Inversiones, Presupuestos, etc.
3. Puede crear, editar y eliminar recursos, siempre dentro del contexto de su equipo y temporada.


## Convenciones y patrones
	- Para cada flujo principal, debe existir un FormRequest dedicado (por ejemplo, `FormFuelOutflowRequest.php`) para centralizar y reutilizar las validaciones de entrada.
- Controladores de acción única para endpoints principales.
	- Cada entidad principal debe tener su carpeta con controladores de acción única para CRUD.
	- Además, puede existir un controlador principal (por ejemplo, `InvestmentsController`, `FuelOutflowController`) para orquestación, carga de vistas principales, endpoints agregados, reportes o lógica especial.
		- Ejemplo: `FuelOutflowController@index` carga la tabla principal y catálogos, mientras que las acciones CRUD están en `FuelOutflows/`.
		- Este patrón es descriptivo: define la arquitectura recomendada, no implica copiar código entre controladores, sino separar responsabilidades y mantener la lógica organizada y escalable.
	- Cada entidad principal (por ejemplo, Invoices, Outflows, FuelOutflows) debe tener su propia carpeta en `app/Http/Controllers/Entidad/`.
	- Dentro de esa carpeta, cada acción (crear, editar, eliminar, mostrar, almacenar, actualizar) debe tener su propio archivo controlador, siguiendo el patrón:
		- `CreateEntidadController.php`, `StoreEntidadController.php`, `EditEntidadController.php`, `UpdateEntidadController.php`, `DeleteEntidadController.php`, `ShowEntidadController.php`.
	- Ejemplo: para FuelOutflows, los controladores estarán en `app/Http/Controllers/FuelOutflows/` y cada acción en su archivo correspondiente.
- Uso de Inertia.js para comunicación backend/frontend.
- Componentes Vue modulares y reutilizables.
- Migraciones claras y reversibles.
- **Patrón de modales y formularios:** Para formularios complejos, el componente del modal (`CreateXModal.vue`, `EditXModal.vue`, etc.) debe encargarse únicamente del header, footer y slots, mientras que el formulario real (inputs, selects, lógica de negocio) debe estar en un componente aparte (por ejemplo, `XForm.vue`). El modal siempre debe estar montado y abrirse/cerrarse usando Bootstrap JS (`$('#modalId').modal('show')`). Esto permite máxima escalabilidad, reutilización y consistencia entre módulos.
- **Estilo de botones de acción:** Los botones principales de acción (Agregar, Guardar, Actualizar, etc.) deben estar alineados a la derecha y tener un ancho apropiado al contenido del texto, NO deben ocupar todo el ancho del contenedor (evitar `w-100` o `col-12` para botones). Esto mejora la usabilidad y mantiene la consistencia visual del sistema.

## Expectativas y buenas prácticas
- Todo nuevo módulo debe respetar el filtrado por season y team.
- La UI debe ser consistente y clara.
- El código debe ser limpio, comentado y fácil de mantener.
- Documentar cualquier lógica especial o excepción en este archivo.

### Sistema de Unidades en Application Orders

**Regla fundamental**: Las órdenes de aplicación (`application_orders`) SIEMPRE trabajan con la **unidad base del producto**.

**Filosofía del sistema:**
- El usuario es responsable de hacer los cálculos de conversión de unidades
- El sistema NO hace conversiones automáticas
- Todas las cantidades se almacenan y calculan en la unidad base del producto
- El `unit_id` en `application_order_products` es el mismo que el del producto base

**Flujo correcto:**

1. **En Application Orders**:
   ```php
   // El usuario ingresa cantidades YA calculadas en la unidad base
   // Ejemplo: Producto base en LT, usuario ingresa dosis en LT
   $product = Product::find($productId);
   
   ApplicationOrderProduct::create([
       'product_id' => $productId,
       'unit_id' => $product->unit_id, // SIEMPRE la unidad base del producto
       'dosis_por_hectarea' => 0.020, // Usuario ingresa 0.020 LT (no 20 cc)
       'cantidad_total' => 0.060, // 0.020 LT × 3 ha = 0.060 LT
   ]);
   ```

2. **En el Frontend**:
   - Se muestra la unidad base del producto
   - NO hay conversiones automáticas
   - El usuario debe calcular manualmente si trabaja con unidades alternativas

3. **Ejemplo práctico**:
   ```
   Producto: Glifosato 5 LT (unidad base: litros)
   Hectáreas: 3 ha
   
   Usuario calcula:
   - Necesita 20 cc/ha
   - 20 cc = 0.020 LT
   - Total: 0.020 LT/ha × 3 ha = 0.060 LT
   
   Usuario ingresa en la orden: 0.020 LT/ha (dosis ya convertida)
   Sistema calcula: 0.060 LT total
   Se guarda: unit_id = LT (unidad base del producto)
   ```

**IMPORTANTE**: 
- ❌ NO usar `convertToBaseUnit()` en Application Orders
- ❌ NO permitir selección de unidad alternativa en el formulario
- ✅ Mostrar siempre la unidad base del producto
- ✅ Responsabilidad del usuario hacer conversiones manuales

### Exportación a Excel
- **Formato de datos numéricos**: Al exportar datos a Excel usando el componente `ExportExcelButton`, los valores numéricos (como totales, precios, cantidades) deben exportarse como números puros (sin formatear con `toLocaleString`).
- **Razón**: Si se exportan como strings formateados, el componente ExportExcelButton los reconvierte y elimina el separador de miles. Al enviar números puros, Excel los formatea automáticamente con separador de miles (coma) según su configuración regional.
- **Ejemplo correcto**:
  ```javascript
  const excelData = computed(() => {
    return items.value.map(item => {
      const totalNum = Number(item.total);
      return {
        ...item,
        total: isNaN(totalNum) ? '' : totalNum // Número puro, no formateado
      };
    });
  });
  ```
- **En la tabla HTML**: Usar `toLocaleString('es-ES')` para mostrar con punto de miles y coma decimal.
- **En Excel**: Exportar números puros para que Excel aplique su formato numérico automáticamente.

### Comparaciones de IDs en JavaScript (Frontend)
- **REGLA CRÍTICA**: En producción, MySQL puede devolver IDs como **strings** (`"3"`) mientras que los mapeos PHP (`->map()`) los pasan como **integers** (`3`). La comparación estricta `===` entre ambos falla silenciosamente.
- **SIEMPRE** usar `String()` al comparar IDs en JavaScript, especialmente en `.find()`, `.filter()`, `.includes()`:
  ```javascript
  // ❌ INCORRECTO: Falla en producción por diferencia de tipos (int vs string)
  const item = items.find(i => i.value === someId);

  // ✅ CORRECTO: Funciona siempre
  const item = items.find(i => String(i.value) === String(someId));
  ```
- Esto aplica a **todos** los componentes que comparan IDs provenientes de props de Inertia contra datos de relaciones Eloquent (cost_center_variety_id, fruit_id, team_id, etc.).
- **Causa raíz**: El driver MySQL de producción puede tener configuración PDO distinta (`ATTR_STRINGIFY_FETCHES`) a la de desarrollo local.

## Notas adicionales
- Si tienes dudas sobre la lógica o reglas, consulta este archivo antes de implementar cambios.
- Actualiza este archivo si agregas reglas o flujos nuevos.

---

> Este archivo sirve como referencia viva para el equipo y para cualquier asistente de IA que colabore en el proyecto.
