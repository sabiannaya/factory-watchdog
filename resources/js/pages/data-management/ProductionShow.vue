<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import IconActionButton from '@/components/ui/IconActionButton.vue';
import { Edit2, Trash2 } from 'lucide-vue-next';
import { type BreadcrumbItem } from '@/types';

const props = defineProps<{ production: { production_id: number; production_name: string; status: string; process_count: number; machine_groups?: Array<any> } }>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Data Management', href: '/data-management/production' },
    { title: 'Production', href: '/data-management/production' },
    { title: props.production?.production_name ?? 'Show', href: window.location.pathname },
];

const goEdit = () => {
    router.get(`/data-management/production/${props.production.production_id}/edit`);
};

const confirmDelete = () => {
    if (! confirm('Are you sure you want to delete this production?')) {
        return;
    }

    router.visit(`/data-management/production/${props.production.production_id}`, { method: 'delete', preserveState: false });
};
</script>

<template>
    <Head :title="`Production — ${props.production.production_name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-4">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h2 class="text-xl font-semibold">{{ props.production.production_name }}</h2>
                    <p class="mt-1 text-sm text-muted-foreground">Status: <strong class="capitalize">{{ props.production.status }}</strong></p>
                    <p class="mt-2 text-sm text-muted-foreground">Processes: <strong>{{ props.production.process_count }}</strong></p>
                </div>

                <div class="flex items-center gap-2">
                    <IconActionButton :icon="Edit2" label="Edit" color="amber" :onClick="goEdit" />
                    <IconActionButton :icon="Trash2" label="Delete" color="red" :onClick="confirmDelete" />
                </div>
            </div>

                <div class="mt-6 rounded-xl border border-sidebar-border/70 p-6">
                    <h3 class="text-lg font-medium">Details</h3>
                    <div class="mt-3 space-y-2 text-sm text-muted-foreground">
                        <div><strong>ID:</strong> {{ props.production.production_id }}</div>
                        <div><strong>Name:</strong> {{ props.production.production_name }}</div>
                        <div><strong>Status:</strong> {{ props.production.status }}</div>
                    </div>

                    <div class="mt-4">
                        <h4 class="text-md font-medium">Attached Machine Groups</h4>
                        <p class="text-sm text-muted-foreground">Groups attached to this production; default target used if no per-date target is set.</p>

                        <div class="mt-3 space-y-2">
                            <div v-if="!props.production.machine_groups || props.production.machine_groups.length === 0" class="text-sm text-muted-foreground">No machine groups attached.</div>
                            <div v-for="g in props.production.machine_groups" :key="g.production_machine_group_id" class="flex items-center justify-between p-3 rounded-md border">
                                <div>
                                    <div class="font-medium">{{ g.group_name ?? g.pmg_name ?? ('Group ' + g.machine_group_id) }}</div>
                                    <div class="text-sm text-muted-foreground">{{ g.group_description ?? '' }}</div>
                                    <div class="text-sm text-muted-foreground mt-1">Machines: <strong>{{ g.machine_count }}</strong></div>
                                </div>
                                <div class="text-right">
                                        <div class="text-sm text-muted-foreground">Default target</div>
                                        <div class="flex items-center gap-2">
                                            <div class="font-medium">{{ g.default_target ?? '-' }}</div>
                                            <span v-if="g.default_target !== null && g.default_target !== undefined" class="text-xs inline-flex items-center px-2 py-0.5 rounded-full bg-sky-600 text-white">Default</span>
                                        </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
        </div>
    </AppLayout>
</template>
