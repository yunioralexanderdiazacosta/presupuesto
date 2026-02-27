<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
    logs: Object,
    term: String,
});

const search = ref(props.term ?? '');

watch(search, (value) => {
    router.get(route('login-logs.index'), { term: value }, {
        preserveState: true,
        replace: true,
    });
});

// Formatear fecha en formato chileno
const formatDate = (dateStr) => {
    if (!dateStr) return '-';
    const d = new Date(dateStr);
    return d.toLocaleDateString('es-CL', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
    }) + ' ' + d.toLocaleTimeString('es-CL', {
        hour: '2-digit',
        minute: '2-digit',
    });
};

// Extraer navegador/dispositivo resumido del user_agent
const parseUserAgent = (ua) => {
    if (!ua) return '-';
    if (ua.includes('Chrome') && !ua.includes('Edg')) return 'Chrome';
    if (ua.includes('Edg')) return 'Edge';
    if (ua.includes('Firefox')) return 'Firefox';
    if (ua.includes('Safari') && !ua.includes('Chrome')) return 'Safari';
    if (ua.includes('Opera') || ua.includes('OPR')) return 'Opera';
    return 'Otro';
};

// Extraer SO
const parseOS = (ua) => {
    if (!ua) return '-';
    if (ua.includes('Windows')) return 'Windows';
    if (ua.includes('Mac OS')) return 'macOS';
    if (ua.includes('Linux')) return 'Linux';
    if (ua.includes('Android')) return 'Android';
    if (ua.includes('iPhone') || ua.includes('iPad')) return 'iOS';
    return 'Otro';
};
</script>

<template>
    <AppLayout title="Registro de Accesos">
        <div class="card my-3">
            <div class="card-header">
                <div class="row flex-between-center">
                    <div class="col-6 col-sm-auto d-flex align-items-center pe-0">
                        <h5 class="fs-9 mb-0 text-nowrap py-2 py-xl-0">
                            <i class="fas fa-users-cog me-2"></i>Registro de Accesos
                        </h5>
                    </div>
                    <div class="col-6 col-sm-auto ms-auto text-end ps-0">
                        <div class="d-flex align-items-center gap-2 justify-content-end">
                            <span class="badge bg-soft-primary text-primary">
                                {{ logs.total }} registros
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-body bg-body-tertiary">
                <!-- Buscador -->
                <div class="row mb-3">
                    <div class="col-md-4">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                            <input
                                type="text"
                                class="form-control form-control-sm"
                                placeholder="Buscar por nombre, email o empresa..."
                                v-model="search"
                            />
                        </div>
                    </div>
                </div>

                <!-- Tabla -->
                <div class="table-responsive">
                    <table class="table table-sm table-striped table-hover mb-0" style="font-size: 0.82rem;">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 5%;">#</th>
                                <th style="width: 18%;">Usuario</th>
                                <th style="width: 18%;">Email</th>
                                <th style="width: 15%;">Empresa</th>
                                <th style="width: 16%;">Fecha/Hora</th>
                                <th style="width: 11%;">IP</th>
                                <th style="width: 8%;">Navegador</th>
                                <th style="width: 9%;">SO</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(log, index) in logs.data" :key="log.id">
                                <td class="text-muted">{{ (logs.current_page - 1) * logs.per_page + index + 1 }}</td>
                                <td class="fw-semibold">{{ log.user?.name ?? '-' }}</td>
                                <td>{{ log.user?.email ?? '-' }}</td>
                                <td>
                                    <span v-if="log.user?.team" class="badge bg-soft-info text-info">
                                        {{ log.user.team.name }}
                                    </span>
                                    <span v-else class="text-muted">-</span>
                                </td>
                                <td>{{ formatDate(log.logged_in_at) }}</td>
                                <td>
                                    <code class="small">{{ log.ip_address ?? '-' }}</code>
                                </td>
                                <td>{{ parseUserAgent(log.user_agent) }}</td>
                                <td>{{ parseOS(log.user_agent) }}</td>
                            </tr>
                            <tr v-if="logs.data.length === 0">
                                <td colspan="8" class="text-center text-muted py-4">
                                    <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                                    No se encontraron registros de acceso
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Paginación -->
                <nav v-if="logs.last_page > 1" class="mt-3">
                    <ul class="pagination pagination-sm justify-content-center mb-0">
                        <li 
                            class="page-item" 
                            :class="{ disabled: !logs.prev_page_url }"
                        >
                            <a 
                                class="page-link" 
                                href="#"
                                @click.prevent="logs.prev_page_url && router.get(logs.prev_page_url)"
                            >
                                <i class="fas fa-chevron-left"></i>
                            </a>
                        </li>
                        <li 
                            v-for="page in logs.last_page" 
                            :key="page" 
                            class="page-item"
                            :class="{ active: page === logs.current_page }"
                        >
                            <a 
                                class="page-link" 
                                href="#"
                                @click.prevent="router.get(logs.path + '?page=' + page + (search ? '&term=' + search : ''))"
                            >
                                {{ page }}
                            </a>
                        </li>
                        <li 
                            class="page-item" 
                            :class="{ disabled: !logs.next_page_url }"
                        >
                            <a 
                                class="page-link" 
                                href="#"
                                @click.prevent="logs.next_page_url && router.get(logs.next_page_url)"
                            >
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        </li>
                    </ul>
                </nav>
            </div>
        </div>
    </AppLayout>
</template>
