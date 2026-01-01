<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { type BreadcrumbItem } from '@/types';
import { computed } from 'vue';
import InputConfigDisplay from '@/components/machine-group/InputConfigDisplay.vue';
import IconActionButton from '@/components/ui/IconActionButton.vue';
import { Edit2, Trash2, ArrowLeft } from 'lucide-vue-next';
import { useLocalization } from '@/composables/useLocalization';

const { t } = useLocalization();

const props = defineProps<{ machineGroup: { machine_group_id: number; name: string; description?: string; total_machines?: number; allocations?: any[] } }>();

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    { title: t('data_management.data_management'), href: '/data-management/production' },
    { title: t('data_management.machines'), href: '/data-management/machine' },
    { title: props.machineGroup?.name ?? t('data_management.detail'), href: window.location.pathname },
]);

const goBack = () => router.get('/data-management/machine');
const goEdit = () => router.get(`/data-management/machine/${props.machineGroup.machine_group_id}/edit`);
const confirmDelete = () => {
    if (!window.confirm(t('data_management.confirm_delete_machine'))) return;
    router.visit(`/data-management/machine/${props.machineGroup.machine_group_id}`, { method: 'delete', preserveState: false });
};
</script>

<template>
    <Head :title="`${t('data_management.machine')} — ${props.machineGroup?.name ?? ''}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-4">
            <div class="rounded-xl border border-sidebar-border/70 p-6">
                <div class="flex items-start justify-between">
                    <div>
                        <h2 class="text-lg font-semibold">{{ props.machineGroup.name }}</h2>
                        <p class="mt-1 text-sm text-muted-foreground">{{ props.machineGroup.description }}</p>
                        <p class="mt-2 text-sm">{{ t('data_management.total_machines') }}: <strong>{{ props.machineGroup.total_machines ?? 0 }}</strong></p>
                    </div>
                    <div class="flex items-center gap-2">
                        <IconActionButton :icon="ArrowLeft" :label="t('data_management.back')" :onClick="goBack" />
                        <IconActionButton :icon="Edit2" :label="t('data_management.edit')" color="amber" :onClick="goEdit" />
                        <IconActionButton :icon="Trash2" :label="t('data_management.delete')" color="red" :onClick="confirmDelete" />
                    </div>
                </div>

                <InputConfigDisplay :config="props.machineGroup.input_config" />

                <div class="mt-6">
                    <h3 class="text-sm font-medium">{{ t('data_management.allocations_to_productions') }}</h3>
                    <div class="mt-2 overflow-x-auto">
                        <table class="w-full table-auto border-collapse">
                            <thead>
                                <tr class="text-left">
                                    <th class="px-3 py-2 text-sm font-medium">{{ t('data_management.production') }}</th>
                                    <th class="px-3 py-2 text-sm font-medium">{{ t('data_management.machine_count') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="alloc in props.machineGroup.allocations" :key="alloc.production_id" class="border-t">
                                    <td class="px-3 py-2 text-sm">{{ alloc.production_name ?? alloc.production_id }}</td>
                                    <td class="px-3 py-2 text-sm">{{ alloc.machine_count }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
