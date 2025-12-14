<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { type BreadcrumbItem } from '@/types';
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { Eye, Edit2, Trash2 } from 'lucide-vue-next';
import IconActionButton from '@/components/ui/IconActionButton.vue';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Data Management',
        href: '/data-management/production',
    },
    {
        title: 'Target',
        href: '/data-management/target',
    },
];

const loading = ref(false);

const props = defineProps<{ dailyTargets?: { data: any[]; next_cursor?: string | null; prev_cursor?: string | null }, meta?: { sort?: string; direction?: string; q?: string; per_page?: number; date_from?: string; date_to?: string } }>();

const dateFrom = ref(props.meta?.date_from ?? '');
const dateTo = ref(props.meta?.date_to ?? '');

const dataSource = props.dailyTargets?.data ?? [];

const onServerSearch = (q: string) => {
    const perPage = props.meta?.per_page ?? 10;
    const sort = props.meta?.sort ?? 'date';
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
    const next = props.dailyTargets?.next_cursor;
    if (!next) return;
    router.get(window.location.pathname, { cursor: next }, { preserveState: true, replace: true });
};

const goPrev = () => {
    const prev = props.dailyTargets?.prev_cursor;
    if (!prev) return;
    router.get(window.location.pathname, { cursor: prev }, { preserveState: true, replace: true });
};

// Router (Inertia) loading
router.on('start', () => (loading.value = true));
router.on('finish', () => (loading.value = false));

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
    const perPage = props.meta?.per_page ?? 10;
    const sort = props.meta?.sort ?? 'date';
    const direction = props.meta?.direction ?? 'asc';
    router.get(window.location.pathname, { date_from: formatDateStart(dateFrom.value || null), date_to: formatDateEnd(dateTo.value || null), q: props.meta?.q ?? '', sort, direction, per_page: perPage }, { preserveState: true, replace: true });
};

const goDetail = (id: number | string) => {
    router.get(`/data-management/target/${id}`);
};

const goEdit = (id: number | string) => {
    router.get(`/data-management/target/${id}/edit`);
};

const confirmDelete = (id: number | string) => {
    // eslint-disable-next-line no-alert
    if (!window.confirm('Are you sure you want to delete this daily target?')) return;
    router.visit(`/data-management/target/${id}`, { method: 'delete', preserveState: false });
};

</script>

<template>
    <Head title="Target" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-4">
            <div class="rounded-xl border border-sidebar-border/70 p-6">
                <h2 class="text-lg font-semibold">Target</h2>
                <p class="mt-2 text-sm text-muted-foreground">Manage daily targets</p>

                <div class="mt-4">
                    <div class="flex items-center gap-3 mb-3">
                        <label class="text-sm">From</label>
                        <input type="date" class="input" v-model="dateFrom" :disabled="loading" />
                        <label class="text-sm">To</label>
                        <input type="date" class="input" v-model="dateTo" :disabled="loading" />
                        <button class="btn" @click="onDateFilter" :disabled="loading">Apply</button>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full table-auto border-collapse">
                            <thead>
                                <tr class="text-left">
                                    <th class="px-3 py-2 text-sm font-medium">ID</th>
                                    <th class="px-3 py-2 text-sm font-medium">Date</th>
                                    <th class="px-3 py-2 text-sm font-medium">Target</th>
                                    <th class="px-3 py-2 text-sm font-medium">Actual</th>
                                    <th class="px-3 py-2 text-sm font-medium">Notes</th>
                                    <th class="px-3 py-2 text-sm font-medium">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="row in dataSource" :key="row.daily_target_id" class="border-t">
                                    <td class="px-3 py-2 text-sm">{{ row.daily_target_id }}</td>
                                    <td class="px-3 py-2 text-sm">{{ row.date }}</td>
                                    <td class="px-3 py-2 text-sm">{{ row.target_value }}</td>
                                    <td class="px-3 py-2 text-sm">{{ row.actual_value }}</td>
                                    <td class="px-3 py-2 text-sm">{{ row.notes }}</td>
                                    <td class="px-3 py-2 text-sm">
                                        <div class="flex items-center gap-3">
                                            <IconActionButton :icon="Eye" label="Detail" color="blue" :onClick="() => goDetail(row.daily_target_id)" />
                                            <IconActionButton :icon="Edit2" label="Edit" color="amber" :onClick="() => goEdit(row.daily_target_id)" />
                                            <IconActionButton :icon="Trash2" label="Delete" color="red" :onClick="() => confirmDelete(row.daily_target_id)" />
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4 flex items-center justify-end gap-2">
                        <button class="btn" :disabled="!props.dailyTargets?.prev_cursor || loading" @click="goPrev">Previous</button>
                        <button class="btn" :disabled="!props.dailyTargets?.next_cursor || loading" @click="goNext">Next</button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

