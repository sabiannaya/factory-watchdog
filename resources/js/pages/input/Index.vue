<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, Link } from '@inertiajs/vue3';
import { type BreadcrumbItem } from '@/types';
import { ref, watch, computed, onMounted, onBeforeUnmount, nextTick } from 'vue';
import { Eye, Edit2, Trash2, Plus, Search, FileDown, ChevronDown, Info } from 'lucide-vue-next';
import {
    DropdownMenu,
    DropdownMenuTrigger,
    DropdownMenuContent,
    DropdownMenuItem,
} from '@/components/ui/dropdown-menu';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';
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
const { toasts, dismiss: removeToast, success, error } = useToast();

const props = defineProps<{
    hourlyInputs?: {
        data: any[];
        next_cursor?: string | null;
        prev_cursor?: string | null;
        pagination?: { current_page?: number; last_page?: number; per_page?: number } | null;
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
const perPageOptions = [10, 20, 50, 100];
const perPage = ref<number>(props.meta?.per_page ?? 20);
const selectedIds = ref<Set<number>>(new Set());
const showDeleteDialog = ref(false);
const deleteTargetId = ref<number | null>(null);
let searchTimer: ReturnType<typeof setTimeout> | undefined;

// Tooltip refs for Variance header (teleported to body so it escapes overflow)
const varianceIconRef = ref<HTMLElement | null>(null);
const tooltipVisible = ref(false);
const tooltipPos = ref({ left: 0, top: 0 });

const showVarianceTooltip = () => {
    if (varianceIconRef.value) {
        const rect = varianceIconRef.value.getBoundingClientRect();
        tooltipPos.value.left = rect.left + window.scrollX;
        tooltipPos.value.top = rect.bottom + window.scrollY + 6;
    }
    tooltipVisible.value = true;
};

const hideVarianceTooltip = () => {
    tooltipVisible.value = false;
};

watch([selectedDate, selectedProduction], ([date, prodId]) => {
    router.get('/input', {
        date,
        production_id: prodId || null,
        q: search.value || null,
        per_page: perPage.value,
        page: 1,
    }, { preserveState: true, replace: true });
});

const triggerSearch = () => {
    router.get('/input', {
        q: search.value || null,
        date: selectedDate.value,
        production_id: selectedProduction.value || null,
        per_page: perPage.value,
        page: 1,
    }, { preserveState: true, replace: true });
};

const changePerPage = (v?: number) => {
    if (v) perPage.value = v;
    router.get('/input', {
        q: search.value || null,
        date: selectedDate.value,
        production_id: selectedProduction.value || null,
        per_page: perPage.value,
        page: 1,
    }, { preserveState: true, replace: true });
};

const selectedProductionLabel = computed(() => {
    if (!selectedProduction.value) return 'All Productions';
    const found = productions.value.find((p: any) => String(p.production_id) === String(selectedProduction.value));
    return found ? found.production_name : 'All Productions';
});

const selectProduction = (id: number | string) => {
    selectedProduction.value = id || '';
    triggerSearch();
};

const triggerRef = ref<HTMLElement | null>(null);
const triggerWidth = ref(0);

const updateTriggerWidth = () => {
    triggerWidth.value = triggerRef.value ? triggerRef.value.offsetWidth : 0;
};

onMounted(() => {
    nextTick(updateTriggerWidth);
    window.addEventListener('resize', updateTriggerWidth);
});

onBeforeUnmount(() => {
    window.removeEventListener('resize', updateTriggerWidth);
});

// Watch for search changes with debounce
watch(search, () => {
    if (searchTimer) clearTimeout(searchTimer);
    searchTimer = setTimeout(triggerSearch, 800);
});

const goNext = () => {
    const pagination = props.hourlyInputs?.pagination;
    const current = pagination?.current_page ?? 1;
    const last = pagination?.last_page ?? 1;
    if (current >= last) return;
    router.get('/input', {
        page: current + 1,
        date: selectedDate.value,
        production_id: selectedProduction.value || null,
        q: search.value || null,
    }, { preserveState: true });
};

const goPrev = () => {
    const pagination = props.hourlyInputs?.pagination;
    const current = pagination?.current_page ?? 1;
    if (current <= 1) return;
    router.get('/input', {
        page: current - 1,
        date: selectedDate.value,
        production_id: selectedProduction.value || null,
        q: search.value || null,
    }, { preserveState: true });
};

const paginationMeta = computed((): { current_page: number; last_page: number; per_page: number } => {
    const p = props.hourlyInputs?.pagination ?? null;
    return {
        current_page: p?.current_page ?? 1,
        last_page: p?.last_page ?? 1,
        per_page: p?.per_page ?? perPage.value,
    };
});

const pagesList = computed<(number | string)[]>(() => {
    const current = paginationMeta.value.current_page ?? 1;
    const last = paginationMeta.value.last_page ?? 1;
    const pages: (number | string)[] = [];
    if (last <= 7) {
        for (let i = 1; i <= last; i++) pages.push(i);
        return pages;
    }

    pages.push(1);
    const left = Math.max(2, current - 2);
    const right = Math.min(last - 1, current + 2);

    if (left > 2) pages.push('...');

    for (let i = left; i <= right; i++) pages.push(i);

    if (right < last - 1) pages.push('...');

    pages.push(last);
    return pages;
});

const goPage = (page: number) => {
    const current = paginationMeta.value.current_page ?? 1;
    if (page === current) return;
    router.get('/input', {
        page,
        date: selectedDate.value,
        production_id: selectedProduction.value || null,
        q: search.value || null,
        per_page: perPage.value,
    }, { preserveState: true });
};

const goDetail = (id: number) => router.get(`/input/${id}`);
const goEdit = (id: number) => router.get(`/input/${id}/edit`);

const buildQueryString = () => {
    const pagination = props.hourlyInputs?.pagination;
    const currentPage = pagination?.current_page ?? 1;
    const params = new URLSearchParams();
    params.append('date', selectedDate.value);
    if (selectedProduction.value) params.append('production_id', String(selectedProduction.value));
    if (search.value) params.append('q', search.value);
    params.append('page', String(currentPage));
    params.append('per_page', String(perPage.value));
    return params.toString();
};

const confirmDelete = (id: number) => {
    deleteTargetId.value = id;
    showDeleteDialog.value = true;
};

const executeDelete = () => {
    if (!deleteTargetId.value) return;
    const queryString = buildQueryString();
    router.delete(`/input/${deleteTargetId.value}?${queryString}`, {
        preserveState: true,
        onSuccess: () => {
            success('Deleted', 'Hourly input deleted successfully.');
        },
        onError: (err: any) => {
            const msg = err?.response?.data?.message ?? 'Unable to delete hourly input.';
            error('Delete failed', msg);
        }
    });
    showDeleteDialog.value = false;
    deleteTargetId.value = null;
};

const toggleSelection = (id: number) => {
    if (selectedIds.value.has(id)) {
        selectedIds.value.delete(id);
    } else {
        selectedIds.value.add(id);
    }
};

const toggleSelectAll = () => {
    if (selectedIds.value.size === dataSource.value.length) {
        selectedIds.value.clear();
    } else {
        dataSource.value.forEach((row: any) => selectedIds.value.add(row.hourly_log_id));
    }
};

const allSelected = computed(() => dataSource.value.length > 0 && selectedIds.value.size === dataSource.value.length);
const someSelected = computed(() => selectedIds.value.size > 0 && selectedIds.value.size < dataSource.value.length);

const bulkDelete = () => {
    if (selectedIds.value.size === 0) return;
    deleteTargetId.value = null;
    showDeleteDialog.value = true;
};

const executeBulkDelete = () => {
    const ids = Array.from(selectedIds.value);
    const queryString = buildQueryString();
    router.post(`/input/bulk-delete?${queryString}`, { ids }, {
        preserveState: true,
        onSuccess: () => {
            selectedIds.value.clear();
            showDeleteDialog.value = false;
            success('Deleted', `${ids.length} hourly inputs deleted.`);
        },
        onError: (err: any) => {
            const msg = err?.response?.data?.message ?? 'Unable to delete hourly inputs.';
            error('Delete failed', msg);
        }
    });
};

const exportToExcel = () => {
    const params = new URLSearchParams();
    params.append('date', selectedDate.value);
    if (selectedProduction.value) {
        params.append('production_id', String(selectedProduction.value));
    }
    if (search.value) {
        params.append('q', search.value);
    }
    window.location.href = `/input/export?${params.toString()}`;
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
                <div class="flex gap-2">
                    <button @click="exportToExcel" class="btn-secondary flex items-center gap-2">
                        <FileDown class="size-4" />
                        Export to Excel
                    </button>
                    <button
                        v-if="selectedIds.size > 0"
                        @click="bulkDelete"
                        class="btn-secondary flex items-center gap-2 text-red-600 hover:bg-red-50 dark:hover:bg-red-950"
                    >
                        <Trash2 class="size-4" />
                        Delete {{ selectedIds.size }} Selected
                    </button>
                    <div class="flex items-center">
                        <label class="text-sm mr-2">Per page</label>
                        <select v-model.number="perPage" @change="changePerPage(undefined)" class="input w-24">
                            <option v-for="opt in perPageOptions" :key="opt" :value="opt">{{ opt }}</option>
                        </select>
                    </div>
                    <Link
                        :href="`/input/create?date=${selectedDate}&production_id=${selectedProduction || ''}`"
                        class="btn flex items-center gap-2 rounded-md shadow-sm transition-colors transition-shadow duration-150 hover:opacity-90 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-black/10 dark:focus:ring-white/20"
                    >
                        <Plus class="size-4" />
                        Record Input
                    </Link>
                </div>
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
                    <DropdownMenu>
                        <DropdownMenuTrigger :as-child="true" :style="{ '--reka-dropdown-menu-trigger-width': triggerWidth + 'px' }">
                            <button ref="triggerRef" type="button" class="input w-full flex items-center justify-between">
                                <span class="truncate">{{ selectedProductionLabel }}</span>
                                <ChevronDown class="ml-2 pr-1 size-4" />
                            </button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="start" :sideOffset="4" class="w-(--reka-dropdown-menu-trigger-width) min-w-[12rem]">
                            <DropdownMenuItem :as-child="true">
                                <button class="block w-full text-left px-3 py-2 text-sm" @click="selectProduction('')">All Productions</button>
                            </DropdownMenuItem>
                            <template v-for="prod in productions" :key="prod.production_id">
                                <DropdownMenuItem :as-child="true">
                                    <button class="block w-full text-left px-3 py-2 text-sm" @click="selectProduction(prod.production_id)">{{ prod.production_name }}</button>
                                </DropdownMenuItem>
                            </template>
                        </DropdownMenuContent>
                    </DropdownMenu>
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
                                <th class="px-3 py-2 text-sm font-medium w-12">
                                    <input
                                        type="checkbox"
                                        :checked="allSelected"
                                        :indeterminate="someSelected"
                                        @change="toggleSelectAll"
                                    />
                                </th>
                                <th class="px-3 py-2 text-sm font-medium">Hour</th>
                                <th class="px-3 py-2 text-sm font-medium">Production</th>
                                <th class="px-3 py-2 text-sm font-medium">Machine Group</th>
                                <!-- Removed Machine # column: group-level logging only -->
                                <th class="px-3 py-2 text-sm font-medium">Total Qty</th>
                                <th class="px-3 py-2 text-sm font-medium">Qty Normal</th>
                                <th class="px-3 py-2 text-sm font-medium">Qty Reject</th>
                                <th class="px-3 py-2 text-sm font-medium">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="row in dataSource" :key="row.hourly_log_id" class="border-t">
                                <td class="px-3 py-2 text-sm">
                                    <input
                                        type="checkbox"
                                        :checked="selectedIds.has(row.hourly_log_id)"
                                        @change="() => toggleSelection(row.hourly_log_id)"
                                    />
                                </td>
                                <td class="px-3 py-2 text-sm font-mono">{{ row.hour }}</td>
                                <td class="px-3 py-2 text-sm">{{ row.production_name }}</td>
                                <td class="px-3 py-2 text-sm">{{ row.machine_group }}</td>
                                <!-- Group-level explicit quantity columns -->
                                <td class="px-3 py-2 text-sm font-semibold">{{ (row.output_qty_normal ?? 0) + (row.output_qty_reject ?? 0) }}</td>
                                <td class="px-3 py-2 text-sm">{{ row.output_qty_normal !== null ? row.output_qty_normal : '-' }}</td>
                                <td class="px-3 py-2 text-sm">{{ row.output_qty_reject !== null ? row.output_qty_reject : '-' }}</td>
                                <td class="px-3 py-2 text-sm">
                                    <div class="flex items-center gap-4">
                                        <IconActionButton :icon="Eye" label="Detail" color="blue" :onClick="() => goDetail(row.hourly_log_id)" />
                                        <IconActionButton :icon="Edit2" label="Edit" color="amber" :onClick="() => goEdit(row.hourly_log_id)" />
                                        <IconActionButton :icon="Trash2" label="Delete" color="red" :onClick="() => confirmDelete(row.hourly_log_id)" />
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="dataSource.length === 0">
                                <td colspan="8" class="px-3 py-8 text-center text-sm text-muted-foreground">
                                    No hourly inputs recorded for this date. Click "Record Input" to add one.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="mt-4 flex items-center justify-end gap-2">
                    <button class="hover:cursor-pointer btn" :disabled="(paginationMeta.current_page <= 1) || loading" @click="goPrev">Previous</button>

                    <template v-for="item in pagesList" :key="typeof item === 'number' ? `p-${item}` : `e-${Math.random()}`">
                        <button
                            v-if="typeof item === 'number'"
                            class="hover:cursor-pointer btn"
                            :class="{ 'opacity-90 ring-2 ring-offset-2 ring-black/10': item === paginationMeta.current_page }"
                            :disabled="item === paginationMeta.current_page || loading"
                            @click="() => goPage(Number(item))"
                        >
                            {{ item }}
                        </button>
                        <span v-else class="px-3 text-sm text-muted-foreground">{{ item }}</span>
                    </template>

                    <button class="hover:cursor-pointer btn" :disabled="(paginationMeta.current_page >= paginationMeta.last_page) || loading" @click="goNext">Next</button>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Dialog -->
        <AlertDialog :open="showDeleteDialog" @update:open="(v) => showDeleteDialog = v">
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>
                        {{ deleteTargetId ? 'Delete Hourly Input?' : `Delete ${selectedIds.size} Selected Items?` }}
                    </AlertDialogTitle>
                    <AlertDialogDescription>
                        {{ deleteTargetId
                            ? 'This action cannot be undone. This will permanently delete this hourly input record.'
                            : `This action cannot be undone. This will permanently delete ${selectedIds.size} hourly input records.`
                        }}
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel>Cancel</AlertDialogCancel>
                    <AlertDialogAction
                        class="bg-red-600 hover:bg-red-700 text-white"
                        @click="deleteTargetId ? executeDelete() : executeBulkDelete()"
                    >
                        Delete
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
        <!-- Teleported tooltip to escape overflow containers -->
        <teleport to="body">
            <div v-if="tooltipVisible" :style="{ left: tooltipPos.left + 'px', top: tooltipPos.top + 'px', position: 'fixed', zIndex: 9999 }">
                <div class="rounded-md bg-white dark:bg-zinc-900 border shadow-md p-3 text-xs text-muted-foreground w-72">
                    <div class="font-medium mb-1">How Variance is Calculated</div>
                    <div>Variance = (Normal Output - Normal Target) + (Target Reject - Reject Output)</div>
                    <div class="text-2xs text-muted-foreground mt-1">Positive variance means output is better than target.</div>
                </div>
            </div>
        </teleport>
    </AppLayout>
</template>
