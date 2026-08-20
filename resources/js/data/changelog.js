// ============================================================
// CHANGELOG DEL SISTEMA
// Copilot actualiza este archivo automáticamente al hacer cambios.
// Formato: { fecha: 'YYYY-MM-DD', titulo: '...', items: ['...'] }
// El más reciente va PRIMERO.
// ============================================================

export const changelog = [
  {
    fecha: '2026-08-20',
    titulo: 'Pago de Facturas — Ajuste en el informe de deuda',
    items: [
      'El informe de deuda por razón social ahora respeta los mismos filtros activos en la tabla principal (tipo de pago, proveedor, fechas, búsqueda), evitando que muestre un total distinto al de las tarjetas de resumen.',
    ]
  },
  {
    fecha: '2026-08-20',
    titulo: 'Pago de Facturas — Corrección en tarjetas de resumen',
    items: [
      'Se corrigió la tarjeta "Parciales", que por diferencias de centavos en el cálculo del IVA mostraba montos casi en cero para facturas que en realidad ya estaban pagadas por completo.',
      'Esas facturas ahora se reflejan correctamente como "Pagadas" y los totales de las tarjetas coinciden con el saldo real de cada factura en la tabla.',
    ]
  },
  {
    fecha: '2026-08-20',
    titulo: 'Pago de Facturas — Nuevo informe de deuda por razón social',
    items: [
      'Se agregó el botón "Informe de Deuda" en la vista principal de Pago de Facturas, que abre una tabla resumen con el saldo pendiente de pago agrupado por razón social y mes.',
      'La tabla se puede filtrar por razón social, mes y proveedor para revisar la deuda de forma más específica.',
      'Se incluyen totales por columna y por fila para ver rápidamente el monto adeudado total.',
    ]
  },
  {
    fecha: '2026-08-20',
    titulo: 'Salidas — Columna Observaciones en pestaña Edición',
    items: [
      'La columna "Notas" de la tabla principal de salidas (pestaña Edición) ahora se llama "Observaciones", igual que en la Matriz de Consumo, incluyendo su exportación a Excel.',
    ]
  },
  {
    fecha: '2026-08-20',
    titulo: 'Salidas — Campo Observaciones más ancho al registrar',
    items: [
      'En el formulario de "Registrar salida de producto", el campo Observaciones ahora ocupa todo el ancho de la fila, permitiendo escribir textos más largos con mayor comodidad.',
    ]
  },
  {
    fecha: '2026-08-20',
    titulo: 'Salidas — Columna Observaciones en Matriz de Consumo',
    items: [
      'Se agregó la columna "Observaciones" en la tabla "Matriz de Consumo", justo después de Sucursal, y también en su exportación a Excel.',
    ]
  },
  {
    fecha: '2026-08-18',
    titulo: 'Maquinarias — Corrección de sucursales duplicadas',
    items: [
      'Se corrigió el listado de sucursales del formulario de Maquinaria, que mostraba la misma sucursal repetida cuando existía en más de una temporada.',
    ]
  },
  {
    fecha: '2026-08-17',
    titulo: 'Empresas — Control de módulos por empresa',
    superAdminOnly: true,
    items: [
      'El Super Admin ahora puede elegir, empresa por empresa, qué módulos del sistema puede ver y usar cada una (por ejemplo, deshabilitar "Remuneraciones" o "Producción" para empresas que no lo necesiten).',
      'Se agregó un nuevo botón en la pantalla de Empresas para abrir el listado de módulos y marcar/desmarcar los que cada empresa tiene permitido usar.',
      'Si un módulo está deshabilitado para una empresa, sus usuarios ya no pueden entrar a esa sección aunque escriban la dirección directamente.',
    ]
  },
  {
    fecha: '2026-08-17',
    titulo: 'Nuevo módulo — Solicitudes de Pago',
    items: [
      'Se agregó un nuevo módulo "Solicitudes de Pago" para pedir que se ejecute el pago de una factura o documento a los usuarios encargados.',
      'Al crear una solicitud se puede adjuntar la factura o imagen del comprobante, indicar la fecha, el nivel de urgencia (Normal, Importante o Urgente), un concepto/observaciones, el o los Centros de Costo relacionados, y elegir a qué usuarios se les enviará.',
      'Ahora se pueden adjuntar varios archivos (facturas o comprobantes) a una misma solicitud, no solo uno.',
      'Los usuarios seleccionados reciben un correo con los datos de la solicitud, un PDF de respaldo y un botón para marcarla como gestionada una vez realizado el pago; el primero que la gestione la cierra para todos.',
      'El listado permite filtrar entre "Todas", "Creadas por mí" y "Pendientes para mí", además de ver el estado y quién gestionó cada solicitud.',
      'Se agregó una vista de detalle para cada solicitud (haciendo clic en el folio o en el botón "Ver") y un botón para imprimir/descargar el PDF con los datos de la solicitud.',
    ]
  },
  {
    fecha: '2026-08-17',
    titulo: 'Salidas — Sucursal en el formulario y monto disponible en la lista',
    items: [
      'En la pestaña "Disponible para Salida", el formulario para registrar una salida ahora muestra también la Sucursal de la línea (solo de referencia, no se puede editar).',
      'Se agregó la columna "Monto" en la lista de líneas disponibles, mostrando el valor total del stock disponible de esa línea (precio unitario multiplicado por el stock).',
    ]
  },
  {
    fecha: '2026-08-15',
    titulo: 'Consolidado de Salidas — Ahora también muestra Remuneraciones',
    items: [
      'Se agregó un filtro "Tipo de Gasto" para elegir entre ver solo Gestión (compras), solo Remuneraciones (jornadas, bonos y horas extra del personal) o ambos juntos en la misma tabla.',
      'Cuando se incluyen Remuneraciones, cada registro se muestra distribuido por centro de costo, igual que las salidas de gestión, para poder compararlos y sumarlos en un solo reporte.',
      'Se agregó la columna "Mes" visible directamente en la tabla (antes solo aparecía en el Excel exportado).',
      'El filtro de Tipo de Gasto y la nueva columna Mes también están disponibles al exportar a Excel, tanto en "Exportar Página" como en "Exportar Todo".',
      'La tabla ahora carga por defecto solo los datos de Gestión, para que la vista se abra más rápido; el usuario puede cambiar a Remuneraciones o Todos cuando lo necesite.',
      'Se simplificó el diseño de la tabla: los botones de exportar y limpiar filtros ahora están en la misma fila que los filtros, ocupando menos espacio.',
    ]
  },
  {
    fecha: '2026-08-13',
    titulo: 'Consolidado de Salidas — Filtros por mes, proveedor y niveles',
    items: [
      'El consolidado de salidas ahora incluye filtros por mes, proveedor, nivel 2 y nivel 3 para buscar dentro del total del período, no solo en la página visible.',
      'Los filtros se aplican sobre todo el conjunto de datos de la temporada y equipo activos, y el archivo Excel exportado respeta exactamente ese mismo alcance.',
    ]
  },
  {
    fecha: '2026-08-11',
    titulo: 'Rendiciones — Ahora se puede editar la descripción en cualquier momento',
    items: [
      'La descripción de una rendición de gastos ya se puede editar sin importar el estado en que se encuentre (borrador, enviada, aprobada, etc.), no solo mientras está en borrador.',
    ]
  },
  {
    fecha: '2026-08-11',
    titulo: 'Pagos de Facturas — Facturas cubiertas por una rendición',
    items: [
      'Las facturas vinculadas a una rendición de gastos se muestran como "Pagada" en el listado de Pagos de Facturas, con una etiqueta "RENDICIÓN" que enlaza directo a la rendición correspondiente.',
      'Ya no aparece el botón "Registrar Pago" para estas facturas, ni se pueden encontrar en el buscador al registrar un pago.',
      'Los totales y resúmenes del dashboard de pagos consideran estas facturas como pagadas para que las cifras cuadren correctamente.',
    ]
  },
  {
    fecha: '2026-08-11',
    titulo: 'Rendiciones y Facturas — Evitar duplicados cuando la factura ya fue ingresada',
    items: [
      'Al crear una factura nueva, si el proveedor y N° de documento coinciden con un documento pendiente de una rendición aprobada, el sistema pregunta si deseas vincularla a esa rendición antes de guardar.',
      'En el listado "Importar desde Rendición" de Facturas, cuando un documento de la rendición ya tiene una factura similar cargada en el sistema, ahora se muestra un botón "Vincular" en vez de "Crear Factura", para asociarlo sin generar una factura duplicada.',
      'En el detalle de cada rendición, los documentos pendientes ahora tienen un botón para buscar y vincular manualmente una factura ya existente, evitando que el documento quede pendiente para siempre.',
    ]
  },
  {
    fecha: '2026-08-10',
    titulo: 'Rendiciones de Gastos — Impresión de reporte en PDF',
    items: [
      'Se agregó un botón para imprimir cada rendición en PDF, disponible tanto en el listado (columna Acciones) como dentro del detalle de la rendición.',
      'El reporte incluye los datos generales (número, rendidor, estado, aprobador, descripción), el detalle completo de documentos rendidos y los totales de la rendición (total, contabilizado y pendiente).',
    ]
  },
  {
    fecha: '2026-08-10',
    titulo: 'Remuneraciones — Razón Social por contrato y Centro de Costo obligatorio en tarjas',
    items: [
      'Si un trabajador cambió de contrato con otra razón social durante la temporada, sus rendimientos ahora se asignan a la razón social vigente en cada período, en vez de asignarse todos a la razón social de su contrato más reciente.',
      'En la tabla "Distribución por Parcela según RS Contratante" se agregó la fila "Sin Parcela" para que ningún monto quede fuera del total cuando falte la asignación de centro de costo.',
      'Al registrar una tarja (individual o masiva) ahora es obligatorio indicar al menos un Centro de Costo, salvo en ausencias no pagadas.',
    ]
  },
  {
    fecha: '2026-08-10',
    titulo: 'Salidas — Desglose de Inversiones y orden de tarjetas en Estado de Desarrollo',
    items: [
      'En "Consumos por Estado de Desarrollo", la tarjeta "Sin Inversiones" ahora se muestra primero (izquierda) y "Resumen con Inversiones" a la derecha.',
      'Al activar "Desglose", la tarjeta "Resumen con Inversiones" ahora muestra además cuánto del total de cada estado corresponde específicamente a inversiones, para entender por qué ese total puede ser mayor al card "Total Inversiones" (que solo suma inversiones de toda la temporada).',
    ]
  },
  {
    fecha: '2026-08-10',
    titulo: 'Panel Técnico — Fila de subtotal en tabla Gastos por Hectárea',
    items: [
      'Se agregó la fila de subtotal por especie en la tabla "Gastos por Hectáreas", igual que en "Estado de Desarrollo".',
    ]
  },
  {
    fecha: '2026-08-10',
    titulo: 'Panel Técnico — Columna de Total en tablas de Estado de Desarrollo y Gastos por Hectárea',
    items: [
      'Se agregó/reposicionó la columna "Total" justo después de "Estado de desarrollo" en las tablas "Estado de Desarrollo" y "Gastos por Hectáreas", mostrando la suma de todos los tipos de gasto de cada fila.',
      'También se incluyó el total en los subtotales por especie y en el Total General de la tabla "Estado de Desarrollo".',
    ]
  },
  {
    fecha: '2026-08-07',
    titulo: 'Detalle de Salidas por Sucursal — Filtro de estado de desarrollo no mostraba datos',
    items: [
      'En la tabla "Consumo por Hectárea", al elegir un estado de desarrollo la tabla quedaba vacía por un problema al comparar el estado seleccionado con los datos.',
      'Ahora el filtro funciona correctamente y muestra la información del estado de desarrollo elegido.',
    ]
  },
  {
    fecha: '2026-08-07',
    titulo: 'Documentos Consolidados — Columna Proveedor más ancha',
    items: [
      'En las tablas "Lista" y "Mensual" se ensanchó la columna Proveedor para leer mejor los nombres largos, y se ajustó el ancho de la columna Tipo.',
    ]
  },
  {
    fecha: '2026-08-07',
    titulo: 'Órdenes de Aplicación — Ahora se ven todas las órdenes en una sola lista',
    items: [
      'Se quitó la paginación de la tabla: ahora se muestran todas las órdenes de la temporada con scroll, para que el buscador, el filtro por estado y el ordenamiento funcionen sobre el listado completo y no solo sobre una página a la vez.',
    ]
  },
  {
    fecha: '2026-08-07',
    titulo: 'Órdenes de Aplicación — Filtro por estado y ordenamiento',
    items: [
      'Se agregó un filtro para mostrar rápidamente solo las órdenes con un estado determinado (Pendiente, En Proceso, Completada, Cancelada).',
      'Ahora se puede ordenar la tabla por número de orden o por fecha, haciendo clic en el encabezado de la columna correspondiente.',
    ]
  },
  {
    fecha: '2026-08-07',
    titulo: 'Órdenes de Aplicación — Tabla más prolija',
    items: [
      'La columna de Centros de Costo ahora muestra los nombres en una sola línea separados por comas, en vez de uno debajo del otro, evitando que las filas de la tabla se vean con alturas irregulares.',
    ]
  },
  {
    fecha: '2026-08-07',
    titulo: 'Sistema — Corrección de espacio sobrante en pantallas de celular',
    items: [
      'Se corrigió un problema que hacía que, en celulares, toda la pantalla quedara con un margen vacío no deseado a la derecha.',
      'La barra superior ahora oculta el precio del dólar y el nombre del equipo en pantallas angostas, dejando esa información visible desde tablet hacia arriba.',
    ]
  },
  {
    fecha: '2026-08-07',
    titulo: 'Rendiciones de Gastos — Editar rendición y documentos antes de enviarla',
    items: [
      'Ahora se puede editar la descripción de una rendición mientras esté en estado Borrador.',
      'También se pueden editar los documentos ya agregados (fecha, proveedor, monto, comprobante, etc.) antes de enviar la rendición a aprobación.',
      'Se agregó un botón "Editar" junto a cada documento, tanto en la tabla de escritorio como en las tarjetas de celular.',
    ]
  },
  {
    fecha: '2026-08-07',
    titulo: 'Rendiciones de Gastos — Aviso de carga al subir un documento',
    items: [
      'Al agregar un documento con una foto o PDF pesado, ahora se muestra un indicador de "Subiendo..." con el porcentaje de avance.',
      'Mientras el documento se está subiendo, la ventana no se puede cerrar por error, evitando dudas sobre si el guardado quedó en curso.',
    ]
  },
  {
    fecha: '2026-08-07',
    titulo: 'Rendiciones de Gastos — Ajustes de visualización en celulares',
    items: [
      'Se corrigió que el título y los botones de la parte superior se sobrepusieran en pantallas de celular.',
      'Las ventanas para crear una rendición y para agregar un documento ahora se ven prolijas en el celular, con márgenes y bordes redondeados en vez de ocupar el borde completo de la pantalla.',
    ]
  },
  {
    fecha: '2026-08-07',
    titulo: 'Rendiciones de Gastos — Mejoras en el formulario para agregar documentos',
    items: [
      'Ahora se puede crear un proveedor nuevo directamente desde el formulario de agregar documento, sin salir de la rendición.',
      'El campo "Producto / Concepto" es más ancho para escribir con más comodidad.',
      'El formulario se adapta mejor a pantallas de celular, ocupando toda la pantalla para facilitar el ingreso.',
      'Las fotos de comprobantes se optimizan automáticamente antes de subirlas, reduciendo su peso sin perder legibilidad.',
    ]
  },
  {
    fecha: '2026-08-06',
    titulo: 'Notas de Crédito/Débito — El mes contable no se mostraba en el detalle de la nota',
    items: [
      'En la vista de detalle de una nota de crédito/débito, el campo "Mes Contable" siempre aparecía vacío.',
      'Ahora se completa automáticamente según la fecha de la nota (tanto para las notas nuevas como para las ya existentes) y se muestra correctamente en el detalle.',
    ]
  },
  {
    fecha: '2026-08-06',
    titulo: 'Dashboard Comparativo — Notas de crédito/débito no se descontaban en el Detalle Mensual por Categoría',
    items: [
      'En "Detalle Mensual por Categoría", el monto Real (Facturado) no estaba descontando las notas de crédito ni sumando las de débito, por lo que el total podía salir más alto que el mostrado en el Resumen Mensual de Presupuesto vs Costos.',
      'Ahora las notas de crédito/débito se aplican correctamente mes a mes en esa tabla, quedando consistente con el resto del dashboard.',
    ]
  },
  {
    fecha: '2026-08-06',
    titulo: 'Documentos Consolidados — Notas de Crédito/Débito no aparecían al filtrar por sucursal',
    items: [
      'En "Documentos Consolidados", al filtrar por una sucursal específica, las notas de crédito y débito desaparecían de las tablas (Resumen, Lista y Mensual) aunque sí correspondían a esa sucursal.',
      'Ahora el sistema identifica correctamente la sucursal de cada nota: para notas de débito usa la sucursal indicada en la nota, y para notas de crédito usa la sucursal de la factura original a la que hace referencia.',
    ]
  },
  {
    fecha: '2026-08-06',
    titulo: 'Dashboard Comparativo — Selector Facturado/Consumido en Detalle Mensual por Categoría',
    items: [
      'En la tabla "Detalle Mensual por Categoría" ahora se puede elegir si la columna "Real" muestra el monto Facturado (como antes) o el Consumido (salidas de bodega).',
      'Se agregó un aviso e ícono de ayuda que explican que Facturado y Consumido usan criterios de clasificación y de reparto por razón social distintos, por lo que algunas categorías pueden variar entre ambas vistas.',
      'El consumido se calcula bajo demanda solo al seleccionarlo, para no afectar el tiempo de carga habitual de la página.',
    ]
  },
  {
    fecha: '2026-08-05',
    titulo: 'Reportes de Remuneraciones — Corrección del Total Líquido en Resumen Liquidación',
    items: [
      'En la pestaña "Resumen Liquidación", la columna "Total Líquido" ahora también descuenta los "Otros Descuentos" de cada trabajador, además de los anticipos.',
      'El total general de la tabla y la exportación a Excel quedan igualmente corregidos.',
    ]
  },
  {
    fecha: '2026-08-04',
    titulo: 'Dashboard Comparativo — Corrección de montos en Generales Campo y Administración al filtrar por razón social',
    items: [
      'Al filtrar el dashboard comparativo por una razón social específica, los montos de "Generales Campo" y "Administración" no coincidían con lo cargado originalmente para esa sucursal.',
      'Ahora estos montos se calculan según la sucursal real de cada registro, buscando a qué razón social pertenece esa sucursal a través de sus centros de costo, en lugar de repartir un porcentaje general del equipo.',
      'Los registros sin sucursal asignada siguen mostrándose siempre, igual que antes.',
    ]
  },
  {
    fecha: '2026-08-03',
    titulo: 'Dashboard Comparativo — Nueva tabla de Detalle Mensual por Categoría',
    items: [
      'Se agregó una tabla nueva que muestra, mes a mes, el Presupuesto, el Real (Facturado + Remuneraciones) y la Diferencia de cada categoría.',
      'Incluye un filtro para elegir qué meses ver (uno o varios a la vez), mostrando cada mes elegido en su propio grupo de columnas.',
      'Las categorías se organizan en un árbol: Nivel 1 aparece cerrado por defecto y se puede desplegar hasta Nivel 2 y Nivel 3 haciendo clic.',
      'Al elegir más de un mes, se agrega al final una columna de "Total selección" con la suma de Presupuesto, Real y Diferencia de todos los meses elegidos.',
    ]
  },
  {
    fecha: '2026-08-03',
    titulo: 'Dashboard Comparativo — Diferencia vs Consumido ahora descuenta Remuneraciones',
    items: [
      'En la tabla "Resumen Mensual: Presupuesto vs Costos", la fila de Diferencia contra Consumido no restaba las Remuneraciones del mes, mostrando un resultado más alto de lo real.',
      'Ahora esa fila descuenta las Remuneraciones igual que la fila de Diferencia contra Facturado, quedando ambas consistentes.',
    ]
  },
  {
    fecha: '2026-08-03',
    titulo: 'Dashboard Comparativo — Remuneraciones distribuidas correctamente en Detalle por Categoría',
    items: [
      'La columna "Remun." de la tabla "Detalle por Categoría" mostraba todo el monto de remuneraciones amontonado en una sola subfamilia (Nivel 3) en vez de distribuirlo entre las categorías reales donde se generó.',
      'Ahora cada monto de remuneraciones aparece en la fila exacta de Nivel 1/Nivel 2/Nivel 3 que le corresponde, igual que Presupuestado, Facturado y Consumido.',
    ]
  },
  {
    fecha: '2026-08-03',
    titulo: 'Dashboard Comparativo — Detalle correcto al hacer clic en Remuneraciones',
    items: [
      'Al hacer clic en la barra de Remuneraciones del gráfico mensual, ahora se muestra el detalle real de remuneraciones del mes (agrupado por rubro y categoría), en lugar de mostrar por error los datos de Consumos.',
      'Se agregó la etiqueta "Remuneraciones" y su propio color en el título y la tabla de detalle, para diferenciarla claramente de Facturado y Consumos.',
    ]
  },
  {
    fecha: '2026-08-03',
    titulo: 'Inventario — Nuevos filtros por Nivel 1 y Nivel 2',
    items: [
      'Se agregaron dos nuevos filtros (Nivel 1 y Nivel 2) junto al de Sucursal en las pestañas Inventario, Inventario Valorizado y Kardex.',
      'El filtro de Nivel 2 se ajusta automáticamente según el Nivel 1 elegido, mostrando solo las categorías relacionadas.',
    ]
  },
  {
    fecha: '2026-08-03',
    titulo: 'Panel Técnico — Nuevo filtro por Sucursal',
    items: [
      'Se agregó un selector de Sucursal en la parte superior del Panel Técnico que permite ver toda la información (tablas, totales y montos mensuales) filtrada por una sucursal específica o por todas en conjunto.',
      'Al elegir una sucursal, se actualizan de forma coherente todas las secciones de la vista: totales por rubro, estado de desarrollo, gasto por hectárea y totales de administración/campo.',
    ]
  },
  {
    fecha: '2026-07-31',
    titulo: 'Salidas — Tarjetas y filtro de sucursal corregidos',
    items: [
      'Las tarjetas de monto por sucursal (arriba de la pestaña Edición) ahora reflejan la sucursal real del cuartel donde se consumió, repartiendo el monto proporcionalmente cuando una salida se usó en cuarteles de distintas sucursales.',
      'El filtro de sucursal de la pestaña Edición ahora busca por la sucursal del cuartel en vez de la sucursal indicada al ingresar la factura.',
      'Se quitó la columna "Sucursal" de la tabla de Edición, ya que una misma salida puede repartirse entre cuarteles de más de una sucursal (esa información ahora se ve reflejada en las tarjetas y el filtro).',
    ]
  },
  {
    fecha: '2026-07-31',
    titulo: 'Detalle de Salidas por Sucursal — Corrección en el criterio de sucursal',
    items: [
      'La tabla de montos ahora asigna cada consumo a la sucursal real del cuartel (centro de costo) donde se usó, en vez de la sucursal indicada al ingresar la factura de compra.',
      'Cuando un consumo se reparte entre cuarteles de distintas sucursales, el monto se divide proporcionalmente según la superficie de cada uno, igual que en la vista por hectárea.',
      'Se corrigió además el cálculo de combustibles, que antes no se estaba sumando correctamente en la vista por hectárea.',
    ]
  },
  {
    fecha: '2026-07-31',
    titulo: 'Detalle de Salidas por Sucursal — Nueva vista de gasto por hectárea',
    items: [
      'Se agregó una tabla de "Consumo por Hectárea" que muestra el gasto dividido por la superficie cultivada, para comparar de forma justa entre sucursales de distinto tamaño.',
      'Incluye un filtro por estado de desarrollo (producción, año 2, administración, etc.) y permite ver una o varias sucursales al mismo tiempo, igual que la tabla de montos.',
    ]
  },
  {
    fecha: '2026-07-31',
    titulo: 'Detalle de Salidas por Sucursal — Comparación entre sucursales',
    items: [
      'El filtro de sucursal ahora permite seleccionar varias sucursales a la vez.',
      'Al elegir más de una sucursal, la tabla muestra el monto de cada una en columnas separadas para comparar fácilmente, en vez de sumarlas en un solo total.',
    ]
  },
  {
    fecha: '2026-07-31',
    titulo: 'Reportes de Remuneraciones — Corrección en descarga de Excel',
    items: [
      'Se corrigió un error en la Nómina de Pago y en Anticipos donde el botón "Excel" descargaba un archivo que no se abría correctamente como planilla.',
    ]
  },
  {
    fecha: '2026-07-30',
    titulo: 'Nuevo Dashboard — Detalle de Salidas por Sucursal',
    items: [
      'Se agregó un nuevo dashboard que permite analizar el consumo y el stock valorizado agrupados por sucursal.',
      'Muestra una tarjeta por sucursal con el monto consumido y el valor del stock disponible.',
      'Incluye una tabla que permite ver el monto por Área (Nivel 1), y abrirla para ver el detalle por Categoría (Nivel 2) y Subcategoría (Nivel 3), filtrando por una sucursal específica si se desea.',
    ]
  },
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
];
