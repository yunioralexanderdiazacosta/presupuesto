<script setup>
import { ref, watch, getCurrentInstance } from 'vue';
import Multiselect from '@vueform/multiselect';
import InputError from '@/Components/InputError.vue';

const { form } = defineProps({ form: Object });

const { appContext } = getCurrentInstance();
const page = appContext.config.globalProperties.$page;

const selectedGrouping = ref('');
watch(selectedGrouping, (newId) => {
    if (!newId) return;
    const grouping = page.props.groupings?.find(g => g.id == newId);
    if (grouping && Array.isArray(grouping.cost_centers)) {
        form.cc = grouping.cost_centers.map(cc => cc.id);
    }
});

const toggleMonth = (monthValue) => {
    const months = form.months || [];
    const idx = months.findIndex(x => String(x) === String(monthValue));
    if (idx >= 0) months.splice(idx, 1);
    else months.push(monthValue);
    form.months = [...months];
};
const selectAllMonths = () => {
    const all = (page.props.months || []).map(m => m.value);
    form.months = (form.months.length === all.length) ? [] : [...all];
};
const monthAbbr = (label) => label ? label.substring(0, 3) : '';
</script>

<template>
    <!-- Encabezado: Nivel 3 / Agrupación CC / CC -->
    <div class="row g-2 mb-3">
        <div class="col-sm-3">
            <label class="form-label small mb-1">Nivel 3 <span class="text-danger">*</span></label>
            <Multiselect
                v-model="form.subfamily_id"
                :options="$page.props.subfamilies"
                placeholder="Seleccione nivel 3"
                :searchable="true"
                :close-on-select="true"
                :class="{ 'is-invalid': form.errors.subfamily_id }"
                class="multiselect-sm"
            />
            <InputError :message="form.errors.subfamily_id" />
        </div>
        <div class="col-sm-3">
            <label class="form-label small mb-1">Agrupación CC</label>
            <Multiselect
                v-model="selectedGrouping"
                :options="page.props.groupings.map(g => ({ value: g.id, label: g.name }))"
                placeholder="Seleccione agrupación"
                :searchable="true"
                :close-on-select="true"
                class="multiselect-sm"
            />
        </div>
        <div class="col-sm-6">
            <label class="form-label small mb-1">
                Centros de Costo <span class="text-danger">*</span>
                <span v-if="form.cc?.length" class="badge bg-primary ms-1">{{ form.cc.length }} sel.</span>
            </label>
            <Multiselect
                mode="tags"
                v-model="form.cc"
                :options="$page.props.costCenters"
                placeholder="Seleccione CC"
                :searchable="true"
                :close-on-select="false"
                :class="{ 'is-invalid': form.errors.cc }"
                class="multiselect-sm"
            />
            <InputError :message="form.errors.cc" />
        </div>
    </div>

    <!-- Tabla fila única de edición -->
    <div class="border rounded" style="overflow:hidden;">
        <table class="table table-sm table-bordered align-middle mb-0 w-100" style="font-size:0.78rem;">
            <colgroup>
                <col style="width:28%;">
                <col style="width:9%;">
                <col style="width:11%;">
                <col style="width:12%;">
                <col>
                <col style="width:9%;">
            </colgroup>
            <thead style="background: linear-gradient(135deg, #3d1a74 0%, #6a35b5 100%); color:#fff; font-size:0.72rem; white-space:nowrap;">
                <tr>
                    <th class="text-white px-2 py-1">Servicio</th>
                    <th class="text-white px-2 py-1">Cantidad</th>
                    <th class="text-white px-2 py-1">Precio</th>
                    <th class="text-white px-2 py-1">Unidad</th>
                    <th class="text-white px-2 py-1">Meses</th>
                    <th class="text-white px-2 py-1">Obs.</th>
                </tr>
            </thead>
            <tbody>
                <tr style="background-color: #f5f0fb; border-left: 3px solid #6a35b5;">
                    <!-- Servicio -->
                    <td class="p-1">
                        <input v-model="form.product_name" type="text"
                            class="form-control form-control-sm agro-input"
                            placeholder="Nombre del servicio..." autocomplete="off" />
                        <InputError :message="form.errors.product_name" />
                    </td>
                    <!-- Cantidad -->
                    <td class="p-1">
                        <input type="number" v-model="form.quantity" step="0.01"
                            class="form-control form-control-sm agro-input agro-no-arrows" />
                        <InputError :message="form.errors.quantity" />
                    </td>
                    <!-- Precio -->
                    <td class="p-1">
                        <input type="number" v-model="form.price"
                            class="form-control form-control-sm agro-input agro-no-arrows" />
                        <InputError :message="form.errors.price" />
                    </td>
                    <!-- Unidad -->
                    <td class="p-1">
                        <select v-model="form.unit_id" class="form-select form-select-sm agro-input"
                            @change="form.unit_id_price = form.unit_id">
                            <option value="">--</option>
                            <option v-for="u in ($page.props.units || [])" :key="u.value" :value="u.value">{{ u.label }}</option>
                        </select>
                        <InputError :message="form.errors.unit_id" />
                    </td>
                    <!-- Meses -->
                    <td class="p-1">
                        <div class="d-grid gap-1"
                            style="grid-template-columns: 22px repeat(6, 1fr); grid-template-rows: auto auto;">
                            <button type="button" @click="selectAllMonths"
                                class="btn px-0 py-0"
                                :class="form.months.length === ($page.props.months||[]).length ? 'btn-primary' : 'btn-outline-secondary'"
                                style="font-size:0.58rem; height:100%; border-radius:3px; grid-row: 1 / 3;">✓✓</button>
                            <button v-for="m in ($page.props.months || [])" :key="m.value"
                                type="button" @click="toggleMonth(m.value)"
                                :class="form.months.some(x => String(x) === String(m.value)) ? 'btn-primary' : 'btn-outline-secondary'"
                                class="btn px-0 py-0"
                                style="font-size:0.62rem; height:18px; border-radius:3px; min-width:0;">{{ monthAbbr(m.label) }}</button>
                        </div>
                        <small class="text-danger">{{ form.errors.months }}</small>
                    </td>
                    <!-- Obs. -->
                    <td class="p-1">
                        <input type="text" v-model="form.observations"
                            class="form-control form-control-sm agro-input" placeholder="..." />
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>

<style scoped>
.agro-no-arrows::-webkit-inner-spin-button,
.agro-no-arrows::-webkit-outer-spin-button { -webkit-appearance: none; margin: 0; }
.agro-no-arrows { -moz-appearance: textfield; }
.agro-input {
    height: 26px !important;
    min-height: 26px !important;
    font-size: 0.75rem !important;
    padding: 2px 5px !important;
    width: 100%;
}
select.agro-input {
    font-size: 0.75rem !important;
    padding-top: 0 !important;
    padding-bottom: 0 !important;
}
</style>


 const { appContext } = getCurrentInstance();
    const page = appContext.config.globalProperties.$page;





// Props de formulario
    const { form } = defineProps({
        form: Object
    });
    // Agrupación para autocompletar CC
    const selectedGrouping = ref('');
    const expandedCC = ref(false);
    // Watch para llenar form.cc según agrupación
    watch(selectedGrouping, (newId) => {
        if (!newId) return;
        const grouping = page.props.groupings?.find(g => g.id == newId);
        if (grouping && Array.isArray(grouping.cost_centers)) {
            form.cc = grouping.cost_centers.map(cc => cc.id);
        }
    });



