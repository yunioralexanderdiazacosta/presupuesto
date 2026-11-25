<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue';
import McpConnectPanel from './McpConnectPanel.vue';

const modal = ref(null);
const panel = ref(null);

const refreshConnections = () => panel.value?.load();

onMounted(() => {
    $(modal.value).on('shown.bs.modal', refreshConnections);
});

onBeforeUnmount(() => {
    $(modal.value).off('shown.bs.modal', refreshConnections);
});

function handleClose() {
    $('#mcpConnectModal').modal('hide');
}
</script>

<template>
    <div ref="modal" class="modal fade" id="mcpConnectModal" tabindex="-1" aria-labelledby="mcpConnectModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="mcpConnectModalLabel">
                        <i class="fas fa-plug me-2"></i>Conecta Alisoft con tu asistente de IA
                    </h5>
                    <button type="button" class="btn-close btn-close-white" aria-label="Cerrar" @click="handleClose"></button>
                </div>

                <div class="modal-body">
                    <McpConnectPanel ref="panel" />
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" @click="handleClose">
                        <i class="fas fa-times me-1"></i>Cerrar
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
