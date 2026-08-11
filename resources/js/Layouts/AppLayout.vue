<script setup>
import { computed, ref } from 'vue';
import { router, Link, usePage } from '@inertiajs/vue3';
import JetDropdownLink from '@/Components/DropdownLink.vue';
import { onMounted, onUnmounted } from 'vue';
import DatabaseChat from '@/Components/AiChat/DatabaseChat.vue';

// Flags compartidos via Inertia
const { hasCostCenter, hasVariety, hasFruit, hasCompanyReason, hasSeason, hasParcel, hasLevel3 } = usePage().props;

const lifetime = computed(() =>usePage().props.lifetime);

// Path base para assets
const path = '';

// Referencia al componente Link para usar en :is dinámico
const $page = usePage();
const isAdminUser = computed(() => {
  const roles = $page.props.gates?.roles || [];
  return roles.includes('Admin') || roles.includes('Super Admin');
});
// Usuario cuyo único rol funcional es "Rendidor": solo ve el módulo de Rendiciones de Gastos
const isRendidorOnly = computed(() => {
  const roles = $page.props.gates?.roles || [];
  return roles.includes('Rendidor') && !roles.includes('Admin') && !roles.includes('Super Admin');
});
const seasonLinkComponent = computed(() => isAdminUser.value ? Link : 'span');

 let timeoutId;
  const inactivityTime = (lifetime.value || 30) * 60 * 1000; // Minutos en milisegundos (default 30 min)

   

  const resetTimer = () => {
    clearTimeout(timeoutId);
    timeoutId = setTimeout(logout, inactivityTime);
  };


 onMounted(() => {
    // Detección de actividad
	window.addEventListener('mousemove', resetTimer);
	window.addEventListener('keypress', resetTimer);
	window.addEventListener('click', resetTimer);

	// Inicia el temporizador
	resetTimer();
});

 onUnmounted(() => {
	// Limpia los event listeners cuando el componente se desmonta
	window.removeEventListener('mousemove', resetTimer);
	window.removeEventListener('keypress', resetTimer);
	window.removeEventListener('click', resetTimer);
	clearTimeout(timeoutId);
});


const logout = () => {
    router.post(route('logout'));
};

// ── Toggle sidebar (ocultar/mostrar completo) ─────────────
const sidebarHidden = ref(localStorage.getItem('sidebarHidden') === 'true');
const toggleSidebar = () => {
    sidebarHidden.value = !sidebarHidden.value;
    localStorage.setItem('sidebarHidden', String(sidebarHidden.value));
};

// ── Buscador del menú lateral ─────────────────────────────────
const menuSearch = ref('');

const menuItems = [
    { label: 'Inicio', route: 'home', icon: 'fas fa-home' },
    { label: 'Guía del Sistema', route: 'system-guide', icon: 'fas fa-map-signs' },
    { label: 'Dashboard', route: 'dashboard', icon: 'fas fa-tachometer-alt', section: 'Presupuestos' },
    { label: 'Panel Técnico', route: 'technicalpanel', icon: 'fas fa-clipboard-check', section: 'Presupuestos' },
    { label: 'Agroquímicos (Presupuesto)', route: 'agrochemicals.index', icon: 'fas fa-flask', section: 'Presupuestar por CC' },
    { label: 'Fertilizantes (Presupuesto)', route: 'fertilizers.index', icon: 'fas fa-leaf', section: 'Presupuestar por CC' },
    { label: 'Mano de Obra (Presupuesto)', route: 'manpowers.index', icon: 'fas fa-hard-hat', section: 'Presupuestar por CC' },
    { label: 'Insumos (Presupuesto)', route: 'supplies.index', icon: 'fas fa-box', section: 'Presupuestar por CC' },
    { label: 'Servicios (Presupuesto)', route: 'services.index', icon: 'fas fa-concierge-bell', section: 'Presupuestar por CC' },
    { label: 'Cosecha (Presupuesto)', route: 'harvests.index', icon: 'fas fa-apple-alt', section: 'Presupuestar por CC' },
    { label: 'Gral Campo', route: 'fields.index', icon: 'fas fa-tractor', section: 'Presupuestos' },
    { label: 'Administración', route: 'administrations.index', icon: 'fas fa-briefcase', section: 'Presupuestos' },
    { label: 'FAQ', route: 'faq', icon: 'fas fa-question-circle' },
    { label: 'Dashboard Gestión', route: 'outflows.dashboard', icon: 'fas fa-chart-bar', section: 'Gestión' },
    { label: 'Comparativo Presupuesto vs Real', route: 'comparative.dashboard', icon: 'fas fa-chart-line', section: 'Gestión' },
    { label: 'Gestión por Hectárea', route: 'hectare.dashboard', icon: 'fas fa-seedling', section: 'Gestión' },
    { label: 'Detalle de Salidas por Sucursal', route: 'dashboard-detail-outflows.index', icon: 'fas fa-code-branch', section: 'Gestión' },
    { label: 'Dashboard Inversiones', route: 'investment.dashboard', icon: 'fas fa-chart-pie', section: 'Gestión' },
    { label: 'Utilidad / Pérdida', route: 'profit-loss.index', icon: 'fas fa-balance-scale', section: 'Gestión' },
    { label: 'Facturas y otros', route: 'invoices.index', icon: 'fas fa-file-invoice', section: 'Registro de Gastos' },
    { label: 'Rendiciones de Gastos', route: 'expense-reports.index', icon: 'fas fa-receipt', section: 'Registro de Gastos' },
    { label: 'Órdenes de Compra', route: 'purchase-orders.index', icon: 'fas fa-shopping-cart', section: 'Registro de Gastos' },
    { label: 'Inversiones', route: 'investments.index', icon: 'fas fa-chart-pie', section: 'Gestión' },
    { label: 'Proyectos', route: 'projects.index', icon: 'fas fa-folder-open', section: 'Gestión' },
    { label: 'Consolidado de Salidas', route: 'consolidated-outflows.index', icon: 'fas fa-sign-out-alt', section: 'Salidas' },
    { label: 'Consumos Gral.', route: 'outflows.index', icon: 'fas fa-boxes', section: 'Salidas' },
    { label: 'Combustible Maquinaria', route: 'fuel-outflows.index', icon: 'fas fa-gas-pump', section: 'Salidas' },
    { label: 'Salida Agroquímicos', route: 'application-orders.index', icon: 'fas fa-spray-can', section: 'Agroquímicos' },
    { label: 'Salida Fertilizantes', route: 'fertilizer-orders.index', icon: 'fas fa-leaf', section: 'Fertilizantes' },
    { label: 'Estimaciones', route: 'estimates.index', icon: 'fas fa-calculator', section: 'Gestión' },
    { label: 'Ingresar Producción', route: 'production-dispatches.index', icon: 'fas fa-truck-loading', section: 'Producción' },
    { label: 'Ingreso Rápido de Producción', route: 'production-summaries.index', icon: 'fas fa-bolt', section: 'Producción' },
    { label: 'Proveedores', route: 'suppliers.index', icon: 'fas fa-handshake', section: 'Gestión' },
    { label: 'Inventario', route: 'inventory', icon: 'fas fa-warehouse', section: 'Gestión' },
    { label: 'Productos', route: 'products.index', icon: 'fas fa-barcode', section: 'Gestión' },
    { label: 'Maquinarias', route: 'machineries.index', icon: 'fas fa-cogs', section: 'Maquinarias' },
    { label: 'Tipo de Maquinarias', route: 'type.machineries.index', icon: 'fas fa-wrench', section: 'Maquinarias' },
    { label: 'Operarios', route: 'operators.index', icon: 'fas fa-id-badge', section: 'Maquinarias' },
    { label: 'Estanques de Combustible', route: 'fuel-tanks.index', icon: 'fas fa-drum', section: 'Maquinarias' },
    { label: 'Equipos de Riego', route: 'irrigation-pumps.index', icon: 'fas fa-tint', section: 'Gestión' },
    { label: 'Colaboradores', route: 'employees.index', icon: 'fas fa-user-tie', section: 'Remuneraciones' },
    { label: 'Contratos', route: 'contracts.index', icon: 'fas fa-file-signature', section: 'Remuneraciones' },
    { label: 'Términos de Faena', route: 'terminations.index', icon: 'fas fa-user-slash', section: 'Remuneraciones' },
    { label: 'Plantillas de Contrato', route: 'contract-templates.index', icon: 'fas fa-file-contract', section: 'Remuneraciones' },
    { label: 'Plantillas Término de Contrato', route: 'termination-templates.index', icon: 'fas fa-file-signature', section: 'Remuneraciones' },
    { label: 'Bonos y Descuentos', route: 'monthly-bonuses.index', icon: 'fas fa-hand-holding-usd', section: 'Remuneraciones' },
    { label: 'Horas Extras', route: 'overtime-hours.index', icon: 'fas fa-clock', section: 'Remuneraciones' },
    { label: 'Vacaciones', route: 'vacations.index', icon: 'fas fa-umbrella-beach', section: 'Remuneraciones' },
    { label: 'Feriados', route: 'holidays.index', icon: 'fas fa-calendar-alt', section: 'Remuneraciones' },
    { label: 'Gestión Diaria', route: 'daily-management.index', icon: 'fas fa-tasks', section: 'Remuneraciones' },
    { label: 'Dashboard Remuneraciones', route: 'payroll-dashboard', icon: 'fas fa-chart-bar', section: 'Remuneraciones' },
    { label: 'Reporte Mensual', route: 'payroll-reports.index', icon: 'fas fa-file-invoice-dollar', section: 'Remuneraciones' },
    { label: 'Horarios', route: 'schedules.index', icon: 'fas fa-clock', section: 'Remuneraciones' },
    { label: 'Ciudades', route: 'cities.index', icon: 'fas fa-city', section: 'Remuneraciones' },
    { label: 'Evaluación de Proyectos', route: 'project-evaluations.index', icon: 'fas fa-seedling', section: 'Planificación' },
    { label: 'Centros de Costos', route: 'cost.centers.index', icon: 'fas fa-sitemap', section: 'Parámetros' },
    { label: 'Sucursales', route: 'branches.index', icon: 'fas fa-code-branch', section: 'Parámetros' },
    { label: 'Grupos de CC', route: 'groupings.index', icon: 'fas fa-layer-group', section: 'Parámetros' },
    { label: 'Variedades por Cuartel', route: 'cost-center-varieties.index', icon: 'fas fa-th', section: 'Parámetros' },
    { label: 'Niveles', route: 'levels.index', icon: 'fas fa-stream', section: 'Parámetros' },
    { label: 'Resumen de Niveles', route: 'levels.summary', icon: 'fas fa-list-ol', section: 'Parámetros' },
    { label: 'Usuarios', route: 'users.index', icon: 'fas fa-users-cog', section: 'Parámetros' },
    { label: 'Razón Social', route: 'company.reasons.index', icon: 'fas fa-id-card', section: 'Parámetros' },
    { label: 'Frutal', route: 'fruits.index', icon: 'fas fa-lemon', section: 'Parámetros' },
    { label: 'Etapas Fenológicas', route: 'phenological-stages.index', icon: 'fas fa-spa', section: 'Parámetros' },
    { label: 'Variedades', route: 'varieties.index', icon: 'fas fa-palette', section: 'Parámetros' },
    { label: 'Portainjertos', route: 'rootstocks.index', icon: 'fas fa-tree', section: 'Parámetros' },
    { label: 'Parcelas', route: 'parcels.index', icon: 'fas fa-map-marked-alt', section: 'Parámetros' },
    { label: 'Temporadas', route: 'seasons.index', icon: 'fas fa-calendar-alt', section: 'Parámetros' },
];

const normalize = (str) => str.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();

const filteredMenuItems = computed(() => {
    if (!menuSearch.value.trim()) return [];
    const q = normalize(menuSearch.value.trim());
    const base = isRendidorOnly.value
        ? menuItems.filter(item => item.route.startsWith('expense-reports.'))
        : menuItems;
    return base.filter(item =>
        normalize(item.label).includes(q) ||
        (item.section && normalize(item.section).includes(q))
    );
});

const navigateTo = (routeName) => {
    menuSearch.value = '';
    router.visit(route(routeName));
};
</script>

<template>
  <div :class="{ 'sidebar-hidden': sidebarHidden }">
    <nav class="navbar navbar-light navbar-vertical navbar-expand-xl position-fixed start-0 top-0 vh-100">
        <div class="d-flex align-items-center ps-2">
            <div class="toggle-icon-wrapper d-xl-none">
              <button class="btn navbar-toggler-humburger-icon navbar-vertical-toggle" data-bs-toggle="tooltip" data-bs-placement="left" title="Toggle Navigation"><span class="navbar-toggle-icon"><span class="toggle-line"></span></span></button>
            </div>

            <a class="navbar-brand" href="index.html">
              <div class="d-flex align-items-center py-3"><img class="me-0 mb-3" src="/assets/img/icons/spot-illustrations/ALISOFT.png" alt="" width="48" /><span class="font-sans-serif text-primary">Alisoft</span>
              </div>
            </a>
        </div>
        <div class="collapse navbar-collapse" id="navbarVerticalCollapse">
        <div class="navbar-vertical-content scrollbar">

          <!-- Buscador del menú -->
          <div class="menu-search-wrapper px-3 pt-2 pb-1 position-sticky top-0" style="z-index: 10;">
            <div class="position-relative">
              <span class="fas fa-search position-absolute text-400" style="top: 50%; left: 12px; transform: translateY(-50%); font-size: 0.7rem;"></span>
              <input
                v-model="menuSearch"
                type="text"
                class="form-control form-control-sm rounded-pill ps-4 pe-4 menu-search-input"
                placeholder="Buscar..."
              />
              <span
                v-if="menuSearch"
                class="position-absolute d-flex align-items-center justify-content-center"
                style="top: 50%; right: 4px; transform: translateY(-50%); width: 22px; height: 22px; cursor: pointer; border: none; background: rgba(0,0,0,0.06); border-radius: 50%; padding: 0;"
                @click.stop="menuSearch = ''"
              >
                <i class="fas fa-times" style="font-size: 0.6rem; color: #9da9bb;"></i>
              </span>
            </div>
          </div>

          <!-- Resultados de búsqueda -->
          <div v-if="menuSearch.trim()" class="px-3 pt-1 pb-2">
            <div v-if="filteredMenuItems.length === 0" class="text-center text-muted py-3" style="font-size: 0.8rem;">
              <i class="fas fa-inbox me-1"></i> Sin resultados
            </div>
            <a
              v-for="item in filteredMenuItems"
              :key="item.route"
              href="#"
              @click.prevent="navigateTo(item.route)"
              class="d-flex align-items-center px-2 py-2 mb-1 rounded-2 text-decoration-none menu-search-item"
            >
              <span class="menu-search-icon d-flex align-items-center justify-content-center rounded-2 me-2">
                <i :class="item.icon" style="font-size: 0.7rem;"></i>
              </span>
              <div class="flex-1 overflow-hidden">
                <div class="text-truncate fw-medium" style="font-size: 0.8rem; color: var(--falcon-nav-link-color, #5e6e82);">{{ item.label }}</div>
                <div v-if="item.section" class="text-truncate" style="font-size: 0.65rem; color: #9da9bb;">{{ item.section }}</div>
              </div>
            </a>
          </div>

          <!-- Menú normal (oculto cuando se busca) -->
          <ul v-show="!menuSearch.trim()" class="navbar-nav flex-column mb-3" id="navbarVerticalNav">

      <!-- Inicio para todos los roles -->
      <li class="nav-item">
        <Link class="nav-link" :href="route('home')" role="button">
          <div class="d-flex align-items-center">
            <span class="nav-link-icon">
              <span class="fas fa-home"></span>
            </span>
            <span class="nav-link-text ps-1">Inicio</span>
          </div>
        </Link>
      </li>

      <!-- Guía del Sistema -->
      <li class="nav-item">
        <a class="nav-link" :href="route('system-guide')" target="_blank">
          <div class="d-flex align-items-center">
            <span class="nav-link-icon">
              <span class="fas fa-map-signs"></span>
            </span>
            <span class="nav-link-text ps-1">Guía del Sistema</span>
          </div>
        </a>
      </li>

      <!-- Menú exclusivo para el rol Rendidor (sin acceso a Admin/Super Admin) -->
      <li class="nav-item" v-if="isRendidorOnly">
        <Link class="nav-link" :href="route('expense-reports.index')" role="button">
          <div class="d-flex align-items-center">
            <span class="nav-link-icon">
              <span class="fas fa-receipt"></span>
            </span>
            <span class="nav-link-text ps-1">Rendiciones de Gastos</span>
          </div>
        </Link>
      </li>

      <li class="nav-item" v-role="'Super Admin'">
        <Link class="nav-link" :href="route('dashboard')" role="button">
          <div class="d-flex align-items-center">
            <span class="nav-link-icon">
              <span class="fas fa-tachometer-alt"></span>
            </span>
            <span class="nav-link-text ps-1">Tablero</span>
          </div>
        </Link>
        <Link class="nav-link" :href="route('teams.index')" role="button">
          <div class="d-flex align-items-center">
            <span class="nav-link-icon">
              <span class="fas fa-building"></span>
            </span>
            <span class="nav-link-text ps-1">Empresas</span>
          </div>
        </Link>
        <Link class="nav-link" :href="route('products2.index')" role="button">
          <div class="d-flex align-items-center">
            <span class="nav-link-icon">
              <span class="fas fa-flask"></span>
            </span>
            <span class="nav-link-text ps-1">Productos2</span>
          </div>
        </Link>
        <Link class="nav-link" href="/login-logs" role="button">
          <div class="d-flex align-items-center">
            <span class="nav-link-icon">
              <span class="fas fa-clock"></span>
            </span>
            <span class="nav-link-text ps-1">Accesos</span>
          </div>
        </Link>
        <Link class="nav-link" :href="route('system-settings.index')" role="button">
          <div class="d-flex align-items-center">
            <span class="nav-link-icon">
              <span class="fas fa-cogs"></span>
            </span>
            <span class="nav-link-text ps-1">Configuración</span>
          </div>
        </Link>
      </li>
            <li class="nav-item" v-role="'Admin'">
          
              <!-- parent pages--><a class="nav-link dropdown-indicator" href="#authentication" role="button" data-bs-toggle="collapse" aria-expanded="false" aria-controls="authentication">
                <div class="d-flex align-items-center"><span class="nav-link-icon"><span class="fas fa-lock"></span></span><span class="nav-link-text text-dark ps-1">Presupuestos</span>
                </div>
              </a>
              <ul class="nav collapse" id="authentication">
                <li class="nav-item"><Link class="nav-link" :href="route('dashboard')">
                    <div class="d-flex align-items-center"><span class="nav-link-text ps-3">Dashboard</span>
                    </div>
                  </Link>
                  <!-- more inner pages-->
                </li>
                 <li class="nav-item"><Link class="nav-link" :href="route('technicalpanel')">
                    <div class="d-flex align-items-center"><span class="nav-link-text ps-3">Panel Tecnico</span>
                    </div>
                  </Link>
                  <!-- more inner pages-->
                </li>
                <li class="nav-item"><Link class="nav-link" :href="route('agrochemicals.index')">
                    <div class="d-flex align-items-center"><span class="nav-link-text ps-3">Presupuestar por CC</span>
                    </div>
                  </Link>
                </li>
                <li class="nav-item"><a class="nav-link dropdown-indicator" href="#card" data-bs-toggle="collapse" aria-expanded="false" aria-controls="authentication">
                    <div class="d-flex align-items-center"><span class="nav-link-text ps-3 mb-1">Presupuestar Gral Campo</span>
                    </div>
                  </a>
                  <!-- more inner pages-->
                  <ul class="nav collapse" id="card">
                    <li class="nav-item"><Link class="nav-link" :href="route('fields.index')">
                        <div class="d-flex align-items-center"><span class="nav-link-text ps-3 mb-1 text-primary">Gral Campo</span>
                        </div>
                      </Link>
                      <!-- more inner pages-->
                    </li>
                  </ul>
                </li>
                <li class="nav-item"><a class="nav-link dropdown-indicator" href="#split" data-bs-toggle="collapse" aria-expanded="false" aria-controls="authentication">
                    <div class="d-flex align-items-center"><span class="nav-link-text ps-3 mb-1">Presupuestar Administración</span>
                    </div>
                  </a>
                  <!-- more inner pages-->
                  <ul class="nav collapse" id="split">
                    <li class="nav-item"><Link class="nav-link" :href="route('administrations.index')">
                        <div class="d-flex align-items-center"><span class="nav-link-text ps-3 mb-1 text-primary">Administración</span>
                        </div>
                      </Link>
                      <!-- more inner pages-->
                    </li>
                  </ul>
                </li>
                <li class="nav-item"><Link class="nav-link" :href="route('faq')">
                    <div class="d-flex align-items-center"><span class="nav-link-text ps-3 mb-1">FAQ</span>
                    </div>
                  </Link>
                  <!-- more inner pages-->
                </li>
              </ul>
              <!-- parent pages--><a class="nav-link dropdown-indicator" href="#pricing" role="button" data-bs-toggle="collapse" aria-expanded="false" aria-controls="pricing">
                <div class="d-flex align-items-center"><span class="nav-link-icon"><span class="fas fa-tags"></span></span><span class="nav-link-text text-dark ps-1">Gestión</span>
                </div>
              </a>
              <ul class="nav collapse" id="pricing">

                    <li class="nav-item">
                      <a class="nav-link dropdown-indicator" href="#dashboards-kpi" data-bs-toggle="collapse" aria-expanded="false" aria-controls="dashboards-kpi">
                        <div class="d-flex align-items-center">
                          <span class="nav-link-text ps-3"><i class="fas fa-tachometer-alt me-2"></i>Dashboard y KPI</span>
                        </div>
                      </a>
                      <ul class="nav collapse" id="dashboards-kpi">
                        <li class="nav-item">
                          <Link class="nav-link" :href="route('outflows.dashboard')">
                            <div class="d-flex align-items-center">
                              <span class="nav-link-text ps-4"><i class="fas fa-chart-bar me-2"></i>Dashboard Gestión</span>
                            </div>
                          </Link>
                        </li>
                        <li class="nav-item">
                          <Link class="nav-link" :href="route('comparative.dashboard')">
                            <div class="d-flex align-items-center">
                              <span class="nav-link-text ps-4"><i class="fas fa-chart-line me-2"></i>Comparativo Presupuesto vs Real</span>
                            </div>
                          </Link>
                        </li>
                        <li class="nav-item">
                          <Link class="nav-link" :href="route('hectare.dashboard')">
                            <div class="d-flex align-items-center">
                              <span class="nav-link-text ps-4"><i class="fas fa-seedling me-2"></i>Gestión por Hectárea</span>
                            </div>
                          </Link>
                        </li>
                        <li class="nav-item">
                          <Link class="nav-link" :href="route('dashboard-detail-outflows.index')">
                            <div class="d-flex align-items-center">
                              <span class="nav-link-text ps-4"><i class="fas fa-code-branch me-2"></i>Detalle de Salidas por Sucursal</span>
                            </div>
                          </Link>
                        </li>
                        <li class="nav-item">
                          <Link class="nav-link" :href="route('investment.dashboard')">
                            <div class="d-flex align-items-center">
                              <span class="nav-link-text ps-4"><i class="fas fa-chart-pie me-2"></i>Dashboard Inversiones</span>
                            </div>
                          </Link>
                        </li>
                        <li class="nav-item">
                          <Link class="nav-link" :href="route('profit-loss.index')">
                            <div class="d-flex align-items-center">
                              <span class="nav-link-text ps-4"><i class="fas fa-balance-scale me-2"></i>Utilidad / Pérdida</span>
                            </div>
                          </Link>
                        </li>
                      </ul>
                    </li>

                  <li class="nav-item">
                  <a class="nav-link dropdown-indicator" href="#documents" data-bs-toggle="collapse" aria-expanded="false" aria-controls="documents">
                    <div class="d-flex align-items-center">
                      <span class="nav-link-text ps-3">Registro de Gastos</span>
                    </div>
                  </a>
                  <ul class="nav collapse" id="documents">
                    <li class="nav-item">
                      <Link class="nav-link" :href="route('invoices.index')">
                        <div class="d-flex align-items-center"><span class="nav-link-text ps-4">Facturas y otros</span></div>
                      </Link>
                    </li>
                    <li class="nav-item">
                      <Link class="nav-link" :href="route('expense-reports.index')">
                        <div class="d-flex align-items-center"><span class="nav-link-text ps-4">Rendiciones de Gastos</span></div>
                      </Link>
                    </li>
                    <li class="nav-item">
                      <Link class="nav-link" :href="route('purchase-orders.index')">
                        <div class="d-flex align-items-center"><span class="nav-link-text ps-4">Órdenes de Compra</span></div>
                      </Link>
                    </li>
                   
                  </ul>
                </li>

                    <li class="nav-item">
                      <Link class="nav-link" :href="route('investments.index')">
                        <div class="d-flex align-items-center">
                          <span class="nav-link-text ps-3">Inversiones</span>
                        </div>
                      </Link>
                    </li>
                    <li class="nav-item">
                      <Link class="nav-link" :href="route('projects.index')">
                        <div class="d-flex align-items-center">
                          <span class="nav-link-text ps-3">Proyectos</span>
                        </div>
                      </Link>
                    </li>
                    <li class="nav-item">
                      <a class="nav-link dropdown-indicator" href="#outflows" data-bs-toggle="collapse" aria-expanded="false" aria-controls="outflows">
                        <div class="d-flex align-items-center">
                          <span class="nav-link-text ps-3">Salidas</span>
                        </div>
                      </a>
                      <ul class="nav collapse" id="outflows">
                        <li class="nav-item">
                          <Link class="nav-link" :href="route('consolidated-outflows.index')">
                            <div class="d-flex align-items-center">
                              <span class="nav-link-text ps-4">Consolidado de Salidas</span>
                            </div>
                          </Link>
                        </li>
                        <li class="nav-item">
                          <Link class="nav-link" :href="route('outflows.index')">
                            <div class="d-flex align-items-center">
                              <span class="nav-link-text ps-4">Consumos Gral.</span>
                            </div>
                          </Link>
                        </li>
                        <li class="nav-item">
                          <Link class="nav-link" :href="route('fuel-outflows.index')">
                            <div class="d-flex align-items-center">
                              <span class="nav-link-text ps-4">Combustible Maquinaria</span>
                            </div>
                          </Link>
                        </li>
                      </ul>
                    </li>
                    <li class="nav-item">
                      <Link class="nav-link" :href="route('application-orders.index')">
                        <div class="d-flex align-items-center">
                          <span class="nav-link-text ps-3">Salida Agroquímicos</span>
                        </div>
                      </Link>
                    </li>
                    <li class="nav-item">
                      <Link class="nav-link" :href="route('fertilizer-orders.index')">
                        <div class="d-flex align-items-center">
                          <span class="nav-link-text ps-3">Salida Fertilizantes</span>
                        </div>
                      </Link>
                    </li>
                    <li class="nav-item"><Link class="nav-link" :href="route('estimates.index')">
                        <div class="d-flex align-items-center"><span class="nav-link-text ps-3">Estimaciones</span>
                        </div>
                      </Link>
                      <!-- more inner pages-->
                    </li>
                    <li class="nav-item">
                      <a class="nav-link dropdown-indicator" href="#productionMenu" data-bs-toggle="collapse" aria-expanded="false" aria-controls="productionMenu">
                        <div class="d-flex align-items-center">
                          <span class="nav-link-text ps-3">Producción</span>
                        </div>
                      </a>
                      <ul class="nav collapse" id="productionMenu">
                        <li class="nav-item">
                          <Link class="nav-link" :href="route('production-dispatches.index')">
                            <div class="d-flex align-items-center">
                              <span class="nav-link-text ps-4">Ingresar Producción</span>
                            </div>
                          </Link>
                        </li>
                        <li class="nav-item">
                          <Link class="nav-link" :href="route('production-summaries.index')">
                            <div class="d-flex align-items-center">
                              <span class="nav-link-text ps-4">Ingreso Rápido de Producción</span>
                            </div>
                          </Link>
                        </li>
                      </ul>
                    </li>

                <li class="nav-item">
                  <a class="nav-link" :href="route('suppliers.index')" target="_blank" rel="noopener">
                    <div class="d-flex align-items-center"><span class="nav-link-text ps-3">Proveedores <i class="fas fa-external-link-alt ms-1"></i></span>
                    </div>
                  </a>
                  <!-- more inner pages-->
                </li>
                <li class="nav-item"><Link class="nav-link" :href="route('inventory')">
                    <div class="d-flex align-items-center"><span class="nav-link-text ps-3">Inventario</span>
                    </div>
                  </Link>
                </li>
                <li class="nav-item">
                  <a class="nav-link" :href="route('products.index')" target="_blank" rel="noopener">
                    <div class="d-flex align-items-center"><span class="nav-link-text ps-3">Productos <i class="fas fa-external-link-alt ms-1"></i></span>
                    </div>
                  </a>
                  <!-- more inner pages-->
                </li>
                 
                
              
                <li class="nav-item"><a class="nav-link dropdown-indicator" href="#machineryMenu" role="button" data-bs-toggle="collapse" aria-expanded="false" aria-controls="machineryMenu">
                    <div class="d-flex align-items-center"><span class="nav-link-text ps-3">Maquinarias</span>
                    </div>
                  </a>
                  <ul class="nav collapse" id="machineryMenu">
                    <li class="nav-item"><Link class="nav-link" :href="route('machineries.index')">
                        <div class="d-flex align-items-center"><span class="nav-link-text ps-4">Maquinarias</span></div>
                      </Link>
                    </li>
                    <li class="nav-item"><Link class="nav-link" :href="route('type.machineries.index')">
                        <div class="d-flex align-items-center"><span class="nav-link-text ps-4">Tipo de Maquinarias</span></div>
                      </Link>
                    </li>
                    <li class="nav-item"><Link class="nav-link" :href="route('operators.index')">
                        <div class="d-flex align-items-center"><span class="nav-link-text ps-4">Operarios</span></div>
                      </Link>
                    </li>
                    <li class="nav-item"><Link class="nav-link" :href="route('fuel-tanks.index')">
                        <div class="d-flex align-items-center"><span class="nav-link-text ps-4">Estanques de Combustible</span></div>
                      </Link>
                    </li>
                  </ul>
                </li>
                <li class="nav-item"><Link class="nav-link" :href="route('irrigation-pumps.index')">
                    <div class="d-flex align-items-center"><span class="nav-link-text ps-3">Equipos de Riego</span>
                    </div>
                  </Link>
                  <!-- more inner pages-->
                </li>
              </ul>
              <!-- Remuneraciones --><a class="nav-link dropdown-indicator" href="#remuneraciones" role="button" data-bs-toggle="collapse" aria-expanded="false" aria-controls="remuneraciones">
                <div class="d-flex align-items-center"><span class="nav-link-icon"><span class="fas fa-money-check-alt"></span></span><span class="nav-link-text text-dark ps-1">Remuneraciones</span>
                </div>
              </a>
              <ul class="nav collapse" id="remuneraciones">
                <li class="nav-item"><Link class="nav-link" :href="route('employees.index')">
                    <div class="d-flex align-items-center"><span class="nav-link-text ps-3">Colaboradores</span>
                    </div>
                  </Link>
                </li>
                <li class="nav-item"><Link class="nav-link" :href="route('contracts.index')">
                    <div class="d-flex align-items-center"><span class="nav-link-text ps-3">Contratos</span>
                    </div>
                  </Link>
                </li>
                <li class="nav-item"><Link class="nav-link" :href="route('terminations.index')">
                    <div class="d-flex align-items-center"><span class="nav-link-text ps-3">Términos de Faena</span>
                    </div>
                  </Link>
                </li>
                <!-- Plantillas (submenú) -->
                <li class="nav-item">
                  <a class="nav-link dropdown-indicator ps-3" href="#plantillas" role="button" data-bs-toggle="collapse" aria-expanded="false" aria-controls="plantillas" style="padding-left: 1rem !important;">
                    <div class="d-flex align-items-center">
                      <span class="nav-link-icon"><span class="fas fa-layer-group fa-xs"></span></span>
                      <span class="nav-link-text ps-1">Plantillas</span>
                    </div>
                  </a>
                  <ul class="nav collapse" id="plantillas">
                    <li class="nav-item"><Link class="nav-link" :href="route('contract-templates.index')">
                        <div class="d-flex align-items-center"><span class="nav-link-text ps-4">Plantillas de Contrato</span>
                        </div>
                      </Link>
                    </li>
                    <li class="nav-item"><Link class="nav-link" :href="route('termination-templates.index')">
                        <div class="d-flex align-items-center"><span class="nav-link-text ps-4">Plantillas Término de Contrato</span>
                        </div>
                      </Link>
                    </li>
                  </ul>
                </li>
                <li class="nav-item"><Link class="nav-link" :href="route('daily-management.index')">
                    <div class="d-flex align-items-center"><span class="nav-link-text ps-3">Gestión Diaria</span>
                    </div>
                  </Link>
                </li>
                <li class="nav-item"><Link class="nav-link" :href="route('payroll-dashboard')">
                    <div class="d-flex align-items-center"><span class="nav-link-icon"><span class="fas fa-chart-bar"></span></span><span class="nav-link-text ps-1">Dashboard Remuneraciones</span>
                    </div>
                  </Link>
                </li>
                <li class="nav-item"><Link class="nav-link" :href="route('payroll-reports.index')">
                    <div class="d-flex align-items-center"><span class="nav-link-icon"><span class="fas fa-file-invoice-dollar"></span></span><span class="nav-link-text ps-1">Reporte Mensual</span>
                    </div>
                  </Link>
                </li>
                <li class="nav-item"><Link class="nav-link" :href="route('monthly-bonuses.index')">
                    <div class="d-flex align-items-center"><span class="nav-link-icon"><span class="fas fa-hand-holding-usd"></span></span><span class="nav-link-text ps-1">Bonos y Descuentos</span>
                    </div>
                  </Link>
                </li>
                <li class="nav-item"><Link class="nav-link" :href="route('overtime-hours.index')">
                    <div class="d-flex align-items-center"><span class="nav-link-icon"><span class="fas fa-clock"></span></span><span class="nav-link-text ps-1">Horas Extras</span>
                    </div>
                  </Link>
                </li>
                <li class="nav-item"><Link class="nav-link" :href="route('vacations.index')">
                    <div class="d-flex align-items-center"><span class="nav-link-icon"><span class="fas fa-umbrella-beach"></span></span><span class="nav-link-text ps-1">Vacaciones</span>
                    </div>
                  </Link>
                </li>
                <li class="nav-item"><Link class="nav-link" :href="route('holidays.index')">
                    <div class="d-flex align-items-center"><span class="nav-link-icon"><span class="fas fa-calendar-day"></span></span><span class="nav-link-text ps-1">Feriados</span>
                    </div>
                  </Link>
                </li>
                <li class="nav-item"><Link class="nav-link" :href="route('schedules.index')">
                    <div class="d-flex align-items-center"><span class="nav-link-icon"><span class="fas fa-business-time"></span></span><span class="nav-link-text ps-1">Horario</span>
                    </div>
                  </Link>
                </li>
                <li class="nav-item"><Link class="nav-link" :href="route('cities.index')">
                    <div class="d-flex align-items-center"><span class="nav-link-icon"><span class="fas fa-city"></span></span><span class="nav-link-text ps-1">Ciudades</span>
                    </div>
                  </Link>
                </li>
              </ul>
              <!-- parent pages--><a class="nav-link dropdown-indicator" href="#planificacion" role="button" data-bs-toggle="collapse" aria-expanded="false" aria-controls="planificacion">
                <div class="d-flex align-items-center"><span class="nav-link-icon"><span class="fas fa-seedling"></span></span><span class="nav-link-text text-dark ps-1">Evaluación de Proyectos</span>
                </div>
              </a>
              <ul class="nav collapse" id="planificacion">
                <li class="nav-item"><Link class="nav-link" :href="route('project-evaluations.index')">
                    <div class="d-flex align-items-center"><span class="nav-link-text ps-3">Evaluación de Proyectos</span>
                    </div>
                  </Link>
                </li>
              </ul>
              <!-- parent pages--><a class="nav-link dropdown-indicator" href="#user" role="button" data-bs-toggle="collapse" aria-expanded="false" aria-controls="user">
                <div class="d-flex align-items-center"><span class="nav-link-icon"><span class="fas fa-user"></span></span><span class="nav-link-text text-dark ps-1">Parametros</span>
                </div>
              </a>
              <ul class="nav collapse" id="user">
                <li class="nav-item">
                  <a class="nav-link dropdown-indicator" href="#costcenters" data-bs-toggle="collapse" aria-expanded="false" aria-controls="costcenters">
                    <div class="d-flex align-items-center">
                      <span class="nav-link-text ps-3">Centros de costos</span>
                    </div>
                  </a>
                  <ul class="nav collapse" id="costcenters">
                    <li class="nav-item">
                      <Link class="nav-link" :href="route('cost.centers.index')">
                        <div class="d-flex align-items-center">
                          <span class="nav-link-text ps-4">Crear Cc</span>
                          <span v-if="hasCostCenter !== null" class="ms-2">
                            <span v-if="hasCostCenter" class="text-success"><i class="fas fa-check-circle"></i></span>
                            <span v-else class="text-danger"><i class="fas fa-times-circle"></i></span>
                          </span>
                        </div>
                      </Link>
                    </li>
                    <li class="nav-item">
                      <Link class="nav-link" :href="route('groupings.index')">
                        <div class="d-flex align-items-center">
                          <span class="nav-link-text ps-4"> Crear Grupos Cc</span>
                        </div>
                      </Link>
                    </li>
                    <li class="nav-item">
                      <Link class="nav-link" :href="route('cost-center-varieties.index')">
                        <div class="d-flex align-items-center">
                          <span class="nav-link-text ps-4">Variedades por Cuartel</span>
                        </div>
                      </Link>
                    </li>
                    <li class="nav-item">
                      <Link class="nav-link" :href="route('branches.index')">
                        <div class="d-flex align-items-center">
                          <span class="nav-link-text ps-4">Sucursales</span>
                        </div>
                      </Link>
                    </li>
                  </ul>
                </li>



                <li class="nav-item">
                  <a class="nav-link dropdown-indicator" href="#niveles" data-bs-toggle="collapse" aria-expanded="false" aria-controls="niveles">
                    <div class="d-flex align-items-center"><span class="nav-link-text ps-3">Niveles</span>
                      <span v-if="hasLevel3 !== null" class="ms-2">
                        <span v-if="hasLevel3" class="text-success"><i class="fas fa-check-circle"></i></span>
                        <span v-else class="text-danger"><i class="fas fa-times-circle"></i></span>
                      </span>
                    </div>
                  </a>
                  <ul class="nav collapse" id="niveles">
                    <li class="nav-item"><Link class="nav-link" :href="route('levels.index')">
                        <div class="d-flex align-items-center"><span class="nav-link-text ps-1">Crear Niveles</span></div>
                      </Link>
                    </li>
                    <li class="nav-item">
                      <a class="nav-link" :href="route('levels.summary')" target="_blank" rel="noopener">
                        <div class="d-flex align-items-center"><span class="nav-link-text ps-1 text-nowrap">Resumen de Niveles <i class="fas fa-external-link-alt ms-1"></i></span></div>
                      </a>
                    </li>
                  </ul>
                </li>
                <li class="nav-item"><Link class="nav-link" :href="route('users.index')">
                    <div class="d-flex align-items-center"><span class="nav-link-text ps-3">Usuarios</span>
                    </div>
                  </Link>
                  <!-- more inner pages-->
                </li>
                <li class="nav-item">
                  <Link class="nav-link" :href="route('company.reasons.index')">
                    <div class="d-flex align-items-center">
                      <span class="nav-link-text ps-3">Razón Social</span>
                      <span v-if="hasCompanyReason !== null" class="ms-2">
                        <span v-if="hasCompanyReason" class="text-success"><i class="fas fa-check-circle"></i></span>
                        <span v-else class="text-danger"><i class="fas fa-times-circle"></i></span>
                      </span>
                    </div>
                  </Link>
                  <!-- more inner pages-->
                </li>
                <li class="nav-item">
                  <Link class="nav-link" :href="route('fruits.index')">
                    <div class="d-flex align-items-center">
                      <span class="nav-link-text ps-3">Frutal</span>
                      <span v-if="hasFruit !== null" class="ms-2">
                        <span v-if="hasFruit" class="text-success"><i class="fas fa-check-circle"></i></span>
                        <span v-else class="text-danger"><i class="fas fa-times-circle"></i></span>
                      </span>
                    </div>
                  </Link>
                  <!-- more inner pages-->
                </li>
                <li class="nav-item">
                  <a class="nav-link" :href="route('phenological-stages.index')" target="_blank" rel="noopener">
                    <div class="d-flex align-items-center">
                      <span class="nav-link-text ps-3">Etapas Fenológicas <i class="fas fa-external-link-alt ms-1"></i></span>
                    </div>
                  </a>
                  <!-- more inner pages-->
                </li>
                <li class="nav-item">
                  <Link class="nav-link" :href="route('varieties.index')">
                    <div class="d-flex align-items-center">
                      <span class="nav-link-text ps-3">Variedades</span>
                      <span v-if="hasVariety !== null" class="ms-2">
                        <span v-if="hasVariety" class="text-success"><i class="fas fa-check-circle"></i></span>
                        <span v-else class="text-danger"><i class="fas fa-times-circle"></i></span>
                      </span>
                    </div>
                  </Link>
                  <!-- more inner pages-->
                </li>
                <li class="nav-item">
                  <Link class="nav-link" :href="route('rootstocks.index')">
                    <div class="d-flex align-items-center">
                      <span class="nav-link-text ps-3">Portainjertos</span>
                    </div>
                  </Link>
                </li>
                <li class="nav-item">
                  <Link class="nav-link" :href="route('parcels.index')">
                    <div class="d-flex align-items-center">
                      <span class="nav-link-text ps-3">Parcelas</span>
                      <span v-if="hasParcel !== null" class="ms-2">
                        <span v-if="hasParcel" class="text-success"><i class="fas fa-check-circle"></i></span>
                        <span v-else class="text-danger"><i class="fas fa-times-circle"></i></span>
                      </span>
                    </div>
                  </Link>
                  <!-- more inner pages-->
                </li>
                <li class="nav-item">
                  <Link class="nav-link" :href="route('seasons.index')">
                    <div class="d-flex align-items-center">
                      <span class="nav-link-text ps-3">Temporadas</span>
                      <span v-if="hasSeason !== null" class="ms-2">
                        <span v-if="hasSeason" class="text-success"><i class="fas fa-check-circle"></i></span>
                        <span v-else class="text-danger"><i class="fas fa-times-circle"></i></span>
                      </span>
                    </div>
                  </Link>
                  <!-- more inner pages-->
                </li>
                <li class="nav-item"><Link class="nav-link" :href="route('faq')">
                    <div class="d-flex align-items-center"><span class="nav-link-text ps-3">FAQ</span>
                    </div>
                  </Link>
                  <!-- more inner pages-->
                </li>
              </ul>

            </li>
          </ul>
        </div>
        <div class="text-center py-2">
          <small class="text-muted" style="font-size: 0.7rem;">v{{ $page.props.appVersion }}</small>
        </div>
        </div>
    </nav>
  <div class="content">
      <nav class="navbar navbar-light navbar-glass navbar-top navbar-expand">

        <button class="btn navbar-toggler-humburger-icon navbar-toggler me-1 me-sm-3 d-xl-none" type="button" data-bs-toggle="collapse" data-bs-target="#navbarVerticalCollapse" aria-controls="navbarVerticalCollapse" aria-expanded="false" aria-label="Toggle Navigation"><span class="navbar-toggle-icon"><span class="toggle-line"></span></span></button>
        <!-- Botón toggle sidebar desktop -->
        <button
            @click="toggleSidebar"
            class="btn d-none d-xl-flex align-items-center justify-content-center sidebar-toggle-btn me-2"
            :title="sidebarHidden ? 'Mostrar menú' : 'Ocultar menú'"
        >
            <i class="fas fa-bars" style="font-size: 0.85rem;"></i>
        </button>
        <a class="navbar-brand me-1 me-sm-3" href="../../index.html">
          <div class="d-flex align-items-center"><img class="me-0 mb-4" :src=" path + '/assets/img/icons/spot-illustrations/alisoft.png'" alt="" width="48" /><span class="font-sans-serif text-primary">ALISOFT</span>
          </div>
        </a>
        <ul class="navbar-nav align-items-center d-none d-lg-block">
          <li class="nav-item">
            <div class="search-box" data-list='{"valueNames":["title"]}'>
              <!-- v2: degradado con color dinámico de temporada -->
              <component
                :is="seasonLinkComponent"
                :href="isAdminUser ? route('select.budget') : undefined"
                class="d-inline-flex align-items-center px-4 py-2 mb-0 mt-1 rounded-pill shadow-lg me-3 text-decoration-none"
                :style="{ background: $page.props.seasonColor ? `linear-gradient(90deg, ${$page.props.seasonColor}cc 0%, ${$page.props.seasonColor} 100%)` : 'linear-gradient(90deg, #6ea8fe 0%, #1e40af 100%)', color: '#fff', whiteSpace: 'nowrap', cursor: isAdminUser ? 'pointer' : 'default' }"
                :title="isAdminUser ? 'Cambiar temporada' : ''"
              >
                <span class="fas fa-calendar-alt me-2 fs-8"></span>
                <span class="fw-bold fs-8">{{$page.props.temporada ?? ''}}</span>
                <span v-if="isAdminUser" class="fas fa-chevron-down ms-2 fs-10 opacity-75"></span>
              </component>
              <!-- Badge temporada bloqueada -->
              <span
                v-if="$page.props.seasonLocked"
                class="d-inline-flex align-items-center px-3 py-1 mb-0 mt-1 rounded-pill me-3 fw-semibold"
                style="background: #e63757; color: #fff; font-size: 0.72rem; white-space: nowrap;"
                v-tooltip="'Esta temporada está bloqueada. Solo lectura.'"
              >
                <i class="fas fa-lock me-1" style="font-size: 0.65rem;"></i> Bloqueada
              </span>
             
              <div class="dropdown-menu border font-base start-0 mt-2 py-0 overflow-hidden w-100">
                <div class="scrollbar list py-3" style="max-height: 24rem;">
                  <h6 class="dropdown-header fw-medium text-uppercase px-x1 fs-11 pt-0 pb-2">Recently Browsed</h6><a class="dropdown-item fs-10 px-x1 py-1 hover-primary" href="../../app/events/event-detail.html">
                    <div class="d-flex align-items-center">
                      <span class="fas fa-circle me-2 text-300 fs-11"></span>

                      <div class="fw-normal title">Pages <span class="fas fa-chevron-right mx-1 text-500 fs-11" data-fa-transform="shrink-2"></span> Events</div>
                    </div>
                  </a>
                  <a class="dropdown-item fs-10 px-x1 py-1 hover-primary" href="../../app/e-commerce/customers.html">
                    <div class="d-flex align-items-center">
                      <span class="fas fa-circle me-2 text-300 fs-11"></span>

                      <div class="fw-normal title">E-commerce <span class="fas fa-chevron-right mx-1 text-500 fs-11" data-fa-transform="shrink-2"></span> Customers</div>
                    </div>
                  </a>

                  <hr class="text-200 dark__text-900" />
                  <h6 class="dropdown-header fw-medium text-uppercase px-x1 fs-11 pt-0 pb-2">Suggested Filter</h6><a class="dropdown-item px-x1 py-1 fs-9" href="../../app/e-commerce/customers.html">
                    <div class="d-flex align-items-center"><span class="badge fw-medium text-decoration-none me-2 badge-subtle-warning">customers:</span>
                      <div class="flex-1 fs-10 title">All customers list</div>
                    </div>
                  </a>
                  <a class="dropdown-item px-x1 py-1 fs-9" href="../../app/events/event-detail.html">
                    <div class="d-flex align-items-center"><span class="badge fw-medium text-decoration-none me-2 badge-subtle-success">events:</span>
                      <div class="flex-1 fs-10 title">Latest events in current month</div>
                    </div>
                  </a>
                  <a class="dropdown-item px-x1 py-1 fs-9" href="../../app/e-commerce/product/product-grid.html">
                    <div class="d-flex align-items-center"><span class="badge fw-medium text-decoration-none me-2 badge-subtle-info">products:</span>
                      <div class="flex-1 fs-10 title">Most popular products</div>
                    </div>
                  </a>

                  <hr class="text-200 dark__text-900" />
                  <h6 class="dropdown-header fw-medium text-uppercase px-x1 fs-11 pt-0 pb-2">Files</h6><a class="dropdown-item px-x1 py-2" href="#!">
                    <div class="d-flex align-items-center">
                      <div class="file-thumbnail me-2"><img class="border h-100 w-100 object-fit-cover rounded-3" src="#" alt="" /></div>
                      <div class="flex-1">
                        <h6 class="mb-0 title">iPhone</h6>
                        <p class="fs-11 mb-0 d-flex"><span class="fw-semi-bold">Antony</span><span class="fw-medium text-600 ms-2">27 Sep at 10:30 AM</span></p>
                      </div>
                    </div>
                  </a>
                  <a class="dropdown-item px-x1 py-2" href="#!">
                    <div class="d-flex align-items-center">
                      <div class="file-thumbnail me-2"><img class="img-fluid" src="#" alt="" /></div>
                      <div class="flex-1">
                        <h6 class="mb-0 title">Falcon v1.8.2</h6>
                        <p class="fs-11 mb-0 d-flex"><span class="fw-semi-bold">John</span><span class="fw-medium text-600 ms-2">30 Sep at 12:30 PM</span></p>
                      </div>
                    </div>
                  </a>

                  <hr class="text-200 dark__text-900" />
                  <h6 class="dropdown-header fw-medium text-uppercase px-x1 fs-11 pt-0 pb-2">Members</h6><a class="dropdown-item px-x1 py-2" href="../../pages/user/profile.html">
                    <div class="d-flex align-items-center">
                      <div class="avatar avatar-l status-online me-2">
                        <img class="rounded-circle" src="#" alt="" />

                      </div>
                      <div class="flex-1">
                        <h6 class="mb-0 title">Anna Karinina</h6>
                        <p class="fs-11 mb-0 d-flex">Technext Limited</p>
                      </div>
                    </div>
                  </a>
                  <a class="dropdown-item px-x1 py-2" href="../../pages/user/profile.html">
                    <div class="d-flex align-items-center">
                      <div class="avatar avatar-l me-2">
                        <img class="rounded-circle" src="#" alt="" />

                      </div>
                      <div class="flex-1">
                        <h6 class="mb-0 title">Antony Hopkins</h6>
                        <p class="fs-11 mb-0 d-flex">Brain Trust</p>
                      </div>
                    </div>
                  </a>
                  <a class="dropdown-item px-x1 py-2" href="../../pages/user/profile.html">
                    <div class="d-flex align-items-center">
                      <div class="avatar avatar-l me-2">
                        <img class="rounded-circle" src="#" alt="" />

                      </div>
                      <div class="flex-1">
                        <h6 class="mb-0 title">Emma Watson</h6>
                        <p class="fs-11 mb-0 d-flex">Google</p>
                      </div>
                    </div>
                  </a>

                </div>
                <div class="text-center mt-n3">
                  <p class="fallback fw-bold fs-8 d-none">No Result Found.</p>
                </div>
              </div>
            </div>
          </li>
        </ul>
        <ul class="navbar-nav navbar-nav-icons ms-auto flex-row align-items-center">
           <li class="nav-item me-2 d-none d-md-block">
            <span class="d-inline-flex align-items-center bg-white shadow-sm border rounded-pill px-3 py-1 mb-1 text-secondary fw-semibold fs-10 bg-opacity-15">
              <span class="fas fa-dollar-sign me-2"></span>
              Dólar: {{$page.props.price ?? ''}}
            </span>
          </li>
          <li class="nav-item me-0 d-none d-md-block" v-if="$page.props.auth.user.team">
            <span class="d-inline-flex align-items-center rounded-pill px-3 py-1 mb-1 fw-semibold fs-10" :style="$page.props.seasonColor ? { background: `linear-gradient(90deg, ${$page.props.seasonColor}cc 0%, ${$page.props.seasonColor} 100%)`, color: '#fff' } : {}" :class="$page.props.seasonColor ? 'shadow-lg' : 'bg-white shadow-sm border text-primary bg-opacity-15'">
              <span class="fas fa-users me-2"></span>
              {{$page.props.auth.user.team.name}}
            </span>
          </li>
         
         
          <li class="nav-item dropdown"><a class="nav-link pe-0 ps-2" id="navbarDropdownUser" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
              <div class="avatar avatar-xl bg-primary bg-opacity-25 p-1 rounded-circle">
                <img class="rounded-circle" :src="$page.props.auth.user.profile_photo_url" alt="photo" />
              </div>
            </a>
            <li class="nav-item dropdown">
            <div class="dropdown-menu dropdown-caret dropdown-caret dropdown-menu-end ps-2" aria-labelledby="navbarDropdownUser">
              <div class="d-flex flex-column align-items-start mb-2">
                <div class="bg-white dark__bg-1000 rounded-2 py-2 px-2 mt-2 w-100">
                  <span class="fas fa-crown text-warning me-2 fs-5"></span>
                  <span class="fw-normal text-dark">{{$page.props.auth.user.name}}</span>
                </div>
                <div class="dropdown-divider my-1 w-100"></div>
                <a class="dropdown-item py-2 w-100" href="#!"><span class="fas fa-user me-2 text-primary"></span>Perfil</a>
                <div class="dropdown-divider my-1 w-100"></div>
                <form @submit.prevent="logout" class="mb-0 w-100">
                  <JetDropdownLink as="button" class="dropdown-item py-2 w-100">
                    <span class="fas fa-sign-out-alt me-2 text-danger"></span>Cerrar Sesión
                  </JetDropdownLink>
                </form>
              </div>
            </div>
            </li>
          </li>
        </ul>
        </nav>

       <slot></slot>

        <!-- Asistente IA flotante -->
        <DatabaseChat />
    </div>
  </div>
</template>

<style>
/* ── Buscador del menú lateral ─────────────────────────────── */
.menu-search-wrapper {
    background: inherit;
}
.menu-search-input {
    font-size: 0.78rem !important;
    background: rgba(0, 0, 0, 0.03);
    border: 1px solid rgba(0, 0, 0, 0.08);
    transition: all 0.2s ease;
    height: 32px;
}
.menu-search-input:focus {
    background: rgba(0, 0, 0, 0.02);
    border-color: rgba(44, 123, 229, 0.4);
    box-shadow: 0 0 0 0.15rem rgba(44, 123, 229, 0.1);
}
.menu-search-input::placeholder {
    color: #b6c1d2;
    font-size: 0.75rem;
}
.menu-search-item {
    transition: all 0.15s ease;
    cursor: pointer;
}
.menu-search-item:hover {
    background: rgba(44, 123, 229, 0.08);
}
.menu-search-icon {
    width: 26px;
    height: 26px;
    min-width: 26px;
    background: rgba(44, 123, 229, 0.08);
    color: #2c7be5;
}

/* ── Punto centrado antes de cada subítem del menú ─────────── */
.navbar-vertical .nav.collapse .nav.collapse .nav-link-text::before,
.navbar-vertical .nav.collapse .nav.collapsing .nav-link-text::before {
    content: '·';
    margin-right: 0.4rem;
    font-size: 1.5em;
    line-height: 1;
    vertical-align: middle;
}
.navbar-vertical .nav.collapse .nav.collapse .nav-link-text,
.navbar-vertical .nav.collapse .nav.collapsing .nav-link-text {
    padding-left: 0.5rem !important;
}

/* ── Menú lateral más grande en móvil ──────────────────────── */
@media (max-width: 1199.98px) {
    /* Área táctil de cada enlace */
    .navbar-vertical .nav-link {
        padding-top: 0.65rem !important;
        padding-bottom: 0.65rem !important;
        font-size: 1rem !important;
    }

    /* Texto de los ítems del menú */
    .navbar-vertical .nav-link-text {
        font-size: 1rem !important;
    }

    /* Ícono del menú */
    .navbar-vertical .nav-link-icon {
        font-size: 1.1rem !important;
        width: 1.5rem;
    }

    /* Ítems de segundo nivel (submenús) */
    .navbar-vertical .nav .nav-link {
        padding-top: 0.55rem !important;
        padding-bottom: 0.55rem !important;
        font-size: 0.95rem !important;
    }

    /* Separación entre grupos del menú */
    .navbar-vertical .navbar-nav > .nav-item {
        margin-bottom: 0.1rem;
    }
}

/* ── Toggle sidebar (ocultar/mostrar completo, solo desktop xl+) ── */
@media (min-width: 1200px) {
    .navbar-vertical {
        transition: transform 0.25s ease;
    }
    .content {
        transition: margin-left 0.25s ease;
    }
    .sidebar-hidden .navbar-vertical {
        transform: translateX(-100%);
    }
    .sidebar-hidden .navbar-vertical.navbar-expand-xl + .content {
        margin-left: 0 !important;
    }
}

.sidebar-toggle-btn {
    width: 32px;
    height: 32px;
    padding: 0;
    color: #5e6e82;
    border: none;
    background: transparent;
    border-radius: 6px;
}
.sidebar-toggle-btn:hover {
    background: rgba(0, 0, 0, 0.06);
    color: #2c7be5;
}
</style>


