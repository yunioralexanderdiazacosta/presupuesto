<script setup>
import EstimateForm from './EstimateForm.vue';
import Modal from '@/Components/Modal.vue';
import { usePage } from '@inertiajs/vue3';
import { router } from '@inertiajs/vue3';
import Swal from 'sweetalert2';

const props = defineProps({
    form: Object,
    costcenters: Array,
    estimates: Array,
    estimate_statuses: Array,
    fruits: Array,
    season_id: Number
});

// Se usan las props recibidas en el componente, no es necesario extraer de usePage().props

const emit = defineEmits(['store', 'edit', 'delete']);


import { ref, watch, nextTick } from 'vue';
const selectedFruitId = ref(null);
const selectedEstimateStatusId = ref(null);

// Inicializar selección al abrir el modal o cuando cambian los datos
watch([
    () => props.fruits,
    () => props.estimate_statuses
], ([fruits, statuses]) => {
    nextTick(() => {
        if (fruits && fruits.length && !selectedFruitId.value) {
            selectedFruitId.value = fruits[0].id;
        }
        if (statuses && statuses.length) {
            // Filtrar estados por fruta seleccionada
            const filtered = statuses.filter(s => s.fruit_id == selectedFruitId.value);
            if (filtered.length && !selectedEstimateStatusId.value) {
                selectedEstimateStatusId.value = filtered[0].id;
            }
        }
    });
}, { immediate: true });

// Cuando cambia la fruta, reiniciar estado de estimación
watch(selectedFruitId, (newFruitId) => {
    if (props.estimate_statuses && props.estimate_statuses.length) {
        const filtered = props.estimate_statuses.filter(s => s.fruit_id == newFruitId);
        selectedEstimateStatusId.value = filtered.length ? filtered[0].id : null;
    }
});

const handleStore = (data) => {
    // Guardar selección actual antes de refrescar
    if (data.length && data[0].fruit_id && data[0].estimate_status_id) {
        selectedFruitId.value = data[0].fruit_id;
        selectedEstimateStatusId.value = data[0].estimate_status_id;
    }
    router.post(route('estimates.store'), data, {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
            props.form.reset && props.form.reset();
            Swal.fire({
                position: 'center',
                icon: 'success',
                title: 'Guardado correctamente',
                text: usePage().props.success || '',
                showConfirmButton: false,
                timer: 2000
            });
        },
        onError: (errors) => {
            // Si hay error de validación o duplicado, mostrar alerta
            let msg = 'Ya existe una estimación para ese centro de costo y estado. Por favor revisa los datos.';
            if (errors && (errors.duplicate || errors.error)) {
                msg = errors.duplicate || errors.error;
            } else if (typeof errors === 'string') {
                msg = errors;
            }
            Swal.fire({
                position: 'center',
                icon: 'error',
                title: 'Dato duplicado',
                text: msg,
                showConfirmButton: true
            });
        }
    });
};

const handleEdit = (estimate) => {
    // Guardar selección actual antes de refrescar
    selectedFruitId.value = estimate.fruit_id;
    selectedEstimateStatusId.value = estimate.estimate_status_id;
    router.post(`/estimates/${estimate.id}/update`, estimate, {
        preserveState: true,
        preserveScroll: true,
        onSuccess: (page) => {
            // Si la respuesta es JSON con error (no Inertia), mostrar error y no cerrar modal
            if (page && page.error) {
                Swal.fire({
                    position: 'center',
                    icon: 'error',
                    title: 'Error',
                    text: page.error,
                    showConfirmButton: true
                });
                return;
            }
            if (props.form.errors) props.form.errors = {};
            Swal.fire({
                position: 'center',
                icon: 'success',
                title: 'Estimación actualizada',
                showConfirmButton: false,
                timer: 1200
            });
            // Refresca estimates (SPA)
            router.reload({ only: ['estimates'] });
        },
        onError: (errors) => {
            if (!props.form.errors) props.form.errors = {};
            Object.keys(errors).forEach(key => {
                props.form.errors[key] = errors[key];
            });
        }
    });
};

const handleDelete = (id) => {
    // Eliminar estimate sin cerrar modal ni pasar datos adicionales
    router.delete(`/estimates/${id}/delete`, {}, {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
            Swal.fire({
                position: 'center',
                icon: 'success',
                title: 'Estimación eliminada',
                showConfirmButton: false,
                timer: 1200
            });
            // Refrescar solo el fragmento 'estimates'
            router.reload({ only: ['estimates'] });
        }
    });
};
</script>
<template>
    <Modal :maxWidth="'xl'" :id="'createEstimateModal'">
        <template #header>
            <div class="d-flex align-items-center gap-2 mb-3">
                <span class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 38px; height: 38px; font-size: 1.4rem;">
                    <i class="fas fa-chess"></i>
                </span>
                <span>
                    <span class="fw-bold" style="font-size: 1.2rem; color: #2d3748; letter-spacing: 0.5px;">Agregar estimaciones</span>
                    <br>
                    <span class="text-muted" style="font-size: 0.85rem;">Completa los datos de la tabla con los kilos estimados</span>
                </span>
            </div>
        </template>
        <template #body>
            <EstimateForm 
                :form="form" 
                :costcenters="costcenters" 
                :estimates="estimates" 
                :estimate_statuses="estimate_statuses" 
                :fruits="fruits" 
                :season_id="season_id" 
                :selected-fruit-id="selectedFruitId"
                :selected-estimate-status-id="selectedEstimateStatusId"
                @store="handleStore"
                @edit="handleEdit"
                @delete="handleDelete"
                enable-edit
            />
        </template>
        <template #footer>
            <button
                type="button"
                id="kt_modal_add_estimate_cancel"
                data-bs-dismiss="modal"
                class="btn btn-secondary"
            >
                <i class="fas fa-times me-1"></i> Cerrar
            </button>
        </template>
    </Modal>
</template>