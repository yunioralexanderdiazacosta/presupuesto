<script setup>
import { computed, ref, watchEffect } from "vue";
import { Link, router, Head, usePage, useForm } from "@inertiajs/vue3";
import Swal from "sweetalert2";
import AppLayout from "@/Layouts/AppLayout.vue";
import Table from "@/Components/Table.vue";
import Empty from "@/Components/Empty.vue";
import Breadcrumb from "@/Components/Breadcrumb.vue";
import SearchInput from "@/Components/SearchInput.vue";
import ExportExcelButton from "@/Components/ExportExcelButton.vue";

const props = defineProps({
    invoices: Object,
    term: String,
});

const title = "Facturas";

const term = ref(props.term || "");

// Filtrado local de facturas
const filteredInvoices = computed(() => {
    if (!props.invoices || !props.invoices.data) return [];
    if (!term.value) return props.invoices.data;
    const search = term.value.toLowerCase();
    return props.invoices.data.filter((item) => {
        const supplier = item.supplier?.name?.toLowerCase() || "";
        const number = item.number_document ? String(item.number_document).toLowerCase() : "";
        const company = item.companyReason?.name?.toLowerCase() || "";
        const month = item.month ? String(item.month).toLowerCase() : "";
        const type = item.type_document ? String(item.type_document).toLowerCase() : "";
        return (
            supplier.includes(search) ||
            number.includes(search) ||
            company.includes(search) ||
            month.includes(search) ||
            type.includes(search)
        );
    });
});


// Suma simple de la columna total
const totalFacturas = computed(() => {
    if (!filteredInvoices.value.length) return 0;
    return filteredInvoices.value.reduce((sum, factura) => {
        let val = factura.total;
        if (typeof val === 'string') {
            val = val.replace(/\./g, '').replace(',', '.');
        }
        return sum + (parseFloat(val) || 0);
    }, 0);
});
// Formatea total con separador de miles y decimales
const totalFacturasFormatted = computed(() => {
    return new Intl.NumberFormat('es-ES', { style: 'decimal', minimumFractionDigits: 0 }).format(totalFacturas.value);
});

const links = [
    { title: "Tablero", link: "dashboard" },
    { title: title, active: true },
];

const msgSuccess = (msg) => {
    Swal.fire({
        position: "center",
        icon: "success",
        title: msg,
        showConfirmButton: false,
        timer: 1000,
    });
};

const onDeleted = (id) => {
    Swal.fire({
        title: "¿Estás seguro de que quieres eliminar el registro?",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "rgb(0, 158, 247)",
        cancelButtonColor: "#6e6e6e",
        cancelButtonText: "Cancelar",
        confirmButtonText: "Confirmar",
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route("invoices.delete", id), {
                preserveScroll: true,
                onSuccess: () => {
                    msgSuccess("Registro eliminado correctamente");
                },
            });
        }
    });
};

const onFilter = () => {
    router.get(route("invoices.index", { term: term.value }), {
        preserveState: true,
    });
};

// Función para parsear números con formato español
const parseSpanishNumber = (val) => {
    if (typeof val === 'number') return val;
    if (typeof val !== 'string') return 0;
    const cleaned = val.replace(/\./g, '').replace(/,/g, '.');
    const num = Number(cleaned);
    return isNaN(num) ? 0 : num;
};

// Datos transformados para exportar a Excel
const invoicesExcelData = computed(() => {
    return filteredInvoices.value.map((invoice) => ({
        type_document: invoice.type_document,
        month: invoice.month,
        supplier: invoice.supplier?.name || "",
        number_document: invoice.number_document,
        company_reason: invoice.companyReason?.name || "",
        date: invoice.date,
        due_date: invoice.due_date,
        products: invoice.products && invoice.products.length ? invoice.products.map(p => p.product_name).join(", ") : "",
        total: parseSpanishNumber(invoice.total),
    }));
});

const page = usePage();

// Calcula el total por mes a partir de la fecha de la factura
function totalForMonth(invoice, monthValue) {
  const mesFactura = invoice.date ? new Date(invoice.date).getMonth() + 1 : null;
  const totalNum = invoice.total
    ? parseFloat(invoice.total.toString().replace(/\./g, '').replace(',', '.'))
    : 0;
    return mesFactura === monthValue ? totalNum : 0;
}
// Suma total por mes para pie de tabla
const monthlyTotals = computed(() => {
    return page.props.months.map(month => {
        return filteredInvoices.value.reduce((sum, invoice) => sum + totalForMonth(invoice, month.value), 0);
    });
});
</script>
<template>

    <Head :title="title" />
    <AppLayout>
        <!--begin::Breadcrumb-->
        <Breadcrumb :links="links" />
        <!--end::Breadcrumb-->

        <div class="card my-3">
            <div class="card-header">
                <div class="row flex-between-center">
                    <div class="col-6 col-sm-auto d-flex align-items-center pe-0">
                        <h5 class="fs-9 mb-0 text-nowrap py-2 py-xl-0">
                            <i class="fas fa-people-carry text-primary me-2"></i>Facturas
                        </h5>
                        </div>
                    <div class="col-6 col-sm-auto ms-auto text-end ps-0">
                        <Link class="btn btn-falcon-default btn-sm" :href="route('invoices.create')">
                        <span class="fas fa-plus" data-fa-transform="shrink-3 down-2"></span>
                        <span class="d-none d-sm-inline-block ms-1">Nuevo</span>
                        </Link>
                    </div>
                </div>
            </div>

            <div class="card-body bg-body-tertiary">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <ul class="nav nav-pills" id="pill-myTab" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" id="pill-resumen" data-bs-toggle="tab" href="#pill-tab-resumen"
                                role="tab" aria-controls="pill-tab-resumen" aria-selected="true">Resumen</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="pill-detalles" data-bs-toggle="tab" href="#pill-tab-detalles" role="tab"
                                aria-controls="pill-tab-detalles" aria-selected="false">Compras mensuales</a>
                        </li>
                        
                        
                    </ul>
                    <!-- Card de total de facturas alineado a la derecha -->
                    <div>
                        <div class="card h-100 p-1 small-card">
                            <div class="card-header pb-0 pt-1 px-2">
                                <h6 class="mb-0 mt-1 fs-10 d-flex align-items-center small-card-title">Total Neto Facturas</h6>
                            </div>
                            <div class="card-body d-flex flex-column justify-content-end py-1 px-2">
                                <p class="font-sans-serif lh-1 mb-1 fs-10 small-card-number">
                                    {{ totalFacturasFormatted }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="tab-content border p-3 mt-3" id="pill-myTabContent">
                    <div class="tab-pane fade show active" id="pill-tab-resumen" role="tabpanel"
                        aria-labelledby="resumen-tab">
                        <div class="row align-items-center mb-3">
                            <div class="col-md-6 col-12 mb-2 mb-md-0">
                                <SearchInput v-model="term" placeholder="Buscar por proveedor, número, razón social..."
                                    @keyup.enter="onFilter()" @change="onFilter()" />
                            </div>
                            <div class="col-md-6 col-12 text-md-end text-start">
                                <a :href="route('invoices.pdf', { term: term })"
                                    target="_blank" class="btn btn-light-primary me-3">
                                    <span class="svg-icon svg-icon-2">
                                        <!-- ...SVG... -->
                                    </span>
                                    Exportar PDF
                                </a>
                                <ExportExcelButton
                                    :data="invoicesExcelData"
                                    :headers="[
                                        { label: 'Tipo documento', key: 'type_document' },
                                        { label: 'Mes contable', key: 'month' },
                                        { label: 'Proveedor', key: 'supplier' },
                                        { label: 'N° Doc', key: 'number_document' },
                                        { label: 'Razón Social', key: 'company_reason' },
                                        { label: 'Fecha', key: 'date' },
                                        { label: 'Fecha de Vencimiento', key: 'due_date' },
                                        { label: 'Productos', key: 'products' },
                                        { label: 'Total', key: 'total', type: 'number' }
                                    ]"
                                    filename="facturas.xlsx"
                                    class="btn btn-light-primary me-3"
                                >
                                    <span class="svg-icon svg-icon-2">
                                        <!-- ...SVG... -->
                                    </span>
                                    Exportar Excel
                                </ExportExcelButton>
                            </div>
                        </div>

                        <!-- Muevo aquí la tabla original -->
                        <div class="card-body pt-0" style="overflow-x: auto;">
                            <Table :id="'invoices'" :total="filteredInvoices.length" :links="invoices.links" class="min-w-full">
                                <!--begin::Table head-->
                                <template #header>
                                    <!--begin::Table row-->
                                    <th width="min-w-150px" style="white-space:nowrap;">Tipo documento</th>
                                    <th width="min-w-150px" style="white-space:nowrap;">Mes contable</th>
                                    <th width="300px" style="white-space:nowrap; max-width:300px; overflow:hidden; text-overflow:ellipsis;">Proveedor</th>
                                    <th width="min-w-150px" style="white-space:nowrap;">N° Doc</th>
                                    <th width="min-w-150px" style="white-space:nowrap;">Razón Social</th>
                                    <th width="min-w-150px" style="white-space:nowrap;">Fecha</th>
                                    <th width="min-w-150px" style="white-space:nowrap;">Fecha de Vencimiento</th>
                                    <th width="min-w-200px" style="white-space:nowrap;">Productos</th>
                                    <th width="min-w-150px" class="text-end" style="white-space:nowrap;">Total</th>
                                    <th width="80px" class="text-end" style="white-space:nowrap;">Acciones</th>
                                    <!--end::Table row-->
                                </template>
                                <!--end::Table head-->
                                <!--begin::Table body-->
                                <template #body>
                                    <template v-if="filteredInvoices.length == 0">
                                        <Empty colspan="3" />
                                    </template>
                                    <template v-else>
                                        <tr v-for="(invoice, index) in filteredInvoices" :key="index">
                                            <td style="white-space:nowrap;">{{ invoice.type_document }}</td>
                                            <td style="white-space:nowrap;">{{ invoice.month }}</td>
                                            <td style="white-space:nowrap; max-width:300px; overflow:hidden; text-overflow:ellipsis;">{{ invoice.supplier.name }}</td>
                                            <td style="white-space:nowrap;">{{ invoice.number_document }}</td>
                                            <td style="white-space:nowrap;">{{ invoice.companyReason.name }}</td>
                                            <td style="white-space:nowrap;">{{ invoice.date }}</td>
                                            <td style="white-space:nowrap;">{{ invoice.due_date }}</td>
                                            <td style="white-space:nowrap;">
                                                <span v-if="invoice.products && invoice.products.length">
                                                    <span v-if="invoice.products.length <= 2">
                                                        <span v-for="(prod, idx) in invoice.products" :key="prod.id || idx">
                                                            {{ prod.product_name }}<span v-if="idx < invoice.products.length - 1">, </span>
                                                        </span>
                                                    </span>
                                                    <span v-else>
                                                        <span v-tooltip="invoice.products.map(p => p.product_name).join(', ')">
                                                            {{ invoice.products[0].product_name }}, {{ invoice.products[1].product_name }} y {{ invoice.products.length - 2 }} más
                                                        </span>
                                                    </span>
                                                </span>
                                                <span v-else class="text-muted">—</span>
                                            </td>
                                            <td class="text-end">{{ invoice.total }}</td>
                                            <td class="text-end">
                                                <div class="btn-group">
                                                    <!--begin::View-->
                                                    <Link :href="route('invoices.show', invoice.id)"
                                                        v-tooltip="'Ver'"
                                                        class="btn btn-icon btn-active-light-primary w-20px h-20px me-1 p-2">
                                                        <span class="svg-icon svg-icon-2">
                                                            <i class="fas fa-eye"></i>
                                                        </span>
                                                    </Link>
                                                    <!--end::View-->
                                                    <!--begin::Update-->
                                                    <Link v-tooltip="'Editar'"
                                                        class="btn btn-icon btn-active-light-primary w-20px h-20px me-1 p-2"
                                                        :href="route('invoices.edit', invoice.id)">
                                                        <span class="svg-icon svg-icon-1">
                                                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                                                                xmlns="http://www.w3.org/2000/svg">
                                                                <path opacity="0.3"
                                                                    d="M21.4 8.35303L19.241 10.511L13.485 4.755L15.643 2.59595C16.0248 2.21423 16.5426 1.99988 17.0825 1.99988C17.6224 1.99988 18.1402 2.21423 18.522 2.59595L21.4 5.474C21.7817 5.85581 21.9962 6.37355 21.9962 6.91345C21.9962 7.45335 21.7817 7.97122 21.4 8.35303ZM3.68699 21.932L9.88699 19.865L4.13099 14.109L2.06399 20.309C1.98815 20.5354 1.97703 20.7787 2.03189 21.0111C2.08674 21.2436 2.2054 21.4561 2.37449 21.6248C2.54359 21.7934 2.75641 21.9115 2.989 21.9658C3.22158 22.0201 3.4647 22.0084 3.69099 21.932H3.68699Z"
                                                                    fill="currentColor"></path>
                                                                <path
                                                                    d="M5.574 21.3L3.692 21.928C3.46591 22.0032 3.22334 22.0141 2.99144 21.9594C2.75954 21.9046 2.54744 21.7864 2.3789 21.6179C2.21036 21.4495 2.09202 21.2375 2.03711 21.0056C1.9822 20.7737 1.99289 20.5312 2.06799 20.3051L2.696 18.422L5.574 21.3ZM4.13499 14.105L9.891 19.861L19.245 10.507L13.489 4.75098L4.13499 14.105Z"
                                                                    fill="currentColor"></path>
                                                            </svg>
                                                        </span>
                                                    </Link>
                                                    <!--end::Update-->
                                                    <!--begin::Delete-->
                                                    <button type="button" v-tooltip="'Eliminar'" @click="onDeleted(invoice.id)"
                                                        class="btn btn-icon btn-active-light-primary w-20px h-20px p-2">
                                                        <span class="svg-icon svg-icon-2">
                                                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                                                                xmlns="http://www.w3.org/2000/svg">
                                                                <path d="M5 9C5 8.44772 5.44772 8 6 8H18C18.5523 8 19 8.44772 19 9V18C19 19.6569 17.6569 21 16 21H8C6.34315 21 5 19.6569 5 18V9Z"
                                                                    fill="currentColor" />
                                                                <path opacity="0.5" d="M5 5C5 4.44772 5.44772 4 6 4H18C18.5523 4 19 4.44772 19 5V5C19 5.55228 18.5523 6 18 6H6C5.44772 6 5 5.55228 5 5V5Z"
                                                                    fill="currentColor" />
                                                                <path opacity="0.5" d="M9 4C9 3.44772 9.44772 3 10 3H14C14.5523 3 15 3.44772 15 4V4H9V4Z"
                                                                    fill="currentColor" />
                                                            </svg>
                                                        </span>
                                                    </button>
                                                    <!--end::Delete-->
                                                </div>
                                            </td>
                                        </tr>
                                    </template>
                                </template>
                                <!--end::Table body-->
                            </Table>
                        </div>
                    </div>

                    <!-- Nuevo tab-pane para Compras mensuales -->
                    <div class="tab-pane fade" id="pill-tab-detalles" role="tabpanel" aria-labelledby="detalles-tab">
                        <div class="table-responsive mt-1" style="max-height: 500px; overflow: auto; font-size: .7rem;">
                            <table class="table table-bordered table-hover table-sm mb-0">
                                <thead>
                                    <tr>
                                      <th style="width: 150px; min-width: 150px; white-space: nowrap;">Proveedor</th>
                                      <th>N° Doc</th>
                                      <th>Fecha</th>
                                      <th v-for="month in page.props.months" :key="month.value" class="text-center">{{ month.label }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="invoice in filteredInvoices" :key="invoice.id">
                                      <td
                                        style="min-width: 200px; max-width: 200px; width: 200px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"
                                        :title="invoice.supplier.name"
                                      >
                                        {{ invoice.supplier.name }}
                                      </td>
                                      <td>{{ invoice.number_document }}</td>
                                      <td>{{ invoice.date }}</td>
                                      <td v-for="month in page.props.months" :key="month.value" class="text-end">
                                          {{ new Intl.NumberFormat('es-ES', { style: 'decimal', minimumFractionDigits: 0 }).format(totalForMonth(invoice, month.value)) }}
                                      </td>
                                    </tr>
                                </tbody>
                                <tfoot>
                                    <tr>
                                      <th colspan="3" class="text-end">Total mensual:</th>
                                      <th v-for="(total, idx) in monthlyTotals" :key="idx" class="text-end">
                                        {{ new Intl.NumberFormat('es-ES', { style: 'decimal', minimumFractionDigits: 0 }).format(total) }}
                                      </th>
                                    </tr>
                                 </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
