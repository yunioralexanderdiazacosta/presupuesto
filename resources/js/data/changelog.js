// ============================================================
// CHANGELOG DEL SISTEMA
// Copilot actualiza este archivo automáticamente al hacer cambios.
// Formato: { fecha: 'YYYY-MM-DD', titulo: '...', items: ['...'] }
// El más reciente va PRIMERO.
// ============================================================

export const changelog = [
  {
    fecha: '2026-06-01',
    titulo: 'Remuneraciones — Nueva pestaña Resumen Liquidación',
    items: [
      'Nueva pestaña "Resumen Liquidación" en el reporte mensual de remuneraciones.',
      'Muestra por contrato: datos del trabajador, tipo de contrato, AFP, salud, sueldo base, jornadas trabajadas, JH vacaciones, licencias, cargas familiares, anticipos y otros descuentos.',
      'Incluye fecha y causal de término para contratos finiquitados en el mes.',
      'Exportación a Excel con todos los campos de la tabla.',
    ],
  },
  {
    fecha: '2026-05-29',
    titulo: 'Inventario — Kardex por sucursal y exportación Excel',
    items: [
      'Kardex ahora filtra correctamente por sucursal: cada fila muestra solo los movimientos de su propia sucursal.',
      'Botón Excel en tab Edición: exporta el listado filtrado de inventario.',
      'Botón Excel en tab Kardex: exporta tanto el listado de productos como el detalle de cada kardex expandido.',
    ]
  },
  {
    fecha: '2026-05-29',
    titulo: 'Salidas — Fecha por defecto desde la factura',
    items: [
      'Al abrir el card de "Disponible para Salida", la fecha se pre-carga automáticamente con la fecha de la factura o nota de débito correspondiente.',
    ]
  },
  {
    fecha: '2026-04-18',
    titulo: 'Presupuesto — Mejoras en pestañas de detalle',
    items: [
      'Totales en tabla (tfoot) en los 6 módulos: Agroquímicos, Fertilizantes, Mano de Obra, Servicios, Insumos y Cosecha.',
      'Botón Agrupar: consolida productos por Subfamilia/Nivel 3, sumando cantidades, montos y distribución mensual.',
      'Exportar Excel en pestañas Detalles y Gastos por Hectárea, adaptándose al modo agrupado.',
      'Rendimientos: columna renombrada "Horas" → "Jornadas".',
    ]
  },
  {
    fecha: '2026-04-01',
    titulo: 'Facturas — Campo Exento por línea',
    items: [
      'Nuevo campo Exento por línea: el IVA (19%) se calcula solo sobre el neto afecto.',
      'Consolidado de Documentos muestra columnas Neto Afecto, Exento, IVA y Total por separado.',
      'Cambio de temporada restringido a roles Admin o Super Admin.',
    ]
  },
];
