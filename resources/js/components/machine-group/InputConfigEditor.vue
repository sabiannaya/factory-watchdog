<script setup lang="ts">
import { watch } from 'vue';
import { useInputConfig, type InputConfig } from '@/composables/useInputConfig';
import FieldSelector from './FieldSelector.vue';
import GradeTypesManager from './GradeTypesManager.vue';
import InputConfigPreview from './InputConfigPreview.vue';

interface Props {
    modelValue: InputConfig;
    showPreview?: boolean;
    showErrors?: boolean;
    errorMessage?: string;
}

const props = withDefaults(defineProps<Props>(), {
    showPreview: true,
    showErrors: true,
    errorMessage: '',
});

const emit = defineEmits<{
    (e: 'update:modelValue', config: InputConfig): void;
}>();

const {
    selectedFields,
    gradeTypes,
    hasGrades,
    toggleField,
    addGradeType,
    removeGradeType,
    buildInputConfig,
    reset,
} = useInputConfig(props.modelValue);

// Initialize from props
reset(props.modelValue);

// Watch for changes and emit updates
watch([selectedFields, gradeTypes], () => {
    const built = buildInputConfig();
    try {
        const current = JSON.parse(JSON.stringify(props.modelValue || {}));
        if (JSON.stringify(built) !== JSON.stringify(current)) {
            emit('update:modelValue', built);
        }
    } catch (e) {
        emit('update:modelValue', built);
    }
}, { deep: true });

// Watch for external changes to modelValue
watch(() => props.modelValue, (newValue) => {
    try {
        const built = buildInputConfig();
        if (JSON.stringify(newValue || {}) !== JSON.stringify(built)) {
            if (newValue) reset(newValue);
        }
    } catch (e) {
        if (newValue) reset(newValue);
    }
}, { deep: true });

function handleFieldToggled(field: string): void {
    toggleField(field);
}

function handleAddGradeType(type: string): void {
    if (type && !gradeTypes.value.includes(type)) {
        gradeTypes.value.push(type);
    }
}

function handleRemoveGradeType(type: string): void {
    removeGradeType(type);
}
</script>

<template>
    <div class="space-y-4">
        <div>
            <h3 class="text-sm font-medium">Input Configuration</h3>
            <p class="text-sm text-muted-foreground">Select which input fields this machine group will use</p>
        </div>

        <FieldSelector
            :selected-fields="selectedFields"
            @field-toggled="handleFieldToggled"
        />

        <GradeTypesManager
            v-if="hasGrades"
            :grade-types="gradeTypes"
            @add="handleAddGradeType"
            @remove="handleRemoveGradeType"
        />

        <div v-if="showErrors && errorMessage" class="text-sm text-red-500">
            {{ errorMessage }}
        </div>

        <InputConfigPreview
            v-if="showPreview"
            :config="buildInputConfig()"
        />
    </div>
</template>

