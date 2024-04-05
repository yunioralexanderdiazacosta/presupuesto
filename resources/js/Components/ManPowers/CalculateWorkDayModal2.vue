<script setup>
import ManPowerForm from './ManPowerFormMultiple.vue';
import Modal from '@/Components/Modal.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';

const props = defineProps({
    form: Object
})

defineEmits(['store']);
</script>
<template>
    <Modal :maxWidth="'xl'" :background="'bg-dark'" :id="'calculateWorkDay2'">
        <template #header>
            <h1 class="mt-1">Calcular jornadas</h1>
            <div class="notice d-flex bg-light-primary rounded border-primary border border-dashed p-6 text-start">
                <!--begin::Icon-->
                <!--begin::Svg Icon | path: icons/duotune/general/gen044.svg-->
                <span class="svg-icon svg-icon-2tx svg-icon-primary me-4">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <rect opacity="0.3" x="2" y="2" width="20" height="20" rx="10" fill="currentColor"></rect>
                        <rect x="11" y="14" width="7" height="2" rx="1" transform="rotate(-90 11 14)" fill="currentColor"></rect>
                        <rect x="11" y="17" width="2" height="2" rx="1" transform="rotate(-90 11 17)" fill="currentColor"></rect>
                    </svg>
                </span>
                <!--end::Svg Icon-->
                <!--end::Icon-->
                <!--begin::Wrapper-->
                <div class="d-flex flex-stack flex-grow-1">
                    <!--begin::Content-->
                    <div class="fw-semibold">
                        <h4 class="text-gray-900">Si no sabes las jornadas por hectarea, pero si sabes el rendimiento de una persona al dia, puedes llenar los campos a continuación, para facilitar su calculo:</h4>
                    </div>
                    <!--end::Content-->
                </div>
                <!--end::Wrapper-->
            </div>
        </template>
        <template #body>
            <div class="row">
                <div class="col-lg-6">
                    <div class="fv-row mb-8">
                        <label class="required fs-6 fw-semibold mb-2">Rendimiento de 1 persona</label>
                        <TextInput
                            id="performance"
                            v-model="form.performance"
                            class="form-control form-control-solid"
                            type="number"
                            step="0.00"
                        />
                        <InputError class="mt-2" :message="form.errors.performance" />
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="fv-row mb-8">
                        <label class="required fs-6 fw-semibold mb-2">Plantas por hectarea</label>
                        <TextInput
                            id="floors"
                            v-model="form.floors"
                            class="form-control form-control-solid"
                            type="number"
                            step="0.00"
                        />
                        <InputError class="mt-2" :message="form.errors.floors" />
                    </div>
                </div>
            </div>
        </template>
        <template #footer>
            <button type="button" id="kt_modal_calculate_workday_cancel" data-bs-dismiss="modal" class="btn btn-light me-3">Cerrar</button>
            <button type="button" @click="$emit('store')" id="kt_modal_calculate_workday_submit" class="btn btn-primary">
                <span class="indicator-label">Calcular</span>
            </button>
        </template>
    </Modal>
</template>