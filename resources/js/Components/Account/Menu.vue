<script setup>
import { computed, ref } from 'vue';
import { usePage, Link, router } from '@inertiajs/vue3';
import JetDropdownLink from '@/Components/DropdownLink.vue';

var isDropdown = ref(false);

const path = computed(() =>usePage().props.public_path);

const user = computed(() =>usePage().props.auth.user);

const viewDropdown = () => {
	isDropdown.value = !isDropdown.value;
}

const logout = () => {
    router.post(route('logout'));
};
</script>
<template>
    <div class="app-navbar-item ms-1 ms-lg-3" id="kt_header_user_menu_toggle">
        <!--begin::Menu wrapper-->
        <div class="cursor-pointer symbol symbol-35px symbol-md-40px" @click="viewDropdown()">
            <img :src="user.profile_photo_url" alt="user" />
        </div>
        <!--begin::User account menu-->
        <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-800 menu-state-bg menu-state-color fw-semibold py-4 fs-6 w-275px" :class="{ 'show menu-account-active': isDropdown == true }" data-kt-menu="true">
            <!--begin::Menu item-->
            <div class="menu-item px-3">
                <div class="menu-content d-flex align-items-center px-3">
                    <!--begin::Avatar-->
                    <div class="symbol symbol-50px me-5">
                        <img alt="Logo" :src="user.profile_photo_url" />
                    </div>
                    <!--end::Avatar-->
                    <!--begin::Username-->
                    <div class="d-flex flex-column">
                        <div class="fw-bold d-flex align-items-center fs-5">{{user.name}}</div>
                        <a href="#" class="fw-semibold text-muted text-hover-primary fs-7">{{user.email}}</a>
                    </div>
                    <!--end::Username-->
                </div>
            </div>
            <!--end::Menu item-->

            <!--begin::Menu separator-->
            <div class="separator my-2"></div>
            <!--end::Menu separator-->

            <!--begin::Menu item-->
            <div class="menu-item px-5">
                <Link :href="'#'" class="menu-link px-5">Perfil</Link>
            </div>
            <!--end::Menu item-->
            
            <!--begin::Menu separator-->
            <div class="separator my-2"></div>
            <!--end::Menu separator-->

            <!--begin::Menu item-->
            <div class="menu-item px-5">
                <form @submit.prevent="logout">
                    <JetDropdownLink as="button" class="menu-link px-5">
                        Cerrar Sesión
                    </JetDropdownLink>
                </form>
            </div>
            <!--end::Menu item-->
        </div>
        <!--end::User account menu-->
        <!--end::Menu wrapper-->
    </div>
</template>
<style scoped>
    .menu-account-active{
        z-index: 107;
        position: fixed;
        inset: 0px 0px auto auto;
        margin: 0px;
        transform: translate(-30.6667px, 70.6667px);
    }
</style>