<template>
  <button @click="exportPdf" class="btn btn-danger btn-sm d-inline-block px-2 py-1 ms-2 mb-1" style="font-size:0.75rem;">
    Pdf
  </button>
</template>

<script setup>
import jsPDF from 'jspdf';
import autoTable from 'jspdf-autotable';
import { usePage } from '@inertiajs/vue3';
const props = defineProps({
  data: { type: Array, required: true }, // Array de objetos plano
  headers: { type: Array, required: true }, // [{ label: 'Nivel 1', key: 'n1' }, ...]
  filename: { type: String, default: 'export.pdf' },
  title: { type: String, default: '' }
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
  const pageWidth = doc.internal.pageSize.getWidth();

  let startY = 15;
  const now = new Date();
  const fecha = now.toLocaleDateString('es-CL', { day: '2-digit', month: '2-digit', year: 'numeric' });
  const hora = now.toLocaleTimeString('es-CL', { hour: '2-digit', minute: '2-digit' });
  const footerText = `Generado el ${fecha} a las ${hora}`;

  if (props.title) {
    const teamName = usePage().props.auth?.user?.team?.name ?? '';
    doc.setFontSize(14);
    doc.setTextColor(40, 40, 40);
    doc.text(props.title, pageWidth / 2, startY, { align: 'center' });
    startY = 23;
    if (teamName) {
      doc.setFontSize(8);
      doc.setTextColor(120, 120, 120);
      doc.text(teamName, pageWidth / 2, startY, { align: 'center' });
      startY = 30;
    }
    startY += 2;
  }

  const head = [props.headers.map(h => h.label)];
  const body = props.data.map(row => props.headers.map(h => stripHtml(getValueByPath(row, h.key))));
  const pageHeight = doc.internal.pageSize.getHeight();
  autoTable(doc, {
    head,
    body,
    styles: { fontSize: 9 },
    headStyles: { fillColor: [41, 128, 185] },
    margin: { top: startY, bottom: 14 },
    startY,
    tableWidth: 'auto',
    didDrawPage: () => {
      doc.setFontSize(7);
      doc.setTextColor(150, 150, 150);
      doc.text(footerText, pageWidth - 14, pageHeight - 8, { align: 'right' });
    },
  });
  doc.save(props.filename);
}
</script>
