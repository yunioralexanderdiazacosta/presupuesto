<script setup>
import { computed, ref } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';

const props = defineProps({
    client: Object,
    scopes: Array,
    authToken: String,
    state: String,
    userName: String,
    teamName: String,
});

const path = computed(() => usePage().props.public_path);
const csrf = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
const submitting = ref(false);

// Formularios HTML normales (no Inertia): la respuesta es una redirección hacia el asistente de IA.
const hostsLabel = computed(() => (props.client.redirect_hosts ?? []).join(', '));
</script>

<template>
    <Head title="Autorizar conexión con IA" />
    <div class="login-bg min-vh-100 d-flex align-items-center justify-content-center py-5">
        <div class="col-sm-10 col-md-8 col-lg-6 col-xl-5 col-xxl-4">
            <div class="text-center mb-4">
                <img :src="path + '/assets/img/icons/spot-illustrations/alisoft.png'" alt="Alisoft" width="110" class="mb-2" />
                <h4 class="font-sans-serif text-primary fw-bolder mb-0">Alisoft</h4>
            </div>

            <div class="card shadow-lg border-0">
                <div class="card-body p-4 p-sm-5">
                    <h5 class="mb-1">
                        <i class="fas fa-plug me-2 text-primary"></i>Autorizar conexión con IA
                    </h5>
                    <p class="text-muted fs-10 mb-4">
                        <strong>{{ client.name }}</strong> quiere acceder a tus datos de Alisoft.
                    </p>

                    <div class="border rounded-3 p-3 mb-3 bg-body-tertiary fs-10">
                        <div class="d-flex align-items-center mb-1">
                            <i class="fas fa-user me-2 text-muted"></i>
                            <span>Cuenta: <strong>{{ userName }}</strong></span>
                        </div>
                        <div v-if="teamName" class="d-flex align-items-center">
                            <i class="fas fa-users me-2 text-muted"></i>
                            <span>Empresa: <strong>{{ teamName }}</strong></span>
                        </div>
                    </div>

                    <p class="fw-semibold fs-10 mb-2">Este asistente podrá:</p>
                    <ul class="list-unstyled fs-10 mb-3">
                        <li v-for="scope in scopes" :key="scope.id" class="d-flex align-items-start mb-1">
                            <i class="fas fa-check text-success me-2 mt-1"></i>
                            <span>{{ scope.description }}</span>
                        </li>
                    </ul>

                    <p class="fw-semibold fs-10 mb-2">Este asistente NO podrá:</p>
                    <ul class="list-unstyled fs-10 mb-3">
                        <li class="d-flex align-items-start mb-1">
                            <i class="fas fa-times text-danger me-2 mt-1"></i>
                            <span>Crear, modificar ni eliminar información.</span>
                        </li>
                        <li class="d-flex align-items-start mb-1">
                            <i class="fas fa-times text-danger me-2 mt-1"></i>
                            <span>Ver más de lo que tu usuario ya puede ver en el sistema.</span>
                        </li>
                    </ul>

                    <div class="alert alert-warning fs-10 py-2 mb-4" role="alert">
                        <i class="fas fa-exclamation-triangle me-1"></i>
                        Autoriza solo si tú iniciaste esta conexión desde tu asistente de IA. Los datos que consulte se
                        enviarán al proveedor de ese asistente. Puedes quitar el acceso cuando quieras desde
                        «Conectar con IA».
                        <div v-if="hostsLabel" class="mt-1 text-muted">Volverás a: <strong>{{ hostsLabel }}</strong></div>
                    </div>

                    <div class="d-flex gap-2">
                        <form method="POST" action="/oauth/authorize" class="flex-fill" @submit="submitting = true">
                            <input type="hidden" name="_token" :value="csrf" />
                            <input type="hidden" name="_method" value="DELETE" />
                            <input type="hidden" name="state" :value="state" />
                            <input type="hidden" name="client_id" :value="client.id" />
                            <input type="hidden" name="auth_token" :value="authToken" />
                            <button type="submit" class="btn btn-falcon-default w-100" :disabled="submitting">Cancelar</button>
                        </form>
                        <form method="POST" action="/oauth/authorize" class="flex-fill" @submit="submitting = true">
                            <input type="hidden" name="_token" :value="csrf" />
                            <input type="hidden" name="state" :value="state" />
                            <input type="hidden" name="client_id" :value="client.id" />
                            <input type="hidden" name="auth_token" :value="authToken" />
                            <button type="submit" class="btn btn-primary w-100" :disabled="submitting">
                                <i class="fas fa-check me-1"></i>Autorizar
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
