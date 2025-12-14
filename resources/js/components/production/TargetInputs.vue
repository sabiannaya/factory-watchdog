<script setup lang="ts">
import { computed } from 'vue';
import type { InputConfig } from '@/composables/useInputConfig';

interface Props {
    inputConfig: InputConfig | null | undefined;
    targets: Record<string, number | null>;
    targetDate?: string | null;
    showDate?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    showDate: false,
});

const emit = defineEmits<{
    (e: 'update:targets', targets: Record<string, number | null>): void;
}>();

const inputFields = computed(() => {
    if (!props.inputConfig?.fields) {
        return ['qty'];
    }
    // Filter out keterangan as it's not a target field
    return props.inputConfig.fields.filter(f => f !== 'keterangan');
});

const gradeTypes = computed(() => props.inputConfig?.grade_types || []);

function updateTarget(field: string, value: number | null): void {
    const newTargets = { ...props.targets };
    if (value === null || value === undefined) {
        delete newTargets[field];
    } else {
        newTargets[field] = value;
    }
    emit('update:targets', newTargets);
}

function updateGradeTarget(gradeType: string, value: number | null): void {
    const fieldKey = `grade_${gradeType}`;
    const newTargets = { ...props.targets };
    if (value === null || value === undefined) {
        delete newTargets[fieldKey];
    } else {
        newTargets[fieldKey] = value;
    }
    emit('update:targets', newTargets);
}
</script>

<template>
    <div class="space-y-4">
        <div v-if="showDate && targetDate" class="mb-4 pb-4 border-b border-sidebar-border/70 dark:border-sidebar-border/70">
            <label class="text-sm font-medium block mb-2">Target Date</label>
            <input 
                type="date" 
                :value="targetDate" 
                class="input max-w-xs" 
                disabled
            />
            <p class="text-xs text-muted-foreground dark:text-muted-foreground mt-2">
                Set targets for each input field for this date
            </p>
        </div>

        <div class="space-y-3">
            <!-- Regular input fields -->
            <div 
                v-for="field in inputFields" 
                :key="field"
                v-show="field !== 'grades' && field !== 'grade'"
                class="flex flex-col gap-2"
            >
                <label class="text-sm font-medium capitalize">
                    {{ field.replace(/_/g, ' ') }}
                </label>
                <input 
                    type="number" 
                    min="0" 
                    :value="targets[field] ?? null"
                    @input="updateTarget(field, ($event.target as HTMLInputElement).value ? Number(($event.target as HTMLInputElement).value) : null)"
                    class="input" 
                    placeholder="Enter value"
                />
            </div>

            <!-- Grades (multiple grade types) -->
            <div v-if="inputConfig?.fields?.includes('grades') && gradeTypes.length > 0" class="space-y-3">
                <label class="text-sm font-medium block">Grades</label>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div 
                        v-for="gradeType in gradeTypes" 
                        :key="gradeType"
                        class="flex flex-col gap-2"
                    >
                        <label class="text-sm font-medium capitalize">
                            {{ gradeType }}
                        </label>
                        <input 
                            type="number" 
                            min="0" 
                            :value="targets[`grade_${gradeType}`] ?? null"
                            @input="updateGradeTarget(gradeType, ($event.target as HTMLInputElement).value ? Number(($event.target as HTMLInputElement).value) : null)"
                            class="input" 
                            placeholder="Enter value"
                        />
                    </div>
                </div>
            </div>

            <!-- Single grade field -->
            <div v-if="inputConfig?.fields?.includes('grade') && !inputConfig?.fields?.includes('grades')" class="flex flex-col gap-2">
                <label class="text-sm font-medium">Grade</label>
                <input 
                    type="number" 
                    min="0" 
                    :value="targets.grade ?? null"
                    @input="updateTarget('grade', ($event.target as HTMLInputElement).value ? Number(($event.target as HTMLInputElement).value) : null)"
                    class="input" 
                    placeholder="Enter value"
                />
            </div>
        </div>
    </div>
</template>

