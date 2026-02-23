<script setup>
import { ref } from 'vue';
import axios from 'axios';
import Swal from 'sweetalert2';

const emit = defineEmits(['extracted', 'supplierNotFound']);

const isProcessing = ref(false);
const uploadProgress = ref(0);
const pdfInput = ref(null);
const fileName = ref('');
const isDragging = ref(false);

const handlePdfUpload = async (file) => {
    if (!file || file.type !== 'application/pdf') {
        Swal.fire({
            icon: 'error',
            title: 'Archivo inválido',
            text: 'Por favor selecciona un archivo PDF válido'
        });
        return;
    }

    fileName.value = file.name;
    isProcessing.value = true;
    uploadProgress.value = 0;

    const formData = new FormData();
    formData.append('pdf', file);

    try {
        const response = await axios.post('/invoices/extract-from-pdf', formData, {
            headers: { 'Content-Type': 'multipart/form-data' },
            onUploadProgress: (progressEvent) => {
                uploadProgress.value = Math.round((progressEvent.loaded * 100) / progressEvent.total);
            }
        });

        const result = response.data;

        if (result.success) {
            // Emitir datos extraídos primero
            emit('extracted', result);

            // Si no se encontró proveedor, emitir evento y NO mostrar SweetAlert todavía
            if (!result.data.supplier_id && result.raw.supplier_name) {
                const supplierData = {
                    name: result.raw.supplier_name,
                    rut: result.raw.supplier_rut || ''
                };
                console.log('⚠️ Proveedor no encontrado, emitiendo evento supplierNotFound:', supplierData);
                console.log('   📄 Nombre detectado:', supplierData.name);
                console.log('   🆔 RUT detectado:', supplierData.rut);
                if (!supplierData.rut) {
                    console.warn('⚠️ ADVERTENCIA: RUT no detectado por el OCR');
                }
                emit('supplierNotFound', supplierData);
                // No mostrar el SweetAlert de éxito aún, esperar a que el usuario maneje el proveedor
                return;
            }

            // Si todo está bien, mostrar SweetAlert de éxito
            const productCount = result.data.products?.length || 0;
            const matchedCount = result.data.products?.filter(p => p.matched).length || 0;

            Swal.fire({
                icon: 'success',
                title: '¡Datos extraídos!',
                html: `
                    <div class="text-start">
                        <p class="mb-2"><strong>✓ Fecha:</strong> ${result.data.date || 'No detectada'}</p>
                        <p class="mb-2"><strong>✓ N° Documento:</strong> ${result.data.number_document || 'No detectado'}</p>
                        <p class="mb-2"><strong>✓ Tipo Documento:</strong> ${result.data.type_document_id ? 'Detectado' : 'Por defecto'}</p>
                        <p class="mb-2"><strong>✓ Proveedor:</strong> ${result.data.supplier_id ? 'Encontrado' : 'No encontrado'}</p>
                        <p class="mb-2"><strong>✓ RUT Empresa:</strong> ${result.data.company_reason_id ? 'Encontrado' : 'No encontrado'}</p>
                        <p class="mb-2"><strong>✓ Forma de Pago:</strong> <span class="badge ${result.raw.payment_detected === 'Contado' ? 'bg-success' : 'bg-warning'}">${result.raw.payment_detected}</span> ${result.data.payment_term > 0 ? `(${result.data.payment_term} días)` : ''}</p>
                        ${productCount > 0 ? `<p class="mb-0"><strong>✓ Productos:</strong> ${matchedCount}/${productCount} identificados</p>` : '<p class="mb-0 text-muted"><em>No se detectaron líneas de productos</em></p>'}
                    </div>
                `,
                confirmButtonText: 'Continuar',
                timer: 5000
            });
        }

    } catch (error) {
        console.error('Error al procesar PDF:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error al procesar PDF',
            text: error.response?.data?.message || 'Ocurrió un error al extraer los datos del PDF'
        });
    } finally {
        isProcessing.value = false;
        fileName.value = '';
        uploadProgress.value = 0;
    }
};

const onFileChange = (event) => {
    const file = event.target.files[0];
    if (file) handlePdfUpload(file);
};

const onDrop = (event) => {
    event.preventDefault();
    isDragging.value = false;
    const file = event.dataTransfer.files[0];
    if (file) handlePdfUpload(file);
};

const onDragOver = (event) => {
    event.preventDefault();
    isDragging.value = true;
};

const onDragLeave = () => {
    isDragging.value = false;
};

const triggerFileInput = () => {
    pdfInput.value.click();
};
</script>

<template>
    <div class="card border-primary mb-3">
        <div class="card-body p-3">
            <div class="row align-items-center g-2">
                <!-- Icono y título -->
                <div class="col-auto">
                    <div class="d-flex align-items-center justify-content-center bg-primary bg-opacity-10 rounded" 
                         style="width: 40px; height: 40px;">
                        <i class="fas fa-magic text-primary"></i>
                    </div>
                </div>
                <div class="col">
                    <h6 class="mb-0 fw-bold text-dark">
                        Autocompletar con PDF
                    </h6>
                    <small class="text-muted">Extrae datos automáticamente</small>
                </div>
                
                <!-- Botón de subida compacto -->
                <div class="col-auto">
                    <input
                        ref="pdfInput"
                        type="file"
                        accept="application/pdf"
                        @change="onFileChange"
                        class="d-none"
                    />
                    
                    <button 
                        v-if="!isProcessing"
                        type="button"
                        @click="triggerFileInput"
                        class="btn btn-primary btn-sm"
                    >
                        <i class="fas fa-upload me-1"></i>
                        Subir PDF
                    </button>
                    
                    <button 
                        v-else
                        type="button"
                        class="btn btn-primary btn-sm"
                        disabled
                    >
                        <span class="spinner-border spinner-border-sm me-1"></span>
                        Procesando... {{ uploadProgress }}%
                    </button>
                </div>
            </div>
            
            <!-- Barra de progreso (solo cuando procesa) -->
            <div v-if="isProcessing" class="mt-2">
                <div class="progress" style="height: 4px;">
                    <div 
                        class="progress-bar bg-primary" 
                        role="progressbar" 
                        :style="{ width: uploadProgress + '%' }"
                    ></div>
                </div>
                <small class="text-muted">{{ fileName }}</small>
            </div>
        </div>
    </div>
</template>

<style scoped>
.bg-opacity-10 {
    background-color: rgba(13, 110, 253, 0.1) !important;
}
</style>
