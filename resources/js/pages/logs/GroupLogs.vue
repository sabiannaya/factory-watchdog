<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { type BreadcrumbItem } from '@/types';
import DataTable from '@/components/DataTable.vue';
import { ref, computed } from 'vue';
import { FileDown, ChevronDown } from 'lucide-vue-next';
import {
    DropdownMenu,
    DropdownMenuTrigger,
    DropdownMenuContent,
    DropdownMenuItem,
} from '@/components/ui/dropdown-menu';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Logs', href: '/logs' },
    { title: 'Group Logs', href: '/logs/group' },
];

const props = defineProps<{ logs?: { data: any[]; next_page?: string | null; prev_page?: string | null }, meta?: any }>();
const loading = ref(false);
const dateFrom = ref(props.meta?.date_from?.split(' ')[0] ?? '');
const hourFrom = ref(props.meta?.date_from ? parseInt(props.meta.date_from.split(' ')[1]?.split(':')[0] || '0') : 0);
const dateTo = ref(props.meta?.date_to?.split(' ')[0] ?? '');
const hourTo = ref(props.meta?.date_to ? parseInt(props.meta.date_to.split(' ')[1]?.split(':')[0] || '23') : 23);
const dataSource = computed(() => props.logs?.data ?? []);

const hours = Array.from({ length: 24 }, (_, i) => ({
    value: i,
    label: `${i.toString().padStart(2, '0')}:00`,
}));

const hourFromLabel = computed(() => hours.find(h => h.value === hourFrom.value)?.label ?? '00:00');
const hourToLabel = computed(() => hours.find(h => h.value === hourTo.value)?.label ?? '23:00');
const selectHourFrom = (v: number) => { hourFrom.value = v; };
const selectHourTo = (v: number) => { hourTo.value = v; };

const onServerSearch = (q: string) => {
    const perPage = props.meta?.per_page ?? 20;
    const sort = props.meta?.sort ?? 'recorded_hour';
    const direction = props.meta?.direction ?? 'desc';
    router.get(window.location.pathname, { 
        q, 
        per_page: perPage, 
        sort, 
        direction,
        date_from: props.meta?.date_from ?? null,
        date_to: props.meta?.date_to ?? null,
    }, { preserveState: true, replace: true });
};

const onServerSort = ({ sort, direction }: { sort?: string; direction?: string }) => {
    const perPage = props.meta?.per_page ?? 20;
    const q = props.meta?.q ?? '';
    if (!sort) return;
    router.get(window.location.pathname, { 
        sort, 
        direction: direction ?? 'desc', 
        per_page: perPage, 
        q,
        date_from: props.meta?.date_from ?? null,
        date_to: props.meta?.date_to ?? null,
    }, { preserveState: true, replace: true });
};

const formatDateStart = (date: string | null, hour: number) => {
    if (!date) return null;
    return `${date} ${hour.toString().padStart(2, '0')}:00:00`;
};

const formatDateEnd = (date: string | null, hour: number) => {
    if (!date) return null;
    return `${date} ${hour.toString().padStart(2, '0')}:59:59`;
};

const onDateFilter = () => {
    const perPage = props.meta?.per_page ?? 20;
    const sort = props.meta?.sort ?? 'recorded_hour';
    const direction = props.meta?.direction ?? 'desc';
    router.get(window.location.pathname, { 
        date_from: formatDateStart(dateFrom.value || null, hourFrom.value), 
        date_to: formatDateEnd(dateTo.value || null, hourTo.value), 
        q: props.meta?.q ?? '', 
        sort, 
        direction, 
        per_page: perPage 
    }, { preserveState: true, replace: true });
};

const goNext = () => {
    const next = props.logs?.next_page;
    if (!next) return;
    router.visit(next, { preserveState: true, replace: true });
};

const goPrev = () => {
    const prev = props.logs?.prev_page;
    if (!prev) return;
    router.visit(prev, { preserveState: true, replace: true });
};

const exportToExcel = () => {
    const params = new URLSearchParams();
    if (dateFrom.value) {
        params.append('date_from', formatDateStart(dateFrom.value, hourFrom.value) || '');
    }
    if (dateTo.value) {
        params.append('date_to', formatDateEnd(dateTo.value, hourTo.value) || '');
    }
    if (props.meta?.q) {
        params.append('q', props.meta.q);
    }
    window.location.href = `/logs/group/export?${params.toString()}`;
};

router.on('start', () => (loading.value = true));
router.on('finish', () => (loading.value = false));

</script>

<template>
    <Head title="Group Logs" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-4 space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-semibold">Group Logs (hourly)</h2>
                    <p class="text-sm text-muted-foreground">Sums per machine group per hour</p>
                </div>
                <button @click="exportToExcel" class="btn-secondary flex items-center gap-2">
                    <FileDown class="size-4" />
                    Export to Excel
                </button>
            </div>

            <div class="flex items-center gap-3 mb-3 flex-wrap">
                <div class="flex items-center gap-2">
                    <label class="text-sm font-medium">From</label>
                    <input type="date" class="input" v-model="dateFrom" :disabled="loading" />
                    <DropdownMenu>
                        <DropdownMenuTrigger :as-child="true">
                            <button type="button" class="input flex items-center justify-between" :disabled="loading">
                                <span>{{ hourFromLabel }}</span>
                                <ChevronDown class="ml-2 size-4" />
                            </button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent class="min-w-[8rem]">
                            <DropdownMenuItem :as-child="true" v-for="h in hours" :key="h.value">
                                <button class="block w-full text-left px-3 py-2 text-sm" @click="selectHourFrom(h.value)">{{ h.label }}</button>
                            </DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
                </div>
                <div class="flex items-center gap-2">
                    <label class="text-sm font-medium">To</label>
                    <input type="date" class="input" v-model="dateTo" :disabled="loading" />
                    <DropdownMenu>
                        <DropdownMenuTrigger :as-child="true">
                            <button type="button" class="input flex items-center justify-between" :disabled="loading">
                                <span>{{ hourToLabel }}</span>
                                <ChevronDown class="ml-2 size-4" />
                            </button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent class="min-w-[8rem]">
                            <DropdownMenuItem :as-child="true" v-for="h in hours" :key="h.value">
                                <button class="block w-full text-left px-3 py-2 text-sm" @click="selectHourTo(h.value)">{{ h.label }}</button>
                            </DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
                </div>
                <button class="btn" @click="onDateFilter" :disabled="loading">Apply</button>
            </div>

            <DataTable serverMode :data="dataSource" :columns="[
                { key: 'recorded_hour', label: 'Hour', type: 'string', sortable: true, findable: false },
                { key: 'production_name', label: 'Production', type: 'string', sortable: true, findable: true },
                { key: 'machine_group_name', label: 'Machine Group', type: 'string', sortable: true, findable: true },
                { key: 'total_target', label: 'Target', type: 'number', sortable: true, findable: false },
                { key: 'total_output', label: 'Output', type: 'number', sortable: true, findable: false },
                { key: 'variance', label: 'Variance', type: 'number', sortable: false, findable: false },
            ]" :per-page="20" :initial-sort="props.meta?.sort ?? 'recorded_hour'" :initial-direction="props.meta?.direction ?? 'desc'" :loading="loading" @server-search="onServerSearch" @server-sort="onServerSort">
                <template #cell="{ row, column }">
                    <template v-if="column.key === 'total_output'">
                        <div class="flex flex-col">
                            <span class="font-medium">{{ Number(row.total_output_qty_normal ?? 0).toLocaleString() }}</span>
                            <span class="text-xs text-red-500">rej: {{ Number(row.total_output_qty_reject ?? 0).toLocaleString() }}</span>
                        </div>
                    </template>
                    <template v-else-if="column.key === 'total_target'">
                        <div class="flex flex-col">
                            <span class="font-medium">{{ Number(row.total_target_qty_normal ?? 0).toLocaleString() }}</span>
                            <span class="text-xs text-muted-foreground">rej: {{ Number(row.total_target_qty_reject ?? 0).toLocaleString() }}</span>
                        </div>
                    </template>
                    <template v-else>
                        {{ row[column.key] }}
                    </template>
                </template>
            </DataTable>

            <div class="mt-4 flex items-center justify-end gap-2">
                <button class="btn" :disabled="!props.logs?.prev_page || loading" @click="goPrev">Previous</button>
                <button class="btn" :disabled="!props.logs?.next_page || loading" @click="goNext">Next</button>
            </div>
        </div>
    </AppLayout>
</template>
