<script setup>
import { onMounted, ref, nextTick, watch } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import mermaid from 'mermaid';

const activeTab = ref('general');
const renderedTabs = ref({});

// Zoom state per tab
const zoomLevels = ref({ general: 1, operativo: 1, egresos: 1, ordenes: 1, rendiciones: 1, facturas: 1 });
const MIN_ZOOM = 0.3;
const MAX_ZOOM = 5;
const ZOOM_STEP = 0.2;

// Pan state
const isPanning = ref(false);
const panStart = ref({ x: 0, y: 0 });
const panOffset = ref({ general: { x: 0, y: 0 }, operativo: { x: 0, y: 0 }, egresos: { x: 0, y: 0 }, ordenes: { x: 0, y: 0 }, rendiciones: { x: 0, y: 0 }, facturas: { x: 0, y: 0 } });

const zoomIn = (tabId) => {
    zoomLevels.value[tabId] = Math.min(MAX_ZOOM, zoomLevels.value[tabId] + ZOOM_STEP);
};
const zoomOut = (tabId) => {
    zoomLevels.value[tabId] = Math.max(MIN_ZOOM, zoomLevels.value[tabId] - ZOOM_STEP);
};
const zoomReset = (tabId) => {
    zoomLevels.value[tabId] = 1;
    panOffset.value[tabId] = { x: 0, y: 0 };
};
const zoomFit = (tabId) => {
    zoomLevels.value[tabId] = 0.5;
    panOffset.value[tabId] = { x: 0, y: 0 };
};

const onWheel = (e, tabId) => {
    e.preventDefault();
    if (e.deltaY < 0) {
        zoomLevels.value[tabId] = Math.min(MAX_ZOOM, zoomLevels.value[tabId] + 0.1);
    } else {
        zoomLevels.value[tabId] = Math.max(MIN_ZOOM, zoomLevels.value[tabId] - 0.1);
    }
};

const onPanStart = (e, tabId) => {
    isPanning.value = true;
    panStart.value = { x: e.clientX - panOffset.value[tabId].x, y: e.clientY - panOffset.value[tabId].y };
    
    const onMove = (ev) => {
        if (!isPanning.value) return;
        panOffset.value[tabId] = {
            x: ev.clientX - panStart.value.x,
            y: ev.clientY - panStart.value.y,
        };
    };
    const onUp = () => {
        isPanning.value = false;
        document.removeEventListener('mousemove', onMove);
        document.removeEventListener('mouseup', onUp);
    };
    document.addEventListener('mousemove', onMove);
    document.addEventListener('mouseup', onUp);
};

const zoomPercent = (tabId) => Math.round(zoomLevels.value[tabId] * 100);

const tabs = [
    { id: 'general', label: 'Vista General', icon: 'fas fa-sitemap' },
    { id: 'operativo', label: 'Flujo Operativo', icon: 'fas fa-project-diagram' },
    { id: 'egresos', label: 'Egresos / Salidas', icon: 'fas fa-sign-out-alt' },
    { id: 'ordenes', label: 'Ordenes de Compra', icon: 'fas fa-shopping-cart' },
    { id: 'rendiciones', label: 'Rendiciones', icon: 'fas fa-receipt' },
    { id: 'facturas', label: 'Facturas y Pagos', icon: 'fas fa-file-invoice-dollar' },
];

onMounted(async () => {
    mermaid.initialize({
        startOnLoad: false,
        theme: 'base',
        securityLevel: 'loose',
        themeVariables: {
            primaryColor: '#e8f4fd',
            primaryTextColor: '#2c3e50',
            primaryBorderColor: '#2196F3',
            lineColor: '#607D8B',
            secondaryColor: '#fff3e0',
            tertiaryColor: '#e8f5e9',
            fontSize: '16px',
        },
        flowchart: {
            htmlLabels: true,
            curve: 'basis',
            nodeSpacing: 30,
            rankSpacing: 50,
            padding: 15,
        },
    });
    await renderCurrentTab();
});

const renderCurrentTab = async () => {
    if (renderedTabs.value[activeTab.value]) return;
    await nextTick();
    const container = document.getElementById('tab-' + activeTab.value);
    if (!container) return;
    const elements = container.querySelectorAll('.mermaid-source');
    for (const el of elements) {
        const id = 'mm-' + Math.random().toString(36).substr(2, 9);
        try {
            const { svg } = await mermaid.render(id, el.textContent.trim());
            el.innerHTML = svg;
            // Ampliar SVG para mejor visualización
            const svgEl = el.querySelector('svg');
            if (svgEl) {
                svgEl.removeAttribute('width');
                svgEl.style.width = '100%';
                svgEl.style.minWidth = '900px';
            }
            el.classList.remove('mermaid-source');
            el.classList.add('mermaid-rendered');
        } catch (e) {
            console.error('Mermaid error:', e);
            el.innerHTML = '<div class="alert alert-danger">Error al renderizar diagrama</div>';
        }
    }
    renderedTabs.value[activeTab.value] = true;
};

const onTabChange = async (tabId) => {
    activeTab.value = tabId;
    renderedTabs.value[tabId] = false;
    await nextTick();
    await renderCurrentTab();
};

// Diagramas Mermaid
const diagramGeneral = `graph TB
    LOGIN[Iniciar Sesion] --> SELECT[Seleccionar Temporada]
    SELECT --> MENU[Menu Principal]
    
    MENU --> PRESUP[PRESUPUESTOS]
    MENU --> PARAM[PARAMETROS]
    MENU --> GESTION[GESTION]
    MENU --> REPORTES[REPORTES]
    
    PRESUP --> P1[Agroquimicos]
    PRESUP --> P2[Fertilizantes]
    PRESUP --> P3[Mano de Obra]
    PRESUP --> P4[Insumos]
    PRESUP --> P5[Servicios]
    PRESUP --> P6[Cosecha]
    PRESUP --> P7[Campo]
    PRESUP --> P8[Administracion]
    
    PARAM --> PA1[Centros de Costo]
    PARAM --> PA2[Niveles]
    PARAM --> PA3[Usuarios]
    PARAM --> PA4[Temporadas]
    PARAM --> PA5[Catalogos]
    PA5 --> PA5A[Frutales / Variedades / Parcelas]
    
    GESTION --> G_CAT[Catalogos]
    GESTION --> G_PLAN[Planificacion]
    GESTION --> G_COMP[Compras]
    GESTION --> G_OPER[Operaciones]
    GESTION --> G_TES[Tesoreria]
    
    G_CAT --> GC1[Proveedores]
    G_CAT --> GC2[Productos]
    G_CAT --> GC3[Maquinarias]
    
    G_PLAN --> GP1[Estimaciones]
    G_PLAN --> GP2[Ordenes de Aplicacion]
    G_PLAN --> GP3[Ordenes de Fertilizante]
    G_PLAN --> GP4[Ordenes de Compra]
    
    G_COMP --> GCO1[Facturas]
    
    G_OPER --> GO1[Egresos por CC]
    G_OPER --> GO2[Combustibles]
    G_OPER --> GO3[Aplicaciones Agroquimicos]
    G_OPER --> GO4[Aplicaciones Fertilizantes]
    
    G_TES --> GT1[Pagos de Facturas]
    G_TES --> GT2[Notas Credito/Debito]
    G_TES --> GT3[Rendiciones de Gastos]
    
    REPORTES --> R1[Dashboard Principal]
    REPORTES --> R2[Panel Tecnico]
    REPORTES --> R3[Dashboard Egresos]
    REPORTES --> R4[Analisis Comparativo]
    REPORTES --> R5[Inventario / Kardex]

    classDef mainNode fill:#1976D2,stroke:#0D47A1,color:#fff,font-weight:bold
    classDef sectionNode fill:#FF9800,stroke:#E65100,color:#fff,font-weight:bold
    classDef subNode fill:#f5f5f5,stroke:#9e9e9e,color:#333
    classDef catNode fill:#e3f2fd,stroke:#1565C0,color:#1565C0

    class LOGIN,SELECT mainNode
    class PRESUP,PARAM,GESTION,REPORTES sectionNode
    class G_CAT,G_PLAN,G_COMP,G_OPER,G_TES catNode`;

const diagramOperativo = `graph LR
    subgraph S1[CONFIGURACION INICIAL]
        direction TB
        T1[Crear Temporada] --> T2[Configurar CC y Niveles]
        T2 --> T3[Registrar Proveedores y Productos]
    end
    
    subgraph S2[PLANIFICACION]
        direction TB
        PL1[Crear Presupuestos por Categoria] --> PL2[Definir Estimaciones de Venta]
        PL2 --> PL3[Crear Ordenes de Aplicacion]
        PL3 --> PL4[Crear Ordenes de Fertilizante]
    end
    
    subgraph S3[COMPRAS]
        direction TB
        C1[Orden de Compra] --> C2[Registrar Factura]
        C2 --> C3[Asociar Productos a Factura]
    end
    
    subgraph S4[OPERACIONES]
        direction TB
        O1[Registrar Egresos por CC]
        O2[Registrar Consumo Combustible]
        O3[Aplicar Agroquimicos]
        O4[Aplicar Fertilizantes]
    end
    
    subgraph S5[TESORERIA]
        direction TB
        TE1[Pagar Facturas]
        TE2[Emitir Notas Credito/Debito]
        TE3[Rendiciones de Gastos]
    end
    
    subgraph S6[ANALISIS]
        direction TB
        A1[Dashboard Principal]
        A2[Comparar Presupuesto vs Real]
        A3[Inventario / Kardex]
    end
    
    S1 --> S2
    S2 --> S3
    S3 --> S4
    S3 --> S5
    S4 --> S6
    S5 --> S6`;

const diagramRendiciones = `graph TD
    START([Usuario necesita rendir gastos]) --> CREATE[Crear Rendicion de Gastos]
    CREATE --> ADD[Agregar Documentos]
    
    ADD --> DOC1[Fecha + Proveedor + Monto]
    ADD --> DOC2[Adjuntar Comprobante]
    ADD --> DOC3[Numero Documento]
    
    DOC1 --> REVIEW{Documentos completos?}
    DOC2 --> REVIEW
    DOC3 --> REVIEW
    
    REVIEW -->|No| ADD
    REVIEW -->|Si| SEND[Enviar a Aprobador]
    
    SEND --> SELECT_APPR[Seleccionar Aprobador del Equipo]
    SELECT_APPR --> NOTIFY[Se envia email al Aprobador]
    NOTIFY --> STATUS_ENV[Estado: ENVIADA]
    
    STATUS_ENV --> APPR_DECISION{Aprobador Revisa}
    
    APPR_DECISION -->|Aprobar| APPROVED[Estado: APROBADA]
    APPR_DECISION -->|Rechazar| REJECTED[Estado: RECHAZADA]
    
    REJECTED --> MOTIVO[Se registra motivo del rechazo]
    MOTIVO --> NOTIFY_RECH[Email al rendidor con motivo]
    NOTIFY_RECH --> BORRADOR[Vuelve a BORRADOR]
    BORRADOR --> ADD
    
    APPROVED --> NOTIFY_APR[Email al rendidor confirmando]
    APPROVED --> CONTAB{Contabilizar?}
    
    CONTAB -->|Si| INVOICE[Se vincula con Factura]
    CONTAB -->|Despues| PAID
    
    INVOICE --> PAID[Marcar como PAGADA]
    
    classDef startEnd fill:#1976D2,stroke:#0D47A1,color:#fff
    classDef process fill:#e3f2fd,stroke:#1565C0,color:#1565C0
    classDef decision fill:#fff3e0,stroke:#E65100,color:#E65100
    classDef approved fill:#e8f5e9,stroke:#2E7D32,color:#2E7D32
    classDef rejected fill:#ffebee,stroke:#C62828,color:#C62828
    classDef email fill:#f3e5f5,stroke:#7B1FA2,color:#7B1FA2
    
    class START,PAID startEnd
    class CREATE,ADD,DOC1,DOC2,DOC3,SEND,SELECT_APPR,STATUS_ENV,BORRADOR,INVOICE process
    class REVIEW,APPR_DECISION,CONTAB decision
    class APPROVED,NOTIFY_APR approved
    class REJECTED,MOTIVO rejected
    class NOTIFY,NOTIFY_RECH,NOTIFY_APR email`;

const diagramFacturas = `graph TD
    START([Recibir Factura del Proveedor]) --> OCR{Usar OCR?}
    
    OCR -->|Si| SCAN[Escanear con Mindee]
    OCR -->|No| MANUAL[Ingreso Manual]
    
    SCAN --> AUTO[Datos auto-completados]
    AUTO --> CREATE
    MANUAL --> CREATE[Crear Factura]
    
    CREATE --> ITEMS[Agregar Productos a la Factura]
    ITEMS --> ITEM_DET[Por cada producto:]
    ITEM_DET --> DET1[Producto + Cantidad + Precio]
    ITEM_DET --> DET2[Asignar Centro de Costo]
    
    DET1 --> SAVED[Factura Registrada]
    DET2 --> SAVED
    
    SAVED --> EGRESOS[Registrar Egresos/Consumos]
    SAVED --> PAGOS[Gestionar Pagos]
    
    EGRESOS --> E1[Egreso General por CC]
    EGRESOS --> E2[Consumo Combustible]
    EGRESOS --> E3[Aplicacion Agroquimicos]
    EGRESOS --> E4[Aplicacion Fertilizantes]
    
    E1 --> KARDEX[Actualiza Inventario/Kardex]
    E2 --> KARDEX
    E3 --> KARDEX
    E4 --> KARDEX
    
    PAGOS --> PAY_DEC{Pago parcial o total?}
    PAY_DEC -->|Parcial| PARTIAL[Registrar Abono]
    PAY_DEC -->|Total| FULL[Pagar Totalidad]
    
    PARTIAL --> PENDING[Estado: Parcialmente Pagada]
    PENDING --> PAY_DEC
    FULL --> PAID[Estado: Pagada]
    
    PAGOS --> NC[Nota de Credito/Debito]
    NC --> ADJUST[Ajusta monto de la factura]
    ADJUST --> PAY_DEC

    classDef startEnd fill:#1976D2,stroke:#0D47A1,color:#fff
    classDef process fill:#e3f2fd,stroke:#1565C0,color:#1565C0
    classDef decision fill:#fff3e0,stroke:#E65100,color:#E65100
    classDef success fill:#e8f5e9,stroke:#2E7D32,color:#2E7D32

    class START,PAID startEnd
    class CREATE,ITEMS,ITEM_DET,DET1,DET2,SAVED,SCAN,AUTO,MANUAL,EGRESOS,PAGOS,E1,E2,E3,E4,KARDEX,PARTIAL,FULL,NC,ADJUST,PENDING process
    class OCR,PAY_DEC decision
    class PAID success`;

const diagramEgresos = `graph TD
    START([Factura Registrada con Productos]) --> DISP[Vista: Disponible para Salidas]
    DISP --> STOCK{Stock disponible?}
    
    STOCK -->|No hay stock| COMPRAR([Registrar nueva Factura])
    STOCK -->|Si| TIPO{Tipo de Egreso}
    
    TIPO --> EG_GEN[Egreso General por CC]
    TIPO --> EG_FUEL[Combustible]
    TIPO --> EG_AGRO[Agroquimicos]
    TIPO --> EG_FERT[Fertilizantes]
    
    subgraph S1[EGRESO GENERAL]
        direction TB
        EG1[Seleccionar Producto disponible] --> EG2[Ingresar Cantidad a consumir]
        EG2 --> EG3[Asignar Proyecto / Operacion]
        EG3 --> EG4[Distribuir en Centros de Costo]
        EG4 --> EG5[Guardar Egreso]
    end
    
    subgraph S2[COMBUSTIBLE]
        direction TB
        F0[Seleccionar Origen: Factura + Producto con stock] --> F1[Se carga automatico: Producto y Litros max]
        F1 --> F2[Seleccionar Maquinaria]
        F2 --> F3[Registrar Contador / Odometro]
        F3 --> F4[Ingresar Litros a consumir]
        F4 --> F5[Asignar Operador]
        F5 --> F6[Asignar Fecha y Proyecto]
        F6 --> F7[Distribuir en Centros de Costo]
        F7 --> F8[Guardar Consumo]
    end
    
    subgraph S3[AGROQUIMICOS]
        direction TB
        A1[Requiere Orden de Aplicacion] --> A2[Seleccionar Orden Pendiente]
        A2 --> A3[Elegir productos de la orden]
        A3 --> A4[Asignar stock de facturas]
        A4 --> A5[Distribuir cantidades]
        A5 --> A6[Guardar Aplicacion]
    end
    
    subgraph S4[FERTILIZANTES]
        direction TB
        FE1[Requiere Orden de Fertilizante] --> FE2[Seleccionar Orden Pendiente]
        FE2 --> FE3[Elegir productos de la orden]
        FE3 --> FE4[Asignar stock de facturas]
        FE4 --> FE5[Sector de riego / Bomba]
        FE5 --> FE6[Guardar Aplicacion]
    end
    
    EG_GEN --> S1
    EG_FUEL --> S2
    EG_AGRO --> S3
    EG_FERT --> S4
    
    EG5 --> KARDEX[Actualiza Inventario / Kardex]
    F8 --> KARDEX
    A6 --> KARDEX
    FE6 --> KARDEX
    
    KARDEX --> CALC[Stock = Comprado - Consumido - Devuelto]
    CALC --> DASH[Dashboard Egresos / Reportes]

    classDef startEnd fill:#1976D2,stroke:#0D47A1,color:#fff
    classDef process fill:#e3f2fd,stroke:#1565C0,color:#1565C0
    classDef decision fill:#fff3e0,stroke:#E65100,color:#E65100
    classDef success fill:#e8f5e9,stroke:#2E7D32,color:#2E7D32
    classDef fuel fill:#fff8e1,stroke:#F57F17,color:#F57F17
    classDef agro fill:#e8f5e9,stroke:#2E7D32,color:#2E7D32
    classDef fert fill:#f3e5f5,stroke:#7B1FA2,color:#7B1FA2
    
    class START,COMPRAR startEnd
    class DISP,STOCK,TIPO process
    class EG_GEN,EG1,EG2,EG3,EG4,EG5 process
    class EG_FUEL,F0,F1,F2,F3,F4,F5,F6,F7,F8 fuel
    class EG_AGRO,A1,A2,A3,A4,A5,A6 agro
    class EG_FERT,FE1,FE2,FE3,FE4,FE5,FE6 fert
    class KARDEX,CALC,DASH success`;

const diagramOrdenes = `graph TD
    START([Usuario necesita comprar insumos]) --> CREATE[Crear Orden de Compra]
    
    CREATE --> HEADER[Datos Generales]
    HEADER --> H1[Seleccionar Proveedor]
    HEADER --> H2[Fecha Orden / Fecha Entrega]
    HEADER --> H3[Condiciones de Pago]
    HEADER --> H4[Asignar Aprobador opcional]
    HEADER --> H5[Seleccionar Centros de Costo]
    
    H1 --> ITEMS[Agregar Productos]
    H2 --> ITEMS
    H3 --> ITEMS
    H4 --> ITEMS
    H5 --> ITEMS
    
    ITEMS --> IT1[Producto + Cantidad + Unidad + Precio]
    IT1 --> IT2{Mas productos?}
    IT2 -->|Si| IT1
    IT2 -->|No| TOTALS[Calculo Automatico: Subtotal + IVA 19% + Total]
    
    TOTALS --> DRAFT[Estado: BORRADOR]
    
    DRAFT --> EDIT{Editar?}
    EDIT -->|Si| CREATE
    EDIT -->|No| SEND[Enviar a Aprobacion]
    
    SEND --> PENDING[Estado: PENDIENTE]
    PENDING --> EMAIL_APPR[Email automatico al Aprobador]
    EMAIL_APPR --> EMAIL_CONT[Contiene botones: Aprobar / Rechazar]
    EMAIL_CONT --> LINK[Links firmados validos 48 horas]
    
    LINK --> DECISION{Aprobador decide}
    
    DECISION -->|Aprobar desde email| APPROVED[Estado: APROBADA]
    DECISION -->|Aprobar desde sistema| APPROVED
    DECISION -->|Rechazar| REJECTED[Estado: RECHAZADA]
    
    REJECTED --> MOTIVO[Se registra motivo del rechazo]
    MOTIVO --> EMAIL_RECH[Email al solicitante con motivo]
    EMAIL_RECH --> DRAFT_EDIT[Puede editar y reenviar]
    DRAFT_EDIT --> SEND
    
    APPROVED --> EMAIL_APR[Email al solicitante confirmando]
    APPROVED --> SENT[Marcar como ENVIADA al proveedor]
    
    SENT --> RECEP{Recepcion?}
    RECEP -->|Parcial| PARTIAL[Estado: RECIBIDA PARCIAL]
    RECEP -->|Completa| COMPLETED[Estado: COMPLETADA]
    PARTIAL --> RECEP
    
    COMPLETED --> INVOICE([Registrar Factura del Proveedor])
    
    DRAFT --> CANCEL[CANCELAR]
    PENDING --> CANCEL
    APPROVED --> CANCEL
    SENT --> CANCEL

    classDef startEnd fill:#1976D2,stroke:#0D47A1,color:#fff
    classDef process fill:#e3f2fd,stroke:#1565C0,color:#1565C0
    classDef decision fill:#fff3e0,stroke:#E65100,color:#E65100
    classDef approved fill:#e8f5e9,stroke:#2E7D32,color:#2E7D32
    classDef rejected fill:#ffebee,stroke:#C62828,color:#C62828
    classDef email fill:#f3e5f5,stroke:#7B1FA2,color:#7B1FA2
    classDef cancel fill:#f5f5f5,stroke:#616161,color:#616161
    classDef pending fill:#fff8e1,stroke:#F57F17,color:#F57F17
    
    class START,INVOICE startEnd
    class CREATE,HEADER,H1,H2,H3,H4,H5,ITEMS,IT1,TOTALS,SENT,PARTIAL process
    class IT2,EDIT,DECISION,RECEP decision
    class DRAFT,DRAFT_EDIT pending
    class PENDING,SEND,EMAIL_APPR,EMAIL_CONT,LINK pending
    class APPROVED,COMPLETED,EMAIL_APR approved
    class REJECTED,MOTIVO rejected
    class EMAIL_RECH,EMAIL_APR email
    class CANCEL cancel`;
</script>

<template>
    <AppLayout title="Guía del Sistema">
        <div class="card my-3">
            <div class="card-header">
                <div class="row flex-between-center">
                    <div class="col-6 col-sm-auto d-flex align-items-center pe-0">
                        <h5 class="fs-9 mb-0 text-nowrap py-2 py-xl-0">
                            <i class="fas fa-map-signs me-2"></i>Guía del Sistema
                        </h5>
                    </div>
                    <div class="col-6 col-sm-auto ms-auto text-end ps-0">
                        <small class="text-muted">Diagramas interactivos del sistema</small>
                    </div>
                </div>
            </div>

            <div class="card-body bg-body-tertiary">
                <!-- Tabs -->
                <ul class="nav nav-tabs mb-3">
                    <li class="nav-item" v-for="tab in tabs" :key="tab.id">
                        <a 
                            class="nav-link" 
                            :class="{ active: activeTab === tab.id }"
                            href="#"
                            @click.prevent="onTabChange(tab.id)"
                        >
                            <i :class="tab.icon" class="me-1"></i>
                            <span class="d-none d-sm-inline">{{ tab.label }}</span>
                        </a>
                    </li>
                </ul>

                <!-- Tab: Vista General -->
                <div v-if="activeTab === 'general'" id="tab-general">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div class="alert alert-info py-2 mb-0 flex-grow-1 me-3">
                            <i class="fas fa-info-circle me-1"></i>
                            <strong>Vista General</strong> — Todos los módulos del sistema. Usa la rueda del mouse para zoom, arrastra para mover.
                        </div>
                        <div class="zoom-controls d-flex align-items-center gap-1">
                            <button class="btn btn-sm btn-outline-secondary" @click="zoomFit('general')" title="Ajustar">
                                <i class="fas fa-compress-arrows-alt"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-secondary" @click="zoomOut('general')" title="Alejar">
                                <i class="fas fa-search-minus"></i>
                            </button>
                            <span class="badge bg-light text-dark border px-2">{{ zoomPercent('general') }}%</span>
                            <button class="btn btn-sm btn-outline-secondary" @click="zoomIn('general')" title="Acercar">
                                <i class="fas fa-search-plus"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-secondary" @click="zoomReset('general')" title="Restablecer">
                                <i class="fas fa-undo"></i>
                            </button>
                        </div>
                    </div>
                    <div class="diagram-viewport bg-white rounded border" @wheel.prevent="onWheel($event, 'general')">
                        <div class="diagram-canvas"
                             :style="{ transform: `translate(${panOffset.general.x}px, ${panOffset.general.y}px) scale(${zoomLevels.general})`, transformOrigin: '0 0' }"
                             @mousedown.prevent="onPanStart($event, 'general')">
                            <div class="mermaid-source">{{ diagramGeneral }}</div>
                        </div>
                    </div>
                </div>

                <!-- Tab: Flujo Operativo -->
                <div v-if="activeTab === 'operativo'" id="tab-operativo">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div class="alert alert-info py-2 mb-0 flex-grow-1 me-3">
                            <i class="fas fa-info-circle me-1"></i>
                            <strong>Flujo Operativo</strong> — Configuración hasta análisis. Usa la rueda del mouse para zoom, arrastra para mover.
                        </div>
                        <div class="zoom-controls d-flex align-items-center gap-1">
                            <button class="btn btn-sm btn-outline-secondary" @click="zoomFit('operativo')" title="Ajustar">
                                <i class="fas fa-compress-arrows-alt"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-secondary" @click="zoomOut('operativo')" title="Alejar">
                                <i class="fas fa-search-minus"></i>
                            </button>
                            <span class="badge bg-light text-dark border px-2">{{ zoomPercent('operativo') }}%</span>
                            <button class="btn btn-sm btn-outline-secondary" @click="zoomIn('operativo')" title="Acercar">
                                <i class="fas fa-search-plus"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-secondary" @click="zoomReset('operativo')" title="Restablecer">
                                <i class="fas fa-undo"></i>
                            </button>
                        </div>
                    </div>
                    <div class="diagram-viewport bg-white rounded border" @wheel.prevent="onWheel($event, 'operativo')">
                        <div class="diagram-canvas"
                             :style="{ transform: `translate(${panOffset.operativo.x}px, ${panOffset.operativo.y}px) scale(${zoomLevels.operativo})`, transformOrigin: '0 0' }"
                             @mousedown.prevent="onPanStart($event, 'operativo')">
                            <div class="mermaid-source">{{ diagramOperativo }}</div>
                        </div>
                    </div>

                    <!-- Leyenda -->
                    <div class="row g-3 mt-3">
                        <div class="col-md-4 col-lg-2" v-for="(step, i) in [
                            { n: '1', title: 'Configuración', desc: 'Temporadas, CC, Proveedores, Productos', color: '#1976D2' },
                            { n: '2', title: 'Planificación', desc: 'Presupuestos, Estimaciones, Órdenes', color: '#388E3C' },
                            { n: '3', title: 'Compras', desc: 'Órdenes de Compra, Facturas', color: '#F57C00' },
                            { n: '4', title: 'Operaciones', desc: 'Egresos, Combustible, Aplicaciones', color: '#7B1FA2' },
                            { n: '5', title: 'Tesorería', desc: 'Pagos, Notas C/D, Rendiciones', color: '#C62828' },
                            { n: '6', title: 'Análisis', desc: 'Dashboards, Comparativos, Kardex', color: '#00838F' },
                        ]" :key="i">
                            <div class="card h-100 border-0 shadow-sm">
                                <div class="card-body p-2 text-center">
                                    <div class="rounded-circle d-inline-flex align-items-center justify-content-center mb-1" 
                                         :style="{ backgroundColor: step.color, width: '28px', height: '28px' }">
                                        <span class="text-white fw-bold small">{{ step.n }}</span>
                                    </div>
                                    <div class="fw-bold small">{{ step.title }}</div>
                                    <div class="text-muted" style="font-size: 0.7rem;">{{ step.desc }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tab: Egresos / Salidas -->
                <div v-if="activeTab === 'egresos'" id="tab-egresos">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div class="alert alert-info py-2 mb-0 flex-grow-1 me-3">
                            <i class="fas fa-info-circle me-1"></i>
                            <strong>Egresos / Salidas</strong> — Desde stock disponible hasta consumo por tipo. Rueda del mouse para zoom, arrastra para mover.
                        </div>
                        <div class="zoom-controls d-flex align-items-center gap-1">
                            <button class="btn btn-sm btn-outline-secondary" @click="zoomFit('egresos')" title="Ajustar">
                                <i class="fas fa-compress-arrows-alt"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-secondary" @click="zoomOut('egresos')" title="Alejar">
                                <i class="fas fa-search-minus"></i>
                            </button>
                            <span class="badge bg-light text-dark border px-2">{{ zoomPercent('egresos') }}%</span>
                            <button class="btn btn-sm btn-outline-secondary" @click="zoomIn('egresos')" title="Acercar">
                                <i class="fas fa-search-plus"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-secondary" @click="zoomReset('egresos')" title="Restablecer">
                                <i class="fas fa-undo"></i>
                            </button>
                        </div>
                    </div>
                    <div class="diagram-viewport bg-white rounded border" @wheel.prevent="onWheel($event, 'egresos')">
                        <div class="diagram-canvas"
                             :style="{ transform: `translate(${panOffset.egresos.x}px, ${panOffset.egresos.y}px) scale(${zoomLevels.egresos})`, transformOrigin: '0 0' }"
                             @mousedown.prevent="onPanStart($event, 'egresos')">
                            <div class="mermaid-source">{{ diagramEgresos }}</div>
                        </div>
                    </div>

                    <!-- Leyenda de tipos -->
                    <div class="row g-2 mt-3">
                        <div class="col-6 col-md-3" v-for="tipo in [
                            { label: 'Egreso General', icon: 'fas fa-boxes', color: '#1565C0', desc: 'Salida directa por centro de costo' },
                            { label: 'Combustible', icon: 'fas fa-gas-pump', color: '#F57F17', desc: 'Requiere maquinaria y odometro' },
                            { label: 'Agroquimicos', icon: 'fas fa-spray-can', color: '#2E7D32', desc: 'Requiere orden de aplicacion' },
                            { label: 'Fertilizantes', icon: 'fas fa-seedling', color: '#7B1FA2', desc: 'Requiere orden de fertilizante' },
                        ]" :key="tipo.label">
                            <div class="card h-100 border-0 shadow-sm">
                                <div class="card-body p-2 text-center">
                                    <i :class="tipo.icon" :style="{ color: tipo.color, fontSize: '1.2rem' }" class="mb-1"></i>
                                    <div class="fw-bold small">{{ tipo.label }}</div>
                                    <div class="text-muted" style="font-size: 0.7rem;">{{ tipo.desc }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tab: Ordenes de Compra -->
                <div v-if="activeTab === 'ordenes'" id="tab-ordenes">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div class="alert alert-info py-2 mb-0 flex-grow-1 me-3">
                            <i class="fas fa-info-circle me-1"></i>
                            <strong>Ordenes de Compra</strong> — Desde la creación hasta la recepción, incluyendo aprobación por email. Rueda del mouse para zoom.
                        </div>
                        <div class="zoom-controls d-flex align-items-center gap-1">
                            <button class="btn btn-sm btn-outline-secondary" @click="zoomFit('ordenes')" title="Ajustar">
                                <i class="fas fa-compress-arrows-alt"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-secondary" @click="zoomOut('ordenes')" title="Alejar">
                                <i class="fas fa-search-minus"></i>
                            </button>
                            <span class="badge bg-light text-dark border px-2">{{ zoomPercent('ordenes') }}%</span>
                            <button class="btn btn-sm btn-outline-secondary" @click="zoomIn('ordenes')" title="Acercar">
                                <i class="fas fa-search-plus"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-secondary" @click="zoomReset('ordenes')" title="Restablecer">
                                <i class="fas fa-undo"></i>
                            </button>
                        </div>
                    </div>
                    <div class="diagram-viewport bg-white rounded border" @wheel.prevent="onWheel($event, 'ordenes')">
                        <div class="diagram-canvas"
                             :style="{ transform: `translate(${panOffset.ordenes.x}px, ${panOffset.ordenes.y}px) scale(${zoomLevels.ordenes})`, transformOrigin: '0 0' }"
                             @mousedown.prevent="onPanStart($event, 'ordenes')">
                            <div class="mermaid-source">{{ diagramOrdenes }}</div>
                        </div>
                    </div>

                    <!-- Estados -->
                    <div class="row g-2 mt-3">
                        <div class="col-6 col-md-auto" v-for="st in [
                            { label: 'Borrador', color: 'secondary', desc: 'En edición' },
                            { label: 'Pendiente', color: 'warning', desc: 'Esperando aprobación' },
                            { label: 'Aprobada', color: 'primary', desc: 'Lista para enviar' },
                            { label: 'Rechazada', color: 'danger', desc: 'Devuelta con motivo' },
                            { label: 'Enviada', color: 'info', desc: 'Enviada al proveedor' },
                            { label: 'Recibida Parcial', color: 'warning', desc: 'Recepción incompleta' },
                            { label: 'Completada', color: 'success', desc: 'Proceso finalizado' },
                            { label: 'Cancelada', color: 'dark', desc: 'Orden anulada' },
                        ]" :key="st.label">
                            <div class="d-flex align-items-center gap-2 p-2 bg-white rounded border">
                                <span :class="'badge bg-' + st.color">{{ st.label }}</span>
                                <small class="text-muted">{{ st.desc }}</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tab: Rendiciones -->
                <div v-if="activeTab === 'rendiciones'" id="tab-rendiciones">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div class="alert alert-info py-2 mb-0 flex-grow-1 me-3">
                            <i class="fas fa-info-circle me-1"></i>
                            <strong>Rendiciones de Gastos</strong> — Flujo completo con aprobación. Rueda del mouse para zoom, arrastra para mover.
                        </div>
                        <div class="zoom-controls d-flex align-items-center gap-1">
                            <button class="btn btn-sm btn-outline-secondary" @click="zoomFit('rendiciones')" title="Ajustar">
                                <i class="fas fa-compress-arrows-alt"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-secondary" @click="zoomOut('rendiciones')" title="Alejar">
                                <i class="fas fa-search-minus"></i>
                            </button>
                            <span class="badge bg-light text-dark border px-2">{{ zoomPercent('rendiciones') }}%</span>
                            <button class="btn btn-sm btn-outline-secondary" @click="zoomIn('rendiciones')" title="Acercar">
                                <i class="fas fa-search-plus"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-secondary" @click="zoomReset('rendiciones')" title="Restablecer">
                                <i class="fas fa-undo"></i>
                            </button>
                        </div>
                    </div>
                    <div class="diagram-viewport bg-white rounded border" @wheel.prevent="onWheel($event, 'rendiciones')">
                        <div class="diagram-canvas"
                             :style="{ transform: `translate(${panOffset.rendiciones.x}px, ${panOffset.rendiciones.y}px) scale(${zoomLevels.rendiciones})`, transformOrigin: '0 0' }"
                             @mousedown.prevent="onPanStart($event, 'rendiciones')">
                            <div class="mermaid-source">{{ diagramRendiciones }}</div>
                        </div>
                    </div>

                    <!-- Estados -->
                    <div class="row g-2 mt-3">
                        <div class="col-6 col-md-auto" v-for="st in [
                            { label: 'Borrador', color: 'secondary', desc: 'En edición' },
                            { label: 'Enviada', color: 'primary', desc: 'Pendiente de aprobación' },
                            { label: 'Aprobada', color: 'success', desc: 'Lista para contabilizar' },
                            { label: 'Rechazada', color: 'danger', desc: 'Devuelta con motivo' },
                            { label: 'Pagada', color: 'info', desc: 'Proceso completado' },
                        ]" :key="st.label">
                            <div class="d-flex align-items-center gap-2 p-2 bg-white rounded border">
                                <span :class="'badge bg-' + st.color">{{ st.label }}</span>
                                <small class="text-muted">{{ st.desc }}</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tab: Facturas y Pagos -->
                <div v-if="activeTab === 'facturas'" id="tab-facturas">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div class="alert alert-info py-2 mb-0 flex-grow-1 me-3">
                            <i class="fas fa-info-circle me-1"></i>
                            <strong>Facturas y Pagos</strong> — Registro hasta pago completo. Rueda del mouse para zoom, arrastra para mover.
                        </div>
                        <div class="zoom-controls d-flex align-items-center gap-1">
                            <button class="btn btn-sm btn-outline-secondary" @click="zoomFit('facturas')" title="Ajustar">
                                <i class="fas fa-compress-arrows-alt"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-secondary" @click="zoomOut('facturas')" title="Alejar">
                                <i class="fas fa-search-minus"></i>
                            </button>
                            <span class="badge bg-light text-dark border px-2">{{ zoomPercent('facturas') }}%</span>
                            <button class="btn btn-sm btn-outline-secondary" @click="zoomIn('facturas')" title="Acercar">
                                <i class="fas fa-search-plus"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-secondary" @click="zoomReset('facturas')" title="Restablecer">
                                <i class="fas fa-undo"></i>
                            </button>
                        </div>
                    </div>
                    <div class="diagram-viewport bg-white rounded border" @wheel.prevent="onWheel($event, 'facturas')">
                        <div class="diagram-canvas"
                             :style="{ transform: `translate(${panOffset.facturas.x}px, ${panOffset.facturas.y}px) scale(${zoomLevels.facturas})`, transformOrigin: '0 0' }"
                             @mousedown.prevent="onPanStart($event, 'facturas')">
                            <div class="mermaid-source">{{ diagramFacturas }}</div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.nav-tabs .nav-link {
    font-size: 0.85rem;
    padding: 0.4rem 0.8rem;
}
.nav-tabs .nav-link.active {
    font-weight: 600;
}
.diagram-viewport {
    overflow: hidden;
    height: 65vh;
    position: relative;
    cursor: grab;
    background: #fafafa;
}
.diagram-viewport:active {
    cursor: grabbing;
}
.diagram-canvas {
    padding: 20px;
    display: inline-block;
    min-width: 100%;
}
.mermaid-rendered svg,
.mermaid-source svg {
    height: auto;
}
.zoom-controls {
    flex-shrink: 0;
}
</style>
