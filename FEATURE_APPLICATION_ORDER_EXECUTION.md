# Feature: Ejecución Real de Órdenes de Aplicación

**Fecha inicio:** 21 de enero de 2026  
**Estado:** En análisis/planificación  
**Objetivo:** Registrar datos reales de aplicación vs datos teóricos planificados

---

## 🎯 Concepto General

Crear sistema para registrar la **ejecución real** de órdenes de aplicación de agroquímicos, capturando:
- **Maquinadas reales** ejecutadas
- **Gasto total real** de cada producto
- Calcular automáticamente las **variaciones** entre lo teórico y lo real

Con estos datos, calcular:
- Dosis real por hectárea
- Eficiencia de aplicación
- Variaciones porcentuales
- Generar salidas (outflows) de inventario

---

## 📐 Arquitectura Definida

### Patrón a Seguir: **FuelOutflow**

El sistema ya tiene implementado este patrón con combustibles:

```
FuelOutflow (tabla específica)
    ├─ Datos específicos de combustible
    └─ Genera automáticamente → Outflow (kardex general)
```

### Aplicación al Nuevo Feature

```
ApplicationOrderExecution (tabla específica)
    ├─ application_order_id (orden teórica original)
    ├─ execution_date
    ├─ real_maquinadas
    ├─ observations
    ├─ team_id, season_id
    └─ executed_by_user_id
    
ApplicationOrderExecutionProduct (productos ejecutados)
    ├─ execution_id
    ├─ product_id
    ├─ theoretical_quantity (de la orden)
    ├─ real_quantity_used (input usuario)
    ├─ real_quantity_per_hectare (calculado)
    ├─ variance_percentage (calculado)
    │
    └─ Genera → AgrochemicalOutflow (salida específica)
            ├─ application_order_execution_product_id
            ├─ product_id
            ├─ quantity
            ├─ invoice_product_id
            ├─ cost_center_id
            │
            └─ Genera → Outflow (kardex maestro)
                    ├─ agrochemical_outflow_id (FK)
                    ├─ level3_id (agroquímico)
                    ├─ quantity
                    └─ invoice_product_id
```

---

## 🔄 Flujo de Usuario

### 1. Selección de Orden
- Usuario navega a "Ejecutar Orden de Aplicación"
- Busca/selecciona `ApplicationOrder` existente
- Sistema carga datos teóricos:
  - Productos con dosis y cantidades teóricas
  - Centros de costo con hectáreas
  - Mojamiento y maquinadas teóricas

### 2. Input de Datos Reales
Usuario completa:
- **Maquinadas reales**: Campo numérico (ej: 8.5)
- **Por cada producto**: Cantidad real usada (ej: Glifosato → 18.2 L)
- Fecha de ejecución
- Observaciones (opcional)

### 3. Cálculos Automáticos
```javascript
hectareas_totales = orderCostCenters.sum(surface)

Por cada producto:
  real_quantity_per_hectare = real_quantity_used / hectareas_totales
  theoretical_quantity = orderProduct.cantidad_total
  variance_percentage = ((real - theoretical) / theoretical) × 100
```

### 4. Validaciones
- Stock disponible de cada agroquímico
- Cantidad real no puede ser negativa
- Debe existir inventario suficiente

### 5. Guardado
- Crear `ApplicationOrderExecution`
- Crear `ApplicationOrderExecutionProduct` por cada producto
- Generar `Outflow` por cada producto (descontar inventario)
- Crear `OutflowCostCenters` (distribución)
- Actualizar status de orden a 'completada' (opcional)

---

## 📊 Estructura de Tablas

### `agrochemical_outflows` (NUEVA - Específica de agroquímicos)
```sql
id (PK)
application_order_execution_product_id (FK, nullable) -- si viene de orden
team_id (FK → teams)
season_id (FK → seasons)
product_id (FK → products)
cost_center_id (FK → cost_centers)
invoice_product_id (FK → invoice_products, nullable)
credit_debit_note_item_id (FK → credit_debit_note_items, nullable)
quantity (decimal 10,2) -- cantidad usada
date (date)
observations (text, nullable)
created_at
updated_at
```

### `application_order_executions`
```sql
id (PK)
application_order_id (FK → application_orders)
execution_date (date)
real_maquinadas (decimal 10,2)
theoretical_maquinadas (decimal 10,2) -- copiado de orden
maquinadas_variance_percentage (decimal 10,2) -- calculado
observations (text, nullable)
executed_by_user_id (FK → users)
team_id (FK → teams)
season_id (FK → seasons)
created_at
updated_at
```

### `application_order_execution_products`
```sql
id (PK)
execution_id (FK → application_order_executions)
product_id (FK → products)
theoretical_quantity (decimal 10,2) -- copiado de order
real_quantity_used (decimal 10,2) -- INPUT USUARIO
real_quantity_per_hectare (decimal 10,2) -- CALCULADO
variance_percentage (decimal 10,2) -- CALCULADO
invoice_product_id (FK → invoice_products, nullable)
credit_debit_note_item_id (FK → credit_debit_note_items, nullable)
created_at
updated_at
```

### Modificación a `outflows` (agregar campo)
```sql
+ agrochemical_outflow_id (FK → agrochemical_outflows, nullable)
```

**Estructura de FKs en `outflows` (kardex maestro):**
- `fuel_outflow_id` → Salidas de combustible
- `agrochemical_outflow_id` → Salidas de agroquímicos (NUEVO)

---

## 🔧 grochemicalOutflow.php**
```php
protected $fillable = [
    'application_order_execution_product_id',
    'team_id',
    'season_id',
    'product_id',
    'cost_center_id',
    'invoice_product_id',
    'credit_debit_note_item_id',
    'quantity',
    'date',
    'observations',
];

// Relaciones
public function applicationOrderExecutionProduct()
public function team()
public function season()
public function product()
public function costCenter()
public function invoiceProduct()
public function creditDebitNoteItem()
public function outflow() // hasOne
```

3. **AImplementación Técnica

### Modelos a Crear

1. **ApplicationOrderExecution.php**
```php
protected $fillable = [
    'application_order_id',
    'execution_date',
    'real_maquinadas',
    'theoretical_maquinadas',
    'maquinadas_variance_percentage',
    'observations',
    'executed_by_user_id',
    'team_id',
    'season_id',
];agrochemicalOutflow() // hasOne
public function applicationOrder()
public function executedByUser()
public function team()
public function season()
public function executionProducts()
```

2. **ApplicationOrderExecutionProduct.php**
```php
protected $fillable = [
    'execution_id',
    'product_id',
    'theoretical_quantity',
    'real_quantity_used',
    'real_quantity_per_hectare',
    'variance_percentage',
    'invoice_product_id',
    'credit_debit_note_item_id',
];

// Relaciones
public function execution()
public function product()
public function invoiceProduct()
public function creditDebitNoteItem()
public function outflow()
```

### Controladores (Patrón de Acción Única)

```
app/Http/Controllers/ApplicationOrderExecutions/
├── ApplicationOrderExecutionController.php (index, orquestador)
├── CreateApplicationOrderExecutionController.php (vista form)
├── StoreApplicationOrderExecutionController.php (guardar)
├── ShowApplicationOrderExecutionController.php (detalle)
├── EditApplicationOrderExecutionController.php (editar?)
├── UpdateApplicationOrderExecutionController.php (actualizar?)
└── DeleteApplicationOrderExecutionController.php (eliminar)
```

### FormRequest

**StoreApplicationOrderExecutionRequest.php**
```php
public function rules()
{
    return [
        'application_order_id' => 'required|exists:application_orders,id',
        'execution_date' => 'required|date',
        'real_maquinadas' => 'required|numeric|min:0',
        'observations' => 'nullable|string',
        'products' => 'required|array|min:1',
        'products.*.product_id' => 'required|exists:products,id',
        'products.*.real_quantity_used' => 'required|numeric|min:0',
        'products.*.invoice_product_id' => 'nullable|exists:invoice_products,id',
    ];
}
```

### Lógica de StoreController (Inspirado en FuelOutflow)

```php
public function __invoke(StoreApplicationOrderExecutionRequest $request)
{
    $user = Auth::user();
    $teamId = $user->team_id;
    $seasonId = session('season_id');
    
    // 1. OBTENER ORDEN ORIGINAL
    $order = ApplicationOrder::with(['orderProducts', 'orderCostCenters.costCenter'])
        ->findOrFail($request->application_order_id);
    
    // 2. VALIDAR STOCK DISPONIBLE (por cada producto)
    foreach ($request->products as $productData) {
        $stockDisponible = calcularStockDisponible($productData['invoice_product_id']);
        if ($productData['real_quantity_used'] > $stockDisponible) {
            return back()->withErrors(['stock' => 'Stock insuficiente']);
        }
    }
    
    // 3. CALCULAR MÉTRICAS
    $hectareasTotales = $order->orderCostCenters->sum('costCenter.surface');
    $maquinadasTeoricas = ($order->mojamiento * $hectareasTotales) / $order->volume;
    $varianzaMaquinadas = (($request->real_maquinadas - $maquinadasTeoricas) / $maquinadasTeoricas) * 100;
    
    DB::beginTransaction();
    
    try {
        // 4. CREAR EXECUTION
        $execution = ApplicationOrderExecution::create([
            'application_order_id' => $order->id,
            'execution_date' => $request->execution_date,
            'real_maquinadas' => $request->real_maquinadas,
            'theoretical_maquinadas' => $maquinadasTeoricas,
            'maquinadas_variance_percentage' => $varianzaMaquinadas,
            'observations' => $request->observations,
            'executed_by_user_id' => $user->id,
            'team_id' => $teamId,
            'season_id' => $seasonId,
        ]);
        
        // 5. PROCESAR CADA PRODUCTO
        foreach ($request->products as $productData) {
            $orderProduct = $order->orderProducts->firstWhere('product_id', $productData['product_id']);
            
            // Calcular métricas
            $realPerHa = $productData['real_quantity_used'] / $hectareasTotales;
            $variance = (($productData['real_quantity_used'] - $orderProduct->cantidad_total) 
                        / $orderProduct->cantidad_total) * 100;
            
            // Crear execution product
            $executionProduct = ApplicationOrderExecutionProduct::create([
                'execution_id' => $execution->id,
                'product_id' => $productData['product_id'],
                'theoretical_quantity' => $orderProduct->cantidad_total,
                'real_quantity_used' => $productData['real_quantity_used'],
                'real_quantity_per_hectare' => $realPerHa,
                'variance_percentage' => $variance,
                'invoice_product_id' => $productData['invoice_product_id'],
            ]);
            ]);
            
            // Obtener producto y level3_id
            $product = Product::find($productData['product_id']);
            
            // POR CADA CENTRO DE COSTO: Crear AgrochemicalOutflow + Outflow
            foreach ($order->orderCostCenters as $occ) {
                // A. Crear AgrochemicalOutflow (específico)
                $agrochemicalOutflow = AgrochemicalOutflow::create([
                    'application_order_execution_product_id' => $executionProduct->id,
                    'team_id' => $teamId,
                    'season_id' => $seasonId,
                    'product_id' => $productData['product_id'],
                    'cost_center_id' => $occ->cost_center_id,
                    'invoice_product_id' => $productData['invoice_product_id'],
                    'quantity' => $productData['real_quantity_used'],
                    'date' => $request->execution_date,
                    'observations' => $execution->observations,
                ]);
                
                // B. Crear OUTFLOW (kardex maestro)
                $outflow = Outflow::create([
                    'agrochemical_outflow_id' => $agrochemicalOutflow->id,
                    'team_id' => $teamId,
                    'season_id' => $seasonId,
                    'user_id' => $user->id,
                    'invoice_product_id' => $productData['invoice_product_id'],
                    'quantity' => $productData['real_quantity_used'],
                    'date' => $request->execution_date,
                    'level3_id' => $product->level3_id,
                    'notes' => 'Aplicación agroquímico - Orden #' . $order->id,
                ]);
                
                // C. Crear outflow_cost_centersr_id,
                    'observations' => $execution->observations,
                ]);
            }
        }
        
        // 6. ACTUALIZAR STATUS DE ORDEN (opcional)
        $order->update(['status' => 'completada']);
        
        DB::commit();
        
    } catch (\Exception $e) {
        DB::rollBack();
        return back()->withErrors(['error' => $e->getMessage()]);
    }
    
    return redirect()->route('application-order-executions.index')
        ->with('success', 'Ejecución registrada correctamente');
}
```

---

## 🎨 Frontend (Vue + PrimeVue)

### Vista Principal: Ejecutar Orden

**Componentes necesarios:**
- `ExecuteApplicationOrderModal.vue` (modal principal)
- `ExecuteApplicationOrderForm.vue` (formulario)
- Tabla con productos y campos editables
- Cálculo en tiempo real de variaciones

**Flujo:**
1. Select de orden → Carga datos teóricos
2. Input maquinadas reales
3. Tabla editable con productos:
   - Columna: Producto
   - Columna: Cantidad teórica (readonly)
   - Columna: Cantidad real (input)
   - Columna: Variación % (calculado en tiempo real)
   - Columna: Select factura origen
4. Botón guardar → SweetAlert confirmación

---

## ❓ Decisiones Pendientes

### 1. Selección de Stock
¿Cómo elegir de qué factura sacar el producto?

**Opciones:**
- A) FIFO automático
- B) Usuario selecciona factura manualmente
- C) Sistema sugiere (FIFO) pero permite override

**Recomendación:** Opción B inicialmente (manual), migrar a C en v2

### 2. Múltiples Ejecuciones
¿Una orden puede ejecutarse varias veces?

**Opciones:**
- A) No - Una orden = una ejecución (validar unicidad)
- B) Sí - Permitir reaplicaciones

**Recomendación:** Opción A inicialmente (simplificar), B en futuro

### 3. Edición de Ejecuciones
¿Se pueden editar/eliminar ejecuciones ya creadas?

**Impacto:** Afecta inventario (outflows ya generados)

**Recomendación:** Solo eliminar con reversión de outflows (como facturas)
grochemical_outflows` (tabla específica)
- [ ] Modificar migración `outflows` (agregar `agrochemical_outflow_id`)
- [ ] Crear migración `application_order_executions`
- [ ] Crear migración `application_order_execution_products`

**Recomendación:** NogrochemicalOutflow`
- [ ] Actualizar modelo `Outflow` (agregar relación `agrochemicalOutflow`)
- [ ] Crear modelo `ApplicationOrderExecution`
- [ ] Crear modelo `ApplicationOrderExecutionProduct`

## ✅ Estado Actual de Implementación

### Completado:
- ✅ Migración `agrochemical_outflows`
- ✅ Migración modificada `outflows` 
- ✅ Modelo `AgrochemicalOutflow`
- ✅ Modelo `Outflow` actualizado
- ✅ Controladores (Index, Store, Delete)
- ✅ FormRequest con validaciones
- ✅ Rutas registradas en web.php
- ✅ Validación de stock
- ✅ Transacción de doble registro

### Pendiente:
- [ ] Frontend Vue (Index, Modal, Form)
- [ ] Ejecutar migraciones
- [ ] Probar funcionalidad completa

---

## 📝 Próximos Pasos

### Fase 1: Base de Datos
- [ ] Crear migración `application_order_executions`
- [ ] Crear migración `application_order_execution_products`
- [ ] Modificar migración `outflows` (agregar campo FK)

### Fase 2: Modelos
- [ ] Crear modelo `ApplicationOrderExecution`
- [ ] Crear modelo `ApplicationOrderExecutionProduct`
- [ ] Actualizar modelo `Outflow` (relación nueva)

### Fase 3: Backend
- [ ] Crear controladores (patrón acción única)
- [ ] Crear `StoreApplicationOrderExecutionRequest`
- [ ] Implementar lógica de validación de stock
- [ ] Implementar transacción de guardado

### Fase 4: Frontend
- [ ] Crear vista index (listado de ejecuciones)
- [ ] Crear modal de ejecución
- [ ] Crear formulario con cálculos en tiempo real
- [ ] Integrar con API

### Fase 5: Reportes
- [ ] Reporte comparativo teórico vs real
- [ ] Exportar a Excel
- [ ] Gráficos de eficiencia

---

## 🔍 Referencias del Código Actual

### Archivos Clave a Estudiar
- `app/Models/FuelOutflow.php` - Patrón de referencia
- `app/Models/Outflow.php` - Kardex general
- `app/Http/Controllers/FuelOutflows/StoreFuelOutflowController.php` - Lógica de transacción
- `app/Models/ApplicationOrder.php` - Orden teórica
- `app/Models/ApplicationOrderProduct.php` - Productos de orden

### 🔄 Patrón de Doble Registro (Como FuelOutflow)

```
FLUJO DE SALIDA:
1. Usuario registra ejecución de orden
2. Por cada producto:
   a) Crear ApplicationOrderExecutionProduct (datos de ejecución)
   b) Crear AgrochemicalOutflow (salida específica de agroquímico)
   c) Crear Outflow (registro maestro en kardex)
   
VENTAJAS:
- AgrochemicalOutflow: Datos específicos del dominio (orden, producto, dosis)
- Outflow: Kardex universal para control de inventario
- Trazabilidad completa
- Simetría con FuelOutflow
```

### Migraciones Relacionadas
- Buscar: `create_fuel_outflows_table`
- Buscar: `create_outflows_table`
- Buscar: `create_application_orders_table`

---

## 💡 Notas Adicionales

- Los productos en `ApplicationOrder` son **agroquímicos** exclusivamente
- Todos deben tener `level3_id` (categoría de agroquímico)
- El sistema ya valida stock en FuelOutflow - reutilizar lógica
- Usar SweetAlert para confirmaciones (estándar del sistema)
- Exports con números puros (sin formato), Excel aplica automático

---

**Última actualización:** 21 de enero de 2026, 23:45
