<script setup lang="ts">
import { AVAILABLE_FIELDS, type InputField } from '@/composables/useInputConfig';

interface Props {
    selectedFields: string[];
    fields?: InputField[];
}

const props = withDefaults(defineProps<Props>(), {
    fields: () => AVAILABLE_FIELDS,
});

const emit = defineEmits<{
    (e: 'update:selectedFields', fields: string[]): void;
    (e: 'fieldToggled', field: string): void;
}>();

function toggleField(field: string): void {
    emit('fieldToggled', field);
}
</script>

<template>
    <div class="space-y-2">
        <label class="text-sm font-medium">Available Fields</label>
        <div class="grid grid-cols-2 gap-2">
            <label
                v-for="field in props.fields"
                :key="field.value"
                class="flex items-center gap-2 p-3 rounded-md border cursor-pointer hover:bg-muted/50 transition-colors"
                :class="selectedFields.includes(field.value) ? 'border-primary bg-primary/5' : ''"
            >
                <input
                    type="checkbox"
                    :checked="selectedFields.includes(field.value)"
                    @change="toggleField(field.value)"
                    class="w-4 h-4 rounded border-input"
                />
                <span class="text-sm">{{ field.label }}</span>
            </label>
        </div>
    </div>
</template>

