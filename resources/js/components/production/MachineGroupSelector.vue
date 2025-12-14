<script setup lang="ts">
import type { InputConfig } from '@/composables/useInputConfig';
import { Search } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import MachineGroupItem from './MachineGroupItem.vue';

interface MachineGroup {
    machine_group_id: number;
    name: string;
    description?: string;
    input_config?: InputConfig;
}

interface AttachedGroup {
    machine_group_id: number;
    machine_count: number;
    targets: Record<string, number | null>;
    default_targets: Record<string, number | null>;
}

interface Props {
    machineGroups: MachineGroup[];
    attachedGroups: Record<number, AttachedGroup>;
    targetDate?: string | null;
    errors?: Record<number, Record<string, string[]>>;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    (e: 'update:attachedGroups', groups: Record<number, AttachedGroup>): void;
    (e: 'update:targetDate', date: string | null): void;
}>();

const searchQuery = ref('');
const showAttachedOnly = ref(false);

const filteredGroups = computed(() => {
    let groups = props.machineGroups.map((mg) => {
        const attachedGroup = props.attachedGroups[mg.machine_group_id];
        const isAttached = !!attachedGroup;
        
        return {
            ...mg,
            attached: isAttached,
            machineCount: attachedGroup?.machine_count ?? 1,
            targets: attachedGroup?.targets ?? {},
            defaultTargets: attachedGroup?.default_targets ?? {},
        };
    });

    // Filter by search query
    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        groups = groups.filter(
            (g) =>
                g.name?.toLowerCase().includes(query) ||
                g.description?.toLowerCase().includes(query),
        );
    }

    // Filter by attached status
    if (showAttachedOnly.value) {
        groups = groups.filter((g) => g.attached);
    }

    // Sort: attached first, then alphabetically
    return groups.sort((a, b) => {
        if (a.attached && !b.attached) return -1;
        if (!a.attached && b.attached) return 1;
        return (a.name || '').localeCompare(b.name || '');
    });
});

const attachedCount = computed(() => Object.keys(props.attachedGroups).length);

function attachAll(): void {
    const newGroups: Record<number, AttachedGroup> = {};

    props.machineGroups.forEach((mg) => {
        newGroups[mg.machine_group_id] = {
            machine_group_id: mg.machine_group_id,
            machine_count: 1,
            targets: {},
            default_targets: {},
        };
    });
    
    emit('update:attachedGroups', newGroups);
}

function detachAll(): void {
    emit('update:attachedGroups', {});
}

function updateGroup(
    machineGroupId: number,
    updates: Partial<AttachedGroup> & { attached?: boolean },
): void {
    // Create a completely new object to trigger reactivity
    const newGroups: Record<number, AttachedGroup> = {};
    
    // Copy existing groups
    for (const key in props.attachedGroups) {
        newGroups[Number(key)] = { ...props.attachedGroups[Number(key)] };
    }
    
    // Handle attachment/detachment
    if ('attached' in updates) {
        if (updates.attached === false) {
            // Detach: remove from the object
            delete newGroups[machineGroupId];
        } else if (updates.attached === true && !newGroups[machineGroupId]) {
            // Attach: add new group
            newGroups[machineGroupId] = {
                machine_group_id: machineGroupId,
                machine_count: 1,
                targets: {},
                default_targets: {},
            };
        }
        emit('update:attachedGroups', newGroups);
        return;
    }

    // Update existing group properties
    if (newGroups[machineGroupId]) {
        if (updates.machine_count !== undefined) {
            newGroups[machineGroupId].machine_count = updates.machine_count;
        }
        if (updates.targets !== undefined) {
            newGroups[machineGroupId].targets = { ...updates.targets };
        }
        if (updates.default_targets !== undefined) {
            newGroups[machineGroupId].default_targets = { ...updates.default_targets };
        }
        emit('update:attachedGroups', newGroups);
    }
}
</script>

<template>
    <div
        class="rounded-lg border border-sidebar-border/70 bg-card p-6 dark:border-sidebar-border/70 dark:bg-card"
    >
        <div class="mb-2 flex items-start justify-between">
            <div>
                <h2 class="text-lg font-semibold">Machine Groups</h2>
                <p
                    class="mt-1 text-sm text-muted-foreground dark:text-muted-foreground"
                >
                    <span class="font-medium">{{ attachedCount }}</span> of
                    {{ machineGroups.length }} groups attached
                </p>
            </div>

            <div class="flex items-center gap-2">
                <button
                    type="button"
                    class="btn btn-sm"
                    @click="attachAll"
                    title="Attach all machine groups"
                >
                    Attach All
                </button>
                <button
                    type="button"
                    class="btn btn-sm btn-ghost"
                    @click="detachAll"
                    title="Detach all machine groups"
                >
                    Detach All
                </button>
            </div>
        </div>

        <!-- Target Date Section -->
        <div
            v-if="targetDate !== undefined"
            class="mb-6 border-b border-sidebar-border/70 pb-6 dark:border-sidebar-border/70"
        >
            <label class="mb-3 block text-sm font-medium">
                Target Date
                <span class="text-muted-foreground dark:text-muted-foreground"
                    >(optional)</span
                >
            </label>
            <input
                type="date"
                :value="targetDate || ''"
                @input="
                    $emit(
                        'update:targetDate',
                        ($event.target as HTMLInputElement).value || null,
                    )
                "
                class="input max-w-xs"
            />
            <p
                class="mt-2 text-xs text-muted-foreground dark:text-muted-foreground"
            >
                Set a date to create per-date targets for attached groups
            </p>
        </div>

        <!-- Search and Filter Controls -->
        <div class="mb-6 space-y-3">
            <div class="relative">
                <Search
                    class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground dark:text-muted-foreground"
                />
                <input
                    v-model="searchQuery"
                    type="text"
                    placeholder="Search machine groups..."
                    class="input pl-10"
                />
            </div>
            <div class="flex items-center gap-4">
                <label class="flex cursor-pointer items-center gap-2">
                    <input
                        type="checkbox"
                        v-model="showAttachedOnly"
                        class="h-4 w-4 rounded border-input dark:border-input"
                    />
                    <span class="text-sm">Show attached only</span>
                </label>
                <span
                    class="text-xs text-muted-foreground dark:text-muted-foreground"
                >
                    {{ filteredGroups.length }} groups shown
                </span>
            </div>
        </div>

        <!-- Machine Groups List -->
        <div class="max-h-[600px] space-y-3 overflow-y-auto pr-2">
            <div
                v-if="filteredGroups.length === 0"
                class="py-12 text-center text-muted-foreground dark:text-muted-foreground"
            >
                <p class="text-sm">
                    {{
                        searchQuery
                            ? 'No groups match your search'
                            : 'No machine groups available'
                    }}
                </p>
            </div>

            <MachineGroupItem
                v-for="group in filteredGroups"
                :key="group.machine_group_id"
                :machine-group="group"
                :attached="group.attached"
                :machine-count="group.machineCount"
                :targets="group.targets"
                :default-targets="group.defaultTargets"
                :target-date="targetDate"
                :errors="errors?.[group.machine_group_id]"
                @update:attached="
                    (value) =>
                        updateGroup(group.machine_group_id, { attached: value })
                "
                @update:machine-count="
                    (value) =>
                        updateGroup(group.machine_group_id, {
                            machine_count: value,
                        })
                "
                @update:targets="
                    (value) =>
                        updateGroup(group.machine_group_id, { targets: value })
                "
                @update:default-targets="
                    (value) =>
                        updateGroup(group.machine_group_id, {
                            default_targets: value,
                        })
                "
            />
        </div>
    </div>
</template>