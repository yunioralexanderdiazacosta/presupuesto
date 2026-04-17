<script setup>
import { ref } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Breadcrumb from '@/Components/Breadcrumb.vue';
import Swal from 'sweetalert2';

const props = defineProps({
    permissionMatrix: Array,
});

const links = [{ title: 'Tablero', link: 'dashboard' }, { title: 'Configuración del Sistema', active: true }];

const toggling = ref({});

const togglePermission = (permName, roleId, currentEnabled) => {
    const key = `${permName}-${roleId}`;
    toggling.value[key] = true;

    router.post(route('system-settings.toggle'), {
        permission: permName,
        role_id: roleId,
        enabled: !currentEnabled,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            Swal.fire({
                icon: 'success',
                title: 'Permiso actualizado',
                showConfirmButton: false,
                timer: 1000,
            });
        },
        onError: (errors) => {
            Swal.fire('Error', errors.error || 'No se pudo actualizar el permiso', 'error');
        },
        onFinish: () => {
            toggling.value[key] = false;
        },
    });
};
</script>

<template>
    <Head title="Configuración del Sistema" />
    <AppLayout>
        <Breadcrumb :links="links" />
        <div class="card my-3">
            <div class="card-header">
                <div class="row flex-between-center">
                    <div class="col-6 col-sm-auto d-flex align-items-center pe-0">
                        <h5 class="fs-9 mb-0 text-nowrap py-2 py-xl-0">
                            <i class="fas fa-cogs me-2"></i>Configuración del Sistema
                        </h5>
                    </div>
                </div>
            </div>

            <div class="card-body bg-body-tertiary">
                <h6 class="mb-3">
                    <i class="fas fa-key me-1"></i> Permisos opcionales por rol
                </h6>
                <p class="text-muted small mb-3">
                    Active o desactive funcionalidades opcionales para cada rol del sistema.
                </p>

                <div class="table-responsive">
                    <table class="table table-sm table-bordered align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="min-width: 250px;">Funcionalidad</th>
                                <th 
                                    v-for="role in (permissionMatrix[0]?.roles || [])" 
                                    :key="role.role_id"
                                    class="text-center"
                                    style="min-width: 120px;"
                                >
                                    {{ role.role_name === 'Normal' ? 'Digitador' : role.role_name }}
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="perm in permissionMatrix" :key="perm.name">
                                <td>
                                    <div class="fw-semibold">{{ perm.label }}</div>
                                    <small class="text-muted">{{ perm.description }}</small>
                                </td>
                                <td 
                                    v-for="role in perm.roles" 
                                    :key="role.role_id"
                                    class="text-center"
                                >
                                    <div class="form-check form-switch d-flex justify-content-center mb-0">
                                        <input
                                            class="form-check-input"
                                            type="checkbox"
                                            :checked="role.enabled"
                                            :disabled="toggling[`${perm.name}-${role.role_id}`]"
                                            @change="togglePermission(perm.name, role.role_id, role.enabled)"
                                        />
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
