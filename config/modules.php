<?php

/**
 * Catálogo de módulos del sistema. Se usa para:
 *  - bloquear módulos por empresa (team_disabled_modules, lo gestiona el Super Admin)
 *  - restringir módulos por usuario (users.restrict_modules + user_modules, lo gestiona el Admin)
 *
 * 'key' debe ser el nombre de una ruta real (idealmente la ruta ".index" del módulo).
 * Un módulo cubre su ruta exacta y, si la key termina en ".index", todas las rutas con el mismo
 * prefijo (ej. 'agrochemicals.index' cubre 'agrochemicals.store', 'agrochemicals.pdf', etc.).
 * 'prefixes' (opcional) agrega rutas de apoyo que el módulo usa (APIs, pestañas, módulos hermanos).
 * 'section' es el submenú; el grupo de primer nivel del menú se deriva en ModuleAccess::GROUPS.
 */

return [
    ['key' => 'dashboard', 'label' => 'Dashboard', 'section' => 'Presupuestos'],
    ['key' => 'technicalpanel', 'label' => 'Panel Técnico', 'section' => 'Presupuestos'],
    ['key' => 'agrochemicals.index', 'label' => 'Agroquímicos (Presupuesto)', 'section' => 'Presupuestar por CC'],
    ['key' => 'fertilizers.index', 'label' => 'Fertilizantes (Presupuesto)', 'section' => 'Presupuestar por CC'],
    ['key' => 'manpowers.index', 'label' => 'Mano de Obra (Presupuesto)', 'section' => 'Presupuestar por CC', 'prefixes' => ['man.powers.']],
    ['key' => 'supplies.index', 'label' => 'Insumos (Presupuesto)', 'section' => 'Presupuestar por CC'],
    ['key' => 'services.index', 'label' => 'Servicios (Presupuesto)', 'section' => 'Presupuestar por CC'],
    ['key' => 'harvests.index', 'label' => 'Cosecha (Presupuesto)', 'section' => 'Presupuestar por CC'],
    ['key' => 'fields.index', 'label' => 'Gral Campo', 'section' => 'Presupuestos'],
    ['key' => 'administrations.index', 'label' => 'Administración', 'section' => 'Presupuestos'],
    ['key' => 'outflows.dashboard', 'label' => 'Dashboard Gestión', 'section' => 'Gestión'],
    ['key' => 'comparative.dashboard', 'label' => 'Comparativo Presupuesto vs Real', 'section' => 'Gestión', 'prefixes' => ['api.comparative.']],
    ['key' => 'hectare.dashboard', 'label' => 'Gestión por Hectárea', 'section' => 'Gestión'],
    ['key' => 'dashboard-detail-outflows.index', 'label' => 'Detalle de Salidas por Sucursal', 'section' => 'Gestión'],
    ['key' => 'investment.dashboard', 'label' => 'Dashboard Inversiones', 'section' => 'Gestión'],
    ['key' => 'profit-loss.index', 'label' => 'Utilidad / Pérdida', 'section' => 'Gestión'],
    ['key' => 'invoices.index', 'label' => 'Facturas y otros', 'section' => 'Registro de Gastos', 'prefixes' => ['credit_debit_notes.', 'consolidated-documents.']],
    ['key' => 'expense-reports.index', 'label' => 'Rendiciones de Gastos', 'section' => 'Registro de Gastos', 'prefixes' => ['api.suppliers.', 'api.pending-expense-items', 'api.expense-items.', 'invoices.search']],
    ['key' => 'purchase-orders.index', 'label' => 'Órdenes de Compra', 'section' => 'Registro de Gastos'],
    ['key' => 'payment-requests.index', 'label' => 'Solicitudes de Pago', 'section' => 'Registro de Gastos'],
    ['key' => 'invoice-payments.index', 'label' => 'Pago de Facturas', 'section' => 'Registro de Gastos'],
    ['key' => 'investments.index', 'label' => 'Inversiones', 'section' => 'Gestión'],
    ['key' => 'projects.index', 'label' => 'Proyectos', 'section' => 'Gestión'],
    ['key' => 'consolidated-outflows.index', 'label' => 'Consolidado de Salidas', 'section' => 'Salidas'],
    ['key' => 'outflows.index', 'label' => 'Consumos Gral.', 'section' => 'Salidas'],
    ['key' => 'fuel-outflows.index', 'label' => 'Combustible Maquinaria', 'section' => 'Salidas'],
    ['key' => 'application-orders.index', 'label' => 'Salida Agroquímicos', 'section' => 'Agroquímicos', 'prefixes' => ['agrochemical-outflows.', 'libro-campo.', 'api.products.']],
    ['key' => 'fertilizer-orders.index', 'label' => 'Salida Fertilizantes', 'section' => 'Fertilizantes', 'prefixes' => ['fertilizer-outflows.']],
    ['key' => 'estimates.index', 'label' => 'Estimaciones', 'section' => 'Gestión', 'prefixes' => ['estimate-status.']],
    ['key' => 'production-dispatches.index', 'label' => 'Ingresar Producción', 'section' => 'Producción', 'prefixes' => ['bin-types.', 'box-types.', 'carriers.', 'exporters.', 'packing-houses.', 'productions.', 'api.bin-types', 'api.box-types', 'api.carriers', 'api.exporters', 'api.packing-houses', 'api.fruit-classifications.']],
    ['key' => 'production-summaries.index', 'label' => 'Ingreso Rápido de Producción', 'section' => 'Producción', 'prefixes' => ['productions.', 'api.bin-types', 'api.box-types', 'api.carriers', 'api.exporters', 'api.packing-houses', 'api.fruit-classifications.']],
    ['key' => 'cost-center-analysis.index', 'label' => 'Análisis Comparativo de Cuarteles', 'section' => 'Producción'],
    ['key' => 'suppliers.index', 'label' => 'Proveedores', 'section' => 'Gestión'],
    ['key' => 'inventory', 'label' => 'Inventario', 'section' => 'Gestión', 'prefixes' => ['kardex.', 'product-stock-lines.']],
    ['key' => 'products.index', 'label' => 'Productos', 'section' => 'Gestión'],
    ['key' => 'machineries.index', 'label' => 'Maquinarias', 'section' => 'Maquinarias'],
    ['key' => 'type.machineries.index', 'label' => 'Tipo de Maquinarias', 'section' => 'Maquinarias'],
    ['key' => 'operators.index', 'label' => 'Operarios', 'section' => 'Maquinarias'],
    ['key' => 'fuel-tanks.index', 'label' => 'Estanques de Combustible', 'section' => 'Maquinarias'],
    ['key' => 'irrigation-pumps.index', 'label' => 'Equipos de Riego', 'section' => 'Gestión'],
    ['key' => 'employees.index', 'label' => 'Colaboradores', 'section' => 'Remuneraciones', 'prefixes' => ['api.available-employees']],
    ['key' => 'contracts.index', 'label' => 'Contratos', 'section' => 'Remuneraciones', 'prefixes' => ['api.available-employees', 'api.schedules', 'api.cities']],
    ['key' => 'terminations.index', 'label' => 'Términos de Faena', 'section' => 'Remuneraciones'],
    ['key' => 'contract-templates.index', 'label' => 'Plantillas de Contrato', 'section' => 'Remuneraciones'],
    ['key' => 'termination-templates.index', 'label' => 'Plantillas Término de Contrato', 'section' => 'Remuneraciones'],
    ['key' => 'monthly-bonuses.index', 'label' => 'Bonos y Descuentos', 'section' => 'Remuneraciones', 'prefixes' => ['monthly-bonus-types.', 'monthly-discounts.', 'monthly-discount-types.', 'bonus-types.']],
    ['key' => 'overtime-hours.index', 'label' => 'Horas Extras', 'section' => 'Remuneraciones'],
    ['key' => 'vacations.index', 'label' => 'Vacaciones', 'section' => 'Remuneraciones', 'prefixes' => ['vacation-entitlement.', 'api.business-days']],
    ['key' => 'holidays.index', 'label' => 'Feriados', 'section' => 'Remuneraciones'],
    ['key' => 'daily-management.index', 'label' => 'Gestión Diaria', 'section' => 'Remuneraciones', 'prefixes' => ['daily-attendances.', 'daily-yields.', 'work-schedules.', 'labor-rates.', 'labor-types.', 'bonus-types.']],
    ['key' => 'payroll-dashboard', 'label' => 'Dashboard Remuneraciones', 'section' => 'Remuneraciones'],
    ['key' => 'payroll-reports.index', 'label' => 'Reporte Mensual', 'section' => 'Remuneraciones'],
    ['key' => 'schedules.index', 'label' => 'Horarios', 'section' => 'Remuneraciones', 'prefixes' => ['api.schedules']],
    ['key' => 'cities.index', 'label' => 'Ciudades', 'section' => 'Remuneraciones', 'prefixes' => ['api.cities']],
    ['key' => 'project-evaluations.index', 'label' => 'Evaluación de Proyectos', 'section' => 'Planificación'],
    ['key' => 'cost.centers.index', 'label' => 'Centros de Costos', 'section' => 'Parámetros'],
    ['key' => 'branches.index', 'label' => 'Sucursales', 'section' => 'Parámetros'],
    ['key' => 'groupings.index', 'label' => 'Grupos de CC', 'section' => 'Parámetros'],
    ['key' => 'cost-center-varieties.index', 'label' => 'Variedades por Cuartel', 'section' => 'Parámetros'],
    ['key' => 'levels.index', 'label' => 'Niveles', 'section' => 'Parámetros', 'prefixes' => ['level2.', 'level3.', 'level4.', 'levels2.', 'levels3.', 'levels4.']],
    ['key' => 'company.reasons.index', 'label' => 'Razón Social', 'section' => 'Parámetros'],
    ['key' => 'fruits.index', 'label' => 'Frutal', 'section' => 'Parámetros'],
    ['key' => 'phenological-stages.index', 'label' => 'Etapas Fenológicas', 'section' => 'Parámetros'],
    ['key' => 'varieties.index', 'label' => 'Variedades', 'section' => 'Parámetros'],
    ['key' => 'rootstocks.index', 'label' => 'Portainjertos', 'section' => 'Parámetros'],
    ['key' => 'parcels.index', 'label' => 'Parcelas', 'section' => 'Parámetros'],
    ['key' => 'seasons.index', 'label' => 'Temporadas', 'section' => 'Parámetros'],
    ['key' => 'users.index', 'label' => 'Usuarios', 'section' => 'Parámetros'],
];
