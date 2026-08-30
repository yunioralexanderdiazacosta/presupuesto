<script setup>
import { getCurrentInstance, watch } from "vue";
import Multiselect from "@vueform/multiselect";
import InputError from "@/Components/InputError.vue";
import { useInversionOperation } from "@/Composables/useInversionOperation";

const { form } = defineProps({
    form: Object,
});

const { appContext } = getCurrentInstance();
const page = appContext.config.globalProperties.$page;
const { isInversionOp } = useInversionOperation();

watch(() => form.operation_id, (newVal) => {
    if (!isInversionOp(newVal, page.props.operations)) {
        form.investment_id = null;
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
    <!-- Encabezado: Sucursal / Nivel 3 / Operación -->
    <div class="row g-2 mb-3">
        <div class="col-sm-4" v-if="$page.props.branches && $page.props.branches.length > 0">
            <label class="form-label small mb-1">Sucursal <span class="text-danger">*</span></label>
            <select v-model="form.branch_id" class="form-select form-select-sm" :class="{ 'is-invalid': form.errors.branch_id }">
                <option :value="null" disabled>Seleccione sucursal...</option>
                <option v-for="b in $page.props.branches" :key="b.value" :value="b.value">{{ b.label }}</option>
            </select>
        </div>
        <div class="col-sm-4">
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
        <div class="col-sm-4">
            <label class="form-label small mb-1">Operación <span class="text-danger">*</span></label>
            <Multiselect
                v-model="form.operation_id"
                :options="$page.props.operations"
                placeholder="Seleccione operación"
                :searchable="true"
                :close-on-select="true"
                :class="{ 'is-invalid': form.errors.operation_id }"
                class="multiselect-sm"
            />
            <InputError :message="form.errors.operation_id" />
        </div>
        <div v-if="isInversionOp(form.operation_id, $page.props.operations)" class="col-sm-4">
            <label class="form-label small mb-1">Inversión <span class="text-danger">*</span></label>
            <Multiselect
                v-model="form.investment_id"
                :options="$page.props.investments"
                placeholder="Seleccione inversión"
                :searchable="true"
                :close-on-select="true"
                :class="{ 'is-invalid': form.errors.investment_id }"
                class="multiselect-sm"
            />
            <InputError :message="form.errors.investment_id" />
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
            <thead style="background: linear-gradient(135deg, #8a5a10 0%, #c98a1f 100%); color:#fff; font-size:0.72rem; white-space:nowrap;">
                <tr>
                    <th class="text-white px-2 py-1">Producto</th>
                    <th class="text-white px-2 py-1">Cantidad</th>
                    <th class="text-white px-2 py-1">Precio</th>
                    <th class="text-white px-2 py-1">Unidad</th>
                    <th class="text-white px-2 py-1">Meses</th>
                    <th class="text-white px-2 py-1">Obs.</th>
                </tr>
            </thead>
            <tbody>
                <tr style="background-color: #fdf6ec; border-left: 3px solid #c98a1f;">
                    <!-- Producto -->
                    <td class="p-1">
                        <input v-model="form.product_name" type="text"
                            class="form-control form-control-sm agro-input"
                            placeholder="Nombre del producto..." autocomplete="off" />
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
                        <select v-model="form.unit_id" class="form-select form-select-sm agro-input">
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

<style>
.multiselect-sm {
    font-size: 0.75rem;
    min-height: 0;
    --ms-py: 0.15rem;
    --ms-px: 0.4rem;
    --ms-tag-py: 0rem;
    --ms-tag-px: 0.3rem;
    --ms-tag-font-size: 0.7rem;
    --ms-option-py: 0.2rem;
    --ms-option-px: 0.5rem;
    --ms-option-font-size: 0.75rem;
}
.multiselect-sm .multiselect-option { font-size: 0.8rem; padding: 3px 8px; line-height: 1.9; }
.multiselect-sm .multiselect-tag { font-size: 0.75rem; padding: 1px 4px; }
.multiselect-sm .multiselect-search input { font-size: 0.7rem; }
</style>

