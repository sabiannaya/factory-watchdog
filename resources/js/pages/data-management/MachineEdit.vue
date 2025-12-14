<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import ResourceForm from '@/components/forms/ResourceForm.vue';
import { type BreadcrumbItem } from '@/types';

const props = defineProps<{ machineGroup: { machine_group_id: number; name: string; description?: string } }>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Data Management', href: '/data-management/production' },
    { title: 'Machine', href: '/data-management/machine' },
    { title: props.machineGroup?.name ?? 'Edit', href: window.location.pathname },
];

const fields = [
    { key: 'name', label: 'Name', type: 'text', required: true },
    { key: 'description', label: 'Description', type: 'textarea' },
];

const action = `/data-management/machine/${props.machineGroup.machine_group_id}`;
const method = 'put';

const onSubmitted = () => {
    router.get('/data-management/machine');
};
</script>

<template>
    <Head :title="`Edit Machine Group — ${props.machineGroup.name}`" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-4">
            <div class="rounded-xl border border-sidebar-border/70 p-6">
                <h2 class="text-lg font-semibold">Edit Machine Group</h2>
                <p class="mt-2 text-sm text-muted-foreground">Edit machine group details</p>

                <div class="mt-4">
                    <ResourceForm :fields="fields" :initial="props.machineGroup" :action="action" :method="method" mode="edit" @submitted="onSubmitted" />
                </div>
            </div>
        </div>
    </AppLayout>
</template>
