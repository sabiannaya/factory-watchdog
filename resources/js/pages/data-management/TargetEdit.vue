<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import ResourceForm from '@/components/forms/ResourceForm.vue';
import { type BreadcrumbItem } from '@/types';

const props = defineProps<{ dailyTarget: { daily_target_id: number; date: string; target_value: number; actual_value?: number; notes?: string } }>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Data Management', href: '/data-management/production' },
    { title: 'Target', href: '/data-management/target' },
    { title: props.dailyTarget?.date ?? 'Edit', href: window.location.pathname },
];

const fields = [
    { key: 'date', label: 'Date', type: 'date', required: true },
    { key: 'target_value', label: 'Target Value', type: 'number', required: true },
    { key: 'actual_value', label: 'Actual Value', type: 'number' },
    { key: 'notes', label: 'Notes', type: 'textarea' },
];

const action = `/data-management/target/${props.dailyTarget.daily_target_id}`;
const method = 'put';

const onSubmitted = () => {
    router.get('/data-management/target');
};
</script>

<template>
    <Head :title="`Edit Target — ${props.dailyTarget.date}`" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-4">
            <div class="rounded-xl border border-sidebar-border/70 p-6">
                <h2 class="text-lg font-semibold">Edit Target</h2>
                <p class="mt-2 text-sm text-muted-foreground">Edit daily target</p>

                <div class="mt-4">
                    <ResourceForm :fields="fields" :initial="props.dailyTarget" :action="action" :method="method" mode="edit" @submitted="onSubmitted" />
                </div>
            </div>
        </div>
    </AppLayout>
</template>
