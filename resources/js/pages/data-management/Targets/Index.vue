<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, Link } from '@inertiajs/vue3';
import IconActionButton from '@/components/ui/IconActionButton.vue';
import { Edit2 } from 'lucide-vue-next';
import { type BreadcrumbItem } from '@/types';
import { ref, watch, computed, onMounted, onBeforeUnmount, nextTick } from 'vue';
import { ChevronDown, Calendar } from 'lucide-vue-next';
import { ref as vueRef } from 'vue';
import {
    DropdownMenu,
    DropdownMenuTrigger,
    DropdownMenuContent,
    DropdownMenuItem,
} from '@/components/ui/dropdown-menu';
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
]);

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
const triggerRef = ref<HTMLElement | null>(null);
const triggerWidth = ref(0);

const updateTriggerWidth = () => {
    triggerWidth.value = triggerRef.value ? triggerRef.value.offsetWidth : 0;
};

onMounted(() => {
    nextTick(updateTriggerWidth);
    window.addEventListener('resize', updateTriggerWidth);
});

onBeforeUnmount(() => {
    window.removeEventListener('resize', updateTriggerWidth);
});
const selectedDate = ref(props.selectedDate);
const dateInputRef = vueRef<HTMLInputElement | null>(null);

const focusDateInput = () => {
    dateInputRef.value?.focus();
    // some browsers need a click to open the calendar
    dateInputRef.value?.click();
};

const selectedProductionLabel = computed(() => {
    if (!selectedProduction.value) return t('data_management.choose_production');
    const found = props.productions.find(p => p.production_id === selectedProduction.value);
    return found ? found.production_name : t('data_management.choose_production');
});

const selectProduction = (id: number | null) => {
    selectedProduction.value = id;
};

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
        <Head :title="t('data_management.targets')" />

        <div class="p-4 space-y-4">
            <!-- <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-semibold">Targets Management</h1>
                    <p class="text-sm text-muted-foreground">
                        View and manage daily targets for production machine groups
                    </p>
                </div>
                <Link href="/data-management/targets/create" class="hover:cursor-pointer btn">Create Daily Targets</Link>
            </div> -->

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                <div>
                    <label class="block text-sm font-medium mb-2">{{ t('data_management.select_production') }}</label>
                    <DropdownMenu class="relative w-full">
                        <DropdownMenuTrigger :as-child="true" :style="{ '--reka-dropdown-menu-trigger-width': triggerWidth + 'px' }">
                            <button ref="triggerRef" type="button" class="input w-full flex items-center justify-between">
                                <span class="truncate">{{ selectedProductionLabel }}</span>
                                <ChevronDown class="ml-2 size-4" />
                            </button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="start" :sideOffset="4" class="w-(--reka-dropdown-menu-trigger-width) min-w-[12rem]">
                            <DropdownMenuItem :as-child="true">
                                <button class="block w-full text-left px-3 py-2 text-sm" @click="selectProduction(null)">{{ t('data_management.choose_production') }}</button>
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
                    <label class="block text-sm font-medium mb-2">{{ t('data_management.select_date') }}</label>
                    <div class="relative">
                        <input
                            v-model="selectedDate"
                            type="date"
                            class="input w-full pr-10"
                            ref="dateInputRef"
                        />
                        <button type="button" class="absolute right-2 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground" @click="focusDateInput" aria-label="Open calendar">
                            <Calendar class="h-4 w-4" />
                        </button>
                    </div>
                </div>
            </div>

            <div v-if="!selectedProduction" class="text-center py-12 rounded-lg border border-dashed border-sidebar-border/70 dark:border-sidebar-border/70 bg-muted/20 dark:bg-muted/20">
                <p class="text-muted-foreground dark:text-muted-foreground">{{ t('data_management.please_select_production_to_view') }}</p>
            </div>

            <div v-else-if="machineGroups && machineGroups.length > 0" class="space-y-6">
                <div v-for="mg in machineGroups" :key="mg.production_machine_group_id" class="rounded-lg border border-sidebar-border/70 dark:border-sidebar-border/70 bg-card dark:bg-card p-6">
                    <div class="flex justify-between items-start mb-6">
                        <div>
                            <h2 class="text-xl font-bold">{{ mg.name }}</h2>
                            <p class="text-sm text-muted-foreground dark:text-muted-foreground mt-1">
                                {{ mg.machine_count }} {{ t('data_management.machines') }}
                            </p>
                        </div>
                        <IconActionButton :icon="Edit2" label="Edit" color="amber" :onClick="() => router.get(`/data-management/targets/${mg.production_machine_group_id}/edit`, { date: selectedDate })" />
                    </div>

                    <div class="space-y-4">
                        <!-- Target Fields -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                            <div v-for="field in mg.fields.filter(f => f !== 'qty')" :key="`${mg.production_machine_group_id}-target-${field}`" class="rounded-lg bg-muted/40 dark:bg-muted/40 p-3">
                                <p class="text-sm font-medium mb-1 capitalize">{{ field.replace(/_/g, ' ') }}</p>
                                <p class="text-lg font-semibold">
                                    {{ getValueForField(mg.production_machine_group_id, field, 'target') ?? t('data_management.not_set') }}
                                </p>
                                <p class="text-xs text-muted-foreground dark:text-muted-foreground mt-1">
                                    {{ t('data_management.default') }}: {{ getDefaultTarget(mg.production_machine_group_id, field) ?? t('data_management.no_default') }}
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
                                {{ t('data_management.record_hourly_output') }}
                            </Link>
                        </div>
                    </div>
                </div>
            </div>

            <div v-else-if="selectedProduction && machineGroups" class="text-center py-12 rounded-lg border border-dashed border-sidebar-border/70 dark:border-sidebar-border/70 bg-muted/20 dark:bg-muted/20">
                <p class="text-muted-foreground dark:text-muted-foreground">{{ t('data_management.no_machine_groups_found') }}</p>
            </div>
        </div>
    </AppLayout>
</template>
