<script setup>
import { onMounted, nextTick } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
 import Multiselect from '@vueform/multiselect';
import Modal from '@/Components/Modal.vue';

const form = useForm({
    budget_id: ''
});

onMounted(() => {
    nextTick(() => {
        $('#selectBudgetModal').modal('show');
        $('#selectBudgetModal').modal({backdrop: 'static', keyboard: false});
    });
});

const onSubmit = () => {
    form.post(route('select.budget.save'), {
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
    <div class="modal fade" id="selectBudgetModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
        <!--begin::Modal dialog-->
        <div class="modal-dialog modal-dialog-centered mw650px">
            <!--begin::Modal content-->
            <div class="modal-content rounded">
                <!--begin::Modal header-->
                <div class="modal-header pb-0 border-0 justify-content-end">
                </div>
                <!--begin::Modal header-->
                <!--begin::Modal body-->
                <div class="modal-body scroll-y px-10 px-lg-15 pt-0 pb-15">
                    <!--begin:Form-->
                        <!--begin::Heading-->
                        <div class="mb-13 text-center">
                            <h1 class="mb-3">Seleccionar presupuesto</h1>
                        </div>
                        <!--end::Heading-->
                        <!--begin::Body-->
                        <div class="fv-row mb-3">
                            <label for="budget_id" class="form-label required fs-6 fw-bold mb-3">Presupuesto</label>
                            <Multiselect
                                placeholder="Seleccione presupuesto"
                                v-model="form.budget_id"
                                :close-on-select="false"
                                :options="$page.props.budgets"
                                class="multiselect-blue form-control"
                                :class="{'is-invalid': form.errors.budget_id}"
                                :searchable="true"
                            />
                        </div>
                        <!--end::Body-->
                        <div class="separator separator-dashed my-6"></div>
                        <!--begin::Actions-->
                        <div class="text-center">
                            <button type="button" @click="onSubmit()" :disabled="form.processing" id="kt_modal_select_budget_submit" class="btn btn-primary">
                                <span class="indicator-label">Guardar</span>
                            </button>
                        </div>
                        <!--end::Actions-->
                    <!--end:Form-->
                </div>
                <!--end::Modal body-->
            </div>
            <!--end::Modal content-->
        </div>
        <!--end::Modal dialog-->
    </div>
</template>
<style src="@vueform/multiselect/themes/default.css"></style>
<style>
.multiselect-blue {
    --ms-bg: var(--kt-input-solid-bg) !important;
    --ms-border-color: var(--kt-input-solid-bg);
    --ms-py: 3px !important;
    --ms-tag-bg: #eee;
    --ms-tag-color: var(--kt-primary);
    --ms-option-bg-selected: var(--kt-primary);
    --ms-option-bg-selected-pointed: var(--kt-primary);
}

.multiselect-tags-search, .multiselect-search{
    background: var(--kt-input-solid-bg) !important;
}
</style>