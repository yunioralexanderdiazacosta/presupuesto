<script setup>
import { ref, watch, nextTick } from 'vue';
import { useForm } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import ProcessForm from './ProcessForm.vue';

const props = defineProps({
    show: Boolean,
    dispatch: Object,
    classifications: Object,
    costCenterVarieties: Array,
});

const emit = defineEmits(['close', 'saved']);

const processFormRef = ref(null);

const form = useForm({
    process_date: '',
    kg_received: '',
    kg_exported: '',
    kg_national: '',
    kg_industrial: '',
    kg_waste: '',
    items: [],
});

watch(() => props.show, (val) => {
    if (val && props.dispatch) {
        // Reset form - ProcessForm se encarga de cargar datos existentes en onMounted
        form.process_date = '';
        form.kg_received = '';
        form.kg_exported = '';
        form.kg_national = '';
        form.kg_industrial = '';
        form.kg_waste = '';
        form.items = [];
    }
});

function closeModal() {
    emit('close');
}

function save() {
    if (processFormRef.value?.anyTypeExceeded()) {
        Swal.fire({ icon: 'warning', title: 'Kg excedidos', text: 'Los kg por tipo de clasificación no pueden superar los kg despachados.' });
        return;
    }
    if (processFormRef.value?.kgBreakdownExceeded) {
        Swal.fire({ icon: 'warning', title: 'Kg excedidos', text: 'La suma de Exportación + Nacional + Industrial + Descarte no puede superar los Kilos a Proceso.' });
        return;
    }
    form.put(route('production-dispatches.process', props.dispatch.id), {
        onSuccess: () => {
            Swal.fire({ icon: 'success', title: 'Procesado', text: 'Despacho procesado correctamente', timer: 1200, showConfirmButton: false });
            emit('saved');
            closeModal();
        },
        onError: () => {
            Swal.fire({ icon: 'error', title: 'Error', text: 'Revisa los campos e inténtalo de nuevo.' });
        }
    });
}
</script>

<template>
    <div class="modal fade show" tabindex="-1" style="display:block; background:rgba(0,0,0,0.2);" v-if="show">
        <div class="modal-dialog modal-xl">
            <div class="modal-content" style="background-color: #f8f9fa;">
                <div class="modal-header bg-white border-bottom">
                    <h5 class="modal-title d-flex align-items-center">
                        <i class="fas fa-industry text-primary me-2 fs-8"></i>
                        Procesar Lote
                    </h5>
                    <button type="button" class="btn-close" @click="closeModal"></button>
                </div>
                <div class="modal-body">
                    <ProcessForm
                        ref="processFormRef"
                        :form="form"
                        :dispatch="dispatch"
                        :classifications="classifications"
                        :costCenterVarieties="costCenterVarieties"
                    />
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-secondary" @click="closeModal">Cancelar</button>
                    <button type="button" class="btn btn-sm btn-primary" @click="save" :disabled="form.processing">
                        <i class="fas fa-check me-1"></i>Procesar
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
