<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, Link } from '@inertiajs/vue3';
import { type BreadcrumbItem } from '@/types';
import { ref, watch, computed } from 'vue';
import { Eye, Edit2, Trash2, Plus, Search } from 'lucide-vue-next';
import IconActionButton from '@/components/ui/IconActionButton.vue';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import ToastNotifications from '@/components/ToastNotifications.vue';
import { useToast } from '@/composables/useToast';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Input', href: '/input' },
    { title: 'Hourly Input', href: '/input' },
];

const loading = ref(false);
const { toasts, dismiss: removeToast } = useToast();

const props = defineProps<{
    hourlyInputs?: {
        data: any[];
        next_cursor?: string | null;
        prev_cursor?: string | null;
    };
    productions?: Array<{ production_id: number; production_name: string }>;
    meta?: {
        sort?: string;
        direction?: string;
        q?: string;
        per_page?: number;
        production_id?: number | string;
        date?: string;
    };
}>();

const dataSource = computed(() => props.hourlyInputs?.data ?? []);
const productions = computed(() => props.productions ?? []);
const search = ref(props.meta?.q ?? '');
const selectedDate = ref(props.meta?.date ?? new Date().toISOString().split('T')[0]);
const selectedProduction = ref<number | string>(props.meta?.production_id ?? '');
let searchTimer: ReturnType<typeof setTimeout> | undefined;

watch([selectedDate, selectedProduction], ([date, prodId]) => {
    const perPage = props.meta?.per_page ?? 20;
    router.get('/input', {
        date,
        production_id: prodId || null,
        q: search.value || null,
        per_page: perPage,
    }, { preserveState: true, replace: true });
});

const triggerSearch = () => {
    const perPage = props.meta?.per_page ?? 20;
    router.get('/input', {
        q: search.value || null,
        date: selectedDate.value,
        production_id: selectedProduction.value || null,
        per_page: perPage,
    }, { preserveState: true, replace: true });
};

// Watch for search changes with debounce
watch(search, () => {
    if (searchTimer) clearTimeout(searchTimer);
    searchTimer = setTimeout(triggerSearch, 800);
});

const goNext = () => {
    const next = props.hourlyInputs?.next_cursor;
    if (!next) return;
    router.get('/input', { 
        cursor: next, 
        date: selectedDate.value,
        production_id: selectedProduction.value || null,
        q: search.value || null,
    }, { preserveState: true, replace: true });
};

const goPrev = () => {
    const prev = props.hourlyInputs?.prev_cursor;
    if (!prev) return;
    router.get('/input', { 
        cursor: prev,
        date: selectedDate.value,
        production_id: selectedProduction.value || null,
        q: search.value || null,
    }, { preserveState: true, replace: true });
};

const goDetail = (id: number) => router.get(`/input/${id}`);
const goEdit = (id: number) => router.get(`/input/${id}/edit`);

const confirmDelete = (id: number) => {
    if (!window.confirm('Are you sure you want to delete this hourly input?')) return;
    router.delete(`/input/${id}`, { preserveState: false });
};

router.on('start', () => (loading.value = true));
router.on('finish', () => (loading.value = false));
</script>

<template>
    <Head title="Hourly Input" />
    <ToastNotifications :toasts="toasts" @dismiss="removeToast" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-4">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-semibold">Hourly Input</h2>
                    <p class="text-sm text-muted-foreground">Record and manage hourly production output</p>
                </div>
                <Link :href="`/input/create?date=${selectedDate}&production_id=${selectedProduction || ''}`" class="btn flex items-center gap-2">
                    <Plus class="size-4" />
                    Record Input
                </Link>
            </div>

            <!-- Filters -->
            <div class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium mb-2">Date</label>
                    <input
                        v-model="selectedDate"
                        type="date"
                        class="input w-full"
                    />
                </div>
                <div>
                    <label class="block text-sm font-medium mb-2">Production</label>
                    <select v-model="selectedProduction" class="input w-full">
                        <option value="">All Productions</option>
                        <option v-for="prod in productions" :key="prod.production_id" :value="prod.production_id">
                            {{ prod.production_name }}
                        </option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-2">Search</label>
                    <div class="relative">
                        <Search class="absolute left-2 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
                        <Input
                            v-model="search"
                            :disabled="loading"
                            type="search"
                            placeholder="Search..."
                            class="pl-8"
                        />
                        <Spinner v-if="loading" class="absolute right-2 top-1/2 -translate-y-1/2 text-muted-foreground" />
                    </div>
                </div>
            </div>

            <!-- Data Table -->
            <div class="mt-4">
                <div class="overflow-x-auto">
                    <table class="w-full table-auto border-collapse">
                        <thead>
                            <tr class="text-left">
                                <th class="px-3 py-2 text-sm font-medium">Hour</th>
                                <th class="px-3 py-2 text-sm font-medium">Production</th>
                                <th class="px-3 py-2 text-sm font-medium">Machine Group</th>
                                <!-- Removed Machine # column: group-level logging only -->
                                <th class="px-3 py-2 text-sm font-medium">Total Qty</th>
                                <th class="px-3 py-2 text-sm font-medium">Qty Normal</th>
                                <th class="px-3 py-2 text-sm font-medium">Qty Reject</th>
                                <th class="px-3 py-2 text-sm font-medium">Total Target</th>
                                <th class="px-3 py-2 text-sm font-medium">Target Normal</th>
                                <th class="px-3 py-2 text-sm font-medium">Target Reject</th>
                                <th class="px-3 py-2 text-sm font-medium">Variance</th>
                                <th class="px-3 py-2 text-sm font-medium">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="row in dataSource" :key="row.hourly_log_id" class="border-t">
                                <td class="px-3 py-2 text-sm font-mono">{{ row.hour }}</td>
                                <td class="px-3 py-2 text-sm">{{ row.production_name }}</td>
                                <td class="px-3 py-2 text-sm">{{ row.machine_group }}</td>
                                <!-- Group-level explicit quantity columns -->
                                <td class="px-3 py-2 text-sm font-semibold">{{ (row.output_qty_normal ?? 0) + (row.output_qty_reject ?? 0) }}</td>
                                <td class="px-3 py-2 text-sm">{{ row.output_qty_normal !== null ? row.output_qty_normal : '-' }}</td>
                                <td class="px-3 py-2 text-sm">{{ row.output_qty_reject !== null ? row.output_qty_reject : '-' }}</td>
                                <td class="px-3 py-2 text-sm">{{ (row.target_qty_normal ?? 0) + (row.target_qty_reject ?? 0) }}</td>
                                <td class="px-3 py-2 text-sm">{{ row.target_qty_normal !== undefined ? row.target_qty_normal : '-' }}</td>
                                <td class="px-3 py-2 text-sm">{{ row.target_qty_reject !== undefined ? row.target_qty_reject : '-' }}</td>
                                <td class="px-3 py-2 text-sm" :class="(row.total_output - row.total_target) >= 0 ? 'text-emerald-600' : 'text-red-600'">
                                    {{ (row.total_output - row.total_target) >= 0 ? '+' : '' }}{{ row.total_output - row.total_target }}
                                </td>
                                <td class="px-3 py-2 text-sm">
                                    <div class="flex items-center gap-4">
                                        <IconActionButton :icon="Eye" label="Detail" color="blue" :onClick="() => goDetail(row.hourly_log_id)" />
                                        <IconActionButton :icon="Edit2" label="Edit" color="amber" :onClick="() => goEdit(row.hourly_log_id)" />
                                        <IconActionButton :icon="Trash2" label="Delete" color="red" :onClick="() => confirmDelete(row.hourly_log_id)" />
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="dataSource.length === 0">
                                <td colspan="11" class="px-3 py-8 text-center text-sm text-muted-foreground">
                                    No hourly inputs recorded for this date. Click "Record Input" to add one.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="mt-4 flex items-center justify-end gap-2">
                    <button class="btn" :disabled="!props.hourlyInputs?.prev_cursor || loading" @click="goPrev">Previous</button>
                    <button class="btn" :disabled="!props.hourlyInputs?.next_cursor || loading" @click="goNext">Next</button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
