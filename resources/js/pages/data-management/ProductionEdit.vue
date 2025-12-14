<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { useForm } from '@inertiajs/vue3';
import { reactive, computed, ref } from 'vue';
import { type BreadcrumbItem } from '@/types';
import { Search, ChevronDown, ChevronRight } from 'lucide-vue-next';

const props = defineProps<{ production: { production_id: number; production_name: string; status: string }, machine_groups?: Array<any>, attached_groups?: Record<string, any> }>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Data Management', href: '/data-management/production' },
    { title: 'Production', href: '/data-management/production' },
    { title: props.production?.production_name ?? 'Edit', href: window.location.pathname },
];

const action = `/data-management/production/${props.production.production_id}`;

// Build initial machine group attachments from props
const initialMachineGroups = (props.machine_groups ?? []).map((mg) => {
    const attached = props.attached_groups?.[mg.machine_group_id];
    return {
        machine_group_id: mg.machine_group_id,
        name: mg.name,
        description: mg.description,
        attached: !!attached,
        production_machine_group_id: attached?.production_machine_group_id ?? null,
        machine_count: attached?.machine_count ?? 1,
        target_value: attached?.default_target ?? null,
    };
});

const form = useForm({
    production_name: props.production.production_name,
    status: props.production.status,
    machine_groups: initialMachineGroups,
    target_date: null,
});

const onSubmitted = () => {
    router.get('/data-management/production');
};

// ease template typing for form errors
const errors: any = (form as any).errors;

// client-side validation errors
const clientErrors = reactive({ groups: {} as Record<string | number, string[]>, form: '' });

// Search and filter state
const searchQuery = ref('');
const showAttachedOnly = ref(false);
const expandedGroups = ref<Set<number>>(new Set());

// Computed filtered and sorted machine groups
const filteredGroups = computed(() => {
    let groups = form.machine_groups;

    // Filter by search query
    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        groups = groups.filter((g: any) => 
            g.name?.toLowerCase().includes(query) || 
            g.description?.toLowerCase().includes(query)
        );
    }

    // Filter by attached status
    if (showAttachedOnly.value) {
        groups = groups.filter((g: any) => g.attached);
    }

    // Sort: attached first, then alphabetically
    return groups.sort((a: any, b: any) => {
        if (a.attached && !b.attached) return -1;
        if (!a.attached && b.attached) return 1;
        return (a.name || '').localeCompare(b.name || '');
    });
});

const attachedCount = computed(() => form.machine_groups.filter((g: any) => g.attached).length);

function toggleGroup(groupId: number) {
    if (expandedGroups.value.has(groupId)) {
        expandedGroups.value.delete(groupId);
    } else {
        expandedGroups.value.add(groupId);
    }
}

function clearClientErrors() {
    clientErrors.form = '';
    clientErrors.groups = {} as Record<string | number, string[]>;
}

function validateClient(): boolean {
    clearClientErrors();
    let ok = true;

    // If target_date is provided, ensure each attached group has a target_value
    const hasDate = !!form.target_date;

    form.machine_groups.forEach((g: any) => {
        if (!g.attached) return;

        if (!g.machine_count || Number(g.machine_count) < 1) {
            clientErrors.groups[g.machine_group_id] = clientErrors.groups[g.machine_group_id] || [];
            clientErrors.groups[g.machine_group_id].push('Machine count must be at least 1');
            ok = false;
        }

        if (hasDate) {
            const val = g.target_value;
            if (val === null || val === undefined || val === '') {
                clientErrors.groups[g.machine_group_id] = clientErrors.groups[g.machine_group_id] || [];
                clientErrors.groups[g.machine_group_id].push('Target is required when a target date is set');
                ok = false;
            } else if (Number(val) < 0) {
                clientErrors.groups[g.machine_group_id] = clientErrors.groups[g.machine_group_id] || [];
                clientErrors.groups[g.machine_group_id].push('Target must be 0 or greater');
                ok = false;
            }
        }
    });

    return ok;
}

const submit = () => {
    // Only send minimal machine_groups fields
    // Client-side validation
    if (!validateClient()) {
        return;
    }

    const payload = {
        production_name: form.production_name,
        status: form.status,
        target_date: form.target_date,
        machine_groups: form.machine_groups.filter((g: any) => g.attached).map((g: any) => ({
            machine_group_id: g.machine_group_id,
            machine_count: g.machine_count,
            target_value: g.target_value,
        })),
    };

    clearClientErrors();

    (form as any).put(action, payload, {
        preserveState: false,
        onSuccess: () => onSubmitted(),
    });
};
</script>

<template>
    <Head :title="`Edit Production — ${props.production.production_name}`" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6">
            <div class="max-w-4xl mx-auto">
                <!-- Header -->
                <div class="mb-8">
                    <h1 class="text-3xl font-bold">Edit Production</h1>
                    <p class="text-muted-foreground mt-2">Configure production details and attach machine groups with targets</p>
                </div>

                <form @submit.prevent="submit" class="space-y-6">
                    <!-- Production Basics Card -->
                    <div class="rounded-lg border border-sidebar-border/70 bg-card p-6">
                        <h2 class="text-lg font-semibold mb-5">Production Details</h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div class="flex flex-col gap-2">
                                <label class="text-sm font-medium">Production Name</label>
                                <input v-model="form.production_name" type="text" class="input" />
                                <div v-if="errors.production_name" class="text-xs text-destructive">{{ errors.production_name }}</div>
                            </div>

                            <div class="flex flex-col gap-2">
                                <label class="text-sm font-medium">Status</label>
                                <select v-model="form.status" class="appearance-none rounded-md border bg-popover text-popover-foreground px-3 py-2 pr-10 text-sm shadow-sm placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2">
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                                <div v-if="errors.status" class="text-xs text-destructive">{{ errors.status }}</div>
                            </div>
                        </div>
                    </div>

                    <!-- Machine Groups Card -->
                    <div class="rounded-lg border border-sidebar-border/70 bg-card p-6">
                        <div class="flex items-start justify-between mb-2">
                            <div>
                                <h2 class="text-lg font-semibold">Machine Groups</h2>
                                <p class="text-sm text-muted-foreground mt-1">
                                    <span class="font-medium">{{ attachedCount }}</span> of {{ form.machine_groups.length }} groups attached
                                </p>
                            </div>
                        </div>

                        <!-- Target Date Section -->
                        <div class="mb-6 pb-6 border-b border-sidebar-border/70">
                            <label class="text-sm font-medium block mb-3">Target Date <span class="text-muted-foreground">(optional)</span></label>
                            <input type="date" v-model="form.target_date" class="input max-w-xs" />
                            <p class="text-xs text-muted-foreground mt-2">Set a date to create per-date targets for attached groups</p>
                        </div>

                        <!-- Search and Filter Controls -->
                        <div class="mb-6 space-y-3">
                            <div class="relative">
                                <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                                <input 
                                    v-model="searchQuery" 
                                    type="text" 
                                    placeholder="Search machine groups..." 
                                    class="input pl-10"
                                />
                            </div>
                            <div class="flex items-center gap-4">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" v-model="showAttachedOnly" class="w-4 h-4 rounded border-input" />
                                    <span class="text-sm">Show attached only</span>
                                </label>
                                <span class="text-xs text-muted-foreground">{{ filteredGroups.length }} groups shown</span>
                            </div>
                        </div>

                        <!-- Machine Groups List -->
                        <div class="space-y-3 max-h-[600px] overflow-y-auto pr-2">
                            <div v-if="filteredGroups.length === 0" class="text-center py-12 text-muted-foreground">
                                <p class="text-sm">{{ searchQuery ? 'No groups match your search' : 'No machine groups available' }}</p>
                            </div>

                            <div v-for="(mg, idx) in filteredGroups" :key="mg.machine_group_id" class="rounded-lg border border-sidebar-border/70 transition-all" :class="mg.attached ? 'bg-muted/40 border-ring/30' : 'bg-muted/10'">
                                <!-- Collapsible Group Header -->
                                <div class="p-4">
                                    <div class="flex items-start justify-between gap-4">
                                        <button 
                                            type="button"
                                            @click="toggleGroup(mg.machine_group_id)"
                                            class="flex items-start gap-3 flex-1 text-left group"
                                        >
                                            <component 
                                                :is="expandedGroups.has(mg.machine_group_id) ? ChevronDown : ChevronRight" 
                                                class="h-5 w-5 text-muted-foreground mt-0.5 transition-transform group-hover:text-foreground"
                                            />
                                            <div class="flex-1 min-w-0">
                                                <div class="flex items-center gap-2 mb-1 flex-wrap">
                                                    <h3 class="font-semibold text-base">{{ mg.name }}</h3>
                                                    <div v-if="mg.attached" class="flex gap-1">
                                                        <span v-if="form.target_date && mg.target_value !== null && mg.target_value !== undefined && mg.target_value !== ''" class="text-xs px-2 py-0.5 rounded-full bg-emerald-600/20 text-emerald-700 dark:text-emerald-400 whitespace-nowrap">Per-date</span>
                                                        <span v-else-if="!form.target_date && mg.production_machine_group_id && mg.target_value !== null && mg.target_value !== undefined" class="text-xs px-2 py-0.5 rounded-full bg-sky-600/20 text-sky-700 dark:text-sky-400 whitespace-nowrap">Default</span>
                                                        <span v-else class="text-xs px-2 py-0.5 rounded-full bg-gray-600/20 text-gray-700 dark:text-gray-400 whitespace-nowrap">No target</span>
                                                    </div>
                                                </div>
                                                <p class="text-sm text-muted-foreground line-clamp-1">{{ mg.description }}</p>
                                            </div>
                                        </button>

                                        <!-- Attach Checkbox -->
                                        <label class="flex items-center gap-2 cursor-pointer flex-shrink-0">
                                            <input type="checkbox" v-model="mg.attached" class="w-4 h-4 rounded border-input" />
                                            <span class="text-sm font-medium">Attach</span>
                                        </label>
                                    </div>

                                    <!-- Expanded Content (Inputs) -->
                                    <div v-if="mg.attached && expandedGroups.has(mg.machine_group_id)" class="mt-4 pt-4 border-t border-sidebar-border/70">
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                            <div class="flex flex-col gap-2">
                                                <label class="text-sm font-medium">Machine Count</label>
                                                <input type="number" min="1" v-model.number="mg.machine_count" class="input" />
                                                <div v-if="clientErrors.groups[mg.machine_group_id]" class="space-y-1">
                                                    <div v-for="(err, i) in clientErrors.groups[mg.machine_group_id]" :key="i" class="text-xs text-destructive">{{ err }}</div>
                                                </div>
                                            </div>

                                            <div class="flex flex-col gap-2">
                                                <label class="text-sm font-medium">Target Value</label>
                                                <input type="number" min="0" v-model.number="mg.target_value" class="input" />
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Compact Summary when attached but collapsed -->
                                    <div v-if="mg.attached && !expandedGroups.has(mg.machine_group_id)" class="mt-3 pt-3 border-t border-sidebar-border/70 flex gap-4 text-sm text-muted-foreground">
                                        <span><strong class="text-foreground">{{ mg.machine_count }}</strong> machines</span>
                                        <span v-if="mg.target_value"><strong class="text-foreground">{{ mg.target_value }}</strong> target</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex items-center gap-3 pt-4">
                        <button type="submit" class="btn">Save Changes</button>
                        <button type="button" class="btn btn-ghost" @click="() => router.get('/data-management/production')">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
