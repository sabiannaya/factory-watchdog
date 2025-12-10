<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { type BreadcrumbItem } from '@/types';
import DataTable, { type DataTableColumn } from '@/components/DataTable.vue';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Data Management',
        href: '/data-management/production',
    },
    {
        title: 'Production',
        href: '/data-management/production',
    },
];

// Define table columns with schema (match `productions` model fields)
const columns: DataTableColumn[] = [
    { key: 'production_id', label: 'ID', type: 'number', sortable: true, findable: false },
    { key: 'production_name', label: 'Production Name', type: 'string', sortable: true, findable: true },
    { key: 'status', label: 'Status', type: 'string', sortable: true, findable: true },
    { key: 'created_at', label: 'Created', type: 'date', sortable: true, findable: true },
];

// Accept backend productions; fall back to sample data for local dev
const props = defineProps<{ productions?: any[] }>();

const sampleData = [
    { production_id: 1, production_name: 'Widget A', status: 'active', created_at: '2025-12-01' },
    { production_id: 2, production_name: 'Widget B', status: 'active', created_at: '2025-12-05' },
    { production_id: 3, production_name: 'Gadget X', status: 'inactive', created_at: '2025-12-03' },
    { production_id: 4, production_name: 'Gadget Y', status: 'active', created_at: '2025-12-08' },
    { production_id: 5, production_name: 'Tool Z', status: 'inactive', created_at: '2025-12-02' },
];

const dataSource = props.productions && props.productions.length ? props.productions : sampleData;
</script>

<template>
    <Head title="Production" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-4 space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-semibold">Production</h2>
                    <p class="text-sm text-muted-foreground">Manage production records</p>
                </div>
            </div>

            <DataTable :data="sampleData" :columns="columns" :per-page="5" />
        </div>
    </AppLayout>
</template>

