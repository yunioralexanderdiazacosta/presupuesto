<script setup>
import { ref, computed } from 'vue';
import axios from 'axios';
import Swal from 'sweetalert2';
import Modal from '@/Components/Modal.vue';

const currentTeam = ref(null); // { id, name } — se recibe directo en load(), no como prop (evita leer el valor viejo por timing de reactividad)
const loading = ref(false);
const saving = ref(false);
const catalog = ref([]);
const disabledModules = ref([]);

const groupedCatalog = computed(() => {
    const groups = {};
    catalog.value.forEach((mod) => {
        if (!groups[mod.section]) groups[mod.section] = [];
        groups[mod.section].push(mod);
    });
    return groups;
});

const isDisabled = (key) => disabledModules.value.includes(key);

const toggleModule = (key) => {
    if (isDisabled(key)) {
        disabledModules.value = disabledModules.value.filter(k => k !== key);
    } else {
        disabledModules.value = [...disabledModules.value, key];
    }
};

const toggleSection = (section, enableAll) => {
    const keys = groupedCatalog.value[section].map(m => m.key);
    if (enableAll) {
        disabledModules.value = disabledModules.value.filter(k => !keys.includes(k));
    } else {
        disabledModules.value = [...new Set([...disabledModules.value, ...keys])];
    }
};

const load = async (team) => {
    if (!team?.id) return;
    currentTeam.value = team;
    loading.value = true;
    try {
        const { data } = await axios.get(route('teams.modules', team.id));
        catalog.value = data.catalog;
        disabledModules.value = data.disabled_modules;
    } catch (error) {
        console.error('Error al cargar módulos:', error);
        Swal.fire('Error', 'No se pudieron cargar los módulos de la empresa', 'error');
    } finally {
        loading.value = false;
    }
};

const save = async () => {
    if (!currentTeam.value?.id) return;
    saving.value = true;
    try {
        await axios.post(route('teams.modules.update', currentTeam.value.id), {
            disabled_modules: disabledModules.value,
        });
        $('#teamModulesModal').modal('hide');
        Swal.fire({
            icon: 'success',
            title: 'Módulos actualizados',
            showConfirmButton: false,
            timer: 1200,
        });
    } catch (error) {
        console.error('Error al guardar módulos:', error);
        Swal.fire('Error', 'No se pudieron guardar los cambios', 'error');
    } finally {
        saving.value = false;
    }
};

defineExpose({ load });
</script>

<template>
    <Modal :id="'teamModulesModal'" maxWidth="lg">
        <template #header>
            <h5 class="mb-0">Módulos habilitados — {{ currentTeam?.name }}</h5>
            <span class="text-muted" style="font-size: 0.82rem;">Desmarca los módulos a los que esta empresa NO debe tener acceso</span>
        </template>
        <template #body>
            <div v-if="loading" class="text-center py-4">
                <i class="fas fa-spinner fa-spin fa-lg text-muted"></i>
            </div>
            <div v-else>
                <div v-for="(mods, section) in groupedCatalog" :key="section" class="mb-3">
                    <div class="d-flex align-items-center justify-content-between border-bottom pb-1 mb-2">
                        <h6 class="text-primary mb-0">{{ section }}</h6>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-sm btn-link p-0" style="font-size: 0.75rem;" @click="toggleSection(section, true)">Habilitar todos</button>
                            <button type="button" class="btn btn-sm btn-link text-danger p-0" style="font-size: 0.75rem;" @click="toggleSection(section, false)">Deshabilitar todos</button>
                        </div>
                    </div>
                    <div class="row g-2">
                        <div class="col-md-6 col-lg-4" v-for="mod in mods" :key="mod.key">
                            <div class="form-check">
                                <input
                                    type="checkbox"
                                    class="form-check-input"
                                    :id="'mod-' + mod.key"
                                    :checked="!isDisabled(mod.key)"
                                    @change="toggleModule(mod.key)"
                                >
                                <label class="form-check-label small" :for="'mod-' + mod.key">{{ mod.label }}</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </template>
        <template #footer>
            <button type="button" data-bs-dismiss="modal" class="btn btn-light me-2">Cerrar</button>
            <button type="button" @click="save" :disabled="saving || loading" class="btn btn-primary">
                <span v-if="saving"><i class="fas fa-spinner fa-spin me-1"></i>Guardando...</span>
                <span v-else>Guardar cambios</span>
            </button>
        </template>
    </Modal>
</template>
