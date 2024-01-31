<script setup>
import Header from './Header.vue';
import Sidebar from './Sidebar.vue';
</script>

<template>
    <!--begin::Page-->
    <div class="app-page flex-column flex-column-fluid" id="kt_app_page">
        <!--begin::Header-->
        <Header />
        <!--end::Header-->
        <!--begin::Wrapper-->
        <div class="app-wrapper flex-column flex-row-fluid" id="kt_app_wrapper">
            <!--begin::sidebar-->
            <Sidebar />
            <!--end::sidebar-->
            <!--begin::Main-->
            <slot></slot>
            <!--end:::Main-->
        </div>
        <!--end::Wrapper-->
    </div>
    <!--end::Page-->
</template>