<script setup>
import { computed, ref } from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import AuthenticationCard from '@/Components/AuthenticationCard.vue';
import AuthenticationCardLogo from '@/Components/AuthenticationCardLogo.vue';
import Checkbox from '@/Components/Checkbox.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';

defineProps({
    canResetPassword: Boolean,
    status: String,
});

const path = computed(() =>usePage().props.public_path);

const form = useForm({
    username: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.transform(data => ({
        ...data,
        remember: form.remember ? 'on' : '',
    })).post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};

const title = 'Iniciar Sesión';

var isView = ref(false);

const visibility = () => {
    isView.value = !isView.value;
}
</script>

<template>
    <Head :title="title" />
    <div class="login-bg min-vh-100 d-flex align-items-center justify-content-center py-5">
      <div class="col-sm-10 col-md-8 col-lg-5 col-xl-4 col-xxl-3">
        <!-- Logo -->
        <div class="text-center mb-4">
          <img :src="path + '/assets/img/icons/spot-illustrations/alisoft.png'" alt="Alisoft" width="130" class="mb-2" />
          <h4 class="font-sans-serif text-primary fw-bolder mb-0">Alisoft</h4>
          <p class="text-muted fs-10 mb-0">Sistema de Gestión Agrícola</p>
        </div>

        <!-- Card -->
        <div class="card shadow-lg border-0 login-card">
          <div class="card-body p-4 p-sm-5">
            <h5 class="mb-1">Bienvenido</h5>
            <p class="text-muted fs-10 mb-4">Ingresa tus credenciales para continuar</p>

            <form @submit.prevent="submit">
              <!-- Usuario -->
              <div class="mb-3">
                <div class="input-group">
                  <span class="input-group-text bg-light border-end-0">
                    <i class="fas fa-user text-muted"></i>
                  </span>
                  <input
                    class="form-control border-start-0"
                    type="text"
                    name="username"
                    v-model="form.username"
                    required
                    autofocus
                    autocomplete="username"
                    placeholder="Usuario"
                    :class="{'is-invalid': form.errors.username}"
                  />
                </div>
                <InputError class="mt-2" :message="form.errors.username" />
              </div>

              <!-- Contraseña -->
              <div class="mb-3">
                <div class="input-group">
                  <span class="input-group-text bg-light border-end-0">
                    <i class="fas fa-lock text-muted"></i>
                  </span>
                  <input
                    class="form-control border-start-0"
                    :type="isView ? 'text' : 'password'"
                    name="password"
                    v-model="form.password"
                    required
                    autocomplete="current-password"
                    placeholder="Contraseña"
                    :class="{'is-invalid': form.errors.password}"
                  />
                  <span class="input-group-text bg-light border-start-0 cursor-pointer" @click="visibility" style="cursor: pointer;">
                    <i class="fas" :class="isView ? 'fa-eye-slash' : 'fa-eye'" style="font-size: 0.85rem; color: #95aac9;"></i>
                  </span>
                </div>
                <InputError class="mt-2" :message="form.errors.password" />
              </div>

              <!-- Recordar + Olvidó contraseña -->
              <div class="row flex-between-center mb-3">
                <div class="col-auto">
                  <div class="form-check mb-0">
                    <Checkbox class="form-check-input" v-model:checked="form.remember" name="remember" />
                    <label class="form-check-label mb-0 fs-10">Recordar contraseña</label>
                  </div>
                </div>
                <div class="col-auto">
                  <Link v-if="canResetPassword" :href="route('password.request')" class="fs-10">¿Olvidó su contraseña?</Link>
                </div>
              </div>

              <!-- Botón -->
              <primary-button
                type="submit"
                id="kt_sign_in_submit"
                class="btn btn-primary d-block w-100 py-2 rounded-3"
                :class="{ 'opacity-25': form.processing }"
                :disabled="form.processing"
              >
                <i class="fas fa-sign-in-alt me-2" v-if="!form.processing"></i>
                <span v-if="form.processing" class="spinner-border spinner-border-sm me-2" role="status"></span>
                {{ title }}
              </primary-button>
            </form>
          </div>
        </div>

        <!-- Footer -->
        <p class="text-center text-muted fs-10 mt-3 mb-0">&copy; {{ new Date().getFullYear() }} Alisoft — Todos los derechos reservados</p>
      </div>
    </div>
</template>
