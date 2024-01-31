<script setup>
import { computed, ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
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
    <div class="d-flex flex-column login flex-lg-row flex-column-fluid">
                <!--begin::Aside-->
                <div class="d-flex flex-lg-row-fluid">
                    <!--begin::Content-->
                    <div class="d-flex flex-column flex-center pb-0 pb-lg-10 p-10 w-100">
                        <!--begin::Image-->
                        <img class="theme-light-show mx-auto mw-100 w-150px w-lg-300px mb-10 mb-lg-20" src="assets/media/auth/agency.png" alt="" />
                        <img class="theme-dark-show mx-auto mw-100 w-150px w-lg-300px mb-10 mb-lg-20" src="assets/media/auth/agency-dark.png" alt="" />
                        <!--end::Image-->
                        <!--begin::Title-->
                        <h1 class="text-gray-800 fs-2qx fw-bold text-center mb-7">Fast, Efficient and Productive</h1>
                        <!--end::Title-->
                        <!--begin::Text-->
                        <div class="text-gray-600 fs-base text-center fw-semibold">In this kind of post,
                        <a href="#" class="opacity-75-hover text-primary me-1">the blogger</a>introduces a person they’ve interviewed
                        <br />and provides some background information about
                        <a href="#" class="opacity-75-hover text-primary me-1">the interviewee</a>and their
                        <br />work following this is a transcript of the interview.</div>
                        <!--end::Text-->
                    </div>
                    <!--end::Content-->
                </div>
                <!--begin::Aside-->
                <!--begin::Body-->
                <div class="d-flex flex-column-fluid flex-lg-row-auto justify-content-center justify-content-lg-end p-12">
                    <!--begin::Wrapper-->
                    <div class="bg-body d-flex flex-center rounded-4 w-md-600px p-10">
                        <!--begin::Content-->
                        <div class="w-md-400px">
                            <!--begin::Form-->
                            <form @submit.prevent="submit">
                                <!--begin::Heading-->
                                <div class="text-center mb-11">
                                    <AuthenticationCardLogo :tam="190" />
                                    <!--begin::Title-->
                                    <h1 class="text-dark fw-bolder mt-10 mb-3">{{title}}</h1>
                                    <!--end::Title-->
                                    <!--begin::Subtitle-->
                                    <div class="text-gray-500 fw-semibold fs-6">Utilice sus credenciales para acceder a su cuenta</div>
                                    <!--end::Subtitle=-->
                                </div>
                                <!--begin::Heading-->

                                <!--begin::Input group=-->
                                <div class="fv-row mb-8">
                                    <!--begin::Email-->
                                    <div class="position-relative d-flex align-items-center">
                                        <span class="svg-icon svg-icon-2 position-absolute mx-4">
                                            <i class="bi bi-envelope fs-3"></i>
                                        </span>
                                        <TextInput
                                            id="email"
                                            v-model="form.email"
                                            type="email"
                                            required
                                            autofocus
                                            autocomplete="username"
                                            class="form-control bg-transparent ps-12"
                                            placeholder="Correo electrónico"
                                            :class="{'is-invalid': form.errors.email}"
                                        />
                                    </div>
                                    <!--end::Email-->
                                    <InputError class="mt-2" :message="form.errors.email" />
                                </div>
                                <!--end::Input group=-->
                                <div class="fv-row mb-3">
                                    <div class="position-relative d-flex align-items-center">
                                        <span class="svg-icon svg-icon-2 position-absolute mx-4">
                                            <i class="bi bi-lock fs-3"></i>
                                        </span>
                                        <!--begin::Password-->
                                        <TextInput
                                            id="password"
                                            v-model="form.password"
                                            :type="isView == false ? 'password' : 'text'"
                                            type="password"
                                            class="form-control bg-transparent ps-12"
                                            required
                                            autocomplete="current-password"
                                            placeholder="Contraseña"
                                            :class="{'is-invalid': form.errors.password}"
                                        />
                                        <!--end::Password-->
                                        <span class="btn btn-sm btn-icon position-absolute translate-middle top-50 end-0 me-n2" @click="visibility()">
                                            <i :class="{'bi bi-eye fs-2': isView == false, 'bi bi-eye-slash fs-2':   isView == true}"></i>
                                        </span>
                                    </div>
                                    <InputError class="mt-2" :message="form.errors.password" />
                                </div>
                                <!--end::Input group=-->

                                <div class="fv-row mb-8 mt-8">
                                    <label class="form-check form-check-inline">
                                        <Checkbox class="form-check-input" v-model:checked="form.remember" name="remember" />
                                        <span class="form-check-label fw-semibold text-gray-700 fs-base ms-1">Recuerdame</span>
                                    </label>
                                </div>
                                <!--begin::Wrapper-->
                                
                                <div class="d-flex flex-stack flex-wrap gap-3 fs-base fw-semibold mb-8">
                                    <div></div>
                                    <!--begin::Link-->
                                    <Link v-if="canResetPassword" :href="route('password.request')" class="link-primary">Olvidó su contraseña?</Link>
                                    <!--end::Link-->
                                </div>
                                <!--end::Wrapper-->
                                <!--begin::Submit button-->
                                <div class="d-grid mb-10">
                                    <primary-button type="submit" id="kt_sign_in_submit" class="btn btn-primary" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                                        <!--begin::Indicator label-->
                                        <span class="indicator-label">{{title}}</span>
                                        <!--end::Indicator label-->
                                    </primary-button>
                                </div>
                                <!--end::Submit button-->
                            </form>
                            <!--end::Form-->
                        </div>
                        <!--end::Content-->
                    </div>
                    <!--end::Wrapper-->
                </div>
                <!--end::Body-->
            </div>
</template>
<style scope>
.login { background-image: url('assets/media/auth/bg10.jpeg'); height: 100vh !important; }
</style>
