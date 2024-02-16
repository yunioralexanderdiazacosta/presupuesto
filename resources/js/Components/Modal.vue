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
    <div class="modal fade" :id="id" tabindex="-1" aria-hidden="true">
        <!--begin::Modal dialog-->
        <div class="modal-dialog modal-dialog-centered" :class="maxWidthClass">
            <!--begin::Modal content-->
            <div class="modal-content rounded">
                <!--begin::Modal header-->
                <div class="modal-header pb-0 border-0 justify-content-end">
                    <!--begin::Close-->
                    <div class="btn btn-sm btn-icon btn-active-color-primary" data-bs-dismiss="modal">
                        <!--begin::Svg Icon | path: icons/duotune/arrows/arr061.svg-->
                        <span class="svg-icon svg-icon-1">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <rect opacity="0.5" x="6" y="17.3137" width="16" height="2" rx="1" transform="rotate(-45 6 17.3137)" fill="currentColor" />
                                <rect x="7.41422" y="6" width="16" height="2" rx="1" transform="rotate(45 7.41422 6)" fill="currentColor" />
                            </svg>
                        </span>
                        <!--end::Svg Icon-->
                    </div>
                    <!--end::Close-->
                </div>
                <!--begin::Modal header-->
                <!--begin::Modal body-->
                <div class="modal-body scroll-y px-10 px-lg-15 pt-0 pb-15">
                    <!--begin:Form-->
                        <!--begin::Heading-->
                        <div class="mb-13 text-center">
                            <slot name="header"></slot>
                        </div>
                        <!--end::Heading-->
                        <!--begin::Body-->
                        <slot name="body"></slot>
                        <!--end::Body-->
                        <div class="separator separator-dashed my-6"></div>
                        <!--begin::Actions-->
                        <div class="text-center">
                            <slot name="footer"></slot>
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
