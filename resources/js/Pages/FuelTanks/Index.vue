<script setup>
import { ref, computed } from 'vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Swal from 'sweetalert2';

const props = defineProps({
    tanks: Array,
    branches: Array,
    fuelProducts: Array,
});

// ── Formulario crear ──────────────────────────────────────────────
const showCreate = ref(false);
const createForm = useForm({
    name: '',
    branch_id: '',
    product_id: '',
    capacity: '',
});

const saveCreate = () => {
    createForm.post(route('fuel-tanks.store'), {
        onSuccess: () => {
            Swal.fire({ icon: 'success', title: 'Estanque creado', timer: 1200, showConfirmButton: false });
            createForm.reset();
            showCreate.value = false;
        },
        onError: () => Swal.fire('Error', 'Revisa los campos e inténtalo de nuevo.', 'error'),
    });
};

// ── Formulario editar ─────────────────────────────────────────────
const editingId = ref(null);
const editForm = useForm({
    name: '',
    branch_id: '',
    product_id: '',
    capacity: '',
    active: true,
});

const startEdit = (tank) => {
    editingId.value = tank.id;
    editForm.name       = tank.name;
    editForm.branch_id  = tank.branch_id ?? '';
    editForm.product_id = tank.product_id ?? '';
    editForm.capacity   = tank.capacity ?? '';
    editForm.active     = tank.active;
};

const cancelEdit = () => {
    editingId.value = null;
    editForm.reset();
};

const saveEdit = (id) => {
    editForm.put(route('fuel-tanks.update', id), {
        onSuccess: () => {
            Swal.fire({ icon: 'success', title: 'Actualizado', timer: 1200, showConfirmButton: false });
            editingId.value = null;
        },
        onError: () => Swal.fire('Error', 'Revisa los campos e inténtalo de nuevo.', 'error'),
    });
};

const deleteTank = (id) => {
    Swal.fire({
        title: '¿Eliminar estanque?',
        text: 'Esta acción no se puede deshacer.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('fuel-tanks.delete', id), {
                onSuccess: () => Swal.fire({ icon: 'success', title: 'Eliminado', timer: 1000, showConfirmButton: false }),
            });
        }
    });
};

const branchName = (id) => props.branches.find(b => b.id === id)?.name ?? '-';
const productName = (id) => props.fuelProducts.find(p => p.id === id)?.name ?? '-';
</script>

<template>
    <AppLayout title="Estanques de Combustible">
        <div class="card my-3">
            <div class="card-header">
                <div class="row flex-between-center">
                    <div class="col-6 col-sm-auto d-flex align-items-center pe-0">
                        <h5 class="fs-9 mb-0 text-nowrap py-2 py-xl-0">
                            <i class="fas fa-tint me-2"></i>Estanques de Combustible
                        </h5>
                    </div>
                    <div class="col-6 col-sm-auto ms-auto text-end ps-0">
                        <div class="d-flex align-items-center gap-2 justify-content-end">
                            <Link :href="route('fuel-outflows.index')" class="btn btn-falcon-default btn-sm">
                                <i class="fas fa-arrow-left me-1"></i>Volver
                            </Link>
                            <button class="btn btn-falcon-default btn-sm" @click="showCreate = !showCreate">
                                <span class="fas fa-plus" data-fa-transform="shrink-3 down-2"></span>
                                <span class="d-none d-sm-inline-block ms-1">Nuevo Estanque</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-body bg-body-tertiary">

                <!-- Formulario crear -->
                <div v-if="showCreate" class="card mb-3 border border-primary">
                    <div class="card-body">
                        <h6 class="mb-3"><i class="fas fa-plus-circle me-1 text-primary"></i>Nuevo Estanque</h6>
                        <div class="row g-2">
                            <div class="col-md-3">
                                <label class="form-label small mb-1">Nombre *</label>
                                <input v-model="createForm.name" type="text" class="form-control form-control-sm"
                                    :class="{ 'is-invalid': createForm.errors.name }"
                                    placeholder="Ej: Estanque Norte" />
                                <div class="invalid-feedback">{{ createForm.errors.name }}</div>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small mb-1">Sucursal</label>
                                <select v-model="createForm.branch_id" class="form-select form-select-sm">
                                    <option value="">Sin sucursal</option>
                                    <option v-for="b in branches" :key="b.id" :value="b.id">{{ b.name }}</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small mb-1">Combustible</label>
                                <select v-model="createForm.product_id" class="form-select form-select-sm">
                                    <option value="">Sin especificar</option>
                                    <option v-for="p in fuelProducts" :key="p.id" :value="p.id">{{ p.name }}</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small mb-1">Capacidad (lts)</label>
                                <input v-model="createForm.capacity" type="number" min="0" step="0.01"
                                    class="form-control form-control-sm" placeholder="Opcional" />
                            </div>
                            <div class="col-md-1 d-flex align-items-end gap-1">
                                <button class="btn btn-falcon-default btn-sm" @click="saveCreate" :disabled="createForm.processing">
                                    <i class="fas fa-save"></i>
                                </button>
                                <button class="btn btn-falcon-default btn-sm" @click="showCreate = false">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tabla de estanques -->
                <div v-if="tanks.length === 0" class="text-center text-muted py-4">
                    <i class="fas fa-tint fa-2x mb-2"></i>
                    <p class="mb-0">No hay estanques registrados aún.</p>
                </div>

                <table v-else class="table table-sm table-hover align-middle" style="font-size: 0.82rem;">
                    <thead class="table-light">
                        <tr>
                            <th>Nombre</th>
                            <th>Sucursal</th>
                            <th>Combustible</th>
                            <th class="text-end">Capacidad (lts)</th>
                            <th class="text-center">Activo</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <template v-for="tank in tanks" :key="tank.id">
                            <!-- Fila normal -->
                            <tr v-if="editingId !== tank.id">
                                <td class="fw-semibold">{{ tank.name }}</td>
                                <td>{{ tank.branch?.name ?? '-' }}</td>
                                <td>{{ tank.product?.name ?? '-' }}</td>
                                <td class="text-end">{{ tank.capacity ? tank.capacity.toLocaleString('es-CL') : '-' }}</td>
                                <td class="text-center">
                                    <span :class="tank.active ? 'badge bg-success' : 'badge bg-secondary'">
                                        {{ tank.active ? 'Sí' : 'No' }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    <div class="d-flex gap-1 justify-content-end">
                                        <button class="btn btn-falcon-default btn-sm" @click="startEdit(tank)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-falcon-default btn-sm text-danger" @click="deleteTank(tank.id)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <!-- Fila edición inline -->
                            <tr v-else class="table-warning">
                                <td>
                                    <input v-model="editForm.name" type="text" class="form-control form-control-sm"
                                        :class="{ 'is-invalid': editForm.errors.name }" />
                                    <div class="invalid-feedback">{{ editForm.errors.name }}</div>
                                </td>
                                <td>
                                    <select v-model="editForm.branch_id" class="form-select form-select-sm">
                                        <option value="">Sin sucursal</option>
                                        <option v-for="b in branches" :key="b.id" :value="b.id">{{ b.name }}</option>
                                    </select>
                                </td>
                                <td>
                                    <select v-model="editForm.product_id" class="form-select form-select-sm">
                                        <option value="">Sin especificar</option>
                                        <option v-for="p in fuelProducts" :key="p.id" :value="p.id">{{ p.name }}</option>
                                    </select>
                                </td>
                                <td>
                                    <input v-model="editForm.capacity" type="number" min="0" step="0.01"
                                        class="form-control form-control-sm" />
                                </td>
                                <td class="text-center">
                                    <div class="form-check form-switch d-flex justify-content-center">
                                        <input v-model="editForm.active" class="form-check-input" type="checkbox" />
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex gap-1 justify-content-end">
                                        <button class="btn btn-falcon-default btn-sm" @click="saveEdit(tank.id)" :disabled="editForm.processing">
                                            <i class="fas fa-save"></i>
                                        </button>
                                        <button class="btn btn-falcon-default btn-sm" @click="cancelEdit">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>
