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
    email: '',
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
        <div class="row flex-center min-vh-100 py-6">
          <div class="col-sm-10 col-md-8 col-lg-6 col-xl-5 col-xxl-4"><a class="d-flex flex-center mb-4" href="../../../index.html"><img class="me-2" :src=" path + '/assets/img/icons/spot-illustrations/falcon.png'" alt="" width="58" /><span class="font-sans-serif text-primary fw-bolder fs-4 d-inline-block">falcon</span></a>
            <div class="card">
              <div class="card-body p-4 p-sm-5">
                <div class="row flex-between-center mb-2">
                  <div class="col-auto">
                    <h5>Iniciar Sesión</h5>
                  </div>
                </div>


                 <form @submit.prevent="submit">
                  <div class="mb-3">
                    <input class="form-control" type="email" v-model="form.email" required autofocus autocomplete="username" placeholder="Correo electrónico" :class="{'is-invalid': form.errors.email}" />

                    <InputError class="mt-2" :message="form.errors.email" />
                  </div>
                  <div class="mb-3">
                    <input class="form-control" type="password" v-model="form.password" required autocomplete="current-password" placeholder="Contraseña" :class="{'is-invalid': form.errors.password}" />
                    <InputError class="mt-2" :message="form.errors.password" />
                  </div>
                  <div class="row flex-between-center">
                    <div class="col-auto">
                      <div class="form-check mb-0">
                        <Checkbox class="form-check-input" v-model:checked="form.remember" name="remember" />
                        <label class="form-check-label mb-0" for="basic-checkbox">Recordar contraseña</label>
                      </div>
                    </div>
                    <div class="col-auto">
                        <Link v-if="canResetPassword" :href="route('password.request')" class="fs-10">Olvidó su contraseña?</Link>
                    </div>
                  </div>
                  <div class="mb-3">
                       <primary-button type="submit" id="kt_sign_in_submit" class="btn btn-primary d-block w-100 mt-3" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                         {{title}}
                            <!--end::Indicator label-->
                        </primary-button>
                    </div>
                </form>
              </div>
            </div>
        </div>
    </div>
</template>
