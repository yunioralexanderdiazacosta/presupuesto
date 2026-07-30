// ============================================================
// CHANGELOG DEL SISTEMA
// Copilot actualiza este archivo automáticamente al hacer cambios.
// Formato: { fecha: 'YYYY-MM-DD', titulo: '...', items: ['...'] }
// El más reciente va PRIMERO.
// ============================================================

export const changelog = [
  {
    fecha: '2026-07-30',
    titulo: 'Inventario — Nueva pestaña "Inventario Valorizado"',
    items: [
      'Se agregó la pestaña "Inventario Valorizado", que muestra el valor en dinero del stock disponible de cada producto (cantidad × precio de costo), además de la cantidad y el precio promedio.',
      'El valor se calcula considerando el costo real de compra de cada lote (factura o nota de débito) y descuenta correctamente devoluciones y descuentos por notas de crédito, igual que en el Kardex.',
      'Se agregaron cards con el valor total del inventario y el valor por cada sucursal.',
      'La pestaña "Edición" del Inventario se renombró a "Inventario" para mayor claridad.',
      'Se quitaron las pestañas "Gastos por Hectárea" y "Detalle de compra" del Inventario, que no tenían contenido.',
    ]
  },
  {
    fecha: '2026-07-30',
    titulo: 'Salidas — Monto total por sucursal en Matriz de Consumo',
    items: [
      'En la vista de Salidas, junto al card "Total Neto Salidas" ahora se muestra un card por cada sucursal con su monto total consumido.',
      'Los montos por sucursal respetan los filtros aplicados y suman exactamente el total neto.',
    ]
  },
  {
    fecha: '2026-07-30',
    titulo: 'Comparativo de salidas — Consumido coherente con el dashboard de salidas',
    items: [
      'Al filtrar por razón social, el "consumido" del comparativo (resumen, gráfico mensual y detalle por categoría) ahora usa el mismo criterio que el dashboard de salidas: la razón social del centro de costo donde se cargó cada consumo, repartido de forma proporcional.',
      'Antes el comparativo calculaba el consumido según la razón social de la factura, por lo que al filtrar mostraba un monto distinto al del dashboard de salidas; ahora ambos coinciden exactamente.',
      'El "facturado" del comparativo se mantiene según la razón social de la factura, ya que representa lo comprado y no lo consumido.',
    ],
  },
  {
    fecha: '2026-07-29',
    titulo: 'Dashboard de salidas — Montos correctos al filtrar por razón social',
    items: [
      'Al filtrar por razón social, las tarjetas de consumos (Total Consumido, Gastos, Inversiones, por nivel y por proyecto) ahora muestran solo la parte del consumo que corresponde a los centros de costo de esa razón social.',
      'Antes, un consumo repartido entre centros de costo de distintas razones sociales sumaba su monto completo en cada una, inflando los totales; ahora se reparte de forma proporcional y los totales cuadran con el desglose por estado de desarrollo.',
      'Sin filtro de razón social, los montos siguen mostrando el total completo de cada consumo, como antes.',
    ],
  },
  {
    fecha: '2026-07-29',
    titulo: 'Dashboard de salidas — Razón social según centro de costo',
    items: [
      'Al filtrar el dashboard de salidas por razón social, los consumos ahora se agrupan por la razón social del centro de costo donde se cargó cada consumo, no por la de la factura.',
      'Esto refleja correctamente los casos en que una compra de una razón social se consume en centros de costo de otra razón social.',
      'Las tarjetas de "Detalle de Compras" (Total Facturas, Notas de Débito, Notas de Crédito y Total Compras) siguen mostrándose según la razón social de la factura.',
    ],
  },
  {
    fecha: '2026-07-29',
    titulo: 'Tablero — Generales Campo y Administración por sucursal',
    items: [
      'Al filtrar el tablero por una sucursal, los montos de "Generales Campo" y "Administración" ahora muestran el valor real de esa sucursal, coincidiendo con la vista de Generales Campo.',
      'Se corrigió un cálculo que repartía estos montos por superficie y mostraba un total menor al real.',
    ],
  },
  {
    fecha: '2026-07-29',
    titulo: 'Rendiciones — Producto en el correo de aprobación',
    items: [
      'El correo que se envía al aprobador ahora incluye una columna con el producto de cada documento, antes de la descripción.',
    ],
  },
  {
    fecha: '2026-07-29',
    titulo: 'Facturas — Importar rendición trae el producto',
    items: [
      'Al importar un documento desde una rendición de gastos, ahora se completa automáticamente el producto en la nueva factura.',
      'Si el producto ya existe en tu catálogo, se selecciona el producto existente (con su unidad) en vez de crear uno nuevo.',
    ],
  },
  {
    fecha: '2026-07-29',
    titulo: 'Rendiciones — Campos obligatorios al agregar documento',
    items: [
      'Al agregar un documento a una rendición, ahora es obligatorio completar Monto, Tipo de Documento, Nº de Documento y Producto, además del Proveedor.',
      'Si falta algún dato, el sistema avisa qué campos debe completar antes de guardar.',
    ],
  },
  {
    fecha: '2026-07-28',
    titulo: 'Estimaciones — Filtro y columna por sucursal',
    items: [
      'Se agregó un selector de sucursal que filtra las variedades por cuartel mostradas en la tabla de estimaciones.',
      'La tabla y la exportación a Excel ahora incluyen una columna con la sucursal de cada cuartel.',
      'Los indicadores (total de kilos, promedio kg/ha, superficie y registros) se ajustan a la sucursal elegida. La opción "Todas las sucursales" muestra todo como antes.',
    ]
  },
  {
    fecha: '2026-07-28',
    titulo: 'Presupiuesto - Gestión por Hectárea — Filtro por sucursal',
    items: [
      'Se agregó un selector de sucursal que filtra todas las tablas, gráficos e indicadores del dashboard de gestión por hectárea.',
      'Al elegir una sucursal, tanto los costos como las superficies se ajustan a esa sucursal para que el costo por hectárea sea correcto.',
      'La opción "Todas" muestra la información consolidada como antes.',
    ]
  },
  {
    fecha: '2026-07-28',
    titulo: 'Facturas — Crear proveedor con cuenta bancaria',
    items: [
      'Al crear un proveedor desde la pantalla de Facturas, ahora el selector de banco carga correctamente las opciones.',
      'También es posible registrar las cuentas bancarias del proveedor (banco, tipo de cuenta y número) directamente desde esa ventana, igual que en el módulo de Proveedores.',
    ]
  },
  {
    fecha: '2026-07-27',
    titulo: 'Generales Campo y Administración — Asignación por sucursal',
    items: [
      'Al registrar un gasto de Generales Campo o Administración, ahora es posible asignarle una sucursal específica.',
      'Si se asigna sucursal, el costo por hectárea se calcula solo sobre la superficie de esa sucursal. Si no se asigna, el prorrateo sigue siendo global como antes.',
      'Los registros existentes no se ven afectados y continúan funcionando de forma global.',
    ]
  },
  {
    fecha: '2026-07-27',
    titulo: 'Panel principal — Estimación y costo por kilo por especie',
    items: [
      'La estimación de kilos y el costo por kilo (cosecha y total) ahora se muestran en un recuadro por cada especie con datos, en lugar de un único bloque donde solo aparecía una especie.',
      'Cada recuadro tiene su propio selector de estimación con únicamente las etapas disponibles para esa especie, y parte mostrando su estimación más reciente.',
    ]
  },
  {
    fecha: '2026-07-27',
    titulo: 'Proveedores — Se evitan RUT duplicados',
    items: [
      'Al crear o editar un proveedor, el sistema ya no permite registrar un RUT que ya exista en tu equipo.',
      'Si intentas guardar un RUT repetido, se muestra un aviso indicando que ya existe un proveedor con ese RUT.',
    ]
  },
  {
    fecha: '2026-07-20',
    titulo: 'Pago de Facturas — Cuenta bancaria del proveedor',
    items: [
      'Al registrar o editar un pago, ahora se puede seleccionar a qué cuenta bancaria del proveedor se pagó.',
      'Si el proveedor tiene varias cuentas, se eligen desde una lista (Banco • Tipo • N° de cuenta); si no tiene ninguna, el pago se registra igual.',
      'La cuenta pagada queda guardada y se muestra en el detalle de pagos de cada factura.',
      'La exportación a Excel incluye la cuenta del proveedor en columnas separadas: Banco, Tipo de cuenta y N° de cuenta.',
    ]
  },
  {
    fecha: '2026-07-20',
    titulo: 'Proveedores — Cuentas bancarias',
    items: [
      'Ahora cada proveedor puede tener una o varias cuentas bancarias.',
      'Al crear o editar un proveedor se pueden agregar y quitar cuentas, indicando banco, tipo de cuenta y número de cuenta.',
      'Las cuentas son opcionales; un proveedor puede quedar sin ninguna.',
    ]
  },
  {
    fecha: '2026-07-20',
    titulo: 'Pago de Facturas — Aviso de notas de crédito/débito',
    items: [
      'La tabla de facturas ahora muestra un distintivo cuando una factura tiene notas de crédito (NC) o débito (ND) asociadas, con el detalle al pasar el mouse.',
      'El "Saldo" de cada factura ahora considera automáticamente las notas: descuenta las notas de crédito y suma las de débito, mostrando siempre el monto real por pagar.',
      'Las facturas anuladas por una nota de crédito se marcan claramente como "ANULADA", su saldo queda en $0 y ya no se pueden pagar por error.',
      'Se agregó un indicador "Anuladas" en el resumen superior y un filtro rápido para verlas.',
      'Al registrar un pago, las facturas anuladas ya no aparecen disponibles para seleccionar.',
    ]
  },
  {
    fecha: '2026-07-20',
    titulo: 'Proveedores — Protección al eliminar',
    items: [
      'Ahora no es posible eliminar un proveedor que tenga facturas, órdenes de compra, notas de crédito/débito o rendiciones de gastos asociadas.',
      'Al intentar borrarlo, se muestra una alerta indicando exactamente qué documentos lo impiden y cuántos, evitando la pérdida accidental de información.',
    ]
  },
  {
    fecha: '2026-07-13',
    titulo: 'Tablero — Filtro por sucursal',
    items: [
      'Se agregó un selector de sucursal en el tablero principal. Al seleccionar una sucursal, todos los totales, gráficos y tablas se actualizan mostrando solo los datos de los cuarteles de esa sucursal.',
      'Los costos de Administración y Generales Campo se prorratean automáticamente según la proporción de superficie de la sucursal seleccionada.',
      'Los indicadores por rubro (gráfico de gauges), los totales por especie y la tabla de resumen ahora respetan correctamente el filtro de sucursal.',
      'Se corrigió que las tarjetas de total por frutal mostraban "Fruta 1", "Fruta 2" en vez del nombre real del frutal.',
      'La opción "Todas" muestra el consolidado completo sin filtro.',
    ]
  },
  {
    fecha: '2026-07-10',
    titulo: 'Mano de Obra — Filtro por sucursal en pestañas Detalles y Gastos por Hectárea',
    items: [
      'Se agregó el selector de sucursal en las pestañas Detalles y Gastos por Hectárea, permitiendo filtrar los centros de costo y sus datos por sucursal.',
    ]
  },
  {
    fecha: '2026-07-10',
    titulo: 'Cosecha — Filtro por sucursal en pestañas Detalles y Gastos por Hectárea',
    items: [
      'Se agregó el selector de sucursal en las pestañas Detalles y Gastos por Hectárea, permitiendo filtrar los centros de costo y sus datos por sucursal.',
    ]
  },
  {
    fecha: '2026-07-10',
    titulo: 'Servicios — Filtro por sucursal en pestañas Detalles y Gastos por Hectárea',
    items: [
      'Se agregó el selector de sucursal en las pestañas Detalles y Gastos por Hectárea, permitiendo filtrar los centros de costo y sus datos por sucursal.',
    ]
  },
  {
    fecha: '2026-07-10',
    titulo: 'Insumos — Filtro por sucursal en pestañas Detalles y Gastos por Hectárea',
    items: [
      'Se agregó el selector de sucursal en las pestañas Detalles y Gastos por Hectárea, permitiendo filtrar los centros de costo y sus datos por sucursal.',
    ]
  },
  {
    fecha: '2026-07-10',
    titulo: 'Fertilizantes — Filtro por sucursal en pestañas Detalles y Gastos por Hectárea',
    items: [
      'Se agregó el selector de sucursal en las pestañas Detalles y Gastos por Hectárea, permitiendo filtrar los centros de costo y sus datos por sucursal.',
    ]
  },
  {
    fecha: '2026-07-10',
    titulo: 'Agroquímicos — Filtro por sucursal en pestaña Detalles',
    items: [
      'Se agregó el selector de sucursal en la pestaña Detalles, permitiendo filtrar los centros de costo y sus datos por sucursal.',
    ]
  },
  {
    fecha: '2026-07-09',
    titulo: 'Insumos — Tabla sin límite de registros',
    items: [
      'La tabla de insumos ahora muestra todos los registros sin límite ni paginación.',
    ]
  },
  {
    fecha: '2026-07-09',
    titulo: 'Pago de Facturas — Mejora en exportación Excel',
    items: [
      'El Excel ahora muestra una fila por cada pago registrado, permitiendo ver el banco utilizado en cada transacción.',
      'Se agregaron las columnas RUT Proveedor, Banco, Método de Pago, Fecha de Pago, Monto Pagado y N° de Transacción.',
      'Las facturas sin pagos siguen apareciendo en el listado con los campos de pago en blanco.',
    ]
  },
  {
    fecha: '2026-07-08',
    titulo: 'Facturas — Columna Mes en exportación Excel',
    items: [
      'Se agregó la columna "Mes" (mes contable) al Excel descargable desde el listado de facturas.',
    ]
  },
  {
    fecha: '2026-07-06',
    titulo: 'Combustible — Monto gastado por maquinaria en análisis de promedios',
    items: [
      'En la pestaña "Promedios" del análisis de consumo de combustible, cada maquinaria ahora muestra el monto total gastado en dinero (pesos), calculado en base al precio unitario de cada factura desde la que se consumió el combustible.',
      'También se muestra el precio promedio por litro, calculado dividiendo el monto total entre los litros efectivamente consumidos.',
    ]
  },
  {
    fecha: '2026-06-30',
    titulo: 'Presupuesto — Mejoras en tabla de edición (6 módulos)',
    items: [
      'En los módulos de Agroquímicos, Fertilizantes, Mano de Obra, Insumos, Servicios y Cosecha: la tabla de edición ahora muestra el número de registro (#) para identificar y ordenar cada fila.',
      'Se agregó la columna "Meses" mostrando los meses asignados a cada producto. Si son muchos, se truncan y se pueden expandir todos con un botón en el encabezado de la columna.',
      'Se agregó la columna "Centros de Costo" con el mismo comportamiento de expansión desde el encabezado.',
      'El precio unitario ahora se muestra con separador de miles y centrado.',
      'Se agregó una fila de totales al pie de la tabla con el conteo de registros visibles y la suma de precios unitarios.',
      'Se agregó el monto total del presupuesto junto al buscador, con un tooltip explicando cómo se calcula.',
      'El encabezado de la tabla ya no se vuelve transparente al hacer scroll; ahora mantiene un fondo sólido.',
      'La fila de totales al pie de la tabla permanece siempre visible al hacer scroll.',
    ]
  },
  {
    fecha: '2026-06-30',
    titulo: 'Salidas — Resaltado de fila en Matriz de Consumo',
    items: [
      'En la tabla "Matriz de Consumo", al hacer clic en una fila se resalta en amarillo para facilitar su seguimiento horizontal en tablas anchas. Hacer clic nuevamente la deselecciona.',
    ]
  },
  {
    fecha: '2026-06-26',
    titulo: 'Facturas — Razón Social en tabla y exportación',
    items: [
      'Se agregó la columna "Razón Social" en la tabla de facturas, mostrando la empresa que realizó la compra.',
      'La exportación a Excel también incluye ahora la Razón Social de cada factura.',
    ]
  },
  {
    fecha: '2026-06-17',
    titulo: 'Facturas — Cards de resumen por estado de pago',
    items: [
      'Se agregaron 4 cards informativos en la vista de pagos de facturas: Total, Pendientes, Parciales y Pagadas.',
      'Cada card muestra la cantidad de facturas y el monto correspondiente a ese estado.',
      'Los cards son clickeables y filtran la tabla directamente al hacer clic en ellos.',
    ]
  },
  {
    fecha: '2026-06-17',
    titulo: 'Facturas — Filtro por forma de pago (Crédito / Contado)',
    items: [
      'En el módulo de pagos de facturas, se agregó un selector de forma de pago (Crédito / Contado) visible en la barra principal.',
      'Por defecto, la tabla muestra solo las facturas de tipo Crédito.',
      'Se puede cambiar a "Contado" o a "Todos" para ver todas las facturas.',
    ]
  },
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
