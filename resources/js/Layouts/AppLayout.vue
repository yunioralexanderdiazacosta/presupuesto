<script setup>
import { router, Link } from '@inertiajs/vue3';
import JetDropdownLink from '@/Components/DropdownLink.vue';
import { onMounted } from 'vue';

const logout = () => {
    router.post(route('logout'));
};
</script>

<template>
  <div class="d-flex">
    <nav class="navbar navbar-light navbar-vertical navbar-expand-xl position-fixed start-0 top-0 vh-100 ms-4">
        <div class="d-flex align-items-center">
            <div class="toggle-icon-wrapper">
              <button class="btn navbar-toggler-humburger-icon navbar-vertical-toggle" data-bs-toggle="tooltip" data-bs-placement="left" title="Toggle Navigation"><span class="navbar-toggle-icon"><span class="toggle-line"></span></span></button>
            </div>

            <a class="navbar-brand" href="index.html">
              <div class="d-flex align-items-center py-3"><img class="me-2" src="#" alt="" width="40" /><span class="font-sans-serif text-primary">Alisoft</span>
              </div>
            </a>
        </div>
        <div class="collapse navbar-collapse" id="navbarVerticalCollapse">
        <div class="navbar-vertical-content scrollbar">
          <ul class="navbar-nav flex-column mb-3" id="navbarVerticalNav">

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
            </li>
            <li class="nav-item" v-role="'Admin'">
              <!--<a class="nav-link" href="pages/starter.html" role="button">
                <div class="d-flex align-items-center"><span class="nav-link-icon"><span class="fas fa-flag"></span></span><span class="nav-link-text ps-1">Starter</span>
                </div>
              </a>
              <a class="nav-link" href="pages/landing.html" role="button">
                <div class="d-flex align-items-center"><span class="nav-link-icon"><span class="fas fa-globe"></span></span><span class="nav-link-text ps-1">Landing</span>
                </div>
              </a>-->
              <!-- parent pages--><a class="nav-link dropdown-indicator" href="#authentication" role="button" data-bs-toggle="collapse" aria-expanded="false" aria-controls="authentication">
                <div class="d-flex align-items-center"><span class="nav-link-icon"><span class="fas fa-lock"></span></span><span class="nav-link-text ps-1">Presupuestos</span>
                </div>
              </a>
              <ul class="nav collapse" id="authentication">
                <li class="nav-item"><Link class="nav-link" :href="route('dashboard')">
                    <div class="d-flex align-items-center"><span class="nav-link-text ps-1">Tablero</span>
                    </div>
                  </Link>
                  <!-- more inner pages-->
                </li>
                <li class="nav-item"><a class="nav-link dropdown-indicator" href="#simple" data-bs-toggle="collapse" aria-expanded="false" aria-controls="authentication">
                    <div class="d-flex align-items-center"><span class="nav-link-text ps-1 mb-1">Presupuesto por CC</span>
                    </div>
                  </a>
                  <!-- more inner pages-->
                  <ul class="nav collapse" id="simple">
                    <li class="nav-item"><Link class="nav-link" :href="route('agrochemicals.index')">
                        <div class="d-flex align-items-center"><span class="nav-link-text ps-4 mt-1 mb-1 text-primary">Agroquimicos</span>
                        </div>
                      </Link>
                      <!-- more inner pages-->
                    </li>
                    <li class="nav-item"><Link class="nav-link" :href="route('fertilizers.index')">
                        <div class="d-flex align-items-center"><span class="nav-link-text ps-4 mb-1 text-primary">Fertilizantes</span>
                        </div>
                      </Link>
                      <!-- more inner pages-->
                    </li>
                    <li class="nav-item"><Link class="nav-link" :href="route('manpowers.index')">
                        <div class="d-flex align-items-center"><span class="nav-link-text ps-4 mb-1 text-primary">Mano de Obra</span>
                        </div>
                      </Link>
                      <!-- more inner pages-->
                    </li>
                    <li class="nav-item"><Link class="nav-link" :href="route('supplies.index')">
                        <div class="d-flex align-items-center"><span class="nav-link-text ps-4  mb-1 text-primary">Insumos</span>
                        </div>
                      </Link>
                      <!-- more inner pages-->
                    </li>
                    <li class="nav-item"><Link class="nav-link" :href="route('services.index')">
                        <div class="d-flex align-items-center"><span class="nav-link-text ps-4  mb-1 text-primary">Servicios</span>
                        </div>
                      </Link>
                      <!-- more inner pages-->
                    </li>
                  </ul>
                </li>
                <li class="nav-item"><a class="nav-link dropdown-indicator" href="#card" data-bs-toggle="collapse" aria-expanded="false" aria-controls="authentication">
                    <div class="d-flex align-items-center"><span class="nav-link-text ps-1 mb-1">Presupuesto por GG Campo</span>
                    </div>
                  </a>
                  <!-- more inner pages-->
                  <ul class="nav collapse" id="card">
                    <li class="nav-item"><Link class="nav-link" :href="route('fields.index')">
                        <div class="d-flex align-items-center"><span class="nav-link-text ps-4 mb-1 text-primary">Gral Campo</span>
                        </div>
                      </Link>
                      <!-- more inner pages-->
                    </li>
                  </ul>
                </li>
                <li class="nav-item"><a class="nav-link dropdown-indicator" href="#split" data-bs-toggle="collapse" aria-expanded="false" aria-controls="authentication">
                    <div class="d-flex align-items-center"><span class="nav-link-text ps-1 mb-1">Presupuesto Administración</span>
                    </div>
                  </a>
                  <!-- more inner pages-->
                  <ul class="nav collapse" id="split">
                    <li class="nav-item"><Link class="nav-link" :href="route('administrations.index')">
                        <div class="d-flex align-items-center"><span class="nav-link-text ps-4 mb-1 text-primary">Administración</span>
                        </div>
                      </Link>
                      <!-- more inner pages-->
                    </li>
                  </ul>
                </li>
                <li class="nav-item"><Link class="nav-link" :href="route('faq')">
                    <div class="d-flex align-items-center"><span class="nav-link-text ps-1 mb-1">FAQ</span>
                    </div>
                  </Link>
                  <!-- more inner pages-->
                </li>
              </ul>
              <!-- parent pages--><a class="nav-link dropdown-indicator" href="#user" role="button" data-bs-toggle="collapse" aria-expanded="false" aria-controls="user">
                <div class="d-flex align-items-center"><span class="nav-link-icon"><span class="fas fa-user"></span></span><span class="nav-link-text ps-1">Parametros</span>
                </div>
              </a>
              <ul class="nav collapse" id="user">
                <li class="nav-item"><Link class="nav-link" :href="route('cost.centers.index')">
                    <div class="d-flex align-items-center"><span class="nav-link-text ps-1">- Centros de costos</span>
                    </div>
                  </Link>
                  <!-- more inner pages-->
                </li>
                <li class="nav-item">
                  <a class="nav-link dropdown-indicator" href="#niveles" data-bs-toggle="collapse" aria-expanded="false" aria-controls="niveles">
                    <div class="d-flex align-items-center"><span class="nav-link-text ps-1">Niveles</span></div>
                  </a>
                  <ul class="nav collapse" id="niveles">
                    <li class="nav-item"><Link class="nav-link" :href="route('levels.index')">
                        <div class="d-flex align-items-center"><span class="nav-link-text ps-1">- Crear Niveles</span></div>
                      </Link>
                    </li>
                    <li class="nav-item"><Link class="nav-link" :href="route('levels.summary')">
                        <div class="d-flex align-items-center"><span class="nav-link-text ps-1">- Resumen de Niveles</span></div>
                      </Link>
                    </li>
                  </ul>
                </li>
                <li class="nav-item"><Link class="nav-link" :href="route('users.index')">
                    <div class="d-flex align-items-center"><span class="nav-link-text ps-1">- Usuarios</span>
                    </div>
                  </Link>
                  <!-- more inner pages-->
                </li>
                <li class="nav-item"><Link class="nav-link" :href="route('company.reasons.index')">
                    <div class="d-flex align-items-center"><span class="nav-link-text ps-1">- Razón Social</span>
                    </div>
                  </Link>
                  <!-- more inner pages-->
                </li>
                <li class="nav-item"><Link class="nav-link" :href="route('fruits.index')">
                    <div class="d-flex align-items-center"><span class="nav-link-text ps-1">- Frutal</span>
                    </div>
                  </Link>
                  <!-- more inner pages-->
                </li>
                <li class="nav-item"><Link class="nav-link" :href="route('varieties.index')">
                    <div class="d-flex align-items-center"><span class="nav-link-text ps-1">- Variedades</span>
                    </div>
                  </Link>
                  <!-- more inner pages-->
                </li>
                <li class="nav-item"><Link class="nav-link" :href="route('parcels.index')">
                    <div class="d-flex align-items-center"><span class="nav-link-text ps-1">- Parcelas</span>
                    </div>
                  </Link>
                  <!-- more inner pages-->
                </li>
                <li class="nav-item"><Link class="nav-link" :href="route('seasons.index')">
                    <div class="d-flex align-items-center"><span class="nav-link-text ps-1">- Temporadas</span>
                    </div>
                  </Link>
                  <!-- more inner pages-->
                </li>
                <li class="nav-item"><Link class="nav-link" :href="route('faq')">
                    <div class="d-flex align-items-center"><span class="nav-link-text ps-1">- FAQ</span>
                    </div>
                  </Link>
                  <!-- more inner pages-->
                </li>
              </ul>
              <!-- parent pages--><a class="nav-link dropdown-indicator" href="#pricing" role="button" data-bs-toggle="collapse" aria-expanded="false" aria-controls="pricing">
                <div class="d-flex align-items-center"><span class="nav-link-icon"><span class="fas fa-tags"></span></span><span class="nav-link-text ps-1">Gestión</span>
                </div>
              </a>
              <ul class="nav collapse" id="pricing">
                <li class="nav-item"><Link class="nav-link" :href="route('suppliers.index')">
                    <div class="d-flex align-items-center"><span class="nav-link-text ps-1">- Proveedores</span>
                    </div>
                  </Link>
                  <!-- more inner pages-->
                </li>
                <li class="nav-item"><Link class="nav-link" :href="route('products.index')">
                    <div class="d-flex align-items-center"><span class="nav-link-text ps-1">- Productos</span>
                    </div>
                  </Link>
                  <!-- more inner pages-->
                </li>
                <li class="nav-item"><Link class="nav-link" :href="route('invoices.index')">
                    <div class="d-flex align-items-center"><span class="nav-link-text ps-1">- Facturas</span>
                    </div>
                  </Link>
                  <!-- more inner pages-->
                </li>
                <li class="nav-item"><Link class="nav-link" :href="route('machineries.index')">
                    <div class="d-flex align-items-center"><span class="nav-link-text ps-1">- Maquinarias</span>
                    </div>
                  </Link>
                  <!-- more inner pages-->
                </li>
                <li class="nav-item"><Link class="nav-link" :href="route('type.machineries.index')">
                    <div class="d-flex align-items-center"><span class="nav-link-text ps-1">- Tipo de Maquinarias</span>
                    </div>
                  </Link>
                  <!-- more inner pages-->
                </li>
              </ul>

            </li>
          </ul>
        </div>
        </div>
    </nav>
    <div class="content flex-grow-1">
      <nav class="navbar navbar-light navbar-glass navbar-top navbar-expand">

        <button class="btn navbar-toggler-humburger-icon navbar-toggler me-1 me-sm-3" type="button" data-bs-toggle="collapse" data-bs-target="#navbarVerticalCollapse" aria-controls="navbarVerticalCollapse" aria-expanded="false" aria-label="Toggle Navigation"><span class="navbar-toggle-icon"><span class="toggle-line"></span></span></button>
        <a class="navbar-brand me-1 me-sm-3" href="../../index.html">
          <div class="d-flex align-items-center"><img class="me-2" src="#" alt="" width="40" /><span class="font-sans-serif text-primary">ALISOFT</span>
          </div>
        </a>
        <ul class="navbar-nav align-items-center d-none d-lg-block">
          <li class="nav-item">
            <div class="search-box" data-list='{"valueNames":["title"]}'>
              <h1 class="fs-6">{{$page.props.temporada ?? ''}}</h1>
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
          <li class="nav-item">
            <button @click="toggleDarkMode" class="btn btn-sm btn-secondary ms-2" title="Alternar modo oscuro">
              <span class="fas fa-moon"></span>
            </button>
          </li>
          <li class="nav-item dropdown"><a class="nav-link pe-0 ps-2" id="navbarDropdownUser" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
              <div class="avatar avatar-xl">
                <img class="rounded-circle" :src="$page.props.auth.user.profile_photo_url" alt="photo" />

              </div>
            </a>
            <div class="dropdown-menu dropdown-caret dropdown-caret dropdown-menu-end py-0" aria-labelledby="navbarDropdownUser">
              <div class="bg-white dark__bg-1000 rounded-2 py-2">
                <a class="dropdown-item fw-bold text-warning" href="#!"><span class="fas fa-crown me-1"></span><span>{{$page.props.auth.user.name}}</span></a>

                <div class="dropdown-divider"></div>
                    <a class="dropdown-item" href="#!">Perfil</a>
                    <div class="dropdown-divider"></div>
                    <form @submit.prevent="logout">
                        <JetDropdownLink as="button" class="dropdown-item">
                            Cerrar Sesión
                        </JetDropdownLink>
                    </form>
              </div>
            </div>
          </li>
        </ul>
        </nav>

       <slot></slot>
    </div>
  </div>
</template>