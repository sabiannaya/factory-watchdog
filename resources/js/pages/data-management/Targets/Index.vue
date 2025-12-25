<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, Link } from '@inertiajs/vue3';
import { type BreadcrumbItem } from '@/types';
import { ref, watch, computed } from 'vue';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Data Management',
        href: '/data-management/production',
    },
    {
        title: 'Targets',
        href: '/data-management/targets',
    },
];

const props = defineProps<{
    productions: Array<{ production_id: number; production_name: string }>;
    machineGroups: Array<{
        production_machine_group_id: number;
        name: string;
        machine_count: number;
        fields: string[];
        default_targets: Record<string, number | null>;
        daily_values: Array<{
            field_name: string;
            target_value: number | null;
            keterangan: string | null;
        }>;
    }> | null;
    selectedProductionId: number | null;
    selectedDate: string;
}>();

const selectedProduction = ref(props.selectedProductionId);
const selectedDate = ref(props.selectedDate);

watch([selectedProduction, selectedDate], ([prodId, date]) => {
    if (!prodId) return;
    router.get(
        '/data-management/targets',
        {
            production_id: prodId,
            date: date,
        },
        { preserveState: true, replace: true }
    );
}, { immediate: false });

const getValueForField = (machineGroupId: number, fieldName: string, type: 'target' | 'keterangan') => {
    const mg = props.machineGroups?.find((m) => m.production_machine_group_id === machineGroupId);
    if (!mg) return null;

    const value = mg.daily_values.find((v) => v.field_name === fieldName);
    if (!value) {
        // Return default target for target_value
        if (type === 'target') {
            return mg.default_targets[fieldName] ?? null;
        }
        return null;
    }

    return type === 'target' ? value.target_value : value.keterangan;
};

const getDefaultTarget = (machineGroupId: number, fieldName: string) => {
    const mg = props.machineGroups?.find((m) => m.production_machine_group_id === machineGroupId);
    return mg?.default_targets[fieldName] ?? null;
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Targets Management" />

        <div class="p-4 space-y-4">
            <!-- <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-semibold">Targets Management</h1>
                    <p class="text-sm text-muted-foreground">
                        View and manage daily targets for production machine groups
                    </p>
                </div>
                <Link href="/data-management/targets/create" class="btn">Create Daily Targets</Link>
            </div> -->

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                <div>
                    <label class="block text-sm font-medium mb-2">Select Production</label>
                    <select
                        v-model.number="selectedProduction"
                        class="input w-full"
                    >
                        <option :value="null">-- Choose Production --</option>
                        <option v-for="prod in productions" :key="prod.production_id" :value="prod.production_id">
                            {{ prod.production_name }}
                        </option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-2">Select Date</label>
                    <input
                        v-model="selectedDate"
                        type="date"
                        class="input w-full"
                    />
                </div>
            </div>

            <div v-if="!selectedProduction" class="text-center py-12 rounded-lg border border-dashed border-sidebar-border/70 dark:border-sidebar-border/70 bg-muted/20 dark:bg-muted/20">
                <p class="text-muted-foreground dark:text-muted-foreground">Please select a production to view machine groups</p>
            </div>

            <div v-else-if="machineGroups && machineGroups.length > 0" class="space-y-6">
                <div v-for="mg in machineGroups" :key="mg.production_machine_group_id" class="rounded-lg border border-sidebar-border/70 dark:border-sidebar-border/70 bg-card dark:bg-card p-6">
                    <div class="flex justify-between items-start mb-6">
                        <div>
                            <h2 class="text-xl font-bold">{{ mg.name }}</h2>
                            <p class="text-sm text-muted-foreground dark:text-muted-foreground mt-1">
                                {{ mg.machine_count }} machines
                            </p>
                        </div>
                        <Link
                            :href="`/data-management/targets/${mg.production_machine_group_id}/edit?date=${selectedDate}`"
                            class="btn btn-ghost"
                        >
                            Edit
                        </Link>
                    </div>

                    <div class="space-y-4">
                        <!-- Target Fields -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                            <div v-for="field in mg.fields" :key="`${mg.production_machine_group_id}-target-${field}`" class="rounded-lg bg-muted/40 dark:bg-muted/40 p-3">
                                <p class="text-sm font-medium mb-1 capitalize">{{ field.replace(/_/g, ' ') }}</p>
                                <p class="text-lg font-semibold">
                                    {{ getValueForField(mg.production_machine_group_id, field, 'target') ?? 'Not set' }}
                                </p>
                                <p class="text-xs text-muted-foreground dark:text-muted-foreground mt-1">
                                    Default: {{ getDefaultTarget(mg.production_machine_group_id, field) ?? 'No default' }}
                                </p>
                                <p v-if="getValueForField(mg.production_machine_group_id, field, 'keterangan')" class="text-xs text-muted-foreground dark:text-muted-foreground mt-2 italic">
                                    {{ getValueForField(mg.production_machine_group_id, field, 'keterangan') }}
                                </p>
                            </div>
                        </div>

                        <!-- Link to record output -->
                        <div class="pt-3 border-t border-sidebar-border/40">
                            <Link
                                :href="`/input/create?date=${selectedDate}&production_id=${selectedProduction}`"
                                class="text-sm text-primary hover:underline inline-flex items-center gap-1"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                Record hourly output
                            </Link>
                        </div>
                    </div>
                </div>
            </div>

            <div v-else-if="selectedProduction && machineGroups" class="text-center py-12 rounded-lg border border-dashed border-sidebar-border/70 dark:border-sidebar-border/70 bg-muted/20 dark:bg-muted/20">
                <p class="text-muted-foreground dark:text-muted-foreground">No machine groups found for this production</p>
            </div>
        </div>
    </AppLayout>
</template>
