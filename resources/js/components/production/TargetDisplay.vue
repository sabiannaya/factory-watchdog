<script setup lang="ts">
import { computed } from 'vue';
import type { InputConfig } from '@/composables/useInputConfig';

interface Props {
    inputConfig: InputConfig | null | undefined;
    defaultTargets: Record<string, number | null>;
    perDateTargets?: Record<string, Record<string, number | null>>;
}

const props = withDefaults(defineProps<Props>(), {
    inputConfig: null,
    defaultTargets: () => ({}),
    perDateTargets: () => ({}),
});

const inputFields = computed(() => {
    let fields = props.inputConfig?.fields ?? [];

    if (!fields.length) {
        fields = ['qty_normal', 'qty_reject'];
    }

    if (fields.includes('qty')) {
        fields = fields.filter((f) => f !== 'qty');
        if (!fields.includes('qty_normal')) fields.push('qty_normal');
        if (!fields.includes('qty_reject')) fields.push('qty_reject');
    }

    return fields.filter((f: string) => f !== 'keterangan');
});

function normalizeTargetMap(map: Record<string, number | null> = {}): Record<string, number | null> {
    const normalized: Record<string, number | null> = { ...map };

    if (normalized.qty !== undefined) {
        if (normalized.qty_normal === undefined) {
            normalized.qty_normal = normalized.qty;
        }
        delete normalized.qty;
    }

    return normalized;
}

const gradeTypes = computed(() => props.inputConfig?.grade_types || []);

function getFieldLabel(field: string): string {
    return field.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
}

function getTargetValue(field: string, date?: string): number | null {
    if (date && (props.perDateTargets ?? {})[date]) {
        const normalizedPerDate = normalizeTargetMap(props.perDateTargets![date]);
        return normalizedPerDate[field] ?? null;
    }
    const normalizedDefaults = normalizeTargetMap(props.defaultTargets ?? {});
    return normalizedDefaults[field] ?? null;
}
</script>

<template>
    <div class="space-y-4">
        <!-- Default Targets -->
        <div v-if="Object.keys(defaultTargets ?? {}).length > 0 || Object.keys(perDateTargets ?? {}).length === 0" class="space-y-3">
            <h5 class="text-sm font-medium text-muted-foreground dark:text-muted-foreground">Default Targets</h5>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <!-- Regular fields -->
                <div 
                    v-for="field in inputFields" 
                    :key="field"
                    v-show="field !== 'grades' && field !== 'grade'"
                    class="flex items-center justify-between p-2 rounded-md bg-muted/20 dark:bg-muted/20"
                >
                    <span class="text-sm capitalize">{{ getFieldLabel(field) }}</span>
                    <span class="text-sm font-medium">{{ (defaultTargets ?? {})[field] ?? '-' }}</span>
                </div>

                <!-- Grades -->
                <template v-if="(inputConfig?.fields ?? []).includes('grades') && gradeTypes.length > 0">
                    <div 
                        v-for="gradeType in gradeTypes" 
                        :key="gradeType"
                        class="flex items-center justify-between p-2 rounded-md bg-muted/20 dark:bg-muted/20"
                    >
                        <span class="text-sm capitalize">{{ gradeType }}</span>
                        <span class="text-sm font-medium">{{ (defaultTargets ?? {})[`grade_${gradeType}`] ?? '-' }}</span>
                    </div>
                </template>

                <!-- Single grade -->
                <div 
                    v-if="(inputConfig?.fields ?? []).includes('grade') && !(inputConfig?.fields ?? []).includes('grades')"
                    class="flex items-center justify-between p-2 rounded-md bg-muted/20 dark:bg-muted/20"
                >
                    <span class="text-sm">Grade</span>
                    <span class="text-sm font-medium">{{ (defaultTargets ?? {}).grade ?? '-' }}</span>
                </div>
            </div>
        </div>

        <!-- Per-Date Targets -->
        <div v-if="Object.keys(perDateTargets ?? {}).length > 0" class="space-y-4">
            <h5 class="text-sm font-medium text-muted-foreground dark:text-muted-foreground">Per-Date Targets</h5>
            <div 
                v-for="(targets, date) in perDateTargets" 
                :key="date"
                class="space-y-3 p-3 rounded-md border border-sidebar-border/70 dark:border-sidebar-border/70"
            >
                <div class="flex items-center justify-between mb-2">
                    <span class="text-sm font-medium">{{ date }}</span>
                    <span class="text-xs px-2 py-0.5 rounded-full bg-emerald-600/20 text-emerald-700 dark:text-emerald-400">
                        Per-date
                    </span>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <!-- Regular fields -->
                    <div 
                        v-for="field in inputFields" 
                        :key="field"
                        v-show="field !== 'grades' && field !== 'grade'"
                        class="flex items-center justify-between p-2 rounded-md bg-muted/10 dark:bg-muted/10"
                    >
                        <span class="text-sm capitalize">{{ getFieldLabel(field) }}</span>
                        <span class="text-sm font-medium">{{ targets[field] ?? '-' }}</span>
                    </div>

                    <!-- Grades -->
                    <template v-if="inputConfig?.fields?.includes('grades') && gradeTypes.length > 0">
                        <div 
                            v-for="gradeType in gradeTypes" 
                            :key="gradeType"
                            class="flex items-center justify-between p-2 rounded-md bg-muted/10 dark:bg-muted/10"
                        >
                            <span class="text-sm capitalize">{{ gradeType }}</span>
                            <span class="text-sm font-medium">{{ targets[`grade_${gradeType}`] ?? '-' }}</span>
                        </div>
                    </template>

                    <!-- Single grade -->
                    <div 
                        v-if="inputConfig?.fields?.includes('grade') && !inputConfig?.fields?.includes('grades')"
                        class="flex items-center justify-between p-2 rounded-md bg-muted/10 dark:bg-muted/10"
                    >
                        <span class="text-sm">Grade</span>
                        <span class="text-sm font-medium">{{ targets.grade ?? '-' }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- No targets message -->
        <div 
            v-if="Object.keys(defaultTargets).length === 0 && Object.keys(perDateTargets).length === 0" 
            class="text-sm text-muted-foreground dark:text-muted-foreground italic"
        >
            No targets set
        </div>
    </div>
</template>

