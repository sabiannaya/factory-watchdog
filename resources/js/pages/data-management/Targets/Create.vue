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
import { useLocalization } from '@/composables/useLocalization';

const { t } = useLocalization();

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    {
        title: t('data_management.data_management'),
        href: '/data-management/production',
    },
    {
        title: t('data_management.targets'),
        href: '/data-management/targets',
    },
    {
        title: t('data_management.create'),
        href: window.location.pathname,
    },
]);

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
    if (!selectedProductionId.value) return `-- ${t('data_management.choose_production')} --`;
    const found = props.productions.find(p => p.production_id === selectedProductionId.value);
    return found ? found.production_name : `-- ${t('data_management.choose_production')} --`;
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
        alert(t('data_management.please_select_production'));
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
        <Head :title="t('data_management.create_daily_targets')" />

        <div class="p-4 space-y-4">
            <div>
                <h1 class="text-3xl font-bold">{{ t('data_management.create_daily_targets') }}</h1>
                <p class="text-muted-foreground dark:text-muted-foreground">
                    {{ t('data_management.set_target_values_description') }}
                </p>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <!-- Production and Date Selection -->
                <div class="rounded-lg border border-sidebar-border/70 dark:border-sidebar-border/70 bg-card dark:bg-card p-6">
                    <h2 class="text-lg font-semibold mb-4">{{ t('data_management.production_and_date') }}</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium mb-2">
                                {{ t('data_management.select_production') }} <span class="text-red-500">*</span>
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
                                        <button class="block w-full text-left px-3 py-2 text-sm" @click="selectProduction(null)">-- {{ t('data_management.choose_production') }} --</button>
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
                                {{ t('data_management.target_date') }} <span class="text-red-500">*</span>
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
                        {{ t('data_management.please_select_production_to_configure') }}
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
                                    {{ mg.machine_count }} {{ t('data_management.machines') }}
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
                                            {{ t('data_management.target_value') }}
                                        </label>
                                        <input
                                            v-model.number="formData[mg.production_machine_group_id][field].target_value"
                                            type="number"
                                            min="0"
                                            :placeholder="t('data_management.enter_target')"
                                            class="input w-full"
                                        />
                                        <p class="text-xs text-muted-foreground dark:text-muted-foreground mt-1">
                                            {{ t('data_management.default') }}: {{ mg.default_targets[field] ?? t('data_management.none') }}
                                        </p>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium mb-1.5">
                                            {{ t('data_management.actual_output') }}
                                        </label>
                                        <input
                                            v-model.number="formData[mg.production_machine_group_id][field].actual_value"
                                            type="number"
                                            min="0"
                                            :placeholder="t('data_management.enter_actual')"
                                            class="input w-full"
                                        />
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium mb-1.5">
                                            {{ t('data_management.notes') }}
                                        </label>
                                        <textarea
                                            v-model="formData[mg.production_machine_group_id][field].keterangan"
                                            :placeholder="t('data_management.optional_notes')"
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
                        {{ t('data_management.no_machine_groups_found') }}
                    </p>
                </div>

                <!-- Form Actions -->
                <div 
                    v-if="selectedProductionId && machineGroups.length > 0" 
                    class="flex items-center gap-3 pt-4"
                >
                    <button 
                        type="submit" 
                        class="hover:cursor-pointer btn"
                        :disabled="form.processing"
                    >
                        {{ form.processing ? t('data_management.saving') : t('data_management.save_daily_targets') }}
                    </button>
                    <button
                        type="button"
                        class="btn btn-ghost"
                        @click="router.get('/data-management/targets')"
                    >
                        {{ t('data_management.cancel') }}
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
