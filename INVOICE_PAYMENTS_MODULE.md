# Módulo de Pagos de Facturas (Invoice Payments)

## 📋 Descripción

Módulo completo para registrar, editar y gestionar los pagos realizados a facturas de proveedores. Permite el control de pagos parciales o totales, con soporte para múltiples métodos de pago (Transferencia, Efectivo, Cheque), filtros avanzados y dashboard de estadísticas.

## 🎯 Características Principales

✅ **Gestión Completa de Pagos**
- Búsqueda de facturas por número de documento
- Registro de pagos con validación de saldo pendiente
- Edición y eliminación de pagos registrados
- Múltiples métodos de pago (Transferencia, Efectivo, Cheque)
- Catálogo de bancos desde base de datos

✅ **Filtros Avanzados**
- Filtro por rango de fechas
- Filtro por proveedor
- Filtro por método de pago
- Filtro por banco
- Búsqueda global por texto

✅ **Dashboard de Estadísticas**
- Resumen de totales (pagado, pendiente, facturas)
- Gráfico de pagos por método (Doughnut Chart)
- Evolución mensual de pagos (Bar Chart)
- Top 10 bancos con más transferencias
- Top 10 proveedores con más pagos
- Últimos 10 pagos registrados

✅ **Exportación**
- Exportar a Excel con todos los pagos filtrados
- Formato correcto para números y fechas

✅ **Integración Completa**
- Menú lateral actualizado
- Filtrado por team_id y season_id automático
- Permisos y validaciones de negocio

## 🗂️ Estructura de Archivos

### Backend

```
app/
├── Models/
│   ├── Bank.php                              # Modelo de bancos
│   ├── InvoicePayment.php                    # Modelo de pagos de facturas
│   └── Invoice.php                           # Actualizado con relaciones y atributos calculados
├── Http/
│   ├── Controllers/InvoicePayments/
│   │   ├── InvoicePaymentController.php              # Vista principal, búsqueda y filtros
│   │   ├── InvoicePaymentDashboardController.php    # Dashboard de estadísticas
│   │   ├── StoreInvoicePaymentController.php        # Guardar nuevo pago
│   │   ├── UpdateInvoicePaymentController.php       # Actualizar pago existente
│   │   └── DeleteInvoicePaymentController.php       # Eliminar pago
│   ├── Requests/InvoicePayments/
│   │   ├── StoreInvoicePaymentRequest.php           # Validaciones para crear
│   │   └── UpdateInvoicePaymentRequest.php          # Validaciones para actualizar
│   └── Controllers/Excels/
│       └── InvoicePaymentsExcelController.php       # Exportar a Excel
├── Exports/
│   └── InvoicePaymentsExport.php             # Export de Maatwebsite Excel
database/
├── migrations/
│   ├── 2026_01_24_000001_create_banks_table.php
│   └── 2026_01_24_000002_create_invoice_payments_table.php
└── seeders/
    └── BankSeeder.php                        # Datos iniciales de bancos peruanos
```

### Frontend

```
resources/js/
├── Pages/InvoicePayments/
│   ├── Index.vue                         # Vista principal con filtros avanzados
│   └── Dashboard.vue                     # Dashboard de estadísticas y gráficos
├── Components/InvoicePayments/
│   ├── CreateInvoicePaymentModal.vue     # Modal para registrar pago
│   ├── EditInvoicePaymentModal.vue       # Modal para editar pago
│   ├── InvoicePaymentForm.vue            # Formulario reutilizable
│   └── PaymentStatusBadge.vue            # Badge de estado (Pendiente/Parcial/Pagado)
└── Layouts/
    └── Sidebar.vue                       # Actualizado con menú de Pagos de Facturas
```

## 🚀 Instalación

### 1. Ejecutar Migraciones

```bash
php artisan migrate
```

Esto creará las tablas:
- `banks` - Catálogo de bancos
- `invoice_payments` - Registro de pagos

### 2. Ejecutar Seeder

```bash
php artisan db:seed --class=BankSeeder
```

Esto llenará la tabla `banks` con 20 bancos peruanos comunes.

## 📊 Estructura de Datos

### Tabla: `banks`

| Campo    | Tipo    | Descripción                |
|----------|---------|----------------------------|
| id       | bigint  | ID único                   |
| name     | string  | Nombre del banco           |
| code     | string  | Código del banco (opcional)|
| active   | boolean | Estado (activo/inactivo)   |

### Tabla: `invoice_payments`

| Campo               | Tipo      | Descripción                           |
|---------------------|-----------|---------------------------------------|
| id                  | bigint    | ID único                              |
| invoice_id          | bigint    | FK a invoices                         |
| team_id             | bigint    | FK a teams                            |
| season_id           | bigint    | FK a seasons                          |
| user_id             | bigint    | Usuario que registró el pago          |
| bank_id             | bigint    | FK a banks (nullable)                 |
| payment_date        | date      | Fecha del pago                        |
| amount              | decimal   | Monto pagado                          |
| payment_method      | integer   | 1:Transferencia, 2:Efectivo, 3:Cheque |
| transaction_number  | string    | Número de transacción/cheque          |
| observations        | text      | Observaciones adicionales             |

## 🎯 Funcionalidades

### 1. Registro de Pagos

1. Click en "Registrar Pago"
2. Buscar factura por número de documento
3. Seleccionar factura de los resultados
4. Ver información de la factura (total, pagado, saldo pendiente)
5. Ingresar datos del pago:
   - Fecha de pago
   - Monto (validado contra saldo pendiente)
   - Método de pago
   - Banco (obligatorio si es transferencia)
   - Número de transacción/cheque (obligatorio excepto efectivo)
   - Observaciones (opcional)
6. Guardar

### 2. Edición de Pagos

1. Click en botón "Editar" en la tabla de pagos
2. Modificar los datos necesarios
3. Guardar cambios

### 3. Eliminación de Pagos

1. Click en botón "Eliminar" en la tabla de pagos
2. Confirmar acción en modal de SweetAlert
3. El pago se elimina y se recalcula el saldo de la factura

### 4. Filtros Avanzados

1. Click en "Mostrar Filtros"
2. Aplicar filtros por:
   - Rango de fechas (desde/hasta)
   - Proveedor
   - Método de pago
   - Banco
3. Click en "Aplicar Filtros"
4. Click en "Limpiar" para resetear filtros

### 5. Dashboard de Estadísticas

1. Click en botón "Dashboard"
2. Ver estadísticas generales:
   - Total pagado en la temporada
   - Saldo pendiente total
   - Facturas pagadas/parciales/pendientes
3. Gráficos interactivos:
   - Distribución de pagos por método (Doughnut)
   - Evolución mensual de pagos (Barras)
   - Top 10 bancos con más transferencias
   - Top 10 proveedores con más pagos
4. Últimos pagos registrados

### 6. Exportación a Excel

1. Click en botón "Excel"
2. Se descarga archivo con todos los pagos filtrados
3. Formato correcto para números (sin formateo, Excel aplica automáticamente)

### 7. Búsqueda Global

Búsqueda en tiempo real por:
- Número de documento de factura
- Nombre de proveedor
- Número de transacción

## 🔐 Validaciones

### Reglas de Negocio

1. **Monto**: No puede exceder el saldo pendiente de la factura
2. **Banco**: Obligatorio si método de pago = Transferencia
3. **Número de Transacción**: Obligatorio para Transferencia y Cheque
4. **Team/Season**: Todos los pagos se filtran por el equipo y temporada activa del usuario
5. **Permisos**: Solo se pueden editar/eliminar pagos del propio equipo

### Validaciones Frontend

- Fecha de pago: requerida
- Monto: requerido, numérico, mayor a 0, no excede saldo pendiente
- Método de pago: requerido
- Banco: condicional (si método = Transferencia)
- Número transacción: condicional (si método = Transferencia o Cheque)

## 🔗 Rutas con filtros
GET    /invoice-payments/dashboard        # Dashboard de estadísticas
GET    /invoice-payments/excel            # Exportar a Excel

```php
GET    /invoice-payments                  # Vista principal
GET    /api/invoices/search               # Buscar facturas (API)
POST   /invoice-payments                  # Guardar pago
PUT    /invoice-payments/{payment}        # Actualizar pago
DELETE /invoice-payments/{payment}        # Eliminar pago
```

## 💡 Uso del API

### Buscar Facturas

```javascript
axios.get(route('invoices.search'), {
    params: {
        number_document: '001-123456',
        supplier_id: 5  // opcional
    }
})
```

**Respuesta:**

```json
[
    {
        "id": 1,
        "number_document": "001-123456",
        "date": "15-01-2026",
        "due_date": "15-02-2026",
        "supplier": {
            "id": 5,
            "name": "Proveedor XYZ"
        },
        "total_invoice": 1500.00,
        "total_paid": 500.00,
        "balance": 1000.00,
        "payment_status": "partial"
    }
]
```

## 📈 Atributos Calculados (Invoice Model)

```php
$invoice->total_paid        // Suma de todos los pagos
$invoice->total_invoice     // Total de la factura
$invoice->balance           // Saldo pendiente
$invoice->payment_status    // Estado: pending/partial/paid
```

## 🎨 Componentes Reutilizables

### PaymentStatusBadge

```vue
<PaymentStatusBadge :status="invoice.payment_status" />
```

Estados disponibles:
- `pending` → Badge rojo "Pendiente"
- `partial` → Badge amarillo "Parcial"
- `paid` → Badge verde "Pagado"

## 📝 Notas Adicionales

- **Método de Pago 1 (Transferencia)**: Requiere banco obligatorio
- **Método de Pago 2 (Efectivo)**: No requiere banco ni número de transacción
- **Método de Pago 3 (Cheque)**: Requiere número de cheque
- Los pagos se pueden registrar en cualquier orden (no requieren ser consecutivos)
- Una factura puede tener múltiples pagos parciales
- El sistema calcula automáticamente el saldo pendiente

## 🐛 Troubleshooting

### Error: "El banco es obligatorio"
- Asegúrate de haber seleccionado "Transferencia" como método de pago

### Error: "El monto no puede exceder el saldo pendiente"
- Verifica que el monto ingresado no sea mayor al saldo pendiente de la factura

### No aparecen bancos en el selector
- Ejecuta el seeder: `php artisan db:seed --class=BankSeeder`

### La búsqueda de facturas no funciona
- Verifica que la temporada activa esté seleccionada
- Confirma que existan facturas en la temporada actual

## 📞 Soporte

Para reportar bugs o solicitar mejoras, contactar al equipo de desarrollo.
