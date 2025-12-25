<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { type BreadcrumbItem } from '@/types';
import DataTable from '@/components/DataTable.vue';
import { ref, computed } from 'vue';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Logs',
        href: '/logs',
    },
    {
        title: 'Hourly Logs',
        href: '/logs',
    },
];

const loading = ref(false);

const props = defineProps<{ 
    hourlyLogs?: { 
        data: any[]; 
        next_cursor?: string | null; 
        prev_cursor?: string | null 
    }, 
    meta?: { 
        sort?: string; 
        direction?: string; 
        q?: string; 
        per_page?: number;
        production_id?: string;
        date_from?: string;
        date_to?: string;
    } 
}>();

const dateFrom = ref(props.meta?.date_from ?? '');
const dateTo = ref(props.meta?.date_to ?? '');
const dataSource = computed(() => props.hourlyLogs?.data ?? []);

const onServerSearch = (q: string) => {
    const perPage = props.meta?.per_page ?? 20;
    const sort = props.meta?.sort ?? 'recorded_at';
    const direction = props.meta?.direction ?? 'desc';
    router.get(window.location.pathname, { q, per_page: perPage, sort, direction }, { preserveState: true, replace: true });
};

const onServerSort = ({ sort, direction }: { sort?: string; direction?: string }) => {
    const perPage = props.meta?.per_page ?? 20;
    const q = props.meta?.q ?? '';
    if (!sort) return;
    router.get(window.location.pathname, { sort, direction: direction ?? 'desc', per_page: perPage, q }, { preserveState: true, replace: true });
};

const goNext = () => {
    const next = props.hourlyLogs?.next_cursor;
    if (!next) return;
    router.get(window.location.pathname, { cursor: next }, { preserveState: true, replace: true });
};

const goPrev = () => {
    const prev = props.hourlyLogs?.prev_cursor;
    if (!prev) return;
    router.get(window.location.pathname, { cursor: prev }, { preserveState: true, replace: true });
};

const formatDateStart = (v: string | null) => {
    if (!v) return null;
    if (v.includes('T')) return v.replace('T', ' ') + ':00';
    return `${v} 00:00:00`;
};

const formatDateEnd = (v: string | null) => {
    if (!v) return null;
    if (v.includes('T')) return v.replace('T', ' ') + ':00';
    return `${v} 23:59:59`;
};

const onDateFilter = () => {
    const perPage = props.meta?.per_page ?? 20;
    const sort = props.meta?.sort ?? 'recorded_at';
    const direction = props.meta?.direction ?? 'desc';
    router.get(window.location.pathname, { 
        date_from: formatDateStart(dateFrom.value || null), 
        date_to: formatDateEnd(dateTo.value || null), 
        q: props.meta?.q ?? '', 
        sort, 
        direction, 
        per_page: perPage 
    }, { preserveState: true, replace: true });
};

router.on('start', () => (loading.value = true));
router.on('finish', () => (loading.value = false));

</script>

<template>
    <Head title="Hourly Logs" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-4 space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-semibold">Hourly Production Logs</h2>
                    <p class="text-sm text-muted-foreground">Real-time hourly output tracking per machine</p>
                </div>
            </div>

            <div class="flex items-center gap-3 mb-3">
                <label class="text-sm">From</label>
                <input type="datetime-local" class="input" v-model="dateFrom" :disabled="loading" />
                <label class="text-sm">To</label>
                <input type="datetime-local" class="input" v-model="dateTo" :disabled="loading" />
                <button class="btn" @click="onDateFilter" :disabled="loading">Apply</button>
            </div>

            <DataTable 
                serverMode 
                :data="dataSource" 
                :columns="[
                    { key: 'hourly_log_id', label: 'ID', type: 'number', sortable: true, findable: false },
                    { key: 'production_name', label: 'Production', type: 'string', sortable: false, findable: true },
                    { key: 'machine_group', label: 'Machine Group', type: 'string', sortable: false, findable: true },
                    { key: 'machine_index', label: 'Machine #', type: 'number', sortable: true, findable: true },
                    { key: 'recorded_at', label: 'Recorded At', type: 'string', sortable: true, findable: true },
                    { key: 'target_value', label: 'Target', type: 'number', sortable: true, findable: false },
                    { key: 'output_value', label: 'Output', type: 'number', sortable: true, findable: false },
                    { key: 'variance', label: 'Variance', type: 'number', sortable: false, findable: false },
                ]" 
                :per-page="20" 
                :initial-sort="props.meta?.sort ?? 'recorded_at'" 
                :initial-direction="(props.meta?.direction === 'asc' ? 'asc' : 'desc')" 
                :loading="loading" 
                @server-search="onServerSearch" 
                @server-sort="onServerSort" 
            />

            <div class="mt-4 flex items-center justify-end gap-2">
                <button class="btn" :disabled="!props.hourlyLogs?.prev_cursor || loading" @click="goPrev">Previous</button>
                <button class="btn" :disabled="!props.hourlyLogs?.next_cursor || loading" @click="goNext">Next</button>
            </div>
        </div>
    </AppLayout>
</template>
