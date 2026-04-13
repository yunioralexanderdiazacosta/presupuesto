

<script setup>
import { ref, watch, nextTick, computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import Multiselect from "@vueform/multiselect";
import TextInput from "@/Components/TextInput.vue";
import InputError from "@/Components/InputError.vue";
import FormProducts from "./FormProducts.vue";
import PdfUploader from "./PdfUploader.vue";
import CreateSupplierModal from '@/Components/Suppliers/CreateSupplierModal.vue';
import { useForm } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import axios from 'axios';

const props = defineProps({
    form: Object,
    protectedProductIds: {
        type: Array,
        default: () => []
    },
});

const page = usePage();

// Lista reactiva de proveedores (copia local para poder agregar nuevos sin perder datos del form)
const supplierOptions = ref([...(page.props.suppliers || [])]);

const paymentTypes = [
    { id: 1, label: "Credito" },
    { id: 2, label: "Contado" },
];

// Estado para modal de crear proveedor
const showCreateSupplierModal = ref(false);
const suggestedSupplierData = ref(null);

// Form para crear proveedor rápido
const supplierForm = useForm({
    name: '',
    rut: '',
    contact: '',
    email: '',
    phone: '',
});

// Sincronización bidireccional entre plazo y tipo de pago
// Si plazo = 0 → Contado automático; si tipo = Contado → plazo = 0
watch(
    () => props.form.payment_term,
    (newVal) => {
        if (newVal === 0 || newVal === '0') {
            props.form.payment_type = 2; // Contado
        } else if (newVal) {
            props.form.payment_type = 1; // Crédito
        }
    }
);

watch(
    () => props.form.payment_type,
    (newVal) => {
        if (newVal === 2 || newVal === '2') {
            props.form.payment_term = 0; // Contado → plazo 0
        } else if ((newVal === 1 || newVal === '1') && (props.form.payment_term === 0 || props.form.payment_term === '0' || !props.form.payment_term)) {
            props.form.payment_term = 30; // Crédito → sugerir 30 días si estaba en 0
        }
    }
);

// Al cambiar la fecha, auto-asignar el mes contable (id = número de mes)
watch(
    () => props.form.date,
    (newDate) => {
        if (newDate) {
            const month = parseInt(newDate.split('-')[1], 10);
            props.form.month_id = month;
        }
    },
    { immediate: true }
);

// Ordenes de compra filtradas por proveedor seleccionado
const filteredPurchaseOrders = computed(() => {
    const allOrders = page.props.purchaseOrders || [];
    if (!props.form.supplier_id) return allOrders;
    return allOrders.filter(po => po.supplier_id === props.form.supplier_id);
});

// Cuando cambia el proveedor, limpiar la OC si no corresponde al nuevo proveedor
watch(
    () => props.form.supplier_id,
    (newSupplierId) => {
        if (props.form.purchase_order_id) {
            const allOrders = page.props.purchaseOrders || [];
            const currentPO = allOrders.find(po => po.value === props.form.purchase_order_id);
            if (currentPO && currentPO.supplier_id !== newSupplierId) {
                props.form.purchase_order_id = null;
            }
        }
    }
);

// Detectar error de factura duplicada
watch(
    () => props.form.errors.number_document,
    (errorMessage) => {
        if (errorMessage && errorMessage.includes('Ya existe una factura')) {
            Swal.fire({
                icon: 'error',
                title: '⚠️ Factura duplicada',
                html: `
                    <div class="text-start">
                        <strong>${errorMessage}</strong>
                        <br><br>
                        Esta factura ya fue registrada previamente en el sistema.
                    </div>
                `,
                confirmButtonText: 'Entendido',
                confirmButtonColor: '#d33'
            });
        }
    }
);

// Cuando se extraen datos del PDF
const handleDataExtracted = (result) => {
    const data = result.data;
    
    // Autocompletar campos
    if (data.date) props.form.date = data.date;
    if (data.due_date) props.form.due_date = data.due_date;
    if (data.number_document) props.form.number_document = data.number_document;
    if (data.type_document_id) props.form.type_document_id = data.type_document_id;
    if (data.supplier_id) props.form.supplier_id = data.supplier_id;
    if (data.company_reason_id) props.form.company_reason_id = data.company_reason_id;
    if (data.payment_type) props.form.payment_type = data.payment_type;
    if (data.payment_term !== undefined) props.form.payment_term = data.payment_term;

    // Autocompletar productos si se extrajeron del PDF
    if (data.products && data.products.length > 0) {
        // Reemplazar todos los productos con los nuevos del PDF
        props.form.products = data.products.map(p => ({
            product_id: p.product_id || (p.pdf_name ? p.pdf_name.trim() : ''),
            unit_id: p.unit_id || '',
            unit_price: p.unit_price || 0,
            amount: p.amount || 1,
            observations: p.observations || '',
        }));

        // Si no quedó ningún producto, agregar una línea vacía
        if (props.form.products.length === 0) {
            props.form.products.push({
                product_id: '',
                unit_id: '',
                unit_price: 0,
                amount: 1,
                observations: '',
            });
        }
    }
};

// Cuando no se encuentra el proveedor
const handleSupplierNotFound = (supplierData) => {
    console.log('🔍 handleSupplierNotFound llamado:', supplierData);
    console.log('📋 Nombre:', supplierData.name);
    console.log('📋 RUT:', supplierData.rut);
    suggestedSupplierData.value = supplierData;
    
    Swal.fire({
        title: '⚠️ Proveedor no encontrado',
        html: `
            El proveedor <strong>${supplierData.name || 'Sin nombre'}</strong><br>
            (RUT: ${supplierData.rut || 'Sin RUT'}) no está registrado.
            <br><br>¿Deseas crearlo ahora?
        `,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: '✚ Crear proveedor',
        cancelButtonText: 'Omitir',
        confirmButtonColor: '#2c7be5',
        cancelButtonColor: '#6c757d',
    }).then((result) => {
        if (result.isConfirmed) {
            // Pre-llenar form con datos detectados
            supplierForm.reset();
            supplierForm.name = supplierData.name;
            supplierForm.rut = supplierData.rut;
            console.log('✏️ Formulario pre-llenado:');
            console.log('   - Nombre:', supplierForm.name);
            console.log('   - RUT:', supplierForm.rut);
            showCreateSupplierModal.value = true;
            
            // Mostrar modal con Bootstrap después de que Vue actualice el DOM
            nextTick(() => {
                console.log('🔓 Abriendo modal de proveedor');
                $('#createSupplierModal').modal('show');
            });
        }
    });
};

// Abrir modal para crear proveedor manualmente
const openCreateSupplierModal = () => {
    supplierForm.reset();
    showCreateSupplierModal.value = true;
    nextTick(() => {
        $('#createSupplierModal').modal('show');
    });
};

// Guardar proveedor nuevo vía API (sin redirect, sin perder datos del formulario)
const storeSupplier = async () => {
    console.log('💾 Guardando proveedor:', supplierForm.data());
    
    try {
        const response = await axios.post(route('api.suppliers.store'), supplierForm.data());
        const newSupplier = response.data.supplier;
        
        console.log('✅ Proveedor creado:', newSupplier);
        
        // Cerrar modal
        $('#createSupplierModal').modal('hide');
        
        // Agregar a la lista local de proveedores
        supplierOptions.value = [
            ...supplierOptions.value,
            {
                label: newSupplier.name,
                value: newSupplier.id
            }
        ];
        
        // Seleccionar automáticamente el proveedor recién creado
        props.form.supplier_id = newSupplier.id;
        
        Swal.fire({
            icon: 'success',
            title: 'Proveedor creado',
            text: 'Se ha seleccionado automáticamente',
            timer: 2000,
            showConfirmButton: false
        });
        
        // Resetear form del modal
        supplierForm.reset();
        showCreateSupplierModal.value = false;
        
    } catch (error) {
        console.log('❌ Error al crear proveedor:', error.response?.data);
        
        const errors = error.response?.data?.errors || {};
        const errorMessages = Object.entries(errors).map(([field, messages]) => {
            const fieldNames = {
                name: 'Nombre',
                rut: 'RUT',
                email: 'Email',
                contact: 'Contacto',
                phone: 'Teléfono'
            };
            const fieldName = fieldNames[field] || field;
            const message = Array.isArray(messages) ? messages[0] : messages;
            return `${fieldName}: ${message}`;
        });
        
        Swal.fire({
            icon: 'error',
            title: 'Error al crear proveedor',
            html: `<div class="text-start">${errorMessages.join('<br>')}</div>`,
            confirmButtonColor: '#d33'
        });
    }
};
</script>
<template>
    <!-- PDF Uploader Component -->
    <PdfUploader 
        @extracted="handleDataExtracted"
        @supplierNotFound="handleSupplierNotFound"
    />

    <!-- Modal crear proveedor rápido (siempre montado, se controla con Bootstrap JS) -->
    <CreateSupplierModal 
        :form="supplierForm"
        @store="storeSupplier"
    />

    <!--begin::Wrapper
<div class="d-flex flex-column align-items-start flex-xxl-row">
	<div class="d-flex flex-center flex-equal fw-row text-nowrap order-1 order-xxl-2 me-4" data-bs-toggle="tooltip" data-bs-trigger="hover" title="Enter invoice number">
		<span class="fs-2x fw-bold text-gray-800">Factura #</span>
		<input type="text" v-model="form.number" class="form-control form-control-solid fw-bold fs-3 w-200px" placehoder="" />
	</div>
</div>-->
    <!--end::Top-->
    <!--begin::Wrapper-->
    <div class="mb-0">
        <div class="row">
            <div class="col-lg-2">
                <div class="fv-row">
                    <label class="col-form-label">Fecha</label>
                    <TextInput
                        id="date"
                        v-model="form.date"
                        class="form-control form-control-solid"
                        type="date"
                        :class="{ 'is-invalid': form.errors.date }"
                    />
                    <InputError class="mt-2" :message="form.errors.date" />
                </div>
            </div>
            <div class="col-lg-2">
                <div class="fv-row">
                    <label class="col-form-label">Mes contable</label>
                    <Multiselect
                        :placeholder="'Sel. mes'"
                        v-model="form.month_id"
                        :options="$page.props.months"
                        value-prop="value"
                        track-by="value"
                        class="multiselect-blue form-control"
                        :class="{ 'is-invalid': form.errors.month_id }"
                        :searchable="true"
                        :close-on-select="true"
                        :hide-selected="false"
                    />
                    <InputError class="mt-2" :message="form.errors.month_id" />
                </div>
            </div>
            <div class="col-lg-2">
                <div class="fv-row">
                    <label class="col-form-label">Fecha de vencimiento</label>
                    <TextInput
                        id="due_date"
                        v-model="form.due_date"
                        class="form-control form-control-solid"
                        type="date"
                        :class="{ 'is-invalid': form.errors.due_date }"
                    />
                    <InputError class="mt-2" :message="form.errors.due_date" />
                </div>
            </div>
            <div class="col-lg-6">
                <div class="fv-row">
                    <div class="d-flex justify-content-between align-items-center">
                        <label for="" class="col-form-label mb-0">Proveedor</label>
                        <button
                            type="button"
                            class="btn btn-link text-primary p-0"
                            @click="openCreateSupplierModal"
                            v-tooltip="'Agregar nuevo proveedor'"
                            style="font-size: 0.875rem;"
                        >
                            <i class="fas fa-plus-circle"></i>
                        </button>
                    </div>
                    <Multiselect
                        :placeholder="'Seleccione proveedor'"
                        v-model="form.supplier_id"
                        :close-on-select="true"
                        :options="supplierOptions"
                        class="multiselect-blue form-control"
                        :class="{ 'is-invalid': form.errors.supplier_id }"
                        :searchable="true"
                        :hide-selected="false"
                    />
                    <InputError
                        class="mt-2"
                        :message="form.errors.supplier_id"
                    />
                </div>
            </div>
        </div>
        <!--begin::Row-->
        <div class="row">
            <div class="col-lg-6">
                <div class="fv-row">
                    <label for="" class="col-form-label">Razón social</label>
                    <Multiselect
                        :placeholder="'Seleccione razón social'"
                        v-model="form.company_reason_id"
                        :close-on-select="true"
                        :options="$page.props.companyReasons"
                        class="multiselect-blue form-control"
                        :class="{ 'is-invalid': form.errors.company_reason_id }"
                        :searchable="true"
                        :hide-selected="false"
                    />
                    <InputError
                        class="mt-2"
                        :message="form.errors.company_reason_id"
                    />
                </div>
            </div>
            <div class="col-lg-6">
                <div class="fv-row">
                    <label class="col-form-label">
                        Orden de Compra
                        <small class="text-muted">(opcional)</small>
                    </label>
                    <Multiselect
                        :placeholder="form.supplier_id ? 'Seleccione OC...' : 'Seleccione proveedor primero'"
                        v-model="form.purchase_order_id"
                        :close-on-select="true"
                        :options="filteredPurchaseOrders"
                        class="multiselect-blue form-control"
                        :class="{ 'is-invalid': form.errors.purchase_order_id }"
                        :searchable="true"
                        :hide-selected="false"
                        :disabled="!form.supplier_id"
                        :canClear="true"
                    />
                    <InputError
                        class="mt-2"
                        :message="form.errors.purchase_order_id"
                    />
                </div>
            </div>
        </div>
        <!--end::Row-->
        <!--begin::Row-->
        <div class="row">
            <div class="col-lg-3">
                <div class="fv-row">
                    <label for="typeDocument" class="col-form-label"
                        >Tipo de documento</label
                    >
                    <Multiselect
                        :placeholder="'Tipo de documento'"
                        v-model="form.type_document_id"
                        :close-on-select="true"
                        :options="$page.props.typeDocuments"
                        class="multiselect-blue form-control"
                        :class="{ 'is-invalid': form.errors.type_document_id }"
                        :searchable="true"
                        :hide-selected="false"
                    />
                    <InputError
                        class="mt-2"
                        :message="form.errors.type_document_id"
                    />
                </div>
            </div>
            <div class="col-lg-3">
                <div class="fv-row">
                    <label class="col-form-label">Número de documento</label>
                    <TextInput
                        id="number_document"
                        v-model="form.number_document"
                        class="form-control form-control-solid"
                        type="text"
                        :class="{ 'is-invalid': form.errors.number_document }"
                    />
                    <InputError
                        class="mt-2"
                        :message="form.errors.number_document"
                    />
                </div>
            </div>
            <div class="col-lg-2">
                <div class="fv-row">
                    <label for="paymentTerm" class="col-form-label"
                        >Plazo de pago</label
                    >
                    <Multiselect
                        :placeholder="'Plazo'"
                        v-model="form.payment_term"
                        :close-on-select="true"
                        :options="[0, 30, 60, 90, 120]"
                        class="multiselect-blue form-control"
                        :class="{ 'is-invalid': form.errors.payment_term }"
                        :searchable="true"
                        :hide-selected="false"
                    />
                    <InputError class="mt-2" :message="form.errors.payment_term" />
                </div>
            </div>
            <div class="col-lg-2">
                <div class="fv-row">
                    <label class="col-form-label">Tipo de pago</label>
                    <div class="d-flex align-items-center gap-3 mt-2">
                        <template v-for="value in paymentTypes">
                            <div class="form-check form-check-solid form-check-inline d-flex align-items-center gap-1 mb-0">
                                <input
                                    class="form-check-input"
                                    type="radio"
                                    v-model="form.payment_type"
                                    :id="'payment_type_' + value.id"
                                    :value="value.id"
                                    :disabled="value.id === 1 && form.payment_term == 0"
                                />
                                <label
                                    class="form-check-label mb-0"
                                    :for="'payment_type_' + value.id"
                                    >{{ value.label }}</label>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>
        <!--end::Row-->

        <FormProducts :form="form" :protectedProductIds="props.protectedProductIds" />
    </div>
    <!--end::Wrapper-->
</template>
<style>
.multiselect-blue {
    --ms-bg: var(--kt-input-solid-bg) !important;
    --ms-border-color: var(--kt-input-solid-bg);
    --ms-py: 3px !important;
    --ms-tag-bg: #2c7be5;
    --ms-tag-color: var(--kt-primary);
    --ms-option-bg-selected: var(--kt-primary);
    --ms-option-bg-selected-pointed: var(--kt-primary);
}

/* Reducir tamaño de letra en opciones */
.multiselect-blue .multiselect-option {
    font-size: 0.75rem !important;
}

.multiselect-tags-search,
.multiselect-search {
    background: var(--kt-input-solid-bg) !important;
}
</style>
