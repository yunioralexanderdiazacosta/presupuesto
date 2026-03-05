<script setup>
import { ref, computed } from 'vue';
import { Link, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Swal from 'sweetalert2';

const props = defineProps({
    report: Object,
    suppliers: Array,
    products: Array,
    approvers: Array,
    authUserId: Number,
});

// ─── Estado de la rendición ─────────────────
const isBorrador = computed(() => props.report.status === 'borrador');
const isEnviada = computed(() => props.report.status === 'enviada');
const isAprobada = computed(() => props.report.status === 'aprobada');
const isRechazada = computed(() => props.report.status === 'rechazada');
const isAssignedApprover = computed(() => props.report.assigned_to === props.authUserId);

// ─── Formulario agregar item ────────────────
const itemForm = useForm({
    date: new Date().toISOString().split('T')[0],
    supplier_id: '',
    document_number: '',
    product_id: '',
    description: '',
    amount: '',
    receipt: null,
    notes: '',
});

const fileInput = ref(null);

const openAddItemModal = () => {
    itemForm.reset();
    itemForm.date = new Date().toISOString().split('T')[0];
    if (fileInput.value) fileInput.value.value = '';
    $('#addItemModal').modal('show');
};

const onFileChange = (e) => {
    itemForm.receipt = e.target.files[0] || null;
};

const submitItem = () => {
    const formData = new FormData();
    formData.append('date', itemForm.date);
    formData.append('supplier_id', itemForm.supplier_id);
    if (itemForm.document_number) formData.append('document_number', itemForm.document_number);
    if (itemForm.product_id) formData.append('product_id', itemForm.product_id);
    if (itemForm.description) formData.append('description', itemForm.description);
    formData.append('amount', itemForm.amount);
    if (itemForm.receipt) formData.append('receipt', itemForm.receipt);
    if (itemForm.notes) formData.append('notes', itemForm.notes);

    router.post(route('expense-reports.items.store', props.report.id), formData, {
        forceFormData: true,
        onSuccess: () => {
            $('#addItemModal').modal('hide');
            Swal.fire({
                icon: 'success',
                title: 'Documento agregado',
                showConfirmButton: false,
                timer: 1500,
            });
        },
        onError: (errors) => {
            const msg = Object.values(errors).flat().join('<br>');
            Swal.fire('Error', msg, 'error');
        },
    });
};

// ─── Eliminar item ──────────────────────────
const deleteItem = (item) => {
    Swal.fire({
        title: '¿Eliminar este documento?',
        text: `${item.supplier_name} - ${formatCurrency(item.amount)}`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonText: 'Cancelar',
        confirmButtonText: 'Sí, eliminar',
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('expense-reports.items.delete', item.id), {
                onSuccess: () => {
                    Swal.fire({
                        icon: 'success',
                        title: 'Documento eliminado',
                        showConfirmButton: false,
                        timer: 1500,
                    });
                },
            });
        }
    });
};

// ─── Enviar con selección de aprobador ──────
const sendForApproval = () => {
    if (props.approvers.length === 0) {
        Swal.fire('Sin aprobadores', 'No hay usuarios con rol "Aprobador Rendiciones" en tu equipo. Contacta al administrador.', 'warning');
        return;
    }

    const inputOptions = {};
    props.approvers.forEach(a => { inputOptions[a.value] = a.label; });

    Swal.fire({
        title: '¿Enviar rendición?',
        text: 'Selecciona quién debe aprobar esta rendición:',
        icon: 'question',
        input: 'select',
        inputOptions: inputOptions,
        inputPlaceholder: 'Seleccione aprobador...',
        inputValidator: (val) => !val && 'Debe seleccionar un aprobador',
        showCancelButton: true,
        cancelButtonText: 'Cancelar',
        confirmButtonText: 'Enviar',
    }).then((result) => {
        if (result.isConfirmed) {
            router.patch(route('expense-reports.update-status', props.report.id), {
                status: 'enviada',
                assigned_to: result.value,
            }, {
                preserveScroll: true,
                onSuccess: () => {
                    Swal.fire({
                        icon: 'success',
                        title: 'Rendición enviada',
                        text: 'Se ha notificado al aprobador por correo.',
                        showConfirmButton: false,
                        timer: 2000,
                    });
                },
                onError: (errors) => {
                    const msg = Object.values(errors).flat().join('<br>');
                    Swal.fire('Error', msg || 'No se pudo enviar', 'error');
                },
            });
        }
    });
};

// ─── Cambiar estado (aprobar, pagar, rechazar, borrador) ──
const changeStatus = (newStatus) => {
    const labels = {
        aprobada: { title: '¿Aprobar rendición?', text: 'Se marcará como aprobada y se notificará al rendidor.', icon: 'question' },
        pagada: { title: '¿Marcar como pagada?', text: 'Se confirmará el pago de esta rendición.', icon: 'question' },
        rechazada: { title: '¿Rechazar rendición?', text: 'Indique el motivo del rechazo.', icon: 'warning', input: 'textarea' },
        borrador: { title: '¿Volver a borrador?', text: 'Podrá editar y corregir la rendición.', icon: 'question' },
    };

    const cfg = labels[newStatus];
    const swalConfig = {
        title: cfg.title,
        text: cfg.text,
        icon: cfg.icon,
        showCancelButton: true,
        cancelButtonText: 'Cancelar',
        confirmButtonText: 'Confirmar',
    };

    if (cfg.input) {
        swalConfig.input = 'textarea';
        swalConfig.inputPlaceholder = 'Motivo del rechazo...';
        swalConfig.inputValidator = (val) => !val && 'Debe indicar el motivo';
    }

    Swal.fire(swalConfig).then((result) => {
        if (result.isConfirmed) {
            const data = { status: newStatus };
            if (newStatus === 'rechazada' && result.value) {
                data.rejection_notes = result.value;
            }
            router.patch(route('expense-reports.update-status', props.report.id), data, {
                preserveScroll: true,
                onSuccess: () => {
                    Swal.fire({
                        icon: 'success',
                        title: 'Estado actualizado',
                        showConfirmButton: false,
                        timer: 1500,
                    });
                },
                onError: (errors) => {
                    const msg = Object.values(errors).flat().join('<br>');
                    Swal.fire('Error', msg || 'No se pudo cambiar el estado', 'error');
                },
            });
        }
    });
};

// ─── Ver comprobante ────────────────────────
const viewReceipt = (item) => {
    if (item.receipt_path) {
        window.open('/storage/' + item.receipt_path, '_blank');
    }
};

// ─── Helpers ────────────────────────────────
const formatCurrency = (value) => {
    return '$ ' + Math.round(value).toLocaleString('es-CL');
};

const totalAmount = computed(() => formatCurrency(props.report.total_amount));
const contabilizedAmount = computed(() => formatCurrency(props.report.contabilized_amount));
const pendingAmount = computed(() => formatCurrency(props.report.pending_amount));
</script>

<template>
    <AppLayout :title="'Rendición ' + report.number">
        <!-- Header card -->
        <div class="card my-3" :class="{'mb-5 pb-2': isBorrador || isEnviada || isAprobada || isRechazada}">
            <div class="card-header">
                <div class="row flex-between-center">
                    <div class="col-6 col-sm-auto d-flex align-items-center pe-0">
                        <h5 class="fs-9 mb-0 text-nowrap py-2 py-xl-0">
                            <i class="fas fa-receipt me-2"></i>{{ report.number }}
                        </h5>
                        <span :class="'badge bg-' + report.status_color + ' ms-2'">
                            {{ report.status_label }}
                        </span>
                    </div>
                    <div class="col-auto ms-auto text-end ps-0">
                        <div class="d-flex align-items-center gap-2">
                            <Link :href="route('expense-reports.index')" class="btn btn-falcon-default btn-sm">
                                <i class="fas fa-arrow-left"></i>
                                <span class="d-none d-sm-inline-block ms-1">Volver</span>
                            </Link>
                            <button v-if="isBorrador" class="btn btn-falcon-default btn-sm" @click="openAddItemModal">
                                <i class="fas fa-plus"></i>
                                <span class="d-none d-sm-inline-block ms-1">Agregar Doc</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-body bg-body-tertiary">
                <!-- Info de la rendición -->
                <div class="row g-3 mb-3">
                    <div class="col-12 col-md-7">
                        <div class="row g-2">
                            <div class="col-6 col-md-4">
                                <small class="text-muted d-block">Rendidor</small>
                                <strong>{{ report.user_name }}</strong>
                            </div>
                            <div class="col-6 col-md-4">
                                <small class="text-muted d-block">Fecha</small>
                                <strong>{{ report.created_at }}</strong>
                            </div>
                            <div class="col-6 col-md-4" v-if="report.approved_by_name">
                                <small class="text-muted d-block">Aprobado por</small>
                                <strong>{{ report.approved_by_name }}</strong>
                                <small class="text-muted d-block">{{ report.approved_at }}</small>
                            </div>
                        </div>
                        <div v-if="report.description" class="mt-2">
                            <small class="text-muted d-block">Descripción</small>
                            <span>{{ report.description }}</span>
                        </div>
                        <div v-if="report.rejection_notes" class="mt-2">
                            <div class="alert alert-danger py-2 mb-0 small">
                                <i class="fas fa-exclamation-circle me-1"></i>
                                <strong>Motivo rechazo:</strong> {{ report.rejection_notes }}
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-md-5">
                        <div class="row g-2 text-md-end">
                            <div class="col-4">
                                <small class="text-muted d-block">Total</small>
                                <strong class="fs-8">{{ totalAmount }}</strong>
                            </div>
                            <div class="col-4">
                                <small class="text-muted d-block">Contabilizado</small>
                                <strong class="text-success">{{ contabilizedAmount }}</strong>
                            </div>
                            <div class="col-4">
                                <small class="text-muted d-block">Pendiente</small>
                                <strong class="text-warning">{{ pendingAmount }}</strong>
                            </div>
                        </div>

                        <!-- Info aprobador asignado -->
                        <div v-if="report.assigned_to_name && (isEnviada || isAprobada || report.status === 'pagada')" class="mt-2 text-md-end">
                            <small class="text-muted"><i class="fas fa-user-check me-1"></i>Aprobador: <strong>{{ report.assigned_to_name }}</strong></small>
                        </div>

                        <!-- Botones de acción según estado -->
                        <div class="mt-3 d-flex gap-2 flex-wrap justify-content-md-end">
                            <button v-if="isBorrador && report.items.length > 0" 
                                class="btn btn-falcon-default btn-sm" @click="sendForApproval">
                                <i class="fas fa-paper-plane me-1"></i>Enviar
                            </button>
                            <button v-if="isEnviada && isAssignedApprover" 
                                class="btn btn-falcon-default btn-sm" @click="changeStatus('aprobada')">
                                <i class="fas fa-check me-1"></i>Aprobar
                            </button>
                            <button v-if="isEnviada && isAssignedApprover" 
                                class="btn btn-falcon-default btn-sm" @click="changeStatus('rechazada')">
                                <i class="fas fa-times me-1 text-danger"></i>Rechazar
                            </button>
                            <button v-if="isAprobada" 
                                class="btn btn-falcon-default btn-sm" @click="changeStatus('pagada')">
                                <i class="fas fa-dollar-sign me-1"></i>Marcar Pagada
                            </button>
                            <button v-if="isRechazada" 
                                class="btn btn-falcon-default btn-sm" @click="changeStatus('borrador')">
                                <i class="fas fa-undo me-1"></i>Volver a Borrador
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Tabla de items (desktop) -->
                <div class="table-responsive d-none d-md-block">
                    <table class="table table-sm table-hover fs-10 mb-0">
                        <thead class="bg-200">
                            <tr>
                                <th>Fecha</th>
                                <th>Proveedor</th>
                                <th>Nº Doc.</th>
                                <th>Producto</th>
                                <th>Descripción</th>
                                <th class="text-end">Monto</th>
                                <th class="text-center">Comprobante</th>
                                <th class="text-center">Contab.</th>
                                <th class="text-center" v-if="isBorrador">Acc.</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="item in report.items" :key="item.id">
                                <td>{{ item.date_formatted }}</td>
                                <td>{{ item.supplier_name }}</td>
                                <td>{{ item.document_number || '—' }}</td>
                                <td>{{ item.product_name || '—' }}</td>
                                <td class="text-truncate" style="max-width: 200px;">{{ item.description || '—' }}</td>
                                <td class="text-end">{{ formatCurrency(item.amount) }}</td>
                                <td class="text-center">
                                    <button v-if="item.receipt_path" class="btn btn-sm btn-link p-0" @click="viewReceipt(item)" v-tooltip="'Ver comprobante'">
                                        <i class="fas fa-paperclip"></i>
                                    </button>
                                    <span v-else class="text-muted">—</span>
                                </td>
                                <td class="text-center">
                                    <span v-if="item.is_contabilized" class="badge bg-success" v-tooltip="'Factura ' + item.invoice_number">
                                        <i class="fas fa-check"></i>
                                    </span>
                                    <span v-else class="badge bg-secondary">
                                        <i class="fas fa-clock"></i>
                                    </span>
                                </td>
                                <td class="text-center" v-if="isBorrador">
                                    <button class="btn btn-sm btn-falcon-default" @click="deleteItem(item)" v-tooltip="'Eliminar'">
                                        <i class="fas fa-trash-alt text-danger"></i>
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="report.items.length === 0">
                                <td :colspan="isBorrador ? 9 : 8" class="text-center text-muted py-4">
                                    No hay documentos. Haz clic en "Agregar Doc" para comenzar.
                                </td>
                            </tr>
                        </tbody>
                        <tfoot v-if="report.items.length > 0">
                            <tr class="fw-bold bg-100">
                                <td colspan="5" class="text-end">Total:</td>
                                <td class="text-end">{{ totalAmount }}</td>
                                <td colspan="3"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- Cards mobile -->
                <div class="d-md-none">
                    <div v-for="item in report.items" :key="item.id" class="card mb-2 shadow-sm">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-start mb-1">
                                <div>
                                    <strong>{{ item.supplier_name }}</strong>
                                    <div class="text-muted small">{{ item.date_formatted }}<span v-if="item.document_number"> · Doc: {{ item.document_number }}</span></div>
                                </div>
                                <span class="fw-bold">{{ formatCurrency(item.amount) }}</span>
                            </div>
                            <div v-if="item.product_name" class="small">
                                <i class="fas fa-box me-1 text-muted"></i>{{ item.product_name }}
                            </div>
                            <div v-if="item.description" class="small text-muted">{{ item.description }}</div>
                            <div class="d-flex justify-content-between align-items-center mt-2">
                                <div class="d-flex gap-2 align-items-center">
                                    <button v-if="item.receipt_path" class="btn btn-sm btn-link p-0" @click="viewReceipt(item)">
                                        <i class="fas fa-paperclip me-1"></i>Ver
                                    </button>
                                    <span v-if="item.is_contabilized" class="badge bg-success small">
                                        <i class="fas fa-check me-1"></i>{{ item.invoice_number }}
                                    </span>
                                    <span v-else class="badge bg-secondary small">Pendiente</span>
                                </div>
                                <button v-if="isBorrador" class="btn btn-sm btn-falcon-default" @click="deleteItem(item)">
                                    <i class="fas fa-trash-alt text-danger"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div v-if="report.items.length === 0" class="text-center text-muted py-4">
                        No hay documentos. Toca "Agregar Doc" para comenzar.
                    </div>
                    <div v-if="report.items.length > 0" class="text-end fw-bold mt-2 fs-8">
                        Total: {{ totalAmount }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Barra de acciones fija en móvil -->
        <div
            v-if="isBorrador || isEnviada || isAprobada || isRechazada"
            class="d-md-none fixed-bottom py-2 px-3 bg-white border-top shadow-sm"
            style="z-index: 100;"
        >
            <div class="d-flex gap-2">
                <button v-if="isBorrador" class="btn btn-sm btn-falcon-default flex-fill" @click="openAddItemModal">
                    <i class="fas fa-plus me-1"></i>Agregar Doc
                </button>
                <button v-if="isBorrador && report.items.length > 0" class="btn btn-sm btn-primary flex-fill" @click="sendForApproval">
                    <i class="fas fa-paper-plane me-1"></i>Enviar
                </button>
                <button v-if="isEnviada && isAssignedApprover" class="btn btn-sm btn-success flex-fill" @click="changeStatus('aprobada')">
                    <i class="fas fa-check me-1"></i>Aprobar
                </button>
                <button v-if="isEnviada && isAssignedApprover" class="btn btn-sm btn-danger flex-fill" @click="changeStatus('rechazada')">
                    <i class="fas fa-times me-1"></i>Rechazar
                </button>
                <button v-if="isAprobada" class="btn btn-sm btn-primary flex-fill" @click="changeStatus('pagada')">
                    <i class="fas fa-dollar-sign me-1"></i>Marcar Pagada
                </button>
                <button v-if="isRechazada" class="btn btn-sm btn-secondary flex-fill" @click="changeStatus('borrador')">
                    <i class="fas fa-undo me-1"></i>Volver a Borrador
                </button>
            </div>
        </div>

        <!-- Modal Agregar Item (teleported to body to avoid z-index issues with Falcon layout) -->
        <Teleport to="body">
            <div class="modal fade" id="addItemModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-scrollable modal-lg" style="max-width: min(800px, 95vw); margin: 5vh auto;">
                    <div class="modal-content" style="max-height: 88vh;">
                        <div class="modal-header py-2 border-bottom">
                            <h6 class="modal-title d-flex align-items-center gap-2 mb-0">
                                <i class="fas fa-plus-circle text-primary"></i>Agregar Documento
                            </h6>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body p-2 p-md-3">
                            <form @submit.prevent="submitItem">
                                <div class="row g-2">
                                    <!-- Fecha -->
                                    <div class="col-6 col-md-4">
                                        <label class="form-label small mb-1">Fecha <span class="text-danger">*</span></label>
                                        <input type="date" v-model="itemForm.date" class="form-control form-control-sm" required>
                                    </div>

                                    <!-- Monto -->
                                    <div class="col-6 col-md-4">
                                        <label class="form-label small mb-1">Monto ($) <span class="text-danger">*</span></label>
                                        <input type="number" v-model="itemForm.amount" class="form-control form-control-sm" min="1" required placeholder="0">
                                    </div>

                                    <!-- Proveedor -->
                                    <div class="col-12 col-md-4">
                                        <label class="form-label small mb-1">Proveedor <span class="text-danger">*</span></label>
                                        <select v-model="itemForm.supplier_id" class="form-select form-select-sm">
                                            <option value="" disabled selected>Seleccione...</option>
                                            <option v-for="s in suppliers" :key="s.value" :value="s.value">{{ s.label }}</option>
                                        </select>
                                    </div>

                                    <!-- Nº Documento -->
                                    <div class="col-6 col-md-4">
                                        <label class="form-label small mb-1">Nº Documento</label>
                                        <input type="text" v-model="itemForm.document_number" class="form-control form-control-sm" placeholder="Ej: 001-12345">
                                    </div>

                                    <!-- Producto -->
                                    <div class="col-6 col-md-4">
                                        <label class="form-label small mb-1">Producto (opcional)</label>
                                        <select v-model="itemForm.product_id" class="form-select form-select-sm">
                                            <option value="" selected>Seleccione...</option>
                                            <option v-for="p in products" :key="p.value" :value="p.value">{{ p.label }}</option>
                                        </select>
                                    </div>

                                    <!-- Descripción -->
                                    <div class="col-12 col-md-4">
                                        <label class="form-label small mb-1">Descripción</label>
                                        <input type="text" v-model="itemForm.description" class="form-control form-control-sm" placeholder="Detalle del gasto...">
                                    </div>

                                    <!-- Comprobante (foto/PDF) -->
                                    <div class="col-12 col-md-6">
                                        <label class="form-label small mb-1">
                                            <i class="fas fa-camera me-1 text-muted"></i>Comprobante (foto o PDF)
                                        </label>
                                        <input 
                                            type="file" 
                                            ref="fileInput"
                                            class="form-control form-control-sm" 
                                            accept="image/*,application/pdf"
                                            capture="environment"
                                            @change="onFileChange"
                                        >
                                        <small class="text-muted">Máx 5 MB. JPG, PNG o PDF</small>
                                    </div>

                                    <!-- Notas -->
                                    <div class="col-12 col-md-6">
                                        <label class="form-label small mb-1">Notas</label>
                                        <input type="text" v-model="itemForm.notes" class="form-control form-control-sm" placeholder="Observaciones...">
                                    </div>
                                </div>
                            </form>
                        </div>
                        <div class="modal-footer py-2 gap-2">
                            <button type="button" class="btn btn-sm btn-secondary flex-fill flex-md-grow-0" data-bs-dismiss="modal">
                                <i class="fas fa-times me-1"></i>Cancelar
                            </button>
                            <button 
                                type="button" 
                                class="btn btn-sm btn-primary flex-fill flex-md-grow-0" 
                                @click="submitItem"
                                :disabled="itemForm.processing || !itemForm.supplier_id || !itemForm.amount"
                            >
                                <i class="fas fa-plus me-1"></i>
                                {{ itemForm.processing ? 'Guardando...' : 'Agregar Documento' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>


