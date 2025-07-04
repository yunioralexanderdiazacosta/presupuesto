<template>
  <button @click="exportExcel" class="btn btn-success btn-sm d-inline-block px-2 py-1 mb-2" style="font-size:0.75rem;">
    <i class="fas fa-file-excel me-1"></i> Exportar a Excel
  </button>
</template>

<script setup>
import * as XLSX from 'xlsx';
const props = defineProps({
  data: { type: Array, required: true }, // Array de objetos plano
  headers: { type: Array, required: true }, // [{ label: 'Nivel 1', key: 'n1' }, ...]
  filename: { type: String, default: 'export.xlsx' }
});

function stripHtml(html) {
  return html ? html.replace(/<[^>]+>/g, '') : '';
}

function exportExcel() {
  // Construye los datos para exportar
  const exportData = props.data.map(row => {
    const obj = {};
    props.headers.forEach(h => {
      obj[h.label] = stripHtml(row[h.key]);
    });
    return obj;
  });
  const ws = XLSX.utils.json_to_sheet(exportData);
  const wb = XLSX.utils.book_new();
  XLSX.utils.book_append_sheet(wb, ws, 'Hoja1');
  XLSX.writeFile(wb, props.filename);
}
</script>
