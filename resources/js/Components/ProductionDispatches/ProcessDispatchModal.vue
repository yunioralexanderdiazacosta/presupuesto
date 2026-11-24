<script setup>
import { ref, computed } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import ProcessForm from './ProcessForm.vue';

const props = defineProps({
    show: Boolean,
    dispatch: Object,
    lot: Object,
    classifications: Object,
    dispatches: { type: Array, default: () => [] },
    processes: { type: Array, default: () => [] },
});

const emit = defineEmits(['close', 'saved']);

const processFormRef = ref(null);

// ProcessForm carga los datos (proceso nuevo o existente) al abrirse
const form = useForm({
    process_id: null,
    process_number: '',
    process_date: '',
    kg_exported: '',
    kg_national: '',
    kg_industrial: '',
    kg_waste: '',
    lots: [],
    items: [],
});

const errorMessages = computed(() => Object.values(form.errors || {}));

function closeModal() {
    emit('close');
}

function save() {
    if (!form.process_number) {
        Swal.fire({ icon: 'warning', title: 'Falta el N° de proceso', text: 'Ingresa el número del proceso.' });
        return;
    }
    if (processFormRef.value?.anyLotExceeded()) {
        Swal.fire({ icon: 'warning', title: 'Kg excedidos', text: 'Los kg a proceso de un lote no pueden superar su saldo disponible.' });
        return;
    }
    if (processFormRef.value?.anyTypeExceeded()) {
        Swal.fire({ icon: 'warning', title: 'Kg excedidos', text: 'Los kg por tipo de clasificación no pueden superar los Kg de Exportación.' });
        return;
    }
    if (processFormRef.value?.kgBreakdownExceeded) {
        Swal.fire({ icon: 'warning', title: 'Kg excedidos', text: 'La suma de Exportación + Comercial + Industrial + Descarte no puede superar los Kilos a Proceso.' });
        return;
    }

    const options = {
        preserveScroll: true,
        onSuccess: () => {
            Swal.fire({ icon: 'success', title: 'Procesado', text: 'Proceso guardado correctamente', timer: 1200, showConfirmButton: false });
            emit('saved');
            closeModal();
        },
        onError: () => {
            Swal.fire({ icon: 'error', title: 'Error', text: 'Revisa los campos e inténtalo de nuevo.' });
        }
    };

    form.transform(({ process_id, ...data }) => data);

    if (form.process_id) {
        form.put(route('production-processes.update', form.process_id), options);
    } else {
        form.post(route('production-processes.store'), options);
    }
}

function deleteProcess() {
    Swal.fire({
        title: '¿Eliminar proceso?',
        text: `Se eliminará el proceso N° ${form.process_number} y sus lotes quedarán con ese saldo sin procesar.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (!result.isConfirmed) return;
        router.delete(route('production-processes.delete', form.process_id), {
            preserveScroll: true,
            onSuccess: () => {
                Swal.fire({ icon: 'success', title: 'Eliminado', text: 'Proceso eliminado correctamente', timer: 1200, showConfirmButton: false });
                emit('saved');
                closeModal();
            },
            onError: () => {
                Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo eliminar el proceso' });
            }
        });
    });
}
</script>

<template>
    <div class="modal fade show" tabindex="-1" style="display:block; background:rgba(0,0,0,0.2); overflow-y:auto;" v-if="show">
        <div class="modal-dialog modal-xl">
            <div class="modal-content" style="background-color: #f8f9fa;">
                <div class="modal-header bg-white border-bottom">
                    <h5 class="modal-title d-flex align-items-center">
                        <i class="fas fa-industry text-primary me-2 fs-8"></i>
                        Procesar Lotes
                    </h5>
                    <button type="button" class="btn-close" @click="closeModal"></button>
                </div>
                <div class="modal-body">
                    <div v-if="errorMessages.length" class="alert alert-danger py-2 fs-10">
                        <div v-for="(msg, i) in errorMessages" :key="i">{{ msg }}</div>
                    </div>
                    <ProcessForm
                        ref="processFormRef"
                        :form="form"
                        :dispatch="dispatch"
                        :lot="lot"
                        :classifications="classifications"
                        :dispatches="dispatches"
                        :processes="processes"
                    />
                </div>
                <div class="modal-footer">
                    <button v-if="form.process_id" type="button" class="btn btn-sm btn-outline-danger me-auto" @click="deleteProcess">
                        <i class="fas fa-trash me-1"></i>Eliminar proceso
                    </button>
                    <button type="button" class="btn btn-sm btn-secondary" @click="closeModal">Cancelar</button>
                    <button type="button" class="btn btn-sm btn-primary" @click="save" :disabled="form.processing">
                        <i class="fas fa-check me-1"></i>Procesar
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
