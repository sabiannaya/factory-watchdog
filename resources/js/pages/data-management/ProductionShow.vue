<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import IconActionButton from '@/components/ui/IconActionButton.vue';
import { Edit2, Trash2 } from 'lucide-vue-next';
import { type BreadcrumbItem } from '@/types';
import TargetDisplay from '@/components/production/TargetDisplay.vue';
import InputConfigDisplay from '@/components/machine-group/InputConfigDisplay.vue';
import type { InputConfig } from '@/composables/useInputConfig';

interface MachineGroup {
    production_machine_group_id: number;
    machine_group_id: number;
    machine_count: number;
    default_target: number | null;
    default_targets: Record<string, number | null>;
    per_date_targets: Record<string, Record<string, number | null>>;
    group_name: string;
    group_description?: string;
    input_config?: InputConfig;
}

const props = defineProps<{ 
    production: { 
        production_id: number; 
        production_name: string; 
        status: string; 
        process_count: number; 
        machine_groups?: MachineGroup[];
    } 
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Data Management', href: '/data-management/production' },
    { title: 'Production', href: '/data-management/production' },
    { title: props.production?.production_name ?? 'Show', href: window.location.pathname },
];

const goEdit = () => {
    router.get(`/data-management/production/${props.production.production_id}/edit`);
};

const confirmDelete = () => {
    if (!confirm('Are you sure you want to delete this production?')) {
        return;
    }

    router.visit(`/data-management/production/${props.production.production_id}`, { method: 'delete', preserveState: false });
};
</script>

<template>
    <Head :title="`Production — ${props.production.production_name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-4">
            <div class="flex items-start justify-between gap-4 mb-6">
                <div>
                    <h2 class="text-xl font-semibold">{{ props.production.production_name }}</h2>
                    <p class="mt-1 text-sm text-muted-foreground dark:text-muted-foreground">
                        Status: <strong class="capitalize">{{ props.production.status }}</strong>
                    </p>
                    <p class="mt-2 text-sm text-muted-foreground dark:text-muted-foreground">
                        Machine Groups: <strong>{{ props.production.process_count }}</strong>
                    </p>
                </div>

                <div class="flex items-center gap-2">
                    <IconActionButton :icon="Edit2" label="Edit" color="amber" :onClick="goEdit" />
                    <IconActionButton :icon="Trash2" label="Delete" color="red" :onClick="confirmDelete" />
                </div>
            </div>

            <div class="mt-6 rounded-xl border border-sidebar-border/70 dark:border-sidebar-border/70 bg-card dark:bg-card p-6">
                <h3 class="text-lg font-medium mb-4">Production Details</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                    <div>
                        <span class="text-muted-foreground dark:text-muted-foreground">ID:</span>
                        <span class="ml-2 font-medium">{{ props.production.production_id }}</span>
                    </div>
                    <div>
                        <span class="text-muted-foreground dark:text-muted-foreground">Name:</span>
                        <span class="ml-2 font-medium">{{ props.production.production_name }}</span>
                    </div>
                    <div>
                        <span class="text-muted-foreground dark:text-muted-foreground">Status:</span>
                        <span class="ml-2 font-medium capitalize">{{ props.production.status }}</span>
                    </div>
                </div>
            </div>

            <div class="mt-6 rounded-xl border border-sidebar-border/70 dark:border-sidebar-border/70 bg-card dark:bg-card p-6">
                <h3 class="text-lg font-medium mb-4">Attached Machine Groups</h3>
                <p class="text-sm text-muted-foreground dark:text-muted-foreground mb-4">
                    Machine groups attached to this production with their configured targets
                </p>

                <div v-if="!props.production.machine_groups || props.production.machine_groups.length === 0" 
                     class="text-sm text-muted-foreground dark:text-muted-foreground py-8 text-center">
                    No machine groups attached.
                </div>

                <div v-else class="space-y-6">
                    <div 
                        v-for="group in props.production.machine_groups" 
                        :key="group.production_machine_group_id"
                        class="border-b border-sidebar-border/70 dark:border-sidebar-border/70 pb-6 last:border-b-0 last:pb-0"
                    >
                        <div class="mb-4">
                            <div class="flex items-start justify-between mb-2">
                                <div>
                                    <h4 class="text-base font-semibold">{{ group.group_name }}</h4>
                                    <p class="text-sm text-muted-foreground dark:text-muted-foreground mt-1">
                                        {{ group.group_description || 'No description' }}
                                    </p>
                                </div>
                                <div class="text-right">
                                    <div class="text-sm text-muted-foreground dark:text-muted-foreground">Machines</div>
                                    <div class="text-base font-medium">{{ group.machine_count }}</div>
                                </div>
                            </div>

                            <InputConfigDisplay 
                                v-if="group.input_config" 
                                :config="group.input_config" 
                            />
                        </div>

                        <div>
                            <h5 class="text-sm font-medium mb-3">Targets</h5>
                            <TargetDisplay
                                :input-config="group.input_config"
                                :default-targets="group.default_targets"
                                :per-date-targets="group.per_date_targets"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
