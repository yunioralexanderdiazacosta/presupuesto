<script setup>
import { computed } from 'vue';
const props = defineProps({
    id: {
        require: true,
        type: String,
    },
    maxWidth: {
        type: String,
        default: 'md',
    },
    background: {
        type: String,
        default: ''
    }
})

const maxWidthClass = computed(() => {
    return {
        'sm': 'modal-sm',
        'md': 'mw650px',
        'lg': 'modal-lg',
        'xl': 'modal-xl',
        'mw': ''
    }[props.maxWidth];
});
</script>
<template>
    <div class="modal fade" :class="background" :id="id" tabindex="-1" aria-hidden="true">
        <!--begin::Modal dialog-->
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <!--begin::Modal content-->
            <div class="modal-content position-relative rounded bg-modal-custom">
                <!--begin::Modal header-->
                <div class="rounded-top-3 bg-white pt-3 pb-0 ps-4 d-flex align-items-center justify-content-between fs-10">
                    <slot name="header"></slot>
                </div>
                <!--end::Modal header-->
                <!--begin::Close button-->
                <div class="position-absolute top-0 end-0 mt-2 me-2 z-1">
                    <button type="button" class="btn-close btn btn-sm btn-circle d-flex flex-center transition-base" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <!--end::Close button-->
                <!--begin::Modal body-->
                <div class="modal-body scroll-y px-3 px-lg-5 pt-0 pb-15">
                    <!--begin::Body-->
                    <slot name="body"></slot>
                    <!--end::Body-->
                </div>
                <!--end::Modal body-->
                <!--begin::Modal footer-->
                <div class="modal-footer">
                    <slot name="footer"></slot>
                </div>
                <!--end::Modal footer-->
            </div>
            <!--end::Modal content-->
        </div>
        <!--end::Modal dialog-->
    </div>
</template>

<style>
.bg-modal-custom {
    background-color: #f4f8fb !important; /* Color suave, diferente al fondo de los inputs */
}

/* Reducir tamaño de selects, inputs y labels dentro del modal */
.modal-content.bg-modal-custom input.form-control,
.modal-content.bg-modal-custom select.form-select,
.modal-content.bg-modal-custom textarea.form-control {
  font-size: 0.85rem;
  padding: 0.15rem 0.4rem;
  height:34px;
}
.modal-content.bg-modal-custom label {
  font-size: 0.85rem;
  margin-bottom: 0.05rem;
}

/* Reducir tamaño del placeholder en selects e inputs dentro del modal */
.modal-content.bg-modal-custom input.form-control::placeholder,
.modal-content.bg-modal-custom textarea.form-control::placeholder {
  font-size: 0.85rem;
}


</style>
