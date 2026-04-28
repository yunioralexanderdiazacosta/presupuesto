<script setup>
import { ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import BonusesTab from './BonusesTab.vue';
import DiscountsTab from './DiscountsTab.vue';
import BonusTypesTab from './BonusTypesTab.vue';
import DiscountTypesTab from './DiscountTypesTab.vue';

const props = defineProps({
    bonuses: Array,
    discounts: Array,
    contracts: Array,
    months: Array,
    bonusTypesSelect: Array,
    discountTypesSelect: Array,
    bonusTypesCatalog: Array,
    discountTypesCatalog: Array,
    costCenters: Array,
    groupings: Array,
    laborTypes: Array,
    level3s: Array,
    activeTab: { type: String, default: 'bonuses' },
});

const tabs = [
    { key: 'bonuses',        label: 'Bonos',              icon: 'fas fa-hand-holding-usd' },
    { key: 'discounts',      label: 'Descuentos',         icon: 'fas fa-minus-circle' },
    { key: 'bonus-types',    label: 'Tipos de Bono',      icon: 'fas fa-tags' },
    { key: 'discount-types', label: 'Tipos de Descuento', icon: 'fas fa-tags' },
];

const currentTab = ref(props.activeTab);

function switchTab(key) {
    currentTab.value = key;
    const url = new URL(window.location);
    url.searchParams.set('tab', key);
    window.history.replaceState({}, '', url);
}
</script>

<template>
    <AppLayout title="Bonos y Descuentos Mensuales">
        <div class="card my-3">
            <div class="card-header" style="background-color: #f5f0e8; border-bottom-color: #e8dfc8;">
                <div class="row flex-between-center">
                    <div class="col-auto d-flex align-items-center pe-0">
                        <h5 class="fs-9 mb-0 text-nowrap py-2 py-xl-0" style="color: #7a6a3e;">
                            <i class="fas fa-hand-holding-usd me-2"></i>Bonos y Descuentos Mensuales
                        </h5>
                    </div>
                </div>
            </div>

            <div class="card-body bg-body-tertiary p-0">
                <!-- Nav Pills -->
                <ul class="nav nav-pills nav-fill border-bottom px-3 pt-3 pb-0">
                    <li class="nav-item" v-for="tab in tabs" :key="tab.key">
                        <button
                            class="nav-link py-2 px-3 rounded-bottom-0 bonus-tab-btn"
                            :class="{ active: currentTab === tab.key }"
                            @click="switchTab(tab.key)"
                        >
                            <i :class="tab.icon" class="me-1"></i>
                            <span class="d-none d-md-inline">{{ tab.label }}</span>
                        </button>
                    </li>
                </ul>

                <!-- Tab Content -->
                <div class="p-3">
                    <BonusesTab
                        v-if="currentTab === 'bonuses'"
                        :bonuses="bonuses"
                        :contracts="contracts"
                        :bonusTypes="bonusTypesSelect"
                        :months="months"
                        :costCenters="costCenters"
                        :groupings="groupings"
                        :laborTypes="laborTypes"
                        :level3s="level3s"
                    />
                    <DiscountsTab
                        v-if="currentTab === 'discounts'"
                        :discounts="discounts"
                        :contracts="contracts"
                        :discountTypes="discountTypesSelect"
                        :months="months"
                    />
                    <BonusTypesTab
                        v-if="currentTab === 'bonus-types'"
                        :bonusTypes="bonusTypesCatalog"
                    />
                    <DiscountTypesTab
                        v-if="currentTab === 'discount-types'"
                        :discountTypes="discountTypesCatalog"
                    />
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.bonus-tab-btn.active {
    background-color: #e07b39 !important;
    border-color: #e07b39 !important;
}
</style>
