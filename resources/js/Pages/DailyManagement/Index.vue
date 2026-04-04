<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import AttendanceTab from './AttendanceTab.vue';
import YieldsTab from './YieldsTab.vue';
import LaborTypesTab from './LaborTypesTab.vue';
import LaborRatesTab from './LaborRatesTab.vue';
import BonusTypesTab from './BonusTypesTab.vue';
import ScheduleTab from './ScheduleTab.vue';

const props = defineProps({
    employees: Array,
    selectedDate: String,
    activeTab: String,
    costCenters: Array,
    maxHoursPerDay: Number,
    hasAttendance: Boolean,
    attendances: Object,
    attendanceSummary: Object,
    yieldsSummary: Object,
    laborTypes: Array,
    laborRates: Array,
    bonusTypes: Array,
    laborTypesCatalog: Array,
    laborRatesCatalog: Array,
    bonusTypesCatalog: Array,
    level3s: Array,
    units: Array,
    schedule: Object,
});

const currentTab = ref(props.activeTab || 'attendance');
const dateFilter = ref(props.selectedDate);

const tabs = [
    { key: 'attendance', label: 'Asistencia', icon: 'fas fa-clipboard-check' },
    { key: 'yields', label: 'Tarjas', icon: 'fas fa-clipboard-list' },
    { key: 'labor-types', label: 'Labores', icon: 'fas fa-hard-hat' },
    { key: 'labor-rates', label: 'Tarifas', icon: 'fas fa-tags' },
    { key: 'bonus-types', label: 'Bonos', icon: 'fas fa-gift' },
    { key: 'schedule', label: 'Horario', icon: 'fas fa-clock' },
];

function switchTab(tabKey) {
    currentTab.value = tabKey;
    // Actualizar URL sin recargar (solo para tabs de catálogo)
    const url = new URL(window.location);
    url.searchParams.set('tab', tabKey);
    window.history.replaceState({}, '', url);
}

function changeDate() {
    router.get(route('daily-management.index'), {
        date: dateFilter.value,
        tab: currentTab.value,
    }, { preserveState: false });
}
</script>

<template>
    <AppLayout title="Gestión Diaria">
        <div class="card my-3">
            <div class="card-header">
                <div class="row flex-between-center">
                    <div class="col-6 col-sm-auto d-flex align-items-center pe-0">
                        <h5 class="fs-9 mb-0 text-nowrap py-2 py-xl-0">
                            <i class="fas fa-tasks me-2"></i>Gestión Diaria
                        </h5>
                    </div>
                    <div class="col-6 col-sm-auto ms-auto text-end ps-0">
                        <input type="date" v-model="dateFilter" @change="changeDate"
                            class="form-control form-control-sm d-inline-block w-auto" />
                    </div>
                </div>
            </div>

            <div class="card-body bg-body-tertiary p-0">
                <!-- Nav Pills -->
                <ul class="nav nav-pills nav-fill border-bottom px-3 pt-3 pb-0">
                    <li class="nav-item" v-for="tab in tabs" :key="tab.key">
                        <button
                            class="nav-link py-2 px-3 rounded-bottom-0"
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
                    <AttendanceTab
                        v-if="currentTab === 'attendance'"
                        :employees="employees"
                        :attendances="attendances"
                        :laborTypes="laborTypes"
                        :costCenters="costCenters"
                        :selectedDate="selectedDate"
                        :summary="attendanceSummary"
                    />

                    <YieldsTab
                        v-if="currentTab === 'yields'"
                        :employees="employees"
                        :laborTypes="laborTypes"
                        :laborRates="laborRates"
                        :bonusTypes="bonusTypes"
                        :costCenters="costCenters"
                        :selectedDate="selectedDate"
                        :hasAttendance="hasAttendance"
                        :maxHoursPerDay="maxHoursPerDay"
                        :summary="yieldsSummary"
                    />

                    <LaborTypesTab
                        v-if="currentTab === 'labor-types'"
                        :laborTypes="laborTypesCatalog"
                        :level3s="level3s"
                        :units="units"
                    />

                    <LaborRatesTab
                        v-if="currentTab === 'labor-rates'"
                        :laborRates="laborRatesCatalog"
                        :laborTypes="laborTypes"
                        :units="units"
                    />

                    <BonusTypesTab
                        v-if="currentTab === 'bonus-types'"
                        :bonusTypes="bonusTypesCatalog"
                    />

                    <ScheduleTab
                        v-if="currentTab === 'schedule'"
                        :schedule="schedule"
                    />
                </div>
            </div>
        </div>
    </AppLayout>
</template>
