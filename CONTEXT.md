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
- Controladores de acción única para endpoints principales.
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
