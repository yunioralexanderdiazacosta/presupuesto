<script setup>
    import Multiselect from '@vueform/multiselect';
	import TextInput from '@/Components/TextInput.vue';
	import InputError from '@/Components/InputError.vue';

	defineProps({
		form: Object
	});

    const colorPalette = [
        '#1e40af', '#2563eb', '#3b82f6', '#0ea5e9',
        '#0d9488', '#10b981', '#22c55e', '#84cc16',
        '#eab308', '#f59e0b', '#f97316', '#ef4444',
        '#dc2626', '#be185d', '#a855f7', '#7c3aed',
        '#6366f1', '#6b7280', '#374151', '#0f172a',
    ];
</script>
<template>
    <div class="fv-row">
        <label class="col-form-label">Nombre</label>
        <TextInput
            id="name"
            v-model="form.name"
            class="form-control form-control-solid"
            type="text"
            :class="{'is-invalid': form.errors.name}"
        />
        <InputError class="mt-2" :message="form.errors.name" />
    </div>

    <div class="fv-row">
        <label for="observations" class="col-form-label">Observaciones</label>
        <textarea v-model="form.observations" rows="3" class="form-control form-control-lg form-control-solid mb-3 mb-lg-0" :class="{'is-invalid': form.errors.observations }"></textarea>
        <InputError class="mt-2" :message="form.errors.observations" />
    </div>

       
    <div class="fv-row">
        <label for="month" class="col-form-label">Mes de inicio</label>
        <Multiselect
            :placeholder="'Seleccione mes de inicio'"
            v-model="form.month_id"
            :close-on-select="true"
            :options="$page.props.months"
            class="multiselect-blue form-control"
            :class="{'is-invalid': form.errors.month_id}"
            :searchable="true"
        />
        <InputError class="mt-2" :message="form.errors.month_id" />
    </div>

    <div class="fv-row">
        <label class="col-form-label">Color de etiqueta</label>
        <div class="d-flex flex-wrap gap-2 mt-1">
            <button
                v-for="color in colorPalette"
                :key="color"
                type="button"
                class="season-color-swatch"
                :class="{ 'selected': form.color === color }"
                :style="{ backgroundColor: color }"
                @click="form.color = form.color === color ? '' : color"
                v-tooltip="color"
            ></button>
        </div>
        <div v-if="form.color" class="mt-2 d-flex align-items-center gap-2">
            <span class="d-inline-flex align-items-center px-3 py-1 rounded-pill text-white fw-bold" :style="{ background: `linear-gradient(90deg, ${form.color}cc 0%, ${form.color} 100%)` }" style="font-size: 0.8rem;">
                <span class="fas fa-calendar-alt me-2"></span>
                Vista previa
            </span>
        </div>
    </div>
</template>
<style>
.multiselect-blue {
    --ms-bg: var(--kt-input-solid-bg) !important;
    --ms-border-color: var(--kt-input-solid-bg);
    --ms-py: 3px !important;
    --ms-tag-bg: #2c7be5;
    --ms-tag-color: var(--kt-primary);
    --ms-option-bg-selected: var(--kt-primary);
    --ms-option-bg-selected-pointed: var(--kt-primary);
}

.multiselect-tags-search, .multiselect-search{
    background: var(--kt-input-solid-bg) !important;
}

.season-color-swatch {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    border: 2px solid transparent;
    cursor: pointer;
    transition: all 0.15s ease;
    padding: 0;
    outline: none;
}
.season-color-swatch:hover {
    transform: scale(1.15);
    box-shadow: 0 0 0 2px rgba(0,0,0,0.15);
}
.season-color-swatch.selected {
    border-color: #fff;
    box-shadow: 0 0 0 3px currentColor, 0 0 0 3px rgba(0,0,0,0.3);
    transform: scale(1.15);
}
</style>