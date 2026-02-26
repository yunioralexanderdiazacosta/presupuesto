<script setup>
import { computed, ref } from "vue";
import { Link, router, Head, usePage, useForm } from "@inertiajs/vue3";
import Swal from "sweetalert2";
import AppLayout from "@/Layouts/AppLayout.vue";
import Table from "@/Components/Table.vue";
import Empty from "@/Components/Empty.vue";
import Breadcrumb from "@/Components/Breadcrumb.vue";
import SearchInput from "@/Components/SearchInput.vue";

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
        return (
            supplier.includes(search) ||
            number.includes(search) ||
            company.includes(search)
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
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <ul class="nav nav-pills nav-pills-sm" id="pill-myTab" role="tablist" style="font-size: 0.75rem;">
                        <li class="nav-item">
                            <a class="nav-link active py-1 px-2" id="pill-resumen" data-bs-toggle="tab" href="#pill-tab-resumen"
                                role="tab" aria-controls="pill-tab-resumen" aria-selected="true">Resumen</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link py-1 px-2" id="pill-detalles" data-bs-toggle="tab" href="#pill-tab-detalles" role="tab"
                                aria-controls="pill-tab-detalles" aria-selected="false">Detalles</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link py-1 px-2" id="pill-gastos" data-bs-toggle="tab" href="#pill-tab-gastos" role="tab"
                                aria-controls="pill-tab-gastos" aria-selected="false">Gastos x Ha</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link py-1 px-2" id="pill-detalles-compra" data-bs-toggle="tab"
                                href="#pill-tab-detalles-compra" role="tab" aria-controls="pill-tab-detalles-compra"
                                aria-selected="false">Det. compra</a>
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
                                    ${{ props.totalFacturas ? Math.round(props.totalFacturas).toLocaleString('es-ES') : '0' }}
                                </p>
                            </div>
                        </div>
                        <div class="card h-100 p-1 small-card">
                            <div class="card-header pb-0 pt-1 px-2">
                                <h6 class="mb-0 mt-1 fs-10 d-flex align-items-center small-card-title">IVA (19%)</h6>
                            </div>
                            <div class="card-body d-flex flex-column justify-content-end py-1 px-2">
                                <p class="font-sans-serif lh-1 mb-1 fs-10 small-card-number">
                                    ${{ props.totalIva ? Math.round(props.totalIva).toLocaleString('es-ES') : '0' }}
                                </p>
                            </div>
                        </div>
                        <div class="card h-100 p-1 small-card">
                            <div class="card-header pb-0 pt-1 px-2">
                                <h6 class="mb-0 mt-1 fs-10 d-flex align-items-center small-card-title">Total General</h6>
                            </div>
                            <div class="card-body d-flex flex-column justify-content-end py-1 px-2">
                                <p class="font-sans-serif lh-1 mb-1 fs-10 small-card-number fw-bold text-primary">
                                    ${{ props.totalGeneral ? Math.round(props.totalGeneral).toLocaleString('es-ES') : '0' }}
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
                                        <td class="text-end">{{ invoice.total }}</td>
                                    </tr>
                                </template>
                            </template>
                            <!--end::Table body-->
                        </Table>
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
