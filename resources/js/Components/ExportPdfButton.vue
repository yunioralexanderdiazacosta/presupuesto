<template>
  <button @click="exportPdf" class="btn btn-danger btn-sm d-inline-block px-2 py-1 ms-2 mb-2" style="font-size:0.75rem;">
    <i class="fas fa-file-pdf me-1"></i> Exportar a PDF
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
  return html ? html.replace(/<[^>]+>/g, '') : '';
}

function exportPdf() {
  const doc = new jsPDF();
  const head = [props.headers.map(h => h.label)];
  const body = props.data.map(row => props.headers.map(h => stripHtml(row[h.key])));
  autoTable(doc, {
    head,
    body,
    styles: { fontSize: 9 },
    headStyles: { fillColor: [220, 53, 69] },
    margin: { top: 20 }
  });
  doc.save(props.filename);
}
</script>
