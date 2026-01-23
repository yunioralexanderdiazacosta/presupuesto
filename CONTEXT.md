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

## Expectativas y buenas prácticas
- Todo nuevo módulo debe respetar el filtrado por season y team.
- La UI debe ser consistente y clara.
- El código debe ser limpio, comentado y fácil de mantener.
- Documentar cualquier lógica especial o excepción en este archivo.

### Sistema de Conversión de Unidades (CRÍTICO)

**Regla fundamental**: La tabla `outflows` (kardex maestro) SIEMPRE debe almacenar cantidades en la **unidad base del producto**.

**Contexto del problema:**
- Un producto puede llegar en factura con una unidad (ej: 5 LT)
- Pero el agrónomo trabaja con otra unidad (ej: dosis de 20 cc/ha)
- El sistema debe permitir trabajar con la unidad práctica pero guardar correctamente en la unidad base

**Implementación:**

1. **Helper global disponible**: `convertToBaseUnit($quantity, $fromUnitName, $toUnitName)`
   - Ubicación: `app/Helpers/UnitHelper.php`
   - Auto-cargado en `composer.json` (sección `autoload.files`)
   - Disponible globalmente en todo el sistema (controladores, modelos, Blade)

2. **Conversiones soportadas**:
   - cc ↔ lt (centímetros cúbicos ↔ litros): factor 1000
   - ml ↔ lt (mililitros ↔ litros): factor 1000
   - gr ↔ kg (gramos ↔ kilogramos): factor 1000
   - mg ↔ gr (miligramos ↔ gramos): factor 1000

3. **Flujo de uso obligatorio**:
   ```php
   // Ejemplo en AgrochemicalOutflows
   $product = Product::with('unit')->find($productId);
   $orderProduct = ApplicationOrderProduct::find($orderProductId);
   
   $usedUnitName = $orderProduct->unit->name; // "cc"
   $baseUnitName = $product->unit->name; // "lt"
   $quantityOriginal = 60; // 60 cc
   
   // Convertir a unidad base antes de guardar en outflows
   $quantityConverted = convertToBaseUnit($quantityOriginal, $usedUnitName, $baseUnitName);
   // $quantityConverted = 0.060 (litros)
   
   // Guardar trazabilidad en registro específico
   AgrochemicalOutflow::create([
       'quantity' => $quantityOriginal, // 60 cc (para trazabilidad)
       'unit_id' => $orderProduct->unit_id, // ID de "cc"
   ]);
   
   // Guardar en kardex EN UNIDAD BASE
   Outflow::create([
       'quantity' => $quantityConverted, // 0.060 lt (¡CRÍTICO!)
   ]);
   ```

4. **Tablas que almacenan unidad usada (trazabilidad)**:
   - `application_order_products`: `unit_id` (unidad usada en la orden)
   - `agrochemical_outflows`: `unit_id` (unidad usada en la aplicación)
   - `fuel_outflows`: No requiere `unit_id` (siempre trabaja en litros)

5. **Display en frontend**: La función `getSimplifiedQuantity()` en componentes Vue convierte cc→lt y gr→kg cuando cantidad >= 1000, SOLO para visualización. Los valores internos permanecen en unidad original.

6. **IMPORTANTE**: Si no existe conversión directa entre dos unidades, `convertToBaseUnit()` devuelve la cantidad original y registra un warning en el log.

**Ejemplo de caso de uso real:**
```
Factura: Insecticida X - 5 LT (unidad base del producto)
Orden de aplicación: 3 ha × 20 cc/ha = 60 cc
Ejecución real: 65 cc consumidos

Guardado en BD:
- application_order_products.quantity = 60, unit_id = "cc"
- agrochemical_outflows.quantity = 65, unit_id = "cc" (trazabilidad)
- outflows.quantity = 0.065 (convertido a LT - unidad base)

Stock resultante: 5.000 LT - 0.065 LT = 4.935 LT ✅
```

**Cuándo usar este sistema:**
- ✅ ApplicationOrders (orden usa cc, producto base es lt)
- ✅ AgrochemicalOutflows (aplicación usa cc, producto base es lt)
- ✅ Cualquier módulo que registre consumos en `outflows`
- ✅ Services (si trabajan con unidades alternativas)
- ❌ FuelOutflows (ya trabaja directo en litros)

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

## Notas adicionales
- Si tienes dudas sobre la lógica o reglas, consulta este archivo antes de implementar cambios.
- Actualiza este archivo si agregas reglas o flujos nuevos.

---

> Este archivo sirve como referencia viva para el equipo y para cualquier asistente de IA que colabore en el proyecto.
