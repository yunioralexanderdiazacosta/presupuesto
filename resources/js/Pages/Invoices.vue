<script setup>
import { computed, ref } from "vue";
import { Link, router, Head, usePage, useForm } from "@inertiajs/vue3";
import Swal from "sweetalert2";
import axios from "axios";
import AppLayout from "@/Layouts/AppLayout.vue";
import Table from "@/Components/Table.vue";
import Empty from "@/Components/Empty.vue";
import Breadcrumb from "@/Components/Breadcrumb.vue";
import SearchInput from "@/Components/SearchInput.vue";
import ExportExcelButton from "@/Components/ExportExcelButton.vue";

const props = defineProps({
    invoices: Object,
    term: String,
    totalFacturas: Number,
    totalIva: Number,
    totalGeneral: Number,
});

const title = "Facturas";

const term = ref(props.term || "");

// Filtrado local de facturas
const filteredInvoices = computed(() => {
    if (!props.invoices || !props.invoices.data) return [];
    if (!term.value) return props.invoices.data;
    const search = term.value.toLowerCase();
    return props.invoices.data.filter((item) => {
        const supplier =
            item.supplier && item.supplier.name
                ? item.supplier.name.toLowerCase()
                : "";
        const number = item.number_document
            ? String(item.number_document).toLowerCase()
            : "";
        const company =
            item.companyReason && item.companyReason.name
                ? item.companyReason.name.toLowerCase()
                : "";
        const products = item.products && item.products.length
            ? item.products.map(p => p.product_name).join(' ').toLowerCase()
            : "";
        return (
            supplier.includes(search) ||
            number.includes(search) ||
            company.includes(search) ||
            products.includes(search)
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

// Vista expandida: una fila por cada producto de cada factura
const termDetalles = ref('');
const activeTab = ref('resumen'); // controla qué pestaña está activa

const expandedInvoices = computed(() => {
    // Solo calcular cuando la pestaña de detalles está activa
    if (activeTab.value !== 'detalles') return [];
    const source = filteredInvoices.value;
    const rows = [];
    source.forEach(invoice => {
        if (invoice.products && invoice.products.length) {
            invoice.products.forEach(prod => {
                const subtotal = (prod.amount || 0) * (prod.unit_price || 0);
                const isFactura = invoice.type_document && invoice.type_document.toLowerCase() === 'factura';
                const iva = isFactura ? subtotal * 0.19 : 0;
                rows.push({
                    ...invoice,
                    product_name: prod.product_name,
                    product_amount: prod.amount || 0,
                    product_unit_price: prod.unit_price || 0,
                    product_subtotal: subtotal,
                    product_iva: iva,
                    product_total: subtotal + iva,
                });
            });
        } else {
            rows.push({
                ...invoice,
                product_name: '—',
                product_amount: 0,
                product_unit_price: 0,
                product_subtotal: 0,
                product_iva: 0,
                product_total: 0,
            });
        }
    });
    if (!termDetalles.value) return rows;
    const search = termDetalles.value.toLowerCase();
    return rows.filter(row => {
        const supplier = row.supplier && row.supplier.name ? row.supplier.name.toLowerCase() : '';
        const number = row.number_document ? String(row.number_document).toLowerCase() : '';
        const product = row.product_name ? row.product_name.toLowerCase() : '';
        return supplier.includes(search) || number.includes(search) || product.includes(search);
    });
});

const totalDetallesNeto = computed(() => {
    return expandedInvoices.value.reduce((sum, row) => sum + row.product_subtotal, 0);
});

const totalDetallesIva = computed(() => {
    return expandedInvoices.value.reduce((sum, row) => sum + row.product_iva, 0);
});

const totalDetallesGeneral = computed(() => {
    return expandedInvoices.value.reduce((sum, row) => sum + row.product_total, 0);
});

const fmt = (val) => new Intl.NumberFormat('es-ES', { maximumFractionDigits: 0 }).format(Math.round(val));

const excelDetallesData = computed(() => {
    return expandedInvoices.value.map(row => ({
        id: row.id,
        type_document: row.type_document,
        month: row.month,
        supplier: row.supplier ? row.supplier.name : '',
        number_document: row.number_document,
        date: row.date,
        product: row.product_name,
        amount: row.product_amount,
        unit_price: Math.round(row.product_unit_price),
        subtotal: Math.round(row.product_subtotal),
        iva: Math.round(row.product_iva),
        total: Math.round(row.product_total),
        expense_report: row.expense_report || '',
    }));
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

// ─── Importar desde Rendición ───────────────
const pendingItems = ref([]);
const loadingPending = ref(false);

const openImportModal = async () => {
    loadingPending.value = true;
    pendingItems.value = [];
    $('#importRendicionModal').modal('show');
    try {
        const response = await axios.get(route('api.pending-expense-items'));
        pendingItems.value = response.data;
    } catch (error) {
        console.error('Error al cargar items:', error);
        Swal.fire('Error', 'No se pudieron cargar los items pendientes', 'error');
    } finally {
        loadingPending.value = false;
    }
};

const importItem = (item) => {
    $('#importRendicionModal').modal('hide');
    router.get(route('invoices.create', { expense_item_id: item.id }));
};

const formatCurrency = (value) => {
    return '$ ' + Math.round(value).toLocaleString('es-CL');
};
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
                        <div class="d-flex align-items-center gap-2 justify-content-end">
                            <button class="btn btn-falcon-default btn-sm" @click="openImportModal">
                                <span class="fas fa-file-import" data-fa-transform="shrink-3 down-2"></span>
                                <span class="d-none d-sm-inline-block ms-1">Importar Rendición</span>
                            </button>
                            <button class="btn btn-falcon-default btn-sm" @click="router.visit(route('invoices.create'), { preserveState: false })">
                                <span class="fas fa-plus" data-fa-transform="shrink-3 down-2"></span>
                                <span class="d-none d-sm-inline-block ms-1">Nuevo</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-body bg-body-tertiary">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <ul class="nav nav-pills nav-pills-sm" id="pill-myTab" role="tablist" style="font-size: 0.75rem;">
                        <li class="nav-item">
                            <a class="nav-link py-1 px-2" :class="{ active: activeTab === 'resumen' }" id="pill-resumen"
                                href="#pill-tab-resumen" role="tab" @click.prevent="activeTab = 'resumen'">Resumen</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link py-1 px-2" :class="{ active: activeTab === 'detalles' }" id="pill-detalles"
                                href="#pill-tab-detalles" role="tab" @click.prevent="activeTab = 'detalles'">Detalles</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link py-1 px-2" :class="{ active: activeTab === 'gastos' }" id="pill-gastos"
                                href="#pill-tab-gastos" role="tab" @click.prevent="activeTab = 'gastos'">Gastos x Ha</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link py-1 px-2" :class="{ active: activeTab === 'detalles-compra' }" id="pill-detalles-compra"
                                href="#pill-tab-detalles-compra" role="tab" @click.prevent="activeTab = 'detalles-compra'">Det. compra</a>
                        </li>
                    </ul>
                    <!-- Cards de totales alineados a la derecha -->
                    <div class="d-flex gap-2">
                        <div class="card h-100 p-1 small-card">
                            <div class="card-header pb-0 pt-1 px-2">
                                <h6 class="mb-0 mt-1 fs-10 d-flex align-items-center small-card-title">Total Neto</h6>
                            </div>
                            <div class="card-body d-flex flex-column justify-content-end py-1 px-2">
                                <p class="font-sans-serif lh-1 mb-1 fs-10 small-card-number">
                                    ${{ props.totalFacturas ? fmt(props.totalFacturas) : '0' }}
                                </p>
                            </div>
                        </div>
                        <div class="card h-100 p-1 small-card">
                            <div class="card-header pb-0 pt-1 px-2">
                                <h6 class="mb-0 mt-1 fs-10 d-flex align-items-center small-card-title">IVA (19%)</h6>
                            </div>
                            <div class="card-body d-flex flex-column justify-content-end py-1 px-2">
                                <p class="font-sans-serif lh-1 mb-1 fs-10 small-card-number">
                                    ${{ props.totalIva ? fmt(props.totalIva) : '0' }}
                                </p>
                            </div>
                        </div>
                        <div class="card h-100 p-1 small-card">
                            <div class="card-header pb-0 pt-1 px-2">
                                <h6 class="mb-0 mt-1 fs-10 d-flex align-items-center small-card-title">Total General</h6>
                            </div>
                            <div class="card-body d-flex flex-column justify-content-end py-1 px-2">
                                <p class="font-sans-serif lh-1 mb-1 fs-10 small-card-number fw-bold text-primary">
                                    ${{ props.totalGeneral ? fmt(props.totalGeneral) : '0' }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="tab-content border p-3 mt-3" id="pill-myTabContent">
                    <div v-show="activeTab === 'resumen'" id="pill-tab-resumen" role="tabpanel">
                        <div class="row align-items-center mb-3">
                            <div class="col-md-6 col-12 mb-2 mb-md-0">
                                <SearchInput v-model="term" placeholder="Buscar por proveedor, número, razón social..."
                                    @keyup.enter="onFilter()" @change="onFilter()" />
                            </div>
                            <div class="col-md-6 col-12 text-md-end text-start">
                                <a :href="route('invoices.pdf', { term: term })
                                    " target="_blank" class="btn btn-falcon-default btn-sm me-2" style="font-size: 0.7rem;">
                                    <i class="fas fa-file-pdf me-1"></i>PDF
                                </a>
                                <a :href="route('invoices.excel', { term: term })
                                    " target="_blank" class="btn btn-falcon-default btn-sm me-2" style="font-size: 0.7rem;">
                                    <i class="fas fa-file-excel me-1"></i>Excel
                                </a>
                            </div>
                        </div>

                        <div class="table-responsive" style="max-height: calc(100vh - 350px); overflow-y: auto;">
                        <Table :id="'invoices'" :total="filteredInvoices.length" :links="invoices.links" class="min-w-full">
                            <!--begin::Table head-->
                            <template #header>
                                <!--begin::Table row-->
                                <th class="text-center" style="white-space:nowrap;">Acc.</th>
                                <th class="text-center" style="white-space:nowrap;">ID</th>
                                <th style="white-space:nowrap;">Tipo Doc.</th>
                                <th style="white-space:nowrap;">Mes</th>
                                <th style="white-space:nowrap; max-width:200px;">Proveedor</th>
                                <th style="white-space:nowrap;">N° Doc</th>
                                <th style="white-space:nowrap;">Fecha</th>
                                <th style="white-space:nowrap;">Vencimiento</th>
                                <th style="white-space:nowrap; max-width:200px;">Productos</th>
                                <th style="white-space:nowrap;">Rendición</th>
                                <th class="text-end" style="white-space:nowrap;">Total</th>
                                <!--end::Table row-->
                            </template>
                            <!--end::Table head-->
                            <!--begin::Table body-->
                            <template #body>
                                <template v-if="filteredInvoices.length == 0">
                                    <Empty colspan="3" />
                                </template>
                                <template v-else>
                                    <tr v-for="(
invoice, index
                                        ) in filteredInvoices" :key="index">
                                        <td class="text-center">
                                            <div class="btn-group">
                                                <!--begin::View-->
                                                <Link :href="route(
                                                    'invoices.show',
                                                    invoice.id
                                                )
                                                    " v-tooltip="'Ver'"
                                                    class="btn btn-icon btn-active-light-primary w-20px h-20px me-1 p-1">
                                                <i class="fas fa-eye" style="font-size:0.65rem;"></i>
                                                </Link>
                                                <!--end::View-->
                                                <!--begin::Update-->
                                                <Link v-tooltip="'Editar'"
                                                    class="btn btn-icon btn-active-light-primary w-20px h-20px me-1 p-1"
                                                    :href="route(
                                                        'invoices.edit',
                                                        invoice.id
                                                    )
                                                        ">
                                                <i class="fas fa-edit" style="font-size:0.65rem;"></i>
                                                </Link>
                                                <!--end::Update-->
                                                <!--begin::Duplicate-->
                                                <Link v-tooltip="'Duplicar factura'"
                                                    class="btn btn-icon btn-active-light-primary w-20px h-20px me-1 p-1"
                                                    :href="route('invoices.duplicate', invoice.id)">
                                                    <i class="fas fa-copy" style="font-size:0.65rem;"></i>
                                                </Link>
                                                <!--end::Duplicate-->
                                                <!--begin::Delete-->
                                                <button type="button" v-tooltip="'Eliminar'" @click="
                                                    onDeleted(invoice.id)
                                                    " class="btn btn-icon btn-active-light-primary w-20px h-20px p-1">
                                                    <i class="fas fa-trash-alt" style="font-size:0.65rem;"></i>
                                                </button>
                                                <!--end::Delete-->
                                            </div>
                                        </td>
                                        <td class="text-center">{{ invoice.id }}</td>
                                        <td style="white-space:nowrap;">{{ invoice.type_document }}</td>
                                        <td style="white-space:nowrap;">{{ invoice.month }}</td>
                                        <td style="white-space:nowrap; max-width:200px; overflow:hidden; text-overflow:ellipsis;">{{ invoice.supplier.name }}</td>
                                        <td style="white-space:nowrap;">{{ invoice.number_document }}</td>
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
                                                    <span
                                                        v-tooltip="invoice.products.map(p => p.product_name).join(', ')"
                                                    >
                                                        {{ invoice.products[0].product_name }}, {{ invoice.products[1].product_name }} y {{ invoice.products.length - 2 }} más
                                                    </span>
                                                </span>
                                            </span>
                                            <span v-else class="text-muted">—</span>
                                        </td>
                                        <td style="white-space:nowrap;">
                                            <span v-if="invoice.expense_report" class="badge bg-info">
                                                <i class="fas fa-receipt me-1"></i>{{ invoice.expense_report }}
                                            </span>
                                            <span v-else class="text-muted">—</span>
                                        </td>
                                        <td class="text-end">{{ invoice.total }}</td>
                                    </tr>
                                </template>
                            </template>
                            <!--end::Table body-->
                        </Table>
                    </div>
                    </div>
                    <!-- end::Tab Resumen -->

                    <!-- begin::Tab Detalles -->
                    <div v-show="activeTab === 'detalles'" id="pill-tab-detalles" role="tabpanel">
                        <div class="row align-items-center mb-3">
                            <div class="col-md-6 col-12 mb-2 mb-md-0">
                                <SearchInput v-model="termDetalles" placeholder="Buscar por proveedor, N° doc, producto..." />
                            </div>
                            <div class="col-md-6 col-12 text-md-end text-start">
                                <ExportExcelButton
                                    :data="excelDetallesData"
                                    :headers="[
                                        { label: 'ID', key: 'id' },
                                        { label: 'Tipo Doc.', key: 'type_document' },
                                        { label: 'Mes', key: 'month' },
                                        { label: 'Proveedor', key: 'supplier' },
                                        { label: 'N° Doc', key: 'number_document' },
                                        { label: 'Fecha', key: 'date' },
                                        { label: 'Producto', key: 'product' },
                                        { label: 'Cantidad', key: 'amount' },
                                        { label: 'P. Unit', key: 'unit_price' },
                                        { label: 'Subtotal Neto', key: 'subtotal' },
                                        { label: 'IVA', key: 'iva' },
                                        { label: 'Total', key: 'total' },
                                        { label: 'Rendición', key: 'expense_report' },
                                    ]"
                                    class="btn btn-falcon-default btn-sm me-2"
                                    style="font-size: 0.7rem;"
                                    filename="Facturas_Detalles.xlsx"
                                />
                            </div>
                        </div>
                        <div class="d-flex justify-content-end mb-2">
                            <div class="border rounded px-3 py-1 bg-light d-flex gap-3 align-items-center">
                                <span class="small">Neto: <b>${{ fmt(totalDetallesNeto) }}</b></span>
                                <span class="text-muted">|</span>
                                <span class="small">IVA: <b>${{ fmt(totalDetallesIva) }}</b></span>
                                <span class="text-muted">|</span>
                                <span class="small fw-bold text-primary">Total: ${{ fmt(totalDetallesGeneral) }}</span>
                            </div>
                        </div>

                        <div class="table-responsive" style="max-height: calc(100vh - 350px); overflow-y: auto;">
                            <Table :id="'invoices-detalles'" :total="expandedInvoices.length" :links="[]" class="min-w-full">
                                <template #header>
                                    <th class="text-center" style="white-space:nowrap;">Acc.</th>
                                    <th class="text-center" style="white-space:nowrap;">ID</th>
                                    <th style="white-space:nowrap;">Tipo Doc.</th>
                                    <th style="white-space:nowrap;">Mes</th>
                                    <th style="white-space:nowrap; max-width:200px;">Proveedor</th>
                                    <th style="white-space:nowrap;">N° Doc</th>
                                    <th style="white-space:nowrap;">Fecha</th>
                                    <th style="white-space:nowrap; max-width:220px;">Producto</th>
                                    <th class="text-end" style="white-space:nowrap;">Cantidad</th>
                                    <th class="text-end" style="white-space:nowrap;">P. Unit</th>
                                    <th class="text-end" style="white-space:nowrap;">Subtotal Neto</th>
                                    <th class="text-end" style="white-space:nowrap;">IVA</th>
                                    <th class="text-end" style="white-space:nowrap;">Total</th>
                                    <th style="white-space:nowrap;">Rendición</th>
                                </template>
                                <template #body>
                                    <template v-if="expandedInvoices.length == 0">
                                        <Empty colspan="14" />
                                    </template>
                                    <template v-else>
                                        <tr v-for="(row, index) in expandedInvoices" :key="'det-' + index">
                                            <td class="text-center">
                                                <div class="btn-group">
                                                    <Link :href="route('invoices.show', row.id)" v-tooltip="'Ver'"
                                                        class="btn btn-icon btn-active-light-primary w-20px h-20px me-1 p-1">
                                                        <i class="fas fa-eye" style="font-size:0.65rem;"></i>
                                                    </Link>
                                                    <Link :href="route('invoices.edit', row.id)" v-tooltip="'Editar'"
                                                        class="btn btn-icon btn-active-light-primary w-20px h-20px me-1 p-1">
                                                        <i class="fas fa-edit" style="font-size:0.65rem;"></i>
                                                    </Link>
                                                    <button type="button" v-tooltip="'Eliminar'" @click="onDeleted(row.id)"
                                                        class="btn btn-icon btn-active-light-primary w-20px h-20px p-1">
                                                        <i class="fas fa-trash-alt" style="font-size:0.65rem;"></i>
                                                    </button>
                                                </div>
                                            </td>
                                            <td class="text-center">{{ row.id }}</td>
                                            <td style="white-space:nowrap;">{{ row.type_document }}</td>
                                            <td style="white-space:nowrap;">{{ row.month }}</td>
                                            <td style="white-space:nowrap; max-width:200px; overflow:hidden; text-overflow:ellipsis;">{{ row.supplier.name }}</td>
                                            <td style="white-space:nowrap;">{{ row.number_document }}</td>
                                            <td style="white-space:nowrap;">{{ row.date }}</td>
                                            <td style="white-space:nowrap; max-width:220px; overflow:hidden; text-overflow:ellipsis;">{{ row.product_name }}</td>
                                            <td class="text-end" style="white-space:nowrap;">{{ row.product_amount.toLocaleString('es-ES') }}</td>
                                            <td class="text-end" style="white-space:nowrap;">${{ Math.round(row.product_unit_price).toLocaleString('es-ES') }}</td>
                                            <td class="text-end" style="white-space:nowrap;">${{ Math.round(row.product_subtotal).toLocaleString('es-ES') }}</td>
                                            <td class="text-end" style="white-space:nowrap;">${{ Math.round(row.product_iva).toLocaleString('es-ES') }}</td>
                                            <td class="text-end" style="white-space:nowrap;">${{ Math.round(row.product_total).toLocaleString('es-ES') }}</td>
                                            <td style="white-space:nowrap;">
                                                <span v-if="row.expense_report" class="badge bg-info">
                                                    <i class="fas fa-receipt me-1"></i>{{ row.expense_report }}
                                                </span>
                                                <span v-else class="text-muted">—</span>
                                            </td>
                                        </tr>
                                    </template>
                                </template>
                            </Table>
                        </div>
                    </div>
                    <!-- end::Tab Detalles -->

                </div>
            </div>
        </div>

        <!-- Modal: Importar desde Rendición -->
        <div class="modal fade" id="importRendicionModal" tabindex="-1" aria-labelledby="importRendicionModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header py-2 border-bottom">
                        <div class="d-flex align-items-center gap-2">
                            <span class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" 
                                  style="width: 38px; height: 38px; font-size: 1.2rem;">
                                <i class="fas fa-file-import"></i>
                            </span>
                            <span>
                                <span class="fw-bold" style="font-size: 1.1rem; color: #2d3748;">
                                    Importar desde Rendición de Gastos
                                </span>
                                <br>
                                <span class="text-muted" style="font-size: 0.8rem;">
                                    Seleccione un item para crear su factura con datos pre-cargados
                                </span>
                            </span>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <!-- Loading -->
                        <div v-if="loadingPending" class="text-center py-5">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Cargando...</span>
                            </div>
                            <p class="mt-2 text-muted small">Cargando items pendientes...</p>
                        </div>

                        <!-- Sin items -->
                        <div v-else-if="pendingItems.length === 0" class="text-center py-5">
                            <i class="fas fa-check-circle text-success fa-3x mb-3"></i>
                            <p class="text-muted mb-0">No hay items pendientes de contabilizar</p>
                            <small class="text-muted">Todos los items de rendiciones aprobadas ya tienen factura asociada</small>
                        </div>

                        <!-- Lista de items -->
                        <div v-else>
                            <!-- Resumen -->
                            <div class="row g-3 mb-3">
                                <div class="col-md-4">
                                    <div class="card border-start border-primary border-3 h-100">
                                        <div class="card-body py-2 px-3">
                                            <small class="text-muted d-block">Items Pendientes</small>
                                            <strong class="fs-7">{{ pendingItems.length }}</strong>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card border-start border-warning border-3 h-100">
                                        <div class="card-body py-2 px-3">
                                            <small class="text-muted d-block">Monto Total Pendiente</small>
                                            <strong class="fs-7">{{ formatCurrency(pendingItems.reduce((sum, i) => sum + i.amount, 0)) }}</strong>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card border-start border-info border-3 h-100">
                                        <div class="card-body py-2 px-3">
                                            <small class="text-muted d-block">Rendiciones</small>
                                            <strong class="fs-7">{{ [...new Set(pendingItems.map(i => i.expense_report_number))].length }}</strong>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Tabla -->
                            <div class="table-responsive" style="max-height: calc(100vh - 380px); overflow-y: auto;">
                                <table class="table table-hover mb-0" style="font-size: 0.82rem;">
                                    <thead class="bg-200 position-sticky top-0">
                                        <tr>
                                            <th style="min-width: 100px;">Rendición</th>
                                            <th style="min-width: 90px;">Fecha</th>
                                            <th style="min-width: 180px;">Proveedor</th>
                                            <th style="min-width: 150px;">Producto</th>
                                            <th style="min-width: 180px;">Descripción</th>
                                            <th class="text-end" style="min-width: 110px;">Monto</th>
                                            <th class="text-center" style="min-width: 50px;">Doc.</th>
                                            <th class="text-center" style="min-width: 130px;">Acción</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="item in pendingItems" :key="item.id" class="align-middle">
                                            <td>
                                                <span class="badge bg-soft-info text-info">{{ item.expense_report_number }}</span>
                                            </td>
                                            <td>{{ item.date }}</td>
                                            <td>
                                                <div class="text-truncate" style="max-width: 200px;" :title="item.supplier_name">
                                                    {{ item.supplier_name }}
                                                </div>
                                            </td>
                                            <td>
                                                <div class="text-truncate" style="max-width: 170px;" :title="item.product_name">
                                                    {{ item.product_name || '—' }}
                                                </div>
                                            </td>
                                            <td>
                                                <div class="text-truncate text-muted" style="max-width: 200px;" :title="item.description">
                                                    {{ item.description || '—' }}
                                                </div>
                                            </td>
                                            <td class="text-end fw-semibold">{{ formatCurrency(item.amount) }}</td>
                                            <td class="text-center">
                                                <i v-if="item.has_receipt" class="fas fa-paperclip text-success" v-tooltip="'Tiene comprobante'"></i>
                                                <span v-else class="text-muted">—</span>
                                            </td>
                                            <td class="text-center">
                                                <button class="btn btn-sm btn-primary py-1 px-3" 
                                                    @click="importItem(item)">
                                                    <i class="fas fa-file-invoice me-1"></i>Crear Factura
                                                </button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer py-2 bg-light">
                        <button type="button" class="btn btn-sm btn-falcon-default" data-bs-dismiss="modal">
                            <i class="fas fa-times me-1"></i>Cerrar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
/* Tabla compacta para Invoices */
:deep(.table) {
    font-size: 0.7rem;
}
:deep(.table th) {
    font-size: 0.7rem;
    padding: 0.25rem 0.35rem;
}
:deep(.table td) {
    padding: 0.2rem 0.35rem;
}
</style>
