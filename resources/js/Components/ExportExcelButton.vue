<template>
  <button @click="exportExcel" class="btn btn-success btn-sm d-inline-block px-2 py-1 mb-1" style="font-size:0.75rem;">
    <i class="fas fa-file-excel fa-2x"></i>
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
  if (typeof html === 'string') {
    return html.replace(/<[^>]+>/g, '');
  }
  if (html === null || html === undefined) {
    return '';
  }
  // Si es un número, booleano, etc., lo convertimos a string
  return String(html);
}

function getValueByPath(obj, path) {
  return path.split('.').reduce((acc, part) => acc && acc[part], obj);
}

function exportExcel() {
  // Detecta columnas numéricas por nombre (puedes ajustar los nombres aquí)
  const numericKeys = ['Dosis', 'Precio', 'Mojamiento'];
  const exportData = props.data.map(row => {
    const obj = {};
    props.headers.forEach(h => {
      let value = getValueByPath(row, h.key);
      // Si la columna es numérica y el valor es un número válido, lo dejamos como número
      if (numericKeys.includes(h.label)) {
        const num = Number(value);
        obj[h.label] = isNaN(num) ? stripHtml(value) : num;
      } else {
        obj[h.label] = stripHtml(value);
      }
    });
    return obj;
  });
  const ws = XLSX.utils.json_to_sheet(exportData);
  const wb = XLSX.utils.book_new();
  XLSX.utils.book_append_sheet(wb, ws, 'Hoja1');
  XLSX.writeFile(wb, props.filename);
}
</script>
