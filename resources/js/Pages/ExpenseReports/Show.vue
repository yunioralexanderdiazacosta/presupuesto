<script setup>
import { ref, computed, onMounted } from 'vue';
import { Link, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import CreateSupplierModal from '@/Components/Suppliers/CreateSupplierModal.vue';
import Swal from 'sweetalert2';
import axios from 'axios';

const props = defineProps({
    report: Object,
    suppliers: Array,
    typeDocuments: Array,
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
    type_document_id: '',
    document_number: '',
    product_name: '',
    description: '',
    amount: '',
    receipt: null,
    notes: '',
});

const fileInput = ref(null);
const isCompressing = ref(false);
const fileSizeInfo = ref('');
const editingItem = ref(null);
const isEditingItem = computed(() => !!editingItem.value);

const openAddItemModal = () => {
    editingItem.value = null;
    itemForm.reset();
    itemForm.clearErrors();
    itemForm.date = new Date().toISOString().split('T')[0];
    if (fileInput.value) fileInput.value.value = '';
    fileSizeInfo.value = '';
    $('#addItemModal').modal('show');
};

const openEditItemModal = (item) => {
    editingItem.value = item;
    itemForm.clearErrors();
    itemForm.date = item.date;
    itemForm.supplier_id = item.supplier_id;
    itemForm.type_document_id = item.type_document_id;
    itemForm.document_number = item.document_number;
    itemForm.product_name = item.product_name;
    itemForm.description = item.description || '';
    itemForm.amount = item.amount;
    itemForm.receipt = null;
    itemForm.notes = item.notes || '';
    if (fileInput.value) fileInput.value.value = '';
    fileSizeInfo.value = '';
    $('#addItemModal').modal('show');
};

// Evita que el modal se cierre (backdrop, Esc, botones) mientras el documento se está subiendo
onMounted(() => {
    const modalEl = document.getElementById('addItemModal');
    if (modalEl) {
        modalEl.addEventListener('hide.bs.modal', (e) => {
            if (isSubmittingItem.value) {
                e.preventDefault();
            }
        });
    }
});

// ─── Proveedor rápido (crear al vuelo) ──────
const supplierOptions = ref([...(props.suppliers || [])]);
const supplierForm = useForm({
    name: '',
    rut: '',
    contact: '',
    email: '',
    phone: '',
    accounts: [],
});

const openCreateSupplierModal = () => {
    supplierForm.reset();
    supplierForm.clearErrors();
    $('#createSupplierModal').modal('show');
};

const storeSupplier = async () => {
    try {
        const response = await axios.post(route('api.suppliers.store'), supplierForm.data());
        const newSupplier = response.data.supplier;

        $('#createSupplierModal').modal('hide');

        supplierOptions.value = [
            ...supplierOptions.value,
            { value: newSupplier.id, label: newSupplier.name },
        ];
        itemForm.supplier_id = newSupplier.id;

        Swal.fire({
            icon: 'success',
            title: 'Proveedor creado',
            text: 'Se ha seleccionado automáticamente',
            timer: 2000,
            showConfirmButton: false,
        });

        supplierForm.reset();
    } catch (error) {
        const errors = error.response?.data?.errors || {};
        const errorMessages = Object.entries(errors).map(([field, messages]) => {
            const fieldNames = { name: 'Nombre', rut: 'RUT', email: 'Email', contact: 'Contacto', phone: 'Teléfono' };
            const fieldName = fieldNames[field] || field;
            const message = Array.isArray(messages) ? messages[0] : messages;
            return `${fieldName}: ${message}`;
        });

        Swal.fire({
            icon: 'error',
            title: 'Error al crear proveedor',
            html: `<div class="text-start">${errorMessages.join('<br>') || 'No se pudo crear el proveedor'}</div>`,
            confirmButtonColor: '#d33',
        });
    }
};

// ─── Compresión automática de imágenes adjuntas ──────
// Reduce el tamaño de fotos (celular) antes de subirlas, sin afectar PDFs.
const compressImage = (file, maxDimension = 1600, quality = 0.7) => {
    return new Promise((resolve) => {
        const img = new Image();
        const reader = new FileReader();
        reader.onload = (e) => {
            img.onload = () => {
                let { width, height } = img;
                if (width > maxDimension || height > maxDimension) {
                    if (width > height) {
                        height = Math.round(height * (maxDimension / width));
                        width = maxDimension;
                    } else {
                        width = Math.round(width * (maxDimension / height));
                        height = maxDimension;
                    }
                }
                const canvas = document.createElement('canvas');
                canvas.width = width;
                canvas.height = height;
                const ctx = canvas.getContext('2d');
                ctx.drawImage(img, 0, 0, width, height);
                canvas.toBlob((blob) => {
                    if (!blob || blob.size >= file.size) {
                        resolve(file);
                        return;
                    }
                    const newName = file.name.replace(/\.(png|jpe?g|webp)$/i, '') + '.jpg';
                    resolve(new File([blob], newName, { type: 'image/jpeg', lastModified: Date.now() }));
                }, 'image/jpeg', quality);
            };
            img.onerror = () => resolve(file);
            img.src = e.target.result;
        };
        reader.onerror = () => resolve(file);
        reader.readAsDataURL(file);
    });
};

const formatKB = (bytes) => (bytes / 1024).toFixed(0) + ' KB';

const onFileChange = async (e) => {
    const file = e.target.files[0];
    if (!file) {
        itemForm.receipt = null;
        fileSizeInfo.value = '';
        return;
    }

    const originalSize = file.size;
    const isCompressibleImage = file.type.startsWith('image/') && file.type !== 'image/svg+xml';

    if (isCompressibleImage && file.size > 400 * 1024) {
        isCompressing.value = true;
        const compressed = await compressImage(file);
        isCompressing.value = false;
        itemForm.receipt = compressed;
        fileSizeInfo.value = compressed.size < originalSize
            ? `Foto optimizada: ${formatKB(originalSize)} → ${formatKB(compressed.size)}`
            : `Tamaño: ${formatKB(originalSize)}`;
    } else {
        itemForm.receipt = file;
        fileSizeInfo.value = `Tamaño: ${formatKB(originalSize)}`;
    }
};

const isSubmittingItem = ref(false);
const uploadProgress = ref(0);

const submitItem = () => {
    // Validación de campos obligatorios
    const faltantes = [];
    if (!itemForm.amount || Number(itemForm.amount) <= 0) faltantes.push('Monto');
    if (!itemForm.type_document_id) faltantes.push('Tipo Documento');
    if (!itemForm.document_number || !itemForm.document_number.trim()) faltantes.push('Nº Documento');
    if (!itemForm.product_name || !itemForm.product_name.trim()) faltantes.push('Producto');
    if (!itemForm.supplier_id) faltantes.push('Proveedor');
    if (faltantes.length > 0) {
        Swal.fire('Campos obligatorios', 'Debe completar: ' + faltantes.join(', '), 'warning');
        return;
    }

    const formData = new FormData();
    formData.append('date', itemForm.date);
    formData.append('supplier_id', itemForm.supplier_id);
    formData.append('type_document_id', itemForm.type_document_id);
    formData.append('document_number', itemForm.document_number);
    formData.append('product_name', itemForm.product_name);
    if (itemForm.description) formData.append('description', itemForm.description);
    formData.append('amount', itemForm.amount);
    if (itemForm.receipt) formData.append('receipt', itemForm.receipt);
    if (itemForm.notes) formData.append('notes', itemForm.notes);

    isSubmittingItem.value = true;
    uploadProgress.value = 0;

    const url = isEditingItem.value
        ? route('expense-reports.items.update', editingItem.value.id)
        : route('expense-reports.items.store', props.report.id);

    if (isEditingItem.value) {
        formData.append('_method', 'put');
    }

    router.post(url, formData, {
        forceFormData: true,
        onProgress: (progress) => {
            if (progress && progress.percentage !== undefined && progress.percentage !== null) {
                uploadProgress.value = progress.percentage;
            }
        },
        onSuccess: () => {
            isSubmittingItem.value = false;
            $('#addItemModal').modal('hide');
            Swal.fire({
                icon: 'success',
                title: isEditingItem.value ? 'Documento actualizado' : 'Documento agregado',
                showConfirmButton: false,
                timer: 1500,
            });
        },
        onError: (errors) => {
            const msg = Object.values(errors).flat().join('<br>');
            Swal.fire('Error', msg, 'error');
        },
        onFinish: () => {
            isSubmittingItem.value = false;
            uploadProgress.value = 0;
        },
    });
};

// ─── Editar datos de la rendición (solo en borrador) ──
const editReportForm = useForm({
    description: props.report.description || '',
});

const openEditReportModal = () => {
    editReportForm.reset();
    editReportForm.description = props.report.description || '';
    editReportForm.clearErrors();
    $('#editReportModal').modal('show');
};

const submitEditReport = () => {
    editReportForm.put(route('expense-reports.update', props.report.id), {
        preserveScroll: true,
        onSuccess: () => {
            $('#editReportModal').modal('hide');
            Swal.fire({
                icon: 'success',
                title: 'Rendición actualizada',
                showConfirmButton: false,
                timer: 1500,
            });
        },
        onError: (errors) => {
            const msg = Object.values(errors).flat().join('<br>');
            Swal.fire('Error', msg || 'No se pudo actualizar', 'error');
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

// ─── Vincular con factura existente ─────────
const linkingItem = ref(null);
const invoiceSearchTerm = ref('');
const invoiceSearchResults = ref([]);
const searchingInvoices = ref(false);

const openLinkInvoiceModal = (item) => {
    linkingItem.value = item;
    invoiceSearchTerm.value = item.document_number || '';
    invoiceSearchResults.value = [];
    $('#linkInvoiceModal').modal('show');
    searchInvoicesForLink();
};

const searchInvoicesForLink = async () => {
    searchingInvoices.value = true;
    try {
        const response = await axios.get(route('invoices.search'), {
            params: {
                number_document: invoiceSearchTerm.value,
                supplier_id: linkingItem.value?.supplier_id,
            },
        });
        invoiceSearchResults.value = response.data;
    } catch (error) {
        Swal.fire('Error', 'No se pudieron buscar facturas.', 'error');
    } finally {
        searchingInvoices.value = false;
    }
};

const confirmLinkInvoice = (invoice) => {
    Swal.fire({
        title: '¿Vincular este documento?',
        html: `Se vinculará con la factura Nº <strong>${invoice.number_document}</strong>, sin crear una factura nueva.`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sí, vincular',
        cancelButtonText: 'Cancelar',
    }).then((result) => {
        if (!result.isConfirmed) return;

        axios.post(route('api.expense-items.link'), {
            expense_report_item_id: linkingItem.value.id,
            invoice_id: invoice.id,
        }).then(() => {
            $('#linkInvoiceModal').modal('hide');
            Swal.fire({
                icon: 'success',
                title: 'Documento vinculado',
                showConfirmButton: false,
                timer: 1500,
            });
            router.reload({ only: ['report'] });
        }).catch((error) => {
            const msg = error.response?.data?.message || 'No se pudo vincular el documento.';
            Swal.fire('Error', msg, 'error');
        });
    });
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
                <div class="row flex-between-center g-2">
                    <div class="col-12 col-sm-auto d-flex align-items-center flex-wrap pe-0">
                        <h5 class="fs-9 mb-0 text-nowrap py-2 py-xl-0">
                            <i class="fas fa-receipt me-2"></i>{{ report.number }}
                        </h5>
                        <span :class="'badge bg-' + report.status_color + ' ms-2'">
                            {{ report.status_label }}
                        </span>
                    </div>
                    <div class="col-12 col-sm-auto ms-sm-auto text-start text-sm-end ps-0">
                        <div class="d-flex align-items-center flex-wrap gap-2">
                            <Link :href="route('expense-reports.index')" class="btn btn-falcon-default btn-sm">
                                <i class="fas fa-arrow-left"></i>
                                <span class="d-none d-sm-inline-block ms-1">Volver</span>
                            </Link>
                            <a :href="route('expense-reports.pdf', report.id)" target="_blank" class="btn btn-falcon-default btn-sm">
                                <i class="fas fa-print"></i>
                                <span class="d-none d-sm-inline-block ms-1">Imprimir PDF</span>
                            </a>
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
                        <div class="mt-2">
                            <div class="d-flex align-items-center gap-2">
                                <small class="text-muted d-block mb-0">Descripción</small>
                                <button
                                    type="button"
                                    class="btn btn-link text-primary p-0"
                                    @click="openEditReportModal"
                                    v-tooltip="'Editar descripción'"
                                    style="font-size: 0.8rem; line-height: 1;"
                                >
                                    <i class="fas fa-pen"></i>
                                </button>
                            </div>
                            <span v-if="report.description">{{ report.description }}</span>
                            <span v-else class="text-muted fst-italic small">Sin descripción</span>
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
                                    <div v-else class="d-flex align-items-center justify-content-center gap-2">
                                        <span class="badge bg-secondary">
                                            <i class="fas fa-clock"></i>
                                        </span>
                                        <button class="btn btn-sm btn-link p-0 ms-1" @click="openLinkInvoiceModal(item)" v-tooltip="'Vincular con factura existente'">
                                            <i class="fas fa-link"></i>
                                        </button>
                                    </div>
                                </td>
                                <td class="text-center" v-if="isBorrador">
                                    <button class="btn btn-sm btn-falcon-default me-1" @click="openEditItemModal(item)" v-tooltip="'Editar'">
                                        <i class="fas fa-edit"></i>
                                    </button>
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
                                    <template v-else>
                                        <span class="badge bg-secondary small">Pendiente</span>
                                        <button class="btn btn-sm btn-link p-0 ms-2" @click="openLinkInvoiceModal(item)">
                                            <i class="fas fa-link"></i>
                                        </button>
                                    </template>
                                </div>
                                <div class="d-flex gap-2">
                                    <button v-if="isBorrador" class="btn btn-sm btn-falcon-default" @click="openEditItemModal(item)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button v-if="isBorrador" class="btn btn-sm btn-falcon-default" @click="deleteItem(item)">
                                        <i class="fas fa-trash-alt text-danger"></i>
                                    </button>
                                </div>
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
                <div class="modal-dialog modal-dialog-scrollable modal-dialog-centered modal-lg" style="max-width: min(800px, 95vw);">
                    <div class="modal-content">
                        <div class="modal-header py-2 border-bottom">
                            <h6 class="modal-title d-flex align-items-center gap-2 mb-0">
                                <i :class="isEditingItem ? 'fas fa-edit text-primary' : 'fas fa-plus-circle text-primary'"></i>
                                {{ isEditingItem ? 'Editar Documento' : 'Agregar Documento' }}
                            </h6>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" :disabled="isSubmittingItem"></button>
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
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <label class="form-label small mb-0">Proveedor <span class="text-danger">*</span></label>
                                            <button
                                                type="button"
                                                class="btn btn-link text-primary p-0"
                                                @click="openCreateSupplierModal"
                                                v-tooltip="'Agregar nuevo proveedor'"
                                                style="font-size: 0.85rem;"
                                            >
                                                <i class="fas fa-plus-circle"></i>
                                            </button>
                                        </div>
                                        <select v-model="itemForm.supplier_id" class="form-select form-select-sm">
                                            <option value="" disabled selected>Seleccione...</option>
                                            <option v-for="s in supplierOptions" :key="s.value" :value="s.value">{{ s.label }}</option>
                                        </select>
                                    </div>

                                    <!-- Tipo Documento -->
                                    <div class="col-6 col-md-3">
                                        <label class="form-label small mb-1">Tipo Documento <span class="text-danger">*</span></label>
                                        <select v-model="itemForm.type_document_id" class="form-select form-select-sm" required>
                                            <option value="" disabled selected>Seleccione...</option>
                                            <option v-for="t in typeDocuments" :key="t.value" :value="t.value">{{ t.label }}</option>
                                        </select>
                                    </div>

                                    <!-- Nº Documento -->
                                    <div class="col-6 col-md-3">
                                        <label class="form-label small mb-1">Nº Documento <span class="text-danger">*</span></label>
                                        <input type="text" v-model="itemForm.document_number" class="form-control form-control-sm" placeholder="Ej: 001-12345" required>
                                    </div>

                                    <!-- Producto -->
                                    <div class="col-12 col-md-6">
                                        <label class="form-label small mb-1">Producto / Concepto <span class="text-danger">*</span></label>
                                        <input type="text" v-model="itemForm.product_name" class="form-control form-control-sm" placeholder="Ej: Herbicida, Repuesto..." required>
                                    </div>

                                    <!-- Descripción -->
                                    <div class="col-12 col-md-6">
                                        <label class="form-label small mb-1">Descripción</label>
                                        <input type="text" v-model="itemForm.description" class="form-control form-control-sm" placeholder="Detalle del gasto...">
                                    </div>

                                    <!-- Comprobante (foto/PDF) -->
                                    <div class="col-12 col-md-6">
                                        <label class="form-label small mb-1">
                                            <i class="fas fa-camera me-1 text-muted"></i>Comprobante (foto o PDF)
                                        </label>
                                        <div v-if="isEditingItem && editingItem?.receipt_path" class="small mb-1">
                                            <button type="button" class="btn btn-sm btn-link p-0" @click="viewReceipt(editingItem)">
                                                <i class="fas fa-paperclip me-1"></i>Ver comprobante actual
                                            </button>
                                        </div>
                                        <input 
                                            type="file" 
                                            ref="fileInput"
                                            class="form-control form-control-sm" 
                                            accept="image/*,application/pdf"
                                            capture="environment"
                                            @change="onFileChange"
                                        >
                                        <small v-if="isCompressing" class="text-primary d-block">
                                            <span class="spinner-border spinner-border-sm me-1" style="width: 0.7rem; height: 0.7rem;"></span>Optimizando foto...
                                        </small>
                                        <small v-else-if="fileSizeInfo" class="text-success d-block">{{ fileSizeInfo }}</small>
                                        <small v-else-if="isEditingItem" class="text-muted">Deja vacío para mantener el comprobante actual.</small>
                                        <small v-else class="text-muted">Máx 5 MB. JPG, PNG o PDF. Las fotos se optimizan automáticamente.</small>
                                    </div>

                                    <!-- Notas -->
                                    <div class="col-12 col-md-6">
                                        <label class="form-label small mb-1">Notas</label>
                                        <input type="text" v-model="itemForm.notes" class="form-control form-control-sm" placeholder="Observaciones...">
                                    </div>
                                </div>
                            </form>
                        </div>
                        <div class="modal-footer py-2 gap-2 flex-wrap">
                            <div v-if="isSubmittingItem" class="w-100 px-1 mb-1">
                                <div class="progress" style="height: 6px;">
                                    <div
                                        class="progress-bar progress-bar-striped progress-bar-animated"
                                        role="progressbar"
                                        :style="{ width: (uploadProgress > 0 ? uploadProgress : 100) + '%' }"
                                    ></div>
                                </div>
                                <small class="text-muted d-block text-center mt-1">
                                    <i class="fas fa-cloud-upload-alt me-1"></i>
                                    Subiendo documento{{ uploadProgress > 0 ? ` (${uploadProgress}%)` : '...' }} No cierre esta ventana.
                                </small>
                            </div>
                            <button type="button" class="btn btn-sm btn-secondary flex-fill flex-md-grow-0" data-bs-dismiss="modal" :disabled="isSubmittingItem">
                                <i class="fas fa-times me-1"></i>Cancelar
                            </button>
                            <button 
                                type="button" 
                                class="btn btn-sm btn-primary flex-fill flex-md-grow-0" 
                                @click="submitItem"
                                :disabled="isSubmittingItem || !itemForm.supplier_id || !itemForm.amount || !itemForm.type_document_id || !itemForm.document_number || !itemForm.product_name"
                            >
                                <span v-if="isSubmittingItem">
                                    <span class="spinner-border spinner-border-sm me-1" role="status"></span>
                                    {{ isEditingItem ? 'Guardando' : 'Subiendo' }}{{ uploadProgress > 0 ? ` ${uploadProgress}%` : '...' }}
                                </span>
                                <span v-else>
                                    <i :class="isEditingItem ? 'fas fa-save me-1' : 'fas fa-plus me-1'"></i>{{ isEditingItem ? 'Guardar Cambios' : 'Agregar Documento' }}
                                </span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Modal Editar Rendición (descripción) -->
        <Teleport to="body">
            <div class="modal fade" id="editReportModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header py-2 border-bottom">
                            <h6 class="modal-title d-flex align-items-center gap-2 mb-0">
                                <i class="fas fa-edit text-primary"></i>Editar Descripción
                            </h6>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <form @submit.prevent="submitEditReport">
                                <div class="mb-3">
                                    <label class="form-label small">Descripción (opcional)</label>
                                    <textarea
                                        v-model="editReportForm.description"
                                        class="form-control form-control-sm"
                                        rows="3"
                                        placeholder="Ej: Gastos operación semana 9..."
                                    ></textarea>
                                </div>
                            </form>
                        </div>
                        <div class="modal-footer py-2">
                            <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button
                                type="button"
                                class="btn btn-sm btn-primary"
                                @click="submitEditReport"
                                :disabled="editReportForm.processing"
                            >
                                <i class="fas fa-save me-1"></i>
                                {{ editReportForm.processing ? 'Guardando...' : 'Guardar Cambios' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Modal Vincular con Factura Existente -->
        <Teleport to="body">
            <div class="modal fade" id="linkInvoiceModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-lg">
                    <div class="modal-content">
                        <div class="modal-header py-2 border-bottom">
                            <h6 class="modal-title d-flex align-items-center gap-2 mb-0">
                                <i class="fas fa-link text-primary"></i>Vincular con Factura Existente
                            </h6>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <p class="text-muted small mb-2">
                                Busca la factura que ya fue ingresada para este documento y vincúlala a la rendición,
                                sin crear una factura nueva.
                            </p>
                            <div class="input-group input-group-sm mb-3">
                                <input
                                    type="text"
                                    v-model="invoiceSearchTerm"
                                    class="form-control"
                                    placeholder="Buscar por N° de documento..."
                                    @keyup.enter="searchInvoicesForLink"
                                >
                                <button class="btn btn-falcon-default" type="button" @click="searchInvoicesForLink">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>

                            <div v-if="searchingInvoices" class="text-center py-4">
                                <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                                <p class="mt-2 text-muted small mb-0">Buscando facturas...</p>
                            </div>
                            <div v-else-if="invoiceSearchResults.length === 0" class="text-center text-muted py-4 small">
                                No se encontraron facturas para ese proveedor/N° de documento.
                            </div>
                            <div v-else class="table-responsive" style="max-height: 320px; overflow-y: auto;">
                                <table class="table table-sm table-hover fs-10 mb-0">
                                    <thead class="bg-200">
                                        <tr>
                                            <th>N° Doc</th>
                                            <th>Fecha</th>
                                            <th>Tipo Doc.</th>
                                            <th class="text-end">Total</th>
                                            <th class="text-center">Acción</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="invoice in invoiceSearchResults" :key="invoice.id">
                                            <td>{{ invoice.number_document }}</td>
                                            <td>{{ invoice.date }}</td>
                                            <td>{{ invoice.type_document || '—' }}</td>
                                            <td class="text-end">{{ formatCurrency(invoice.total_invoice) }}</td>
                                            <td class="text-center">
                                                <button class="btn btn-sm btn-primary py-0 px-2" @click="confirmLinkInvoice(invoice)">
                                                    <i class="fas fa-link me-1"></i>Vincular
                                                </button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="modal-footer py-2">
                            <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Modal Crear Proveedor rápido (teleported para quedar por encima del modal de documento) -->
        <Teleport to="body">
            <CreateSupplierModal :form="supplierForm" @store="storeSupplier" />
        </Teleport>
    </AppLayout>
</template>


