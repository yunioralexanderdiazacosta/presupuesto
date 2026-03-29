<template>
    <!-- Botón flotante para abrir/cerrar el chat -->
    <div>
        <button
            @click="toggleChat"
            class="ai-chat-fab"
            :title="isOpen ? 'Cerrar asistente' : 'Abrir asistente IA'"
        >
            <i v-if="!isOpen" class="fas fa-comment-dots"></i>
            <i v-else class="fas fa-times"></i>
        </button>

        <!-- Panel del chat -->
        <transition name="chat-slide">
            <div v-if="isOpen" class="ai-chat-panel card shadow-lg">
                <!-- Header -->
                <div class="ai-chat-header card-header d-flex align-items-center gap-2 py-2 px-3">
                    <i class="fas fa-comment-dots text-primary"></i>
                    <div>
                        <div class="fw-semibold small">Asistente IA</div>
                        <div class="text-muted" style="font-size: 0.7rem;">Consulta sobre tus datos</div>
                    </div>
                    <button @click="clearChat" class="btn btn-sm btn-link ms-auto p-0 text-muted" title="Limpiar conversación">
                        <i class="fas fa-trash-alt fa-xs"></i>
                    </button>
                </div>

                <!-- Historial de mensajes -->
                <div class="ai-chat-messages" ref="messagesContainer">
                    <!-- Mensaje de bienvenida -->
                    <div v-if="messages.length === 0" class="ai-chat-welcome text-center py-4 px-3">
                        <i class="fas fa-comment-dots fa-2x text-primary mb-2"></i>
                        <p class="small text-muted mb-1">Hola, soy tu asistente de datos agrícolas.</p>
                        <p class="small text-muted mb-3">Puedes preguntarme sobre tus inversiones, facturas, insumos y más.</p>
                        <div class="d-flex flex-column gap-1">
                            <button
                                v-for="suggestion in suggestions"
                                :key="suggestion"
                                @click="sendSuggestion(suggestion)"
                                class="btn btn-sm btn-outline-secondary text-start"
                                style="font-size: 0.75rem;"
                            >
                                {{ suggestion }}
                            </button>
                        </div>
                    </div>

                    <!-- Mensajes de la conversación -->
                    <div
                        v-for="(msg, index) in messages"
                        :key="index"
                        class="ai-chat-message"
                        :class="msg.role === 'user' ? 'ai-chat-message--user' : 'ai-chat-message--assistant'"
                    >
                        <div class="ai-chat-bubble">
                            <span v-if="msg.role === 'assistant'" v-html="formatMarkdown(msg.content)"></span>
                            <span v-else>{{ msg.content }}</span>
                        </div>
                        <div class="ai-chat-time text-muted" style="font-size: 0.65rem;">
                            {{ msg.time }}
                        </div>
                    </div>

                    <!-- Indicador de carga -->
                    <div v-if="isLoading" class="ai-chat-message ai-chat-message--assistant">
                        <div class="ai-chat-bubble ai-chat-bubble--typing">
                            <span class="typing-dot"></span>
                            <span class="typing-dot"></span>
                            <span class="typing-dot"></span>
                        </div>
                    </div>
                </div>

                <!-- Input -->
                <div class="ai-chat-input-area border-top p-2">
                    <div class="d-flex gap-2 align-items-end">
                        <textarea
                            v-model="question"
                            @keydown.enter.exact.prevent="sendMessage"
                            @keydown.enter.shift.exact="newLine"
                            class="form-control form-control-sm"
                            placeholder="Escribe tu pregunta... (Enter para enviar)"
                            rows="2"
                            style="resize: none; font-size: 0.8rem;"
                            :disabled="isLoading"
                        ></textarea>
                        <button
                            @click="sendMessage"
                            :disabled="isLoading || !question.trim()"
                            class="btn btn-primary btn-sm"
                            style="min-width: 36px; height: 56px;"
                        >
                            <i v-if="!isLoading" class="fas fa-paper-plane fa-xs"></i>
                            <i v-else class="fas fa-spinner fa-spin fa-xs"></i>
                        </button>
                    </div>
                    <div class="text-muted mt-1" style="font-size: 0.65rem;">
                        <i class="fas fa-lock-alt fa-xs me-1"></i>Solo consulta tus datos de la temporada activa
                    </div>
                </div>
            </div>
        </transition>
    </div>
</template>

<script setup>
import { ref, nextTick } from 'vue';
import axios from 'axios';

const isOpen          = ref(false);
const isLoading       = ref(false);
const question        = ref('');
const messages        = ref([]);
const messagesContainer = ref(null);

const suggestions = [
    '¿Cuánto se ha gastado en facturas esta temporada?',
    '¿Cuáles son las inversiones pendientes?',
    '¿Cuántas salidas de combustible hay?',
    '¿Qué proveedores tienen facturas este período?',
];

function toggleChat() {
    isOpen.value = !isOpen.value;
}

function clearChat() {
    messages.value = [];
    question.value = '';
}

function newLine() {
    question.value += '\n';
}

function formatTime() {
    return new Date().toLocaleTimeString('es-CL', { hour: '2-digit', minute: '2-digit' });
}

/**
 * Convierte saltos de línea y viñetas básicas de Markdown a HTML seguro.
 * No usa una librería completa para evitar dependencias.
 */
function formatMarkdown(text) {
    if (!text) return '';
    return text
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
        .replace(/\*(.*?)\*/g, '<em>$1</em>')
        .replace(/^• (.+)$/gm, '<li>$1</li>')
        .replace(/^- (.+)$/gm, '<li>$1</li>')
        .replace(/(<li>.*<\/li>)/s, '<ul class="mb-0 ps-3">$1</ul>')
        .replace(/\n/g, '<br>');
}

async function sendSuggestion(text) {
    question.value = text;
    await sendMessage();
}

async function sendMessage() {
    const text = question.value.trim();
    if (!text || isLoading.value) return;

    // Añadir mensaje del usuario
    messages.value.push({ role: 'user', content: text, time: formatTime() });
    question.value = '';
    isLoading.value = true;
    await scrollToBottom();

    try {
        const response = await axios.post(route('api.ai-chat'), { question: text });

        if (response.data.answer) {
            messages.value.push({
                role: 'assistant',
                content: response.data.answer,
                time: formatTime(),
            });
        } else if (response.data.error) {
            messages.value.push({
                role: 'assistant',
                content: '⚠️ ' + response.data.error,
                time: formatTime(),
            });
        }
    } catch (error) {
        const errMsg = error.response?.data?.error ?? 'Error de conexión. Por favor intenta de nuevo.';
        messages.value.push({
            role: 'assistant',
            content: '⚠️ ' + errMsg,
            time: formatTime(),
        });
    } finally {
        isLoading.value = false;
        await scrollToBottom();
    }
}

async function scrollToBottom() {
    await nextTick();
    if (messagesContainer.value) {
        messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight;
    }
}
</script>

<style scoped>
/* ── Botón flotante ────────────────────────────────────────── */
.ai-chat-fab {
    position: fixed;
    bottom: 24px;
    right: 24px;
    width: 52px;
    height: 52px;
    border-radius: 50%;
    background: #2563eb;
    color: #fff;
    border: none;
    font-size: 1.2rem;
    box-shadow: 0 4px 16px rgba(37, 99, 235, 0.4);
    cursor: pointer;
    z-index: 1050;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: transform 0.2s, box-shadow 0.2s;
}
.ai-chat-fab:hover {
    transform: scale(1.08);
    box-shadow: 0 6px 20px rgba(37, 99, 235, 0.5);
}

/* ── Panel del chat ────────────────────────────────────────── */
.ai-chat-panel {
    position: fixed;
    bottom: 88px;
    right: 24px;
    width: 360px;
    max-height: 560px;
    display: flex;
    flex-direction: column;
    z-index: 1049;
    border-radius: 12px;
    overflow: hidden;
}

/* ── Header ───────────────────────────────────────────────── */
.ai-chat-header {
    background: #f8f9fa;
    border-bottom: 1px solid #e9ecef;
}

/* ── Área de mensajes ─────────────────────────────────────── */
.ai-chat-messages {
    flex: 1;
    overflow-y: auto;
    padding: 12px;
    background: #fff;
    min-height: 200px;
    max-height: 360px;
}

/* ── Burbujas de mensajes ─────────────────────────────────── */
.ai-chat-message {
    display: flex;
    flex-direction: column;
    margin-bottom: 10px;
}
.ai-chat-message--user {
    align-items: flex-end;
}
.ai-chat-message--assistant {
    align-items: flex-start;
}
.ai-chat-bubble {
    max-width: 85%;
    padding: 8px 12px;
    border-radius: 12px;
    font-size: 0.82rem;
    line-height: 1.5;
    word-break: break-word;
}
.ai-chat-message--user .ai-chat-bubble {
    background: #2563eb;
    color: #fff;
    border-bottom-right-radius: 4px;
}
.ai-chat-message--assistant .ai-chat-bubble {
    background: #f1f3f5;
    color: #212529;
    border-bottom-left-radius: 4px;
}
.ai-chat-time {
    font-size: 0.65rem;
    margin-top: 2px;
    padding: 0 4px;
}

/* ── Indicador de "escribiendo" ───────────────────────────── */
.ai-chat-bubble--typing {
    display: flex;
    align-items: center;
    gap: 4px;
    padding: 10px 14px;
}
.typing-dot {
    width: 7px;
    height: 7px;
    background: #868e96;
    border-radius: 50%;
    animation: typing-bounce 1.2s infinite;
}
.typing-dot:nth-child(2) { animation-delay: 0.2s; }
.typing-dot:nth-child(3) { animation-delay: 0.4s; }
@keyframes typing-bounce {
    0%, 80%, 100% { transform: scale(1);   opacity: 0.5; }
    40%            { transform: scale(1.3); opacity: 1;   }
}

/* ── Animación de entrada del panel ──────────────────────── */
.chat-slide-enter-active,
.chat-slide-leave-active {
    transition: opacity 0.2s, transform 0.2s;
}
.chat-slide-enter-from,
.chat-slide-leave-to {
    opacity: 0;
    transform: translateY(16px) scale(0.97);
}

/* ── Responsive ───────────────────────────────────────────── */
@media (max-width: 480px) {
    .ai-chat-panel {
        width: calc(100vw - 16px);
        right: 8px;
        bottom: 80px;
    }
    .ai-chat-fab {
        right: 16px;
        bottom: 16px;
    }
}
</style>
