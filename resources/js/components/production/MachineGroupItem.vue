<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { ChevronDown, ChevronRight } from 'lucide-vue-next';
import TargetInputs from './TargetInputs.vue';
import type { InputConfig } from '@/composables/useInputConfig';

interface Props {
    machineGroup: {
        machine_group_id: number;
        name: string;
        description?: string;
        input_config?: InputConfig;
    };
    attached: boolean;
    machineCount: number;
    targets: Record<string, number | null>;
    defaultTargets: Record<string, number | null>;
    targetDate?: string | null;
    errors?: Record<string, string[]>;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    (e: 'update:attached', attached: boolean): void;
    (e: 'update:machineCount', count: number): void;
    (e: 'update:targets', targets: Record<string, number | null>): void;
    (e: 'update:defaultTargets', defaultTargets: Record<string, number | null>): void;
}>();

const expanded = ref(false);

// Auto-expand when attached, but ignore the first change caused by parent initialization
const _attachedInitialIgnored = { value: false };
watch(() => props.attached, (newVal, oldVal) => {
    if (!_attachedInitialIgnored.value) {
        _attachedInitialIgnored.value = true;
        return;
    }

    if (newVal && !expanded.value) {
        expanded.value = true;
    }
});

const hasTargets = computed(() => {
    return Object.values(props.targets).some(v => v !== null && v !== undefined);
});

const targetBadgeClass = computed(() => {
    if (props.targetDate && hasTargets.value) {
        return 'bg-emerald-600/20 text-emerald-700 dark:text-emerald-400';
    }
    if (!props.targetDate && hasTargets.value) {
        return 'bg-sky-600/20 text-sky-700 dark:text-sky-400';
    }
    return 'bg-gray-600/20 text-gray-700 dark:text-gray-400';
});

const targetBadgeText = computed(() => {
    if (props.targetDate && hasTargets.value) return 'Per-date';
    if (!props.targetDate && hasTargets.value) return 'Default';
    return 'No target';
});

function toggleExpanded(): void {
    expanded.value = !expanded.value;
}
</script>

<template>
    <div 
        class="rounded-lg border transition-all"
        :class="attached 
            ? 'border-sidebar-border/70 dark:border-sidebar-border/70 bg-muted/40 dark:bg-muted/40 border-ring/30 dark:border-ring/30' 
            : 'border-sidebar-border/30 dark:border-sidebar-border/30 bg-muted/5 dark:bg-muted/5 opacity-60'"
    >
        <div class="p-4">
            <div class="flex items-start justify-between gap-4">
                <button 
                    type="button"
                    @click="attached ? toggleExpanded() : null"
                    :disabled="!attached"
                    class="flex items-start gap-3 flex-1 text-left transition-colors"
                    :class="attached 
                        ? 'group cursor-pointer hover:text-foreground dark:hover:text-foreground' 
                        : 'cursor-not-allowed opacity-60'"
                >
                    <component 
                        :is="expanded ? ChevronDown : ChevronRight" 
                        class="h-5 w-5 mt-0.5 transition-colors"
                        :class="attached 
                            ? 'text-muted-foreground dark:text-muted-foreground group-hover:text-foreground dark:group-hover:text-foreground' 
                            : 'text-muted-foreground/50 dark:text-muted-foreground/50'"
                    />
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-1 flex-wrap">
                            <h3 
                                class="font-semibold text-base"
                                :class="attached 
                                    ? 'text-foreground dark:text-foreground' 
                                    : 'text-muted-foreground dark:text-muted-foreground'"
                            >
                                {{ machineGroup.name }}
                            </h3>
                            <div v-if="attached" class="flex gap-1">
                                <span 
                                    class="text-xs px-2 py-0.5 rounded-full whitespace-nowrap"
                                    :class="targetBadgeClass"
                                >
                                    {{ targetBadgeText }}
                                </span>
                            </div>
                        </div>
                        <p 
                            class="text-sm line-clamp-1"
                            :class="attached 
                                ? 'text-muted-foreground dark:text-muted-foreground' 
                                : 'text-muted-foreground/60 dark:text-muted-foreground/60'"
                        >
                            {{ machineGroup.description || 'No description' }}
                        </p>
                    </div>
                </button>

                <label class="flex items-center gap-2 cursor-pointer flex-shrink-0">
                    <input 
                        type="checkbox" 
                        :checked="attached"
                        @change="emit('update:attached', ($event.target as HTMLInputElement).checked)"
                        class="w-4 h-4 rounded border-input dark:border-input" 
                    />
                    <span 
                        class="text-sm font-medium transition-colors"
                        :class="attached ? 'text-foreground dark:text-foreground' : 'text-muted-foreground dark:text-muted-foreground'"
                    >
                        {{ attached ? 'Attached' : 'Attach' }}
                    </span>
                </label>
            </div>

            <!-- Expanded Content -->
            <div v-if="attached && expanded" class="mt-4 pt-4 border-t border-sidebar-border/70 dark:border-sidebar-border/70">
                <div class="space-y-4">
                    <div class="flex flex-col gap-2">
                        <label class="text-sm font-medium">Machine Count <span class="text-red-500">*</span></label>
                        <input 
                            type="number" 
                            min="1" 
                            :value="machineCount"
                            @input="emit('update:machineCount', Number(($event.target as HTMLInputElement).value))"
                            class="input" 
                        />
                        <div v-if="errors?.machine_count" class="space-y-1">
                            <div 
                                v-for="(err, i) in errors.machine_count" 
                                :key="i" 
                                class="text-xs text-destructive dark:text-destructive"
                            >
                                {{ err }}
                            </div>
                        </div>
                    </div>

                    <!-- Production-level Default Targets Section -->
                    <div class="space-y-2 pb-4 border-b border-sidebar-border/70 dark:border-sidebar-border/70">
                        <div class="flex items-center gap-2 mb-2">
                            <h4 class="text-sm font-semibold">Production Defaults</h4>
                            <span class="text-xs px-2 py-0.5 rounded-full bg-sky-600/20 text-sky-700 dark:text-sky-400">
                                Per machine group
                            </span>
                        </div>
                        <p class="text-xs text-muted-foreground dark:text-muted-foreground mb-3">
                            Set default target values for this machine group (used unless overridden daily)
                        </p>
                        <TargetInputs
                            :input-config="machineGroup.input_config"
                            :targets="defaultTargets"
                            :show-date="false"
                            @update:targets="emit('update:defaultTargets', $event)"
                        />
                        <div v-if="errors?.default_targets" class="space-y-1">
                            <div 
                                v-for="(err, i) in errors.default_targets" 
                                :key="i" 
                                class="text-xs text-destructive dark:text-destructive"
                            >
                                {{ err }}
                            </div>
                        </div>
                    </div>

                    <!-- Per-Date Targets Section (only if target date is set) -->
                    <div v-if="targetDate" class="space-y-2">
                        <div class="flex items-center gap-2 mb-2">
                            <h4 class="text-sm font-semibold">Per-Date Targets</h4>
                            <span class="text-xs px-2 py-0.5 rounded-full bg-emerald-600/20 text-emerald-700 dark:text-emerald-400">
                                Override for {{ targetDate }}
                            </span>
                        </div>
                        <p class="text-xs text-muted-foreground dark:text-muted-foreground mb-3">
                            Override defaults for this specific date
                        </p>
                        <TargetInputs
                            :input-config="machineGroup.input_config"
                            :targets="targets"
                            :target-date="targetDate"
                            :show-date="false"
                            @update:targets="emit('update:targets', $event)"
                        />
                        <div v-if="errors?.targets" class="space-y-1">
                            <div 
                                v-for="(err, i) in errors.targets" 
                                :key="i" 
                                class="text-xs text-destructive dark:text-destructive"
                            >
                                {{ err }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Compact Summary when attached but collapsed -->
            <div 
                v-if="attached && !expanded" 
                class="mt-3 pt-3 border-t border-sidebar-border/70 dark:border-sidebar-border/70 flex gap-4 text-sm text-muted-foreground dark:text-muted-foreground"
            >
                <span><strong class="text-foreground dark:text-foreground">{{ machineCount }}</strong> machines</span>
                <span v-if="hasTargets">
                    <strong class="text-foreground dark:text-foreground">{{ Object.values(targets).filter(v => v !== null).length }}</strong> targets set
                </span>
            </div>
        </div>
    </div>
</template>

