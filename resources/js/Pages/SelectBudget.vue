<script setup>
import { onMounted, nextTick } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
 import Multiselect from '@vueform/multiselect';
import Modal from '@/Components/Modal.vue';

const form = useForm({
    season_id: ''
});

onMounted(() => {
    nextTick(() => {
        $('#selectBudgetModal').modal('show');
        $('#selectBudgetModal').modal({backdrop: 'static', keyboard: false});
    });
});

const onSubmit = () => {
    form.post(route('select.seasons.save'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            $('#selectBudgetModal').modal('hide');
            router.get('dashboard');
        }
    });
}
</script>

<template>
    <Head title="Seleccionar presupuesto" />

    <div class="modal fade" id="selectBudgetModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document" style="max-width: 500px">
            <div class="modal-content position-relative">
                <div class="position-absolute top-0 end-0 mt-2 me-2 z-1">
                </div>
                <div class="modal-body p-0">
                    <div class="rounded-top-3 py-3 ps-4 pe-6 bg-body-tertiary">
                        <h4 class="mb-1" id="seasonId">Seleccionar temporada</h4>
                    </div>
                    <div class="p-4 pb-0">
                        <div class="mb-3">
                            <label class="col-form-label" for="season">Temporada</label>
                            <Multiselect
                                placeholder="Seleccione temporada"
                                v-model="form.season_id"
                                :close-on-select="true"
                                :options="$page.props.seasons"
                                class="multiselect-blue form-control"
                                :class="{'is-invalid': form.errors.season_id}"
                                :searchable="true"
                                />
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" @click="onSubmit()" :disabled="form.processing" class="btn btn-primary">Guardar</button>
                </div>
            </div>
        </div>
    </div>
</template>
<style src="@vueform/multiselect/themes/default.css"></style>
<style>
.multiselect-blue {
    --ms-bg: var(--kt-input-solid-bg) !important;
    --ms-border-color: var(--kt-input-solid-bg);
    --ms-py: 3px !important;
    --ms-tag-bg: #2c7be5;
    --ms-tag-color: var(--kt-primary);
    --ms-option-bg-selected: var(--kt-primary);
    --ms-option-bg-selected-pointed: var(--kt-primary);
}

.multiselect-tags-search, .multiselect-search{
    background: var(--kt-input-solid-bg) !important;
}
</style>