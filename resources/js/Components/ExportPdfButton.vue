<template>
  <button @click="exportPdf" class="btn btn-danger btn-sm d-inline-block px-2 py-1 ms-2 mb-1" style="font-size:0.75rem;">
    Pdf
  </button>
</template>

<script setup>
import jsPDF from 'jspdf';
import autoTable from 'jspdf-autotable';
const props = defineProps({
  data: { type: Array, required: true }, // Array de objetos plano
  headers: { type: Array, required: true }, // [{ label: 'Nivel 1', key: 'n1' }, ...]
  filename: { type: String, default: 'export.pdf' }
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

function exportPdf() {
  // Si hay muchas columnas, usar orientación horizontal
  const isWide = props.headers.length > 7;
  const doc = new jsPDF({ orientation: isWide ? 'landscape' : 'portrait' });
  const head = [props.headers.map(h => h.label)];
  const body = props.data.map(row => props.headers.map(h => stripHtml(getValueByPath(row, h.key))));
  autoTable(doc, {
    head,
    body,
    styles: { fontSize: 9 },
    headStyles: { fillColor: [220, 53, 69] },
    margin: { top: 20 },
    tableWidth: 'auto',
  });
  doc.save(props.filename);
}
</script>
