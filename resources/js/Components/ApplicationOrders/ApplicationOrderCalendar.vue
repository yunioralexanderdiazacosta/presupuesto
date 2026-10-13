<script setup>
import { computed, ref } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import FullCalendar from '@fullcalendar/vue3';
import dayGridPlugin from '@fullcalendar/daygrid';
import listPlugin from '@fullcalendar/list';
import esLocale from '@fullcalendar/core/locales/es';
import jsPDF from 'jspdf';
import autoTable from 'jspdf-autotable';
import ExportExcelButton from '@/Components/ExportExcelButton.vue';

const props = defineProps({
    orders: { type: Array, default: () => [] },
});

const STATUS_STYLES = {
    pendiente: { label: 'Pendiente', color: '#f59f00' },
    atrasada: { label: 'Atrasada', color: '#e63757' },
    completada: { label: 'Completada', color: '#00d27a' },
    completada_manual: { label: 'Completada sin salida', color: '#00d27a', dashed: true },
    cancelada: { label: 'Cancelada', color: '#9da9bb' },
};

const legend = Object.values(STATUS_STYLES);

// El backend serializa las fechas en ISO; solo interesa la parte YYYY-MM-DD
const toDateKey = (value) => (value ? String(value).substring(0, 10) : null);

const todayKey = () => {
    const d = new Date();
    const pad = (n) => String(n).padStart(2, '0');
    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
};

const resolveStatus = (order, dateKey) => {
    if (order.status === 'pendiente' && dateKey < todayKey()) return 'atrasada';
    if (order.status === 'completada' && Number(order.agrochemical_outflows_count || 0) === 0) {
        return 'completada_manual';
    }
    return STATUS_STYLES[order.status] ? order.status : 'pendiente';
};

const events = computed(() => {
    return (props.orders || [])
        .map((order) => {
            const dateKey = toDateKey(order.start_date) || toDateKey(order.date);
            if (!dateKey) return null;

            const statusKey = resolveStatus(order, dateKey);
            const color = STATUS_STYLES[statusKey].color;
            const products = (order.order_products || [])
                .map((op) => op.product?.name)
                .filter(Boolean);
            const costCenters = (order.order_cost_centers || [])
                .map((occ) => occ.cost_center?.name)
                .filter(Boolean);

            return {
                id: String(order.id),
                title: `#${order.id}`,
                start: dateKey,
                allDay: true,
                backgroundColor: color,
                borderColor: color,
                classNames: STATUS_STYLES[statusKey].dashed ? ['ao-manual'] : [],
                extendedProps: {
                    orderId: order.id,
                    dateKey,
                    statusLabel: STATUS_STYLES[statusKey].label,
                    products,
                    costCenters,
                },
            };
        })
        .filter(Boolean);
});

// Período visible del calendario (lo actualiza FullCalendar al navegar o cambiar de vista)
const visibleRange = ref({ startKey: null, endKey: null, title: '' });

const formatLocalKey = (date) => {
    const pad = (n) => String(n).padStart(2, '0');
    return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`;
};

const formatDisplayDate = (key) => key.split('-').reverse().join('/');

// Filas de lo que se ve en el calendario, para exportar a Excel y PDF
const exportRows = computed(() => {
    const { startKey, endKey } = visibleRange.value;
    if (!startKey) return [];
    return events.value
        .filter((e) => e.extendedProps.dateKey >= startKey && e.extendedProps.dateKey < endKey)
        .sort((a, b) => a.extendedProps.dateKey.localeCompare(b.extendedProps.dateKey) || a.extendedProps.orderId - b.extendedProps.orderId)
        .map((e) => ({
            orden: e.extendedProps.orderId,
            fecha: formatDisplayDate(e.extendedProps.dateKey),
            estado: e.extendedProps.statusLabel,
            productos: e.extendedProps.products.join(', '),
            centros: e.extendedProps.costCenters.join(', '),
        }));
});

const exportHeaders = [
    { label: 'N° Orden', key: 'orden' },
    { label: 'Fecha', key: 'fecha' },
    { label: 'Estado', key: 'estado' },
    { label: 'Productos', key: 'productos' },
    { label: 'Centros de Costo', key: 'centros' },
];

const exportFilename = (ext) => {
    const slug = (visibleRange.value.title || 'calendario')
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .replace(/[^a-zA-Z0-9]+/g, '_')
        .replace(/^_|_$/g, '');
    return `ordenes_aplicacion_${slug}.${ext}`;
};

function exportPdf() {
    const doc = new jsPDF({ orientation: 'landscape' });
    const pageWidth = doc.internal.pageSize.getWidth();
    const pageHeight = doc.internal.pageSize.getHeight();
    const teamName = usePage().props.auth?.user?.team?.name ?? '';
    const now = new Date();
    const footerText = `Generado el ${now.toLocaleDateString('es-CL')} a las ${now.toLocaleTimeString('es-CL', { hour: '2-digit', minute: '2-digit' })}`;

    doc.setFontSize(14);
    doc.setTextColor(40, 40, 40);
    doc.text(`Órdenes de Aplicación — ${visibleRange.value.title}`, pageWidth / 2, 15, { align: 'center' });
    let startY = 21;
    if (teamName) {
        doc.setFontSize(8);
        doc.setTextColor(120, 120, 120);
        doc.text(teamName, pageWidth / 2, 21, { align: 'center' });
        startY = 27;
    }

    autoTable(doc, {
        head: [exportHeaders.map((h) => h.label)],
        body: exportRows.value.map((row) => exportHeaders.map((h) => row[h.key])),
        startY,
        margin: { top: startY, bottom: 14 },
        styles: { fontSize: 9, cellPadding: 2 },
        headStyles: { fillColor: [41, 128, 185] },
        columnStyles: { 0: { cellWidth: 22 }, 1: { cellWidth: 26 }, 2: { cellWidth: 40 } },
        didDrawPage: () => {
            doc.setFontSize(7);
            doc.setTextColor(150, 150, 150);
            doc.text(footerText, pageWidth - 14, pageHeight - 8, { align: 'right' });
        },
    });

    doc.save(exportFilename('pdf'));
}

const calendarOptions = computed(() => ({
    plugins: [dayGridPlugin, listPlugin],
    locales: [esLocale],
    locale: 'es',
    initialView: 'dayGridMonth',
    height: 'auto',
    firstDay: 1,
    datesSet: (info) => {
        visibleRange.value = {
            startKey: formatLocalKey(info.view.currentStart),
            endKey: formatLocalKey(info.view.currentEnd),
            title: info.view.title,
        };
    },
    headerToolbar: {
        left: 'prev,next today',
        center: 'title',
        right: 'dayGridMonth,listWeek,listMonth',
    },
    buttonText: {
        today: 'Hoy',
        month: 'Mes',
        listWeek: 'Semana',
        listMonth: 'Lista mensual',
    },
    dayMaxEvents: 3,
    displayEventTime: false,
    moreLinkText: (n) => `+${n} más`,
    noEventsText: 'No hay órdenes de aplicación en este período',
    events: events.value,
    eventClick: (info) => {
        info.jsEvent.preventDefault();
        router.visit(route('application-orders.show', info.event.extendedProps.orderId));
    },
    eventDidMount: (info) => {
        const { statusLabel, products, costCenters } = info.event.extendedProps;
        info.el.title = [
            `Orden ${info.event.title} (${statusLabel})`,
            `Productos: ${products.join(', ') || '—'}`,
            `Centros de costo: ${costCenters.join(', ') || '—'}`,
        ].join('\n');
    },
}));
</script>

<template>
    <div class="application-order-calendar">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
            <div class="d-flex flex-wrap gap-3 small">
                <span v-for="item in legend" :key="item.label" class="d-inline-flex align-items-center gap-1">
                    <span
                        class="legend-dot"
                        :class="{ 'legend-dot-dashed': item.dashed }"
                        :style="{ backgroundColor: item.color }"
                    ></span>
                    {{ item.label }}
                </span>
            </div>
            <div class="d-flex align-items-center gap-2">
                <ExportExcelButton
                    :data="exportRows"
                    :headers="exportHeaders"
                    :filename="exportFilename('xlsx')"
                    class="btn btn-falcon-default btn-sm"
                >
                    <span class="fas fa-file-excel"></span>
                    <span class="d-none d-sm-inline-block ms-1">Excel</span>
                </ExportExcelButton>
                <button type="button" class="btn btn-falcon-default btn-sm d-inline-block px-2 py-1 mb-1" :disabled="!exportRows.length" @click="exportPdf">
                    <span class="fas fa-file-pdf"></span>
                    <span class="d-none d-sm-inline-block ms-1">PDF</span>
                </button>
            </div>
        </div>

        <FullCalendar :options="calendarOptions">
            <template #eventContent="arg">
                <div class="ao-event">
                    <div class="ao-event-title">{{ arg.event.title }}</div>
                    <div class="ao-event-line" v-if="arg.event.extendedProps.products.length">
                        <i class="fas fa-flask me-1"></i>{{ arg.event.extendedProps.products.join(', ') }}
                    </div>
                    <div class="ao-event-line" v-if="arg.event.extendedProps.costCenters.length">
                        <i class="fas fa-map-marker-alt me-1"></i>{{ arg.event.extendedProps.costCenters.join(', ') }}
                    </div>
                </div>
            </template>
        </FullCalendar>
    </div>
</template>

<style scoped>
.legend-dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    display: inline-block;
}

.legend-dot-dashed {
    outline: 1.5px dashed #00a862;
    outline-offset: 1px;
}

.application-order-calendar :deep(.fc-daygrid-event.ao-manual) {
    border: 2px dashed rgba(255, 255, 255, 0.9);
    outline: 1px solid #00d27a;
}

.application-order-calendar :deep(.fc) {
    --fc-border-color: #e3e9f2;
    --fc-today-bg-color: rgba(44, 123, 229, 0.08);
    --fc-neutral-bg-color: #f9fafd;
    font-size: 0.8rem;
}

.application-order-calendar :deep(.fc-toolbar-title) {
    font-size: 1.1rem;
    font-weight: 600;
    text-transform: capitalize;
}

.application-order-calendar :deep(.fc .fc-button) {
    background: #fff;
    border: 1px solid #d8e2ef;
    color: #5e6e82;
    font-size: 0.75rem;
    text-transform: capitalize;
    box-shadow: none;
}

.application-order-calendar :deep(.fc .fc-button:hover),
.application-order-calendar :deep(.fc .fc-button-active) {
    background: #edf2f9;
    color: #344050;
    border-color: #d8e2ef;
}

.application-order-calendar :deep(.fc-col-header-cell-cushion) {
    text-transform: capitalize;
    font-weight: 600;
    color: #5e6e82;
    padding: 0.5rem 0;
}

.application-order-calendar :deep(.fc-daygrid-event) {
    border-radius: 6px;
    padding: 2px 5px;
    margin-bottom: 2px;
    cursor: pointer;
    white-space: normal;
}

.application-order-calendar :deep(.fc-list-event) {
    cursor: pointer;
}

.application-order-calendar :deep(.fc-list-event .ao-event) {
    color: inherit;
}

.application-order-calendar :deep(.fc-list-day-cushion) {
    text-transform: capitalize;
}

.ao-event {
    overflow: hidden;
    line-height: 1.25;
    color: #fff;
}

.ao-event-title {
    font-weight: 700;
}

.ao-event-line {
    font-size: 0.7rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    opacity: 0.95;
}

.application-order-calendar :deep(.fc-list-event .ao-event-line) {
    white-space: normal;
}
</style>
