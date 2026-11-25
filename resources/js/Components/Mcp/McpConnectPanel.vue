<script setup>
import { computed, ref } from 'vue';
import { usePage } from '@inertiajs/vue3';
import axios from 'axios';
import Swal from 'sweetalert2';

const serverUrl = computed(() => usePage().props.mcp?.url ?? '');

const assistants = [
    { key: 'claude', label: 'Claude', icon: 'fas fa-comment-dots' },
    { key: 'chatgpt', label: 'ChatGPT', icon: 'fas fa-comments' },
    { key: 'copilot', label: 'Copilot (VS Code)', icon: 'fas fa-code' },
    { key: 'gemini', label: 'Gemini CLI', icon: 'fas fa-terminal' },
];
const active = ref('claude');

const questions = [
    '¿Cuánto tenemos pendiente de pago esta temporada?',
    '¿Qué facturas están vencidas y cuánto suman?',
    '¿Cuánto le debemos a cada proveedor?',
    '¿Qué pagos hicimos este mes?',
];

const copy = async (text, message = 'Copiado') => {
    try {
        await navigator.clipboard.writeText(text);
        Swal.fire({ icon: 'success', title: message, showConfirmButton: false, timer: 1000 });
    } catch (error) {
        Swal.fire('Error', 'No se pudo copiar. Selecciona el texto y cópialo manualmente.', 'error');
    }
};

const copilotConfig = computed(() => JSON.stringify({ servers: { alisoft: { type: 'http', url: serverUrl.value } } }, null, 2));
const geminiConfig = computed(() => JSON.stringify({ mcpServers: { alisoft: { httpUrl: serverUrl.value } } }, null, 2));

const connections = ref([]);
const loading = ref(false);

const formatDate = (iso) => (iso ? new Date(iso).toLocaleString('es-CL', { dateStyle: 'medium', timeStyle: 'short' }) : '');

const load = async () => {
    loading.value = true;
    try {
        const response = await axios.get(route('mcp.connections.index'));
        connections.value = response.data;
    } catch (error) {
        console.error('Error al cargar las conexiones:', error);
    } finally {
        loading.value = false;
    }
};

const revoke = async (connection) => {
    const result = await Swal.fire({
        icon: 'warning',
        title: `¿Quitar el acceso de ${connection.name}?`,
        text: 'El asistente dejará de poder consultar tus datos de inmediato. Podrás conectarlo de nuevo cuando quieras.',
        showCancelButton: true,
        confirmButtonText: 'Sí, quitar acceso',
        cancelButtonText: 'Cancelar',
    });

    if (!result.isConfirmed) {
        return;
    }

    try {
        await axios.delete(route('mcp.connections.revoke', { client: connection.id }));
        connections.value = connections.value.filter((c) => c.id !== connection.id);
        Swal.fire({ icon: 'success', title: 'Acceso revocado', showConfirmButton: false, timer: 1200 });
    } catch (error) {
        Swal.fire('Error', 'No se pudo revocar el acceso. Intenta nuevamente.', 'error');
    }
};

defineExpose({ load });
</script>

<template>
    <div>
        <p class="text-muted fs-10 mb-3">
            Pregúntale a tu asistente de IA por tus facturas y pagos a proveedores. El asistente solo ve lo que tu
            usuario puede ver, en <strong>modo lectura</strong>: no puede crear, modificar ni eliminar nada.
        </p>

        <label class="form-label small mb-1">URL del servidor MCP</label>
        <div class="input-group input-group-sm mb-3">
            <input type="text" class="form-control" :value="serverUrl" readonly @focus="$event.target.select()" />
            <button type="button" class="btn btn-falcon-default" @click="copy(serverUrl, 'URL copiada')">
                <span class="fas fa-copy me-1"></span>Copiar
            </button>
        </div>

        <ul class="nav nav-tabs mb-3">
            <li v-for="assistant in assistants" :key="assistant.key" class="nav-item">
                <button type="button" class="nav-link py-1 px-3 fs-10" :class="{ active: active === assistant.key }" @click="active = assistant.key">
                    <span :class="assistant.icon" class="me-1"></span>{{ assistant.label }}
                </button>
            </li>
        </ul>

        <ol v-if="active === 'claude'" class="fs-10 ps-3">
            <li class="mb-1">Entra a <strong>claude.ai</strong> (web o app de escritorio) y abre <strong>Personalizar → Conectores</strong>.</li>
            <li class="mb-1">Haz clic en <strong>«+ Agregar»</strong> y luego en <strong>«Agregar conector personalizado»</strong>.</li>
            <li class="mb-1">Nombre: <strong>Alisoft</strong>. URL del servidor MCP remoto: pega la URL de arriba y haz clic en <strong>«Continuar»</strong>. Deja la autenticación que Claude detecta por defecto y haz clic en <strong>«Agregar»</strong>.</li>
            <li class="mb-1">Haz clic en <strong>«Conectar»</strong>. Se abrirá el portal: inicia sesión con tu usuario y autoriza el acceso.</li>
            <li class="mb-1">En un chat, haz clic en <strong>«+» → Conectores</strong>, activa Alisoft y pregunta, por ejemplo: «{{ questions[0] }}».</li>
            <li class="text-muted">En planes Team o Enterprise, el propietario de la organización debe agregar el conector primero en Configuración de la organización → Conectores; luego cada usuario solo hace clic en «Conectar».</li>
        </ol>

        <ol v-else-if="active === 'chatgpt'" class="fs-10 ps-3">
            <li class="mb-1">En ChatGPT abre <strong>Configuración → Apps / Conectores → Configuración avanzada</strong> y activa el <strong>modo desarrollador</strong>.</li>
            <li class="mb-1">Crea una nueva app o conector: nombre <strong>Alisoft</strong>, pega la URL del servidor MCP y elige autenticación <strong>OAuth</strong>.</li>
            <li class="mb-1">Haz clic en <strong>«Conectar»</strong>, inicia sesión en el portal con tu usuario y autoriza el acceso.</li>
            <li class="mb-1">En un chat, activa Alisoft desde el menú <strong>«+»</strong> y pregunta, por ejemplo: «{{ questions[1] }}».</li>
            <li class="text-muted">La disponibilidad depende de tu plan de ChatGPT; en planes de empresa puede requerir que un administrador lo habilite.</li>
        </ol>

        <div v-else-if="active === 'copilot'" class="fs-10">
            <p class="mb-2">En VS Code con GitHub Copilot, agrega este servidor en <strong>MCP: Add Server → HTTP</strong> o en tu archivo <code>mcp.json</code>:</p>
            <div class="position-relative mb-2">
                <pre class="bg-body-tertiary border rounded p-2 mb-0 small"><code>{{ copilotConfig }}</code></pre>
                <button type="button" class="btn btn-falcon-default btn-sm position-absolute top-0 end-0 m-1" @click="copy(copilotConfig)">
                    <span class="fas fa-copy"></span>
                </button>
            </div>
            <p class="mb-1">Inicia el servidor desde VS Code, inicia sesión en el portal cuando se abra el navegador y autoriza el acceso. Luego usa el modo <strong>Agente</strong> del chat de Copilot.</p>
        </div>

        <div v-else class="fs-10">
            <p class="mb-2">Para usuarios técnicos: agrega este bloque en <code>~/.gemini/settings.json</code>:</p>
            <div class="position-relative mb-2">
                <pre class="bg-body-tertiary border rounded p-2 mb-0 small"><code>{{ geminiConfig }}</code></pre>
                <button type="button" class="btn btn-falcon-default btn-sm position-absolute top-0 end-0 m-1" @click="copy(geminiConfig)">
                    <span class="fas fa-copy"></span>
                </button>
            </div>
            <p class="mb-1">Abre Gemini CLI, ejecuta <code>/mcp auth alisoft</code>, inicia sesión en el portal y autoriza el acceso.</p>
        </div>

        <p class="text-muted fs-11 mb-3">Los nombres de los menús pueden variar levemente según la versión de cada asistente.</p>

        <div class="border rounded-3 p-2 mb-3">
            <p class="fw-semibold fs-10 mb-1"><span class="fas fa-lightbulb me-1 text-warning"></span>Ejemplos de preguntas</p>
            <ul class="fs-10 mb-0 ps-3">
                <li v-for="question in questions" :key="question">{{ question }}</li>
            </ul>
        </div>

        <div class="border rounded-3 p-2">
            <p class="fw-semibold fs-10 mb-2"><span class="fas fa-shield-alt me-1 text-success"></span>Conexiones activas</p>

            <p v-if="loading" class="text-muted fs-10 mb-0">Cargando...</p>
            <p v-else-if="!connections.length" class="text-muted fs-10 mb-0">No tienes asistentes conectados.</p>

            <ul v-else class="list-unstyled mb-0">
                <li v-for="connection in connections" :key="connection.id" class="d-flex align-items-center justify-content-between py-1 border-bottom">
                    <div class="fs-10">
                        <strong>{{ connection.name }}</strong>
                        <div class="text-muted fs-11">Autorizado: {{ formatDate(connection.authorized_at) }}</div>
                    </div>
                    <button type="button" class="btn btn-falcon-default btn-sm" @click="revoke(connection)">
                        <span class="fas fa-ban me-1"></span>Quitar acceso
                    </button>
                </li>
            </ul>

            <p class="text-muted fs-11 mt-2 mb-0">
                Los datos que consulta el asistente se envían al proveedor de IA (Anthropic, OpenAI, Microsoft, Google, etc.), según sus propias políticas de privacidad.
            </p>
        </div>
    </div>
</template>
