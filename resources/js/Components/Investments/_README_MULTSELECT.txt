// Documentación rápida para migrar select a Multiselect en InvestmentForm.vue
// 1. Importa Multiselect de '@vueform/multiselect'.
// 2. Usa <Multiselect> para los campos de centros de costo y mes, igual que en ServiceForm.vue.
// 3. Para centros de costo: mode="tags", v-model="form.cost_centers", :options="costCenters".
// 4. Para mes: mode="single" o normal, v-model="form.month_execute", :options="months" (adaptar formato si es necesario).
// 5. Agrega el import del CSS de Multiselect si no está.
// 6. Elimina los <select> antiguos.
// 7. Verifica que el v-model sea compatible con el backend (array de ids para cost_centers, int para month_execute).
// 8. Usa la clase 'multiselect-blue form-control' para consistencia visual.
// 9. Revisa ServiceForm.vue como referencia visual y de props.
