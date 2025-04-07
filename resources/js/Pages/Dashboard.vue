<script setup>
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Pie from '@/Components/Pie.vue';
const title = 'Tablero';

const links = [{ title: 'Tablero', link: 'dashboard', active: true }];


const props = defineProps({
    totalSeason: String,
    pieLabels: Array,
    pieDatasets: Array,
    months: Array,
    monthsAgrochemical: Object,
    monthsFertilizer: Object,
    monthsManPower: Object,
    totalAgrochemical: Number,
    totalFertilizer: Number,
    totalManPower: Number
})
</script>

<template>
    <Head :title="title" />
    <AppLayout>
        <div class="app-main flex-column flex-row-fluid" id="kt_app_main">
            <!--begin::Content wrapper-->
            <div class="d-flex flex-column flex-column-fluid">
                <!--begin::Toolbar-->
                <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
                    <!--begin::Toolbar container-->
                    <div id="kt_app_toolbar_container" class="app-container container-xxl d-flex flex-stack">
                        <!--begin::Page title-->
                        <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
                            <!--begin::Title-->
                            <h1 class="page-heading d-flex text-dark fw-bold fs-3 flex-column justify-content-center my-0">{{title}}</h1>
                            <!--end::Title-->
                        </div>
                        <!--end::Page title-->
                    </div>
                    <!--end::Toolbar container-->
                </div>
                <!--end::Toolbar-->
                <!--begin::Content-->
                <div id="kt_app_content" class="app-content flex-column-fluid">
                    <!--begin::Content container-->
                    <div id="kt_app_content_container" class="app-container container-xxl">
                        <div class="row g-5 g-xl-8">
                            <div class="col-xl-6 d-flex align-items-center">
                                <!--begin::Statistics Widget 5-->
                                <a style="max-height: 162.45;" href="#" class="card bg-primary hoverable mb-xl-8 w-100">
                                    <!--begin::Body-->
                                    <div class="card-body">
                                        <!--begin::Svg Icon | path: icons/duotune/ecommerce/ecm008.svg-->
                                        <span class="svg-icon svg-icon-gray-100 svg-icon-3x ms-n1">
                                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path opacity="0.3" d="M18 21.6C16.3 21.6 15 20.3 15 18.6V2.50001C15 2.20001 14.6 1.99996 14.3 2.19996L13 3.59999L11.7 2.3C11.3 1.9 10.7 1.9 10.3 2.3L9 3.59999L7.70001 2.3C7.30001 1.9 6.69999 1.9 6.29999 2.3L5 3.59999L3.70001 2.3C3.50001 2.1 3 2.20001 3 3.50001V18.6C3 20.3 4.3 21.6 6 21.6H18Z" fill="currentColor"></path>
                                                <path d="M12 12.6H11C10.4 12.6 10 12.2 10 11.6C10 11 10.4 10.6 11 10.6H12C12.6 10.6 13 11 13 11.6C13 12.2 12.6 12.6 12 12.6ZM9 11.6C9 11 8.6 10.6 8 10.6H6C5.4 10.6 5 11 5 11.6C5 12.2 5.4 12.6 6 12.6H8C8.6 12.6 9 12.2 9 11.6ZM9 7.59998C9 6.99998 8.6 6.59998 8 6.59998H6C5.4 6.59998 5 6.99998 5 7.59998C5 8.19998 5.4 8.59998 6 8.59998H8C8.6 8.59998 9 8.19998 9 7.59998ZM13 7.59998C13 6.99998 12.6 6.59998 12 6.59998H11C10.4 6.59998 10 6.99998 10 7.59998C10 8.19998 10.4 8.59998 11 8.59998H12C12.6 8.59998 13 8.19998 13 7.59998ZM13 15.6C13 15 12.6 14.6 12 14.6H10C9.4 14.6 9 15 9 15.6C9 16.2 9.4 16.6 10 16.6H12C12.6 16.6 13 16.2 13 15.6Z" fill="currentColor"></path>
                                                <path d="M15 18.6C15 20.3 16.3 21.6 18 21.6C19.7 21.6 21 20.3 21 18.6V12.5C21 12.2 20.6 12 20.3 12.2L19 13.6L17.7 12.3C17.3 11.9 16.7 11.9 16.3 12.3L15 13.6V18.6Z" fill="currentColor"></path>
                                            </svg>
                                        </span>
                                        <!--end::Svg Icon-->
                                        <div class="text-white fw-bold fs-1 mb-2 mt-5">{{totalSeason}}</div>
                                        <div class="fw-semibold text-white fs-3">Total presupuestos</div>
                                    </div>
                                    <!--end::Body-->
                                </a>
                                <!--end::Statistics Widget 5-->
                            </div>

                            <div class="col-xl-6">
                                <Pie :pieLabels="pieLabels" :pieDatasets="pieDatasets"></Pie>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-xl-12">
                                <table class="table table-row-full-dashed table-row-gray-300 align-middle gs-0 gy-4">
                                    <!--begin::Table head-->
                                    <thead>
                                        <tr class="fw-bold text-muted">
                                            <th class="min-w-150px"></th>
                                            <th class="text-dark">TOTAL</th>
                                            <th v-for="month in $page.props.months" class="text-primary">{{month.label}}</th>
                                        </tr>
                                    </thead>
                                    <!--end::Table head-->
                                    <!--begin::Table body-->
                                    <tbody>  
                                        <tr>
                                            
                                            <td>Agroquimicos</td>
                                            <td>{{totalAgrochemical}}</td>
                                            <td class="bg-opacity-5 bg-primary" v-for="value in months">{{monthsAgrochemical[value.value]}}</td>
                                        </tr>

                                        <tr>
                                            <td>Fertilizantes</td>
                                            <td>{{totalFertilizer}}</td>
                                            <td class="bg-opacity-5 bg-primary" v-for="value in months">{{monthsFertilizer[value.value]}}</td>
                                        </tr>
                                        
                                        <tr>
                                            <td>Mano de obra</td>
                                            <td>{{totalManPower}}</td>
                                            <td class="bg-opacity-5 bg-primary" v-for="value in months">{{monthsManPower[value.value]}}</td>
                                        </tr>
                                    </tbody>
                                    <!--end::Table body-->
                                </table>
                            </div>
                        </div>
                    </div>
                    <!--end::Content container-->
                </div>
                <!--end::Content-->
            </div>
        </div>

    </AppLayout>
</template>
