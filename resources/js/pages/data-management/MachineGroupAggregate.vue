<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { type BreadcrumbItem } from '@/types';
import DataTable from '@/components/DataTable.vue';
import { ref } from 'vue';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Data Management', href: '/data-management/production' },
    { title: 'Machine Group Aggregates', href: '/data-management/aggregates/machine-groups' },
];

const props = defineProps<{ aggregates?: { data: any[]; next_page?: string | null; prev_page?: string | null }, meta?: any }>();
const loading = ref(false);
const dateFrom = ref(props.meta?.date_from ?? '');
const dateTo = ref(props.meta?.date_to ?? '');
const dataSource = props.aggregates?.data ?? [];

const onServerSearch = (q: string) => {
    const perPage = props.meta?.per_page ?? 20;
    const sort = props.meta?.sort ?? 'total_output';
    const direction = props.meta?.direction ?? 'desc';
    router.get(window.location.pathname, { q, per_page: perPage, sort, direction }, { preserveState: true, replace: true });
};

const onServerSort = ({ sort, direction }: { sort?: string; direction?: string }) => {
    const perPage = props.meta?.per_page ?? 20;
    const q = props.meta?.q ?? '';
    if (!sort) return;
    router.get(window.location.pathname, { sort, direction: direction ?? 'desc', per_page: perPage, q }, { preserveState: true, replace: true });
};

const formatDateStart = (v: string | null) => {
    if (!v) return null;
    // if datetime-local (contains 'T'), convert to 'YYYY-MM-DD HH:MM:SS'
    if (v.includes('T')) return v.replace('T', ' ') + ':00';
    // otherwise assume date 'YYYY-MM-DD' and return start of day
    return `${v} 00:00:00`;
};

const formatDateEnd = (v: string | null) => {
    if (!v) return null;
    if (v.includes('T')) return v.replace('T', ' ') + ':00';
    return `${v} 23:59:59`;
};

const onDateFilter = () => {
    const perPage = props.meta?.per_page ?? 20;
    const sort = props.meta?.sort ?? 'total_output';
    const direction = props.meta?.direction ?? 'desc';
    router.get(window.location.pathname, { date_from: formatDateStart(dateFrom.value || null), date_to: formatDateEnd(dateTo.value || null), q: props.meta?.q ?? '', sort, direction, per_page: perPage }, { preserveState: true, replace: true });
};

const goNext = () => {
    const next = props.aggregates?.next_page;
    if (!next) return;
    router.visit(next, { preserveState: true, replace: true });
};

const goPrev = () => {
    const prev = props.aggregates?.prev_page;
    if (!prev) return;
    router.visit(prev, { preserveState: true, replace: true });
};

router.on('start', () => (loading.value = true));
router.on('finish', () => (loading.value = false));

</script>

<template>
    <Head title="Machine Group Aggregates" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-4 space-y-4">
            <h2 class="text-2xl font-semibold">Machine Group Aggregates</h2>
            <p class="text-sm text-muted-foreground">Aggregated output/target per machine group (sums across machines)</p>

            <div class="flex items-center gap-3 mb-3">
                <label class="text-sm">From</label>
                <input type="date" class="input" v-model="dateFrom" :disabled="loading" />
                <label class="text-sm">To</label>
                <input type="date" class="input" v-model="dateTo" :disabled="loading" />
                <button class="btn" @click="onDateFilter" :disabled="loading">Apply</button>
            </div>

            <DataTable serverMode :data="dataSource" :columns="[
                { key: 'machine_group_name', label: 'Machine Group', type: 'string', sortable: true, findable: true },
                { key: 'production_name', label: 'Production', type: 'string', sortable: true, findable: true },
                { key: 'machine_count', label: 'Machines', type: 'number', sortable: true, findable: false },
                { key: 'total_target', label: 'Target', type: 'number', sortable: true, findable: false },
                { key: 'total_output', label: 'Output', type: 'number', sortable: true, findable: false },
                { key: 'variance', label: 'Variance', type: 'number', sortable: false, findable: false },
            ]" :per-page="20" :initial-sort="props.meta?.sort ?? 'total_output'" :initial-direction="props.meta?.direction ?? 'desc'" :loading="loading" @server-search="onServerSearch" @server-sort="onServerSort" />

            <div class="mt-4 flex items-center justify-end gap-2">
                <button class="btn" :disabled="!props.aggregates?.prev_page || loading" @click="goPrev">Previous</button>
                <button class="btn" :disabled="!props.aggregates?.next_page || loading" @click="goNext">Next</button>
            </div>
        </div>
    </AppLayout>
</template>
