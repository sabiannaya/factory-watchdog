<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { type BreadcrumbItem } from '@/types';
import { ref, computed, watch, reactive } from 'vue';
import { ChevronDown } from 'lucide-vue-next';
import {
    DropdownMenu,
    DropdownMenuTrigger,
    DropdownMenuContent,
    DropdownMenuItem,
} from '@/components/ui/dropdown-menu';
import type { InputConfig } from '@/composables/useInputConfig';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Data Management',
        href: '/data-management/production',
    },
    {
        title: 'Targets',
        href: '/data-management/targets',
    },
    {
        title: 'Create',
        href: window.location.pathname,
    },
];

interface MachineGroup {
    production_machine_group_id: number;
    name: string;
    machine_count: number;
    default_targets: Record<string, number | null>;
    fields: string[];
    machineGroup: {
        name: string;
        description: string;
        input_config?: InputConfig;
    };
}

interface Production {
    production_id: number;
    production_name: string;
    machine_groups: MachineGroup[];
}

const props = defineProps<{
    productions: Production[];
}>();

const selectedProductionId = ref<number | null>(null);
const selectedDate = ref<string>(new Date().toISOString().split('T')[0]);

const selectedProductionLabel = computed(() => {
    if (!selectedProductionId.value) return '-- Choose Production --';
    const found = props.productions.find(p => p.production_id === selectedProductionId.value);
    return found ? found.production_name : '-- Choose Production --';
});

const selectProduction = (id: number | null) => {
    selectedProductionId.value = id;
};

const selectedProduction = computed(() => 
    props.productions.find(p => p.production_id === selectedProductionId.value)
);

const machineGroups = computed(() => selectedProduction.value?.machine_groups ?? []);

// Collapsible state per machine group
const openGroups = reactive<Record<number, boolean>>({});

watch(machineGroups, (mgs) => {
    // initialize open state to true for every machine group when production changes
    mgs.forEach((mg) => {
        openGroups[mg.production_machine_group_id] = true;
    });
}, { immediate: true });

function toggleGroup(id: number): void {
    openGroups[id] = !openGroups[id];
}

// Initialize form data structure
const formData = ref<Record<number, Record<string, {
    field_name: string;
    target_value: number | null;
    actual_value: number | null;
    keterangan: string | null;
}>>>({});

// Reset form data when production changes
watch(selectedProductionId, (newProdId) => {
    if (!newProdId) {
        formData.value = {};
        return;
    }

    const prod = props.productions.find(p => p.production_id === newProdId);
    if (!prod) return;

    const data: typeof formData.value = {};
    
    prod.machine_groups.forEach(mg => {
        data[mg.production_machine_group_id] = {};
        mg.fields.forEach(field => {
            data[mg.production_machine_group_id][field] = {
                field_name: field,
                target_value: mg.default_targets[field] ?? null,
                actual_value: null,
                keterangan: null,
            };
        });
    });

    formData.value = data;
});

const form = useForm({
    production_id: null as number | null,
    date: selectedDate.value,
    machine_groups: [] as Array<{
        production_machine_group_id: number;
        values: Array<{
            field_name: string;
            target_value: number | null;
            actual_value: number | null;
            keterangan: string | null;
        }>;
    }>,
});

function submit(): void {
    if (!selectedProductionId.value) {
        alert('Please select a production');
        return;
    }

    form.production_id = selectedProductionId.value;
    form.date = selectedDate.value;
    form.machine_groups = Object.entries(formData.value).map(([mgId, fields]) => ({
        production_machine_group_id: Number(mgId),
        values: Object.values(fields),
    }));

    form.post('/data-management/targets', {
        onSuccess: () => {
            router.get(`/data-management/targets?production_id=${selectedProductionId.value}&date=${selectedDate.value}`);
        },
    });
}

function getFieldLabel(fieldName: string): string {
    return fieldName.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Create Daily Targets" />

        <div class="p-4 space-y-4">
            <div>
                <h1 class="text-3xl font-bold">Create Daily Targets</h1>
                <p class="text-muted-foreground dark:text-muted-foreground">
                    Set target values for all machine groups in a production for a specific date
                </p>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <!-- Production and Date Selection -->
                <div class="rounded-lg border border-sidebar-border/70 dark:border-sidebar-border/70 bg-card dark:bg-card p-6">
                    <h2 class="text-lg font-semibold mb-4">Production & Date</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium mb-2">
                                Select Production <span class="text-red-500">*</span>
                            </label>
                            <DropdownMenu>
                                <DropdownMenuTrigger :as-child="true">
                                    <button type="button" class="input w-full flex items-center justify-between" required>
                                        <span class="truncate">{{ selectedProductionLabel }}</span>
                                        <ChevronDown class="ml-2 size-4" />
                                    </button>
                                </DropdownMenuTrigger>
                                <DropdownMenuContent class="min-w-[12rem]">
                                    <DropdownMenuItem :as-child="true">
                                        <button class="block w-full text-left px-3 py-2 text-sm" @click="selectProduction(null)">-- Choose Production --</button>
                                    </DropdownMenuItem>
                                    <template v-for="prod in productions" :key="prod.production_id">
                                        <DropdownMenuItem :as-child="true">
                                            <button class="block w-full text-left px-3 py-2 text-sm" @click="selectProduction(prod.production_id)">{{ prod.production_name }}</button>
                                        </DropdownMenuItem>
                                    </template>
                                </DropdownMenuContent>
                            </DropdownMenu>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-2">
                                Target Date <span class="text-red-500">*</span>
                            </label>
                            <input
                                v-model="selectedDate"
                                type="date"
                                required
                                class="input w-full"
                            />
                        </div>
                    </div>
                </div>

                <!-- No Production Selected -->
                <div 
                    v-if="!selectedProductionId" 
                    class="text-center py-12 rounded-lg border border-dashed border-sidebar-border/70 dark:border-sidebar-border/70 bg-muted/20 dark:bg-muted/20"
                >
                    <p class="text-muted-foreground dark:text-muted-foreground">
                        Please select a production to configure daily targets
                    </p>
                </div>

                <!-- Machine Groups Forms -->
                <div v-else-if="machineGroups.length > 0" class="space-y-6">
                    <div 
                        v-for="mg in machineGroups" 
                        :key="mg.production_machine_group_id"
                        class="rounded-lg border border-sidebar-border/70 dark:border-sidebar-border/70 bg-card dark:bg-card"
                    >
                        <div class="p-4 border-b border-sidebar-border/40 flex items-center justify-between">
                            <div>
                                <h3 class="text-xl font-bold">{{ mg.name }}</h3>
                                <p class="text-sm text-muted-foreground dark:text-muted-foreground mt-1">
                                    {{ mg.machineGroup.name }}
                                    <span v-if="mg.machineGroup.description" class="mx-2">•</span>
                                    <span v-if="mg.machineGroup.description">{{ mg.machineGroup.description }}</span>
                                </p>
                            </div>

                            <div class="flex items-center gap-3">
                                <span class="text-sm px-3 py-1 rounded-full bg-muted/60 dark:bg-muted/60">
                                    {{ mg.machine_count }} machines
                                </span>
                                <button type="button" class="btn btn-ghost" @click="toggleGroup(mg.production_machine_group_id)">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 transition-transform" :class="{'rotate-180': !openGroups[mg.production_machine_group_id]}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <div v-show="openGroups[mg.production_machine_group_id]" class="p-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                <div 
                                    v-for="field in mg.fields" 
                                    :key="`${mg.production_machine_group_id}-${field}`"
                                    class="rounded-lg bg-muted/40 dark:bg-muted/40 p-4 space-y-3"
                                >
                                    <h4 class="font-semibold text-base">{{ getFieldLabel(field) }}</h4>
                                    
                                    <div>
                                        <label class="block text-sm font-medium mb-1.5">
                                            Target Value
                                        </label>
                                        <input
                                            v-model.number="formData[mg.production_machine_group_id][field].target_value"
                                            type="number"
                                            min="0"
                                            placeholder="Enter target"
                                            class="input w-full"
                                        />
                                        <p class="text-xs text-muted-foreground dark:text-muted-foreground mt-1">
                                            Default: {{ mg.default_targets[field] ?? 'None' }}
                                        </p>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium mb-1.5">
                                            Actual Output
                                        </label>
                                        <input
                                            v-model.number="formData[mg.production_machine_group_id][field].actual_value"
                                            type="number"
                                            min="0"
                                            placeholder="Enter actual"
                                            class="input w-full"
                                        />
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium mb-1.5">
                                            Notes
                                        </label>
                                        <textarea
                                            v-model="formData[mg.production_machine_group_id][field].keterangan"
                                            placeholder="Optional notes"
                                            rows="2"
                                            class="input w-full resize-none"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- No Machine Groups -->
                <div 
                    v-else-if="selectedProductionId" 
                    class="text-center py-12 rounded-lg border border-dashed border-sidebar-border/70 dark:border-sidebar-border/70 bg-muted/20 dark:bg-muted/20"
                >
                    <p class="text-muted-foreground dark:text-muted-foreground">
                        No machine groups found for this production
                    </p>
                </div>

                <!-- Form Actions -->
                <div 
                    v-if="selectedProductionId && machineGroups.length > 0" 
                    class="flex items-center gap-3 pt-4"
                >
                    <button 
                        type="submit" 
                        class="btn"
                        :disabled="form.processing"
                    >
                        {{ form.processing ? 'Saving...' : 'Save Daily Targets' }}
                    </button>
                    <button
                        type="button"
                        class="btn btn-ghost"
                        @click="router.get('/data-management/targets')"
                    >
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
