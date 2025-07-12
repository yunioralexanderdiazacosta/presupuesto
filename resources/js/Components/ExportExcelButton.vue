<template>
  <button @click="exportExcel" class="btn btn-success btn-sm d-inline-block px-2 py-1 mb-1" style="font-size:0.75rem;">
    <i class="fas fa-file-excel fa-2x"></i>
  </button>
</template>

<script setup>
import * as XLSX from 'xlsx';
import { toRefs } from 'vue';
const props = defineProps({
  data: { type: Array, required: false }, // Array de objetos plano
  headers: { type: Array, required: false }, // [{ label: 'Nivel 1', key: 'n1' }, ...]
  filename: { type: String, default: 'export.xlsx' },
  tableId: { type: String, required: false },
  fileName: { type: String, required: false }
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
  // Si tiene data y headers, exporta como json (modo antiguo)
  if (props.data && props.headers) {
    const exportData = props.data.map(row => {
      const obj = {};
      props.headers.forEach(h => {
        let value = getValueByPath(row, h.key);
        if (h.type === 'number') {
          // Asegurarse de que value es un número entero, sin decimales ni redondeos
          let num = String(value).replace(/\./g, '').replace(/,/g, '');
          num = parseInt(num, 10);
          if (isNaN(num)) {
            obj[h.label] = '';
          } else {
            obj[h.label] = num.toLocaleString('es-CL', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
          }
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
    return;
  }
  // Si tiene tableId, exporta la tabla HTML
  if (props.tableId) {
    const table = document.getElementById(props.tableId);
    if (!table) {
      alert('No se encontró la tabla para exportar');
      return;
    }
    // Clonar la tabla para evitar problemas con Vue y quitar botones
    const clonedTable = table.cloneNode(true);
    clonedTable.querySelectorAll('button, .btn, .no-export').forEach(el => el.remove());
    const wb = XLSX.utils.book_new();
    const ws = XLSX.utils.table_to_sheet(clonedTable);
    XLSX.utils.book_append_sheet(wb, ws, 'Hoja1');
    XLSX.writeFile(wb, props.fileName || props.filename || 'export.xlsx');
    return;
  }
  alert('No se encontraron datos para exportar');
}
</script>
