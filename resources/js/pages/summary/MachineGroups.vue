<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { type BreadcrumbItem } from '@/types';
import DataTable from '@/components/DataTable.vue';
import { ref, computed } from 'vue';
import { FileDown } from 'lucide-vue-next';
import { useLocalization } from '@/composables/useLocalization';

const { t } = useLocalization();

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    { title: t('summary.title'), href: '/summary/machine-groups' },
    { title: t('summary.machine_groups_title'), href: '/summary/machine-groups' },
]);

const props = defineProps<{ aggregates?: { data: any[]; next_page?: string | null; prev_page?: string | null }, meta?: any }>();
const loading = ref(false);
const dateFrom = ref(props.meta?.date_from ?? '');
const dateTo = ref(props.meta?.date_to ?? '');
const dataSource = computed(() => props.aggregates?.data ?? []);

const onServerSearch = (q: string) => {
    const perPage = props.meta?.per_page ?? 20;
    const sort = props.meta?.sort ?? 'total_output';
    const direction = props.meta?.direction ?? 'desc';
    router.get(window.location.pathname, { q, per_page: perPage, sort, direction, date_from: dateFrom.value, date_to: dateTo.value }, { preserveState: true, replace: true });
};

const onServerSort = ({ sort, direction }: { sort?: string; direction?: string }) => {
    const perPage = props.meta?.per_page ?? 20;
    const q = props.meta?.q ?? '';
    if (!sort) return;
    router.get(window.location.pathname, { sort, direction: direction ?? 'desc', per_page: perPage, q, date_from: dateFrom.value, date_to: dateTo.value }, { preserveState: true, replace: true });
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

const exportToExcel = () => {
    const params = new URLSearchParams();
    if (dateFrom.value) {
        params.append('date_from', formatDateStart(dateFrom.value) || '');
    }
    if (dateTo.value) {
        params.append('date_to', formatDateEnd(dateTo.value) || '');
    }
    if (props.meta?.q) {
        params.append('q', props.meta.q);
    }
    window.location.href = `/summary/machine-groups/export?${params.toString()}`;
};

router.on('start', () => (loading.value = true));
router.on('finish', () => (loading.value = false));

</script>

<template>
    <Head :title="t('summary.machine_groups_title')" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-4 space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-semibold">{{ t('summary.machine_groups_title') }}</h2>
                    <p class="text-sm text-muted-foreground">{{ t('summary.machine_groups_description') }}</p>
                </div>
                <button @click="exportToExcel" class="btn-secondary flex items-center gap-2">
                    <FileDown class="size-4" />
                    {{ t('app.export') }}
                </button>
            </div>

            <div class="flex items-center gap-3 mb-3">
                <label class="text-sm">{{ t('app.from') }}</label>
                <input type="date" class="input" v-model="dateFrom" :disabled="loading" />
                <label class="text-sm">{{ t('app.to') }}</label>
                <input type="date" class="input" v-model="dateTo" :disabled="loading" />
                <button class="hover:cursor-pointer btn" @click="onDateFilter" :disabled="loading">{{ t('app.apply') }}</button>
            </div>

            <DataTable serverMode :data="dataSource" :columns="[
                { key: 'machine_group_name', label: t('summary.machine_group'), type: 'string', sortable: true, findable: true },
                { key: 'production_name', label: t('summary.production'), type: 'string', sortable: true, findable: true },
                { key: 'machine_count', label: t('summary.machines'), type: 'number', sortable: true, findable: false },
                { key: 'total_output_qty_normal', label: t('summary.actual_normal'), type: 'number', sortable: true, findable: false },
                { key: 'total_output_qty_reject', label: t('summary.actual_reject'), type: 'number', sortable: true, findable: false },
                { key: 'total_target_qty_normal', label: t('summary.target_normal'), type: 'number', sortable: true, findable: false },
                { key: 'total_target_qty_reject', label: t('summary.target_reject'), type: 'number', sortable: true, findable: false },
                { key: 'total_output', label: t('summary.actual_total'), type: 'number', sortable: true, findable: false },
                { key: 'total_target', label: t('summary.target_total'), type: 'number', sortable: true, findable: false },
                { key: 'variance', label: t('summary.variance'), type: 'number', sortable: false, findable: false },
            ]" :per-page="20" :initial-sort="props.meta?.sort ?? 'total_output'" :initial-direction="props.meta?.direction ?? 'desc'" :loading="loading" @server-search="onServerSearch" @server-sort="onServerSort">
                <template #cell="{ row, column }">
                    <template v-if="column.key === 'total_output_qty_normal'">
                        <span class="font-medium">{{ Number(row.total_output_qty_normal ?? 0).toLocaleString() }}</span>
                    </template>
                    <template v-else-if="column.key === 'total_output_qty_reject'">
                        <span class="text-red-500 font-medium">{{ Number(row.total_output_qty_reject ?? 0).toLocaleString() }}</span>
                    </template>
                    <template v-else-if="column.key === 'total_target_qty_normal'">
                        <span class="font-medium">{{ Number(row.total_target_qty_normal ?? 0).toLocaleString() }}</span>
                    </template>
                    <template v-else-if="column.key === 'total_target_qty_reject'">
                        <span class="text-muted-foreground">{{ Number(row.total_target_qty_reject ?? 0).toLocaleString() }}</span>
                    </template>
                    <template v-else-if="column.key === 'variance'">
                        <span :class="(row.variance ?? 0) >= 0 ? 'text-emerald-600 font-medium' : 'text-red-600 font-medium'">{{ (row.variance >= 0 ? '+' : '') + Number(row.variance ?? 0).toLocaleString() }}</span>
                    </template>
                    <template v-else>
                        {{ row[column.key] }}
                    </template>
                </template>
            </DataTable>

            <div class="mt-4 flex items-center justify-end gap-2">
                <button class="hover:cursor-pointer btn" :disabled="!props.aggregates?.prev_page || loading" @click="goPrev">{{ t('app.previous') }}</button>
                <button class="hover:cursor-pointer btn" :disabled="!props.aggregates?.next_page || loading" @click="goNext">{{ t('app.next') }}</button>
            </div>
        </div>
    </AppLayout>
</template>
