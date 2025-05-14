<script setup>
import { ref, computed, onMounted, nextTick } from 'vue';
import { Link } from '@inertiajs/vue3';
import AplicationLogo from '@/Components/ApplicationLogo.vue';
import Menu from '@/Components/Sidebar/Menu.vue';
import { usePage } from '@inertiajs/vue3';


const presupuestos = ['/dashboard', '/budgets', '/agrochemicals', '/fertilizers', '/manpowers', '/supplies', '/services'];

const parametros = ['/cost-centers', '/levels', '/users', '/company-reasons', '/fruits', '/varieties', '/parcels', '/seasons'];

const gestion = ['/suppliers', '/products', '/invoices', '/machineries', '/type-machineries'];

var active = ref(0);
var active2 = ref('');
if(presupuestos.includes(window.location.pathname)){
    active.value = 0;
} else if(parametros.includes(window.location.pathname)){
    active.value = 1;
} else if(gestion.includes(window.location.pathname)){
    active.value = 2;
}

const items = [
    {
        title: 'Presupuestos',
        icon: `<i class="bi bi-speedometer2"></i>`,
        link: null,
        role: 'Admin',
        subitems: [
            {
                title: 'Tablero',
                icon: `<i class="bi bi-speedometer2"></i>`,
                link: 'dashboard',
                subitems: []
            },

            {
                title: 'Presupuesto por CC',
                icon: `<i class="bi bi-card-checklist"></i>`,
                link: null,
                subitems: [
                    {
                        title: 'Agroquimicos',
                        icon: `<i class="bi bi-card-checklist"></i>`,
                        link: 'agrochemicals.index'
                    },

                    {
                        title: 'Fertilizantes',
                        icon: `<i class="bi bi-card-checklist"></i>`,
                        link: 'fertilizers.index'
                    },

                    {
                        title: 'Mano de Obra',
                        icon: `<i class="bi bi-card-checklist"></i>`,
                        link: 'manpowers.index'
                    },

                    {
                        title: 'Insumos',
                        icon: `<i class="bi bi-card-checklist"></i>`,
                        link: 'supplies.index'
                    },

                    {
                        title: 'Servicios',
                        icon: `<i class="bi bi-card-checklist"></i>`,
                        link: 'services.index'
                    }
                ]
            },

            {
                title: 'Presupuesto por GG Campo',
                icon: `<i class="bi bi-card-checklist"></i>`,
                link: null,
                subitems: [
                    {
                        title: 'Gnral Campo',
                        icon: `<i class="bi bi-card-checklist"></i>`,
                        link: 'dashboard'
                    }
                ]
            },

            {
                title: 'Presupuesto Administración',
                icon: `<i class="bi bi-card-checklist"></i>`,
                link: null,
                subitems: [
                    {
                        title: 'Gnral Administración',
                        icon: `<i class="bi bi-card-checklist"></i>`,
                        link: 'budgets.index'
                    }
                ]
            }

        ]
    },

    {
        title: 'Parametros',
        icon: `<i class="bi bi-speedometer2"></i>`,
        link: null,
        role: 'Admin',
        subitems: [
            {
                title: 'Centros de costos',
                icon: `<i class="bi bi-card-checklist"></i>`,
                link: 'cost.centers.index',
                subitems: []
            },

            {
                title: 'Niveles',
                icon: `<i class="bi bi-card-checklist"></i>`,
                link: 'levels.index',
                subitems: []
            },

            {
                title: 'Usuarios',
                icon: `<i class="bi bi-card-checklist"></i>`,
                link: 'users.index',
                subitems: []
            },

            {
                title: 'Razon Social',
                icon: `<i class="bi bi-card-checklist"></i>`, 
                link: 'company.reasons.index',
                subitems: []
            },

            {
                title: 'Frutal',
                icon: `<i class="bi bi-card-checklist"></i>`, 
                link: 'fruits.index',
                subitems: []
            },

            {
                title: 'Variedades',
                icon: `<i class="bi bi-card-checklist"></i>`, 
                link: 'varieties.index',
                subitems: []
            },
            {
                title: 'Parcelas',
                icon: `<i class="bi bi-card-checklist"></i>`, 
                link: 'parcels.index',
                subitems: []
            },
            {
                title: 'Temporadas',
                icon: `<i class="bi bi-card-checklist"></i>`, 
                link: 'seasons.index',
                subitems: []
            }
        ]
    },

    {
        title: 'Gestión',
        icon: `<i class="bi bi-speedometer2"></i>`,
        link: null,
        role: 'Admin',
        subitems: [
            {
                title: 'Proveedores',
                icon: `<i class="bi bi-card-checklist"></i>`,
                link: 'suppliers.index',
                subitems: []
            },

            {
                title: 'Productos',
                icon: `<i class="bi bi-card-checklist"></i>`,
                link: 'products.index',
                subitems: []
            },

            {
                title: 'Facturas',
                icon: `<i class="bi bi-card-checklist"></i>`,
                link: 'invoices.index',
                subitems: []
            },

            {
                title: 'Maquinarias',
                icon: `<i class="bi bi-card-checklist"></i>`,
                link: 'machineries.index',
                subitems: []
            },

            {
                title: 'Tipo de Maquinarias',
                icon: `<i class="bi bi-card-checklist"></i>`,
                link: 'type.machineries.index',
                subitems: []
            }
        ]
    }, 

    {
        title: 'Tablero',
        icon: `<i class="bi bi-speedometer2"></i>`,
        link: 'dashboard',
        role: 'Super Admin', 
        subitems: []
    },
   
    {
        title: 'Empresas',
        icon: `<i class="bi bi-building"></i>`,
        link: 'teams.index',
        subitems: [],
        role: 'Super Admin'
    }
];

const change = (position, position2 = '') => {
    if(active.value == position){
        active.value = 0;
    }else{
        active.value = position;
    }

    if(active2.value == position2){
        active2.value = 0;
    }else{
        active2.value = position2;
    }
}
</script>
<template>
    <div id="kt_app_sidebar" class="app-sidebar bg-red flex-column" data-kt-drawer="true" data-kt-drawer-name="app-sidebar" data-kt-drawer-activate="{default: true, lg: false}" data-kt-drawer-overlay="true" data-kt-drawer-width="225px" data-kt-drawer-direction="start" data-kt-drawer-toggle="#kt_app_sidebar_mobile_toggle">
        <!--begin::Logo-->
        <div class="app-sidebar-logo px-6 justify-content-center" id="kt_app_sidebar_logo">
            <!--begin::Logo image-->
            <Link :href="route('dashboard')">
                <AplicationLogo />
            </Link>
            <!--end::Logo image-->
            <!--begin::Sidebar toggle-->
            <div id="kt_app_sidebar_toggle" class="app-sidebar-toggle btn btn-icon btn-shadow btn-sm btn-color-muted btn-active-color-primary body-bg h-30px w-30px position-absolute top-50 start-100 translate-middle rotate" data-kt-toggle="true" data-kt-toggle-state="active" data-kt-toggle-target="body" data-kt-toggle-name="app-sidebar-minimize">
                <!--begin::Svg Icon | path: icons/duotune/arrows/arr079.svg-->
                <span class="svg-icon svg-icon-2 rotate-180">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path opacity="0.5" d="M14.2657 11.4343L18.45 7.25C18.8642 6.83579 18.8642 6.16421 18.45 5.75C18.0358 5.33579 17.3642 5.33579 16.95 5.75L11.4071 11.2929C11.0166 11.6834 11.0166 12.3166 11.4071 12.7071L16.95 18.25C17.3642 18.6642 18.0358 18.6642 18.45 18.25C18.8642 17.8358 18.8642 17.1642 18.45 16.75L14.2657 12.5657C13.9533 12.2533 13.9533 11.7467 14.2657 11.4343Z" fill="currentColor" />
                        <path d="M8.2657 11.4343L12.45 7.25C12.8642 6.83579 12.8642 6.16421 12.45 5.75C12.0358 5.33579 11.3642 5.33579 10.95 5.75L5.40712 11.2929C5.01659 11.6834 5.01659 12.3166 5.40712 12.7071L10.95 18.25C11.3642 18.6642 12.0358 18.6642 12.45 18.25C12.8642 17.8358 12.8642 17.1642 12.45 16.75L8.2657 12.5657C7.95328 12.2533 7.95328 11.7467 8.2657 11.4343Z" fill="currentColor" />
                    </svg>
                </span>
                <!--end::Svg Icon-->
            </div>
            <!--end::Sidebar toggle-->
        </div>
        <!--end::Logo-->
        <!--begin::sidebar menu-->
        <div class="app-sidebar-menu overflow-hidden flex-column-fluid">
            <!--begin::Menu wrapper-->
            <div id="kt_app_sidebar_menu_wrapper" class="app-sidebar-wrapper hover-scroll-overlay-y my-5" data-kt-scroll="true" data-kt-scroll-activate="true" data-kt-scroll-height="auto" data-kt-scroll-dependencies="#kt_app_sidebar_logo, #kt_app_sidebar_footer" data-kt-scroll-wrappers="#kt_app_sidebar_menu" data-kt-scroll-offset="5px" data-kt-scroll-save-state="true">
                <!--begin::Menu-->
                <div class="menu menu-column menu-rounded menu-sub-indention px-3" id="#kt_app_sidebar_menu" data-kt-menu="true" data-kt-menu-expand="false">
                    <template class="menu-item" v-for="(item, index) in items" :key="index">

                        <div class="menu-item" v-if="item.subitems.length == 0" v-role:any="item.role">
                            <!--begin:Menu link-->
                            <Link class="menu-link" :href="route(item.link)">
                                <span class="menu-icon">
                                    <span class="svg-icon svg-icon-2" v-html="item.icon"></span>
                                </span>
                                <span class="menu-title">{{item.title}}</span>
                            </Link>
                            <!--end:Menu link-->
                        </div>

                        <div data-kt-menu-trigger="click" class="menu-item menu-accordion"  @click="change(index)" v-else :class="{'hover show': active == index}" v-role:any="item.role">
                            <!--begin:Menu link-->
                            <span class="menu-link">
                                <span class="menu-icon">
                                     <span class="svg-icon svg-icon-2" v-html="item.icon"></span>
                                </span>
                                <span class="menu-title">{{item.title}}</span>
                                <span class="menu-arrow"></span>
                            </span>
                            <!--end:Menu link-->
                            <!--begin:Menu sub-->
                            <div class="menu-sub menu-sub-accordion" v-for="(subitem, i) in item.subitems" :key="i">
                                <!--begin:Menu item-->
                                <div class="menu-item" v-if="subitem.subitems.length == 0">
                                    <!--begin:Menu link-->
                                    <Link class="menu-link" :href="route(subitem.link)">
                                        <span class="menu-bullet">
                                            <span class="bullet bullet-dot"></span>
                                        </span>
                                        <span class="menu-title">{{subitem.title}}</span>
                                    </Link>
                                    <!--end:Menu link-->
                                </div>
                                <!--end:Menu item-->

                                <!--begin:Menu item-->
                                <div data-kt-menu-trigger="click" class="menu-item menu-accordion" v-else @click="change(index)" :class="{'hover show': active == index}">
                                    <!--begin:Menu link-->
                                    <span class="menu-link">
                                        <span class="menu-bullet">
                                            <span class="bullet bullet-dot"></span>
                                        </span>
                                        <span class="menu-title">{{subitem.title}}</span>
                                        <span class="menu-arrow"></span>
                                    </span>
                                    <!--end:Menu link-->
                                    <!--begin:Menu sub-->
                                    <div class="menu-sub menu-sub-accordion menu-active-bg" v-for="(subitemj, j) in subitem.subitems" :key="j">
                                        <!--begin:Menu item-->
                                        <div class="menu-item">
                                            <!--begin:Menu link-->
                                            <Link class="menu-link" :href="route(subitemj.link)">
                                                <span class="menu-bullet">
                                                    <span class="bullet bullet-dot"></span>
                                                </span>
                                                <span class="menu-title">{{subitemj.title}}</span>
                                            </Link>
                                            <!--end:Menu link-->
                                        </div>
                                        <!--end:Menu item-->
                                    </div>
                                    <!--end:Menu sub-->
                                </div>
                                <!--end:Menu item-->
                                <!--end:Menu sub-->
                            </div>
                        </div>
                    </template>
                </div>
                <!--end::Menu-->
            </div>
            <!--end::Menu wrapper-->
        </div>
    </div>
</template>