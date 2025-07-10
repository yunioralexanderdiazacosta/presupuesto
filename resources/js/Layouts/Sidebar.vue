<script setup>
import { ref, computed, onMounted, nextTick } from 'vue';
import { Link } from '@inertiajs/vue3';
import AplicationLogo from '@/Components/ApplicationLogo.vue';
import Menu from '@/Components/Sidebar/Menu.vue';
import { usePage } from '@inertiajs/vue3';


const presupuestos = ['/dashboard','/technicalpanel','/budgets', '/agrochemicals', '/fertilizers', '/manpowers', '/supplies', '/services','/fields','/administration'];

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
                title: 'Dashboard',
                icon: `<i class="bi bi-speedometer2"></i>`,
                link: 'dashboard',
                subitems: []
            },
             {
                title: 'Panel Tecnico',
                icon: `<i class="bi bi-speedometer2"></i>`,
                link: 'technicalpanel',
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
                        title: 'Gral Campo',
                        icon: `<i class="bi bi-card-checklist"></i>`,
                        link: 'fields.index'
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
                title: 'Summary de Niveles',
                icon: `<i class="bi bi-diagram-3"></i>`,
                link: 'levels.summary',
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
