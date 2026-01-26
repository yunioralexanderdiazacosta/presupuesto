<script setup>
import { ref, computed } from 'vue';
import Swal from 'sweetalert2';
import { router, Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';
import CreateIrrigationPumpModal from '@/Components/IrrigationPumps/CreateIrrigationPumpModal.vue';
import EditIrrigationPumpModal from '@/Components/IrrigationPumps/EditIrrigationPumpModal.vue';

const props = defineProps({
    irrigationPumps: Object,
    term: String,
});

const title = 'Equipos de Riego';
const links = [
    { title: 'Tablero', link: 'dashboard' },
    { title, active: true },
];

const searchTerm = ref(props.term || '');
const showCreateModal = ref(false);
const showEditModal = ref(false);
const editingPump = ref(null);

function search() {
    router.get(route('irrigation-pumps.index'), { term: searchTerm.value }, {
        preserveState: true,
        replace: true,
    });
}

function openCreateModal() {
    showCreateModal.value = true;
}

function closeCreateModal() {
    showCreateModal.value = false;
}

function openEditModal(pump) {
    // Recargar desde servidor para obtener orders_count actualizado
    router.reload({
        only: ['irrigationPumps'],
        onSuccess: () => {
            // Buscar la bomba actualizada en los datos recargados
            const updatedPump = props.irrigationPumps.data.find(p => p.id === pump.id);
            editingPump.value = updatedPump || pump;
            showEditModal.value = true;
        }
    });
}

function closeEditModal() {
    showEditModal.value = false;
    editingPump.value = null;
}

function confirmDelete(pumpId) {
    Swal.fire({
        title: '¿Está seguro?',
        text: 'Esta acción eliminará la bomba y todos sus sectores asociados',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('irrigation-pumps.delete', pumpId), {
                onSuccess: () => {
                    Swal.fire('¡Eliminado!', 'La bomba ha sido eliminada.', 'success');
                }
            });
        }
    });
}

const getTotalSurface = (pump) => {
    if (!pump.sectors || pump.sectors.length === 0) return 0;
    return pump.sectors.reduce((sum, sector) => sum + parseFloat(sector.surface || 0), 0);
};
</script>

<template>
    <AppLayout :title="title">
        <Head :title="title" />
        
        <Breadcrumb :title="title" :links="links" />

        <div class="card my-3">
            <div class="card-header">
                <div class="row flex-between-center">
                    <div class="col-6 col-sm-auto d-flex align-items-center pe-0">
                        <h5 class="fs-9 mb-0 text-nowrap py-2 py-xl-0">
                            <i class="fas fa-tint me-2"></i>{{ title }}
                        </h5>
                    </div>
                    <div class="col-6 col-sm-auto ms-auto text-end ps-0">
                        <button @click="openCreateModal" class="btn btn-falcon-default btn-sm">
                            <span class="fas fa-plus" data-fa-transform="shrink-3 down-2"></span>
                            <span class="d-none d-sm-inline-block ms-1">Nueva Bomba</span>
                        </button>
                    </div>
                </div>
            </div>

            <div class="card-body bg-body-tertiary">
                <!-- Buscador -->
                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                            <input
                                v-model="searchTerm"
                                @keyup.enter="search"
                                type="text"
                                class="form-control"
                                placeholder="Buscar por nombre, código, marca o modelo..."
                            />
                            <button @click="search" class="btn btn-falcon-default" type="button">
                                Buscar
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Tabla -->
                <div class="table-responsive">
                    <table class="table table-hover table-sm">
                        <thead class="table-light">
                            <tr>
                                <th>Nombre</th>
                                <th>Código</th>
                                <th>Marca</th>
                                <th>Modelo</th>
                                <th>Sectores</th>
                                <th>Superficie Total (ha)</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="pump in irrigationPumps.data" :key="pump.id">
                                <td><strong>{{ pump.name }}</strong></td>
                                <td>{{ pump.code || '-' }}</td>
                                <td>{{ pump.brand || '-' }}</td>
                                <td>{{ pump.model || '-' }}</td>
                                <td>
                                    <span class="badge bg-secondary">{{ pump.sectors?.length || 0 }} sectores</span>
                                </td>
                                <td>{{ getTotalSurface(pump).toFixed(2) }}</td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm">
                                        <button
                                            @click="openEditModal(pump)"
                                            class="btn btn-sm btn-falcon-default"
                                            title="Editar"
                                        >
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button
                                            @click="confirmDelete(pump.id)"
                                            class="btn btn-sm btn-falcon-default"
                                            title="Eliminar"
                                        >
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="irrigationPumps.data.length === 0">
                                <td colspan="7" class="text-center text-muted">
                                    No hay bombas de riego registradas
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Paginación -->
                <div v-if="irrigationPumps.links" class="d-flex justify-content-center mt-3">
                    <nav>
                        <ul class="pagination pagination-sm">
                            <li
                                v-for="(link, index) in irrigationPumps.links"
                                :key="index"
                                class="page-item"
                                :class="{ active: link.active, disabled: !link.url }"
                            >
                                <a
                                    v-if="link.url"
                                    :href="link.url"
                                    class="page-link"
                                    v-html="link.label"
                                    @click.prevent="router.visit(link.url)"
                                />
                                <span v-else class="page-link" v-html="link.label" />
                            </li>
                        </ul>
                    </nav>
                </div>
            </div>
        </div>

        <!-- Modales -->
        <CreateIrrigationPumpModal
            :show="showCreateModal"
            @close="closeCreateModal"
        />

        <EditIrrigationPumpModal
            :show="showEditModal"
            :pump="editingPump"
            @close="closeEditModal"
        />
    </AppLayout>
</template>
