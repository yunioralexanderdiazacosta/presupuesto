// ============================================================
// CHANGELOG DEL SISTEMA
// Copilot actualiza este archivo automáticamente al hacer cambios.
// Formato: { fecha: 'YYYY-MM-DD', titulo: '...', items: ['...'] }
// El más reciente va PRIMERO.
// ============================================================

export const changelog = [
  {
    fecha: '2026-06-12',
    titulo: 'Combustibles — Filtro por sucursal en análisis de consumo',
    items: [
      'En el análisis de consumo de combustible, pestaña "Promedios", se agregó un selector de sucursal para filtrar las maquinarias por sucursal asignada.',
      'En la pestaña "Gráficos", se agregó el mismo filtro: el gráfico de barras y el ranking de maquinarias muestran solo las de la sucursal seleccionada.',
      'En la pestaña "Stock Disponible", se agregó filtro por sucursal en la sección de estanques, filtrando tanto las cards visuales como la tabla detallada.',
      'Cada card de maquinaria en "Promedios" y "Gráficos" muestra la sucursal a la que pertenece.',
      'Los filtros aparecen solo cuando hay elementos de más de una sucursal.',
    ],
  },
  {
    fecha: '2026-06-12',
    titulo: 'Gestión Diaria — Nuevo reporte "Por Labor"',
    items: [
      'Se agregó una nueva vista "Por Labor" en la sección de Reportes del mes.',
      'Muestra todas las tarjas del mes agrupadas por tipo de labor, con las columnas: Labor, Nivel 3, Nombre, Fecha, Trato, Tarifa, Cant., Monto, JH, Bono y P.Obj.',
      'Cada grupo de labor incluye un subtotal y al final se muestra el total general del mes.',
    ],
  },
  {
    fecha: '2026-06-12',
    titulo: 'Salidas — Buscador con botón y carga más rápida',
    items: [
      'En las pestañas "Edición", "Matriz de Consumo" y "Matriz de Consumo por Hectárea", la búsqueda ahora se aplica al presionar el botón Buscar o la tecla Enter, en lugar de filtrar con cada letra, lo que hace la pantalla más fluida.',
      'Se agregó un botón para limpiar la búsqueda rápidamente en esas pestañas.',
      'Se corrigió la búsqueda en las pestañas de Matriz de Consumo, que no estaba aplicando el texto ingresado.',
      'Estas tres pestañas ahora muestran los primeros 200 registros y un botón "Ver más" para cargar el resto, agilizando la apertura cuando hay muchas salidas. Los totales siguen considerando todos los registros.',
    ],
  },
  {
    fecha: '2026-06-12',
    titulo: 'Salidas — Buscador con botón y carga progresiva',
    items: [
      'En la pestaña "Disponible para Salida", la búsqueda ahora se aplica al presionar el botón Buscar o la tecla Enter, en lugar de filtrar con cada letra, lo que hace la pantalla más fluida.',
      'Se agregó un botón para limpiar la búsqueda rápidamente.',
      'La tabla ahora muestra las primeras 200 filas y un botón "Ver más" para cargar el resto por bloques, evitando que la pantalla se vuelva lenta con muchos registros.',
    ],
  },
  {
    fecha: '2026-06-12',
    titulo: 'Facturas y Notas de Crédito — Corrección de stock disponible',
    items: [
      'Se corrigió un problema por el cual, al editar una factura que tenía notas de crédito, esas notas dejaban de descontar el stock y los productos volvían a aparecer como disponibles para salida.',
      'Ahora las líneas de una factura que tengan una nota de crédito asociada quedan protegidas al editar la factura, evitando que se pierda el vínculo.',
      'Si se intenta quitar un producto que tiene una nota de crédito asociada, el sistema lo impide e indica el número de la nota.',
    ],
  },
  {
    fecha: '2026-06-11',
    titulo: 'Facturas y Dashboard de Inversiones — Mejoras generales',
    items: [
      'Al eliminar una factura con salidas registradas, ahora el sistema muestra cuántas salidas tiene y permite eliminarlas todas junto con la factura, con advertencia de que la información no se puede recuperar.',
      'Al editar una factura, ahora es posible agregar nuevas líneas de productos aunque existan otras líneas del mismo producto con salidas registradas.',
      'La tabla de facturas ahora carga más rápido: las primeras 100 filas se muestran de inmediato y el botón "Ver más" carga el resto sin bloquear la pantalla.',
      'Las columnas Rendición y Digitado por se movieron al final de la tabla de facturas.',
      'Se agregaron índices en la base de datos para las tablas de facturas, salidas y notas de crédito/débito, mejorando la velocidad de carga.',
      'En el Dashboard de Inversiones se agregó una nueva tabla que muestra el detalle de las salidas de inversión organizadas por Inversión → Nivel 3 → Nivel 2 → Producto, con filas expandibles y botones para expandir/colapsar todo.',
      'La tabla de detalle por inversión del Dashboard de Inversiones fue rediseñada con un estilo visual más consistente con el resto del sistema.',
    ]
  },
  {
    fecha: '2026-06-10',
    titulo: 'Fertilizantes — Nuevo formulario de ingreso y edición',
    items: [
      'El formulario de creación y edición de fertilizantes fue rediseñado con un formato de tabla compacta, igual al de Agroquímicos.',
      'Ahora se pueden ingresar varios productos a la vez sin que el modal ocupe tanto espacio vertical.',
      'La selección de meses se hace con botones rápidos que ocupan mucho menos espacio.',
      'Los modales se amplían para aprovechar mejor el ancho de la pantalla.',
    ]
  },
  {
    fecha: '2026-06-10',
    titulo: 'Dashboard de Salidas — Filtro multiselección de Razón Social',
    items: [
      'El filtro de Razón Social ahora permite seleccionar una o varias razones sociales al mismo tiempo desde un desplegable de selección múltiple.',
      'Al seleccionar varias razones sociales, todos los datos del dashboard se filtran en conjunto para las razones sociales elegidas.',
    ]
  },
  {
    fecha: '2026-06-09',
    titulo: 'Gestión Diaria — Registro masivo de tarjas por fechas',
    items: [
      'Nuevo botón "Masivo por fechas" en la gestión diaria de tarjas.',
      'Permite registrar la misma configuración de labor, jornada, centros de costo y bono para N colaboradores en múltiples fechas del mes de forma simultánea.',
      'Selector visual de días del mes con atajo para marcar todos los días hábiles de una vez.',
      'Las fechas donde ya exista una tarja registrada se omiten automáticamente para evitar duplicados.',
    ],
  },
  {
    fecha: '2026-06-09',
    titulo: 'Temporadas — Bloqueo de temporadas pasadas',
    items: [
      'Se puede bloquear una temporada para evitar ingresar o modificar datos en ella por error.',
      'Cuando una temporada está bloqueada, los botones de creación y edición quedan deshabilitados en todos los módulos del sistema.',
      'Se muestra una etiqueta "Bloqueada" en la barra superior cuando la temporada activa está bloqueada.',
      'Desde la pantalla de Temporadas se puede bloquear o desbloquear cada temporada con un botón de candado.',
      'El bloqueo también protege a nivel de servidor, impidiendo cualquier escritura aunque se intente por otros medios.',
    ]
  },
  {
    fecha: '2026-06-06',
    titulo: 'Presupuestos — Filtro por Razón Social en módulos productivos',
    items: [
      'Se agrega el selector de Razón Social en las pestañas "Detalles" y "Gasto por Hectárea" de Fertilizantes, Mano de Obra, Servicios, Cosecha e Insumos.',
      'Al elegir una razón social, el listado de centros de costo y la tabla se filtran para mostrar únicamente los centros asociados a esa razón social.',
      'Si se cambia la razón social y el centro de costo seleccionado ya no pertenece a ella, la selección de centro de costo se limpia automáticamente.',
    ]
  },
  {
    fecha: '2026-06-06',
    titulo: 'Agroquímicos — Filtro por Razón Social',
    items: [
      'Se agrega un selector de Razón Social en las pestañas "Detalles" y "Gasto por Hectárea" de Agroquímicos.',
      'Al elegir una razón social, el listado de centros de costo y la tabla se filtran para mostrar únicamente los centros asociados a esa razón social.',
      'Si se cambia la razón social y el centro de costo seleccionado ya no pertenece a ella, la selección de centro de costo se limpia automáticamente.',
    ]
  },
  {
    fecha: '2026-06-06',
    titulo: 'Dashboard Comparativo — Filtro por Razón Social',
    items: [
      'Se agrega un selector de Razón Social en el Dashboard Comparativo (Presupuesto vs Real).',
      'Al filtrar, los datos de presupuesto consideran solo los centros de costo de la razón social seleccionada; los centros de costo sin razón social asignada siempre se incluyen (prorrateados).',
      'El filtro aplica también a los datos de facturado, consumido y remuneraciones. Las facturas e ítems sin razón social asignada se incluyen en todos los filtros.',
    ]
  },
  {
    fecha: '2026-06-04',
    titulo: 'Salidas — Tabla Consumo Matriz con prorrateo por CC',
    items: [
      'Se agrega la pestaña "Consumo Matriz" en la vista de Salidas.',
      'Muestra una fila por cada salida registrada (producto × factura) con columnas dinámicas, una por cada centro de costo de la temporada.',
      'Cada celda muestra el monto en pesos prorrateado según la superficie asignada al centro de costo. Si el CC no está asociado a la salida, muestra "—".',
      'Fila de totales al pie de la tabla para visualizar el gasto total por centro de costo.',
    ]
  },
  {
    fecha: '2026-06-03',
    titulo: 'Dashboard Outflows — Filtro por Razón Social',
    items: [
      'Se agrega un selector de Razón Social en el encabezado del Dashboard de Outflows.',
      'Al seleccionar una razón social, todos los datos del dashboard se filtran: consumos, inversiones, gastos, facturas, notas de crédito/débito, gráficos por nivel y por estado de desarrollo.',
      'El cálculo de Costo/Kilo también respeta el filtro, ajustando tanto la producción como las estimaciones de kilos al contexto de la razón social seleccionada.',
      'Las remuneraciones (tarjas, bonos y horas extra) también se filtran por razón social, usando el centro de costo asociado a cada registro como enlace.',
      'El selector muestra solo las razones sociales con movimientos en la temporada activa.',
    ]
  },
  {
    fecha: '2026-06-03',
    titulo: 'Dashboard Comparativo — Nivel 3 en Detalle por Categoría',
    items: [
      'La tabla "Detalle por Categoría" ahora desglosa cada fila hasta el Nivel 3 (subfamilia), permitiendo ver con mayor granularidad el presupuesto, facturado y consumido.',
      'El presupuesto, facturado, notas de crédito/débito y egresos se agrupan ahora por Nivel 1 + Nivel 2 + Nivel 3.',
      'Se agrega la columna "Nivel 3" en la tabla, visible también al expandir grupos.',
    ]
  },
  {
    fecha: '2026-06-01',
    titulo: 'Gestión Diaria — Reportes agrupados por contrato',
    items: [
      'El reporte mensual de tarjas ahora agrupa por ID de contrato en vez de por empleado.',
      'Cada fila muestra el badge con el número de contrato (#ID) junto al nombre del trabajador.',
      'Un trabajador con dos contratos activos en el mismo mes aparece como dos filas independientes.',
    ],
  },
  {
    fecha: '2026-06-01',
    titulo: 'Remuneraciones — Agrupación por contrato en todos los tabs',
    items: [
      'Todos los tabs del reporte mensual (Resumen, Nómina, Sueldos, Liquidación) ahora agrupan por ID de contrato en vez de por empleado.',
      'Un trabajador con dos contratos activos en el mismo mes aparece como dos filas independientes.',
      'El botón "Ver" y la impresión PDF mantienen compatibilidad con la vista individual de cada empleado.',
    ],
  },
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
