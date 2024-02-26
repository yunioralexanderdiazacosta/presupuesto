<script setup>
    import Multiselect from '@vueform/multiselect';
    import { useForm } from '@inertiajs/vue3';
    import Modal from '@/Components/Modal.vue';

    const form = useForm({
        budget_id: ''
    });
</script>
<template>
    <Modal :maxWidth="'sm'" :id="'selectBudgetModal'">
        <template #header>
            <h1 class="mb-3">Seleccionar presupuesto</h1>
        </template>
        <template #body>
            <div class="fv-row mb-3">
                <label for="budget_id" class="form-label required fs-6 fw-bold mb-3"></label>
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
        </template>
        <template #footer>
            <button type="button" id="kt_modal_select_budget_cancel" data-bs-dismiss="modal" class="btn btn-light me-3">Cerrar</button>
            <button type="button" @click="" :disabled="form.processing" id="kt_modal_select_budget_submit" class="btn btn-primary">
                <span class="indicator-label">Guardar</span>
            </button>
        </template>
    </Modal>
</template>