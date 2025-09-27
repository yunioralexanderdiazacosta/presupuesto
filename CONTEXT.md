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

## Notas adicionales
- Si tienes dudas sobre la lógica o reglas, consulta este archivo antes de implementar cambios.
- Actualiza este archivo si agregas reglas o flujos nuevos.

---

> Este archivo sirve como referencia viva para el equipo y para cualquier asistente de IA que colabore en el proyecto.
