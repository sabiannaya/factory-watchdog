<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { type BreadcrumbItem } from '@/types';
import DataTable, { type DataTableColumn } from '@/components/DataTable.vue';
import { router } from '@inertiajs/vue3';
import { ref, onMounted, onBeforeUnmount } from 'vue';
import IconActionButton from '@/components/ui/IconActionButton.vue';
import { Eye, Edit2, Trash2 } from 'lucide-vue-next';

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
    { key: 'actions', label: 'Actions', type: 'string', sortable: false, findable: false },
];

// Accept backend productions and meta; fall back to an empty list for local dev
const props = defineProps<{ 
    productions?: { 
        data: any[]; 
        next_cursor?: string | null; 
        prev_cursor?: string | null 
    }, 
    meta?: { 
        sort?: string; 
        direction?: string; 
        q?: string; 
        per_page?: number;
        status?: string;
    } 
}>();

const dataSource = props.productions?.data ?? [];
const loading = ref(false);
const status = ref(props.meta?.status ?? '');

const onServerSearch = (q: string) => {
    const perPage = props.meta?.per_page ?? 10;
    const sort = props.meta?.sort ?? 'production_name';
    const direction = props.meta?.direction ?? 'asc';
    router.get(window.location.pathname, { q, per_page: perPage, sort, direction }, { preserveState: true, replace: true });
};

const onServerSort = ({ sort, direction }: { sort?: string; direction?: string }) => {
    const perPage = props.meta?.per_page ?? 10;
    const q = props.meta?.q ?? '';
    if (!sort) return;
    router.get(window.location.pathname, { sort, direction: direction ?? 'asc', per_page: perPage, q }, { preserveState: true, replace: true });
};

const goNext = () => {
    const next = props.productions?.next_cursor;
    if (!next) return;
    router.get(window.location.pathname, { cursor: next }, { preserveState: true, replace: true });
};

const goPrev = () => {
    const prev = props.productions?.prev_cursor;
    if (!prev) return;
    router.get(window.location.pathname, { cursor: prev }, { preserveState: true, replace: true });
};

const onStatusChange = () => {
    const perPage = props.meta?.per_page ?? 10;
    const q = props.meta?.q ?? '';
    const sort = props.meta?.sort ?? 'production_name';
    const direction = props.meta?.direction ?? 'asc';
    router.get(window.location.pathname, { status: status.value || null, q, sort, direction, per_page: perPage }, { preserveState: true, replace: true });
};

let startHandler: (() => void) | null = null;
let finishHandler: (() => void) | null = null;
let errorHandler: (() => void) | null = null;

onMounted(() => {
    startHandler = () => (loading.value = true);
    finishHandler = () => (loading.value = false);
    errorHandler = () => (loading.value = false);
    
    router.on('start', startHandler);
    router.on('finish', finishHandler);
    router.on('error', errorHandler);
});

onBeforeUnmount(() => {
    if (startHandler) router.on('start', startHandler);
    if (finishHandler) router.on('finish', finishHandler);
    if (errorHandler) router.on('error', errorHandler);
});

const confirmDelete = (id: number | string) => {
    // eslint-disable-next-line no-alert
    if (!window.confirm('Are you sure you want to delete this production?')) return;
    router.visit(`/data-management/production/${id}`, { method: 'delete', preserveState: false });
};
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

            <div class="flex items-center gap-3 mb-2">
                <label class="text-sm">Status</label>
                <select
                    class="appearance-none rounded-md border bg-popover text-popover-foreground px-3 py-2 pr-10 text-sm shadow-sm placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2"
                    v-model="status"
                    @change="onStatusChange"
                    :disabled="loading"
                >
                    <option value="">All</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>

            <DataTable 
                serverMode 
                :data="dataSource" 
                :columns="columns" 
                :per-page="5" 
                :initial-sort="props.meta?.sort ?? 'production_name'" 
                :initial-direction="(props.meta?.direction === 'desc' ? 'desc' : 'asc')" 
                :loading="loading" 
                @server-search="onServerSearch" 
                @server-sort="onServerSort"
            >
                <template #cell="{ row, column }">
                    <template v-if="column.key !== 'actions'">
                        {{ row[column.key] }}
                    </template>
                    <template v-else>
                        <div class="flex items-center gap-4">
                            <IconActionButton :icon="Eye" label="Detail" color="blue" :onClick="() => router.get(`/data-management/production/${row.production_id}`)" />
                            <IconActionButton :icon="Edit2" label="Edit" color="amber" :onClick="() => router.get(`/data-management/production/${row.production_id}/edit`)" />
                            <IconActionButton :icon="Trash2" label="Delete" color="red" :onClick="() => confirmDelete(row.production_id)" />
                        </div>
                    </template>
                </template>
            </DataTable>

            <div class="mt-4 flex items-center justify-end gap-2">
                <button class="btn" :disabled="!props.productions?.prev_cursor" @click="goPrev">Previous</button>
                <button class="btn" :disabled="!props.productions?.next_cursor" @click="goNext">Next</button>
            </div>
        </div>
    </AppLayout>
</template>