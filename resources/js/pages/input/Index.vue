<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, Link } from '@inertiajs/vue3';
import { type BreadcrumbItem } from '@/types';
import { ref, watch, computed, onMounted, onBeforeUnmount, nextTick } from 'vue';
import { Eye, Edit2, Trash2, Plus, Search, FileDown, FileUp, ChevronDown, X, Check, AlertTriangle, Download, Upload } from 'lucide-vue-next';
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
import { useLocalization } from '@/composables/useLocalization';

const { t } = useLocalization();

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    { title: t('input.title'), href: '/input' },
    { title: t('input.hourly_input'), href: '/input' },
]);

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

// Import section state
const showImportSection = ref(false);
const importFile = ref<File | null>(null);
const importLoading = ref(false);
const showImportPreviewModal = ref(false);
const importPreviewData = ref<any[]>([]);
const importSummary = ref<{ total_rows: number; valid_rows: number; invalid_rows: number; can_import: boolean } | null>(null);
const importPagination = ref<{ current_page: number; last_page: number; per_page: number; total: number }>({ current_page: 1, last_page: 1, per_page: 20, total: 0 });
const importAllData = ref<any[]>([]); // Store all validated rows for actual import
const fileInputRef = ref<HTMLInputElement | null>(null);
const importExecuting = ref(false);

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
    if (!selectedProduction.value) return t('app.all_productions');
    const found = productions.value.find((p: any) => String(p.production_id) === String(selectedProduction.value));
    return found ? found.production_name : t('app.all_productions');
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

// Import functions
const toggleImportSection = () => {
    showImportSection.value = !showImportSection.value;
    if (!showImportSection.value) {
        resetImportState();
    }
};

const resetImportState = () => {
    importFile.value = null;
    importPreviewData.value = [];
    importSummary.value = null;
    importAllData.value = [];
    if (fileInputRef.value) {
        fileInputRef.value.value = '';
    }
};

const downloadTemplate = () => {
    window.location.href = '/input/import/template';
};

const handleFileSelect = (event: Event) => {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0];
    if (file) {
        importFile.value = file;
    }
};

const uploadAndPreview = async () => {
    if (!importFile.value) {
        error('No file', 'Please select a file to upload.');
        return;
    }

    importLoading.value = true;

    const formData = new FormData();
    formData.append('file', importFile.value);

    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        const response = await fetch('/input/import/preview', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken || '',
            },
            body: formData,
        });

        if (!response.ok) {
            const result = await response.json();
            error('Upload failed', result.message || `Upload failed with status ${response.status}`);
            return;
        }

        const result = await response.json();

        if (!result.success) {
            error('Upload failed', result.message || 'Failed to process file.');
            return;
        }

        importPreviewData.value = result.data;
        importSummary.value = result.summary;
        importPagination.value = result.pagination;
        importAllData.value = result.import_data;
        showImportPreviewModal.value = true;
    } catch (err: any) {
        error('Upload failed', err.message || 'An error occurred while processing the file.');
    } finally {
        importLoading.value = false;
    }
};

const goImportPreviewPage = async (page: number) => {
    if (page === importPagination.value.current_page || importLoading.value) return;

    // Re-calculate pagination from stored data
    const perPage = importPagination.value.per_page;
    const total = importAllData.value.length;
    const lastPage = Math.ceil(total / perPage);
    const offset = (page - 1) * perPage;

    importPreviewData.value = importAllData.value.slice(offset, offset + perPage);
    importPagination.value = {
        ...importPagination.value,
        current_page: page,
        last_page: lastPage,
    };
};

const importPreviewPagesList = computed<(number | string)[]>(() => {
    const current = importPagination.value.current_page;
    const last = importPagination.value.last_page;
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

const closeImportPreview = () => {
    showImportPreviewModal.value = false;
};

const executeImport = async () => {
    if (!importSummary.value?.can_import) {
        error('Cannot import', 'Please fix all validation errors before importing.');
        return;
    }

    importExecuting.value = true;

    // Only send valid rows
    const validRows = importAllData.value.filter((row: any) => row.is_valid).map((row: any) => ({
        production_machine_group_id: row.production_machine_group_id,
        recorded_at: row.datetime_parsed,
        qty_normal: row.qty_normal,
        qty_reject: row.qty_reject,
        keterangan: row.keterangan,
    }));

    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        const response = await fetch('/input/import/execute', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken || '',
            },
            body: JSON.stringify({ rows: validRows }),
        });

        if (!response.ok) {
            const result = await response.json();
            error('Import failed', result.message || `Import failed with status ${response.status}`);
            return;
        }

        const result = await response.json();

        if (!result.success) {
            error('Import failed', result.message || 'Failed to import data.');
            return;
        }

        success('Import successful', result.message);
        showImportPreviewModal.value = false;
        showImportSection.value = false;
        resetImportState();

        // Refresh the page data
        router.reload({ preserveState: true });
    } catch (err: any) {
        error('Import failed', err.message || 'An error occurred while importing data.');
    } finally {
        importExecuting.value = false;
    }
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
                    <h2 class="text-2xl font-semibold">{{ t('input.hourly_input') }}</h2>
                    <p class="text-sm text-muted-foreground">{{ t('input.description') }}</p>
                </div>
                <div class="flex gap-2">
                    <button @click="exportToExcel" class="btn-secondary flex items-center gap-2 hover:cursor-pointer">
                        <FileDown class="size-4" />
                        {{ t('app.export') }}
                    </button>
                    <button @click="toggleImportSection" class="btn-secondary flex items-center gap-2 hover:cursor-pointer" :class="{ 'bg-blue-50 dark:bg-blue-950 border-blue-300 dark:border-blue-700': showImportSection }">
                        <FileUp class="size-4" />
                        {{ t('app.import') }}
                    </button>
                    <button
                        v-if="selectedIds.size > 0"
                        @click="bulkDelete"
                        class="btn-secondary flex items-center gap-2 text-red-600 hover:bg-red-50 dark:hover:bg-red-950 hover:cursor-pointer"
                    >
                        <Trash2 class="size-4" />
                        {{ t('input.delete_selected', { count: selectedIds.size }) }}
                    </button>
                    <div class="flex items-center">
                        <label class="text-sm mr-2">{{ t('app.per_page') }}</label>
                        <select v-model.number="perPage" @change="changePerPage(undefined)" class="input w-24">
                            <option v-for="opt in perPageOptions" :key="opt" :value="opt">{{ opt }}</option>
                        </select>
                    </div>
                    <Link
                        :href="`/input/create?date=${selectedDate}&production_id=${selectedProduction || ''}`"
                        class="btn flex items-center gap-2 rounded-md shadow-sm transition-colors transition-shadow duration-150 hover:opacity-90 hover:shadow-md hover:cursor-pointer focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-black/10 dark:focus:ring-white/20"
                    >
                        <Plus class="size-4" />
                        {{ t('input.record_input') }}
                    </Link>
                </div>
            </div>

            <!-- Import Section (Collapsible) -->
            <transition
                enter-active-class="transition-all duration-300 ease-out"
                enter-from-class="opacity-0 max-h-0"
                enter-to-class="opacity-100 max-h-[500px]"
                leave-active-class="transition-all duration-200 ease-in"
                leave-from-class="opacity-100 max-h-[500px]"
                leave-to-class="opacity-0 max-h-0"
            >
                <div v-if="showImportSection" class="mt-4 overflow-hidden">
                    <div class="rounded-lg border border-blue-200 dark:border-blue-800 bg-blue-50 dark:bg-blue-950/30 p-6">
                        <div class="flex items-start justify-between mb-4">
                            <div>
                                <h3 class="text-lg font-semibold text-blue-900 dark:text-blue-100">{{ t('input.import_from_excel') }}</h3>
                                <p class="text-sm text-blue-700 dark:text-blue-300 mt-1">
                                    {{ t('input.bulk_import') }}
                                </p>
                            </div>
                            <button @click="toggleImportSection" class="p-1 hover:bg-blue-100 dark:hover:bg-blue-900 rounded hover:cursor-pointer">
                                <X class="size-5 text-blue-600 dark:text-blue-400" />
                            </button>
                        </div>

                        <!-- Instructions -->
                        <div class="mb-4 p-4 bg-white dark:bg-zinc-900 rounded-lg border border-blue-100 dark:border-blue-900">
                            <h4 class="font-medium text-sm mb-2">{{ t('input.import_procedure') }}</h4>
                            <ol class="text-sm text-muted-foreground space-y-1 list-decimal list-inside">
                                <li>{{ t('input.import_step_1') }}</li>
                                <li>{{ t('input.import_step_2') }}</li>
                                <li>
                                    <strong>{{ t('input.production') }}</strong> {{ t('app.and') || 'and' }} <strong>{{ t('input.machine_group') }}</strong> {{ t('input.must_match_exactly') || 'names must match exactly (case-sensitive)' }}
                                </li>
                                <li>
                                    <strong>{{ t('input.datetime') }}</strong> {{ t('input.format') || 'format' }}: <code class="px-1 py-0.5 bg-gray-100 dark:bg-zinc-800 rounded text-xs">YYYY-MM-DD HH:00:00</code> (e.g., 2026-01-15 08:00:00)
                                </li>
                                <li>{{ t('input.import_step_5') }}</li>
                            </ol>
                        </div>

                        <!-- Actions -->
                        <div class="flex flex-wrap items-center gap-4">
                            <button @click="downloadTemplate" class="btn-secondary flex items-center gap-2 hover:cursor-pointer">
                                <Download class="size-4" />
                                {{ t('input.download_template') }}
                            </button>

                            <div class="flex-1 min-w-[200px]">
                                <div class="flex items-center gap-2">
                                    <input
                                        ref="fileInputRef"
                                        type="file"
                                        accept=".xlsx,.xls,.csv"
                                        @change="handleFileSelect"
                                        class="block w-full text-sm text-gray-500 dark:text-gray-400
                                            file:mr-4 file:py-2 file:px-4
                                            file:rounded-md file:border-0
                                            file:text-sm file:font-semibold
                                            file:bg-blue-50 file:text-blue-700
                                            dark:file:bg-blue-900 dark:file:text-blue-200
                                            hover:file:bg-blue-100 dark:hover:file:bg-blue-800
                                            file:cursor-pointer file:transition-colors"
                                    />
                                </div>
                            </div>

                            <button
                                @click="uploadAndPreview"
                                :disabled="!importFile || importLoading"
                                class="btn flex items-center gap-2 hover:cursor-pointer"
                                :class="{ 'opacity-50 cursor-not-allowed': !importFile || importLoading }"
                            >
                                <Upload v-if="!importLoading" class="size-4" />
                                <Spinner v-else class="size-4" />
                                {{ importLoading ? t('input.processing') : t('input.upload_preview') }}
                            </button>
                        </div>

                        <p v-if="importFile" class="mt-2 text-sm text-blue-700 dark:text-blue-300">
                            {{ t('input.selected_file') }} <strong>{{ importFile.name }}</strong>
                        </p>
                    </div>
                </div>
            </transition>

            <!-- Filters -->
            <div class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium mb-2">{{ t('app.date') }}</label>
                    <input
                        v-model="selectedDate"
                        type="date"
                        class="input w-full"
                    />
                </div>
                <div>
                    <label class="block text-sm font-medium mb-2">{{ t('input.production') }}</label>
                    <DropdownMenu>
                        <DropdownMenuTrigger :as-child="true" :style="{ '--reka-dropdown-menu-trigger-width': triggerWidth + 'px' }">
                            <button ref="triggerRef" type="button" class="input w-full flex items-center justify-between">
                                <span class="truncate">{{ selectedProductionLabel }}</span>
                                <ChevronDown class="ml-2 pr-1 size-4" />
                            </button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="start" :sideOffset="4" class="w-(--reka-dropdown-menu-trigger-width) min-w-[12rem]">
                            <DropdownMenuItem :as-child="true">
                                <button class="block w-full text-left px-3 py-2 text-sm" @click="selectProduction('')">{{ t('app.all_productions') }}</button>
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
                    <label class="block text-sm font-medium mb-2">{{ t('input.search') }}</label>
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
                                <th class="px-3 py-2 text-sm font-medium">{{ t('app.hour') }}</th>
                                <th class="px-3 py-2 text-sm font-medium">{{ t('input.production') }}</th>
                                <th class="px-3 py-2 text-sm font-medium">{{ t('input.machine_group') }}</th>
                                <!-- Removed Machine # column: group-level logging only -->
                                <th class="px-3 py-2 text-sm font-medium">{{ t('input.total_qty') }}</th>
                                <th class="px-3 py-2 text-sm font-medium">{{ t('input.normal_qty') }}</th>
                                <th class="px-3 py-2 text-sm font-medium">{{ t('input.reject_qty') }}</th>
                                <th class="px-3 py-2 text-sm font-medium">{{ t('app.actions') }}</th>
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
                                    {{ t('input.no_data') }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="mt-4 flex items-center justify-end gap-2">
                    <button class="hover:cursor-pointer btn" :disabled="(paginationMeta.current_page <= 1) || loading" @click="goPrev">{{ t('app.previous') }}</button>

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

                    <button class="hover:cursor-pointer btn" :disabled="(paginationMeta.current_page >= paginationMeta.last_page) || loading" @click="goNext">{{ t('app.next') }}</button>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Dialog -->
        <AlertDialog :open="showDeleteDialog" @update:open="(v) => showDeleteDialog = v">
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>
                        {{ deleteTargetId ? t('input.confirm_delete') : t('input.confirm_delete_multiple', { count: selectedIds.size }) }}
                    </AlertDialogTitle>
                    <AlertDialogDescription>
                        {{ deleteTargetId
                            ? t('input.delete_permanent')
                            : t('input.delete_permanent_multiple', { count: selectedIds.size })
                        }}
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel>{{ t('app.cancel') }}</AlertDialogCancel>
                    <AlertDialogAction
                        class="bg-red-600 hover:bg-red-700 text-white"
                        @click="deleteTargetId ? executeDelete() : executeBulkDelete()"
                    >
                        {{ t('app.delete') }}
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>

        <!-- Import Preview Modal -->
        <teleport to="body">
            <transition
                enter-active-class="transition-opacity duration-200"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition-opacity duration-150"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div v-if="showImportPreviewModal" class="fixed inset-0 z-50 flex items-center justify-center">
                    <!-- Backdrop -->
                    <div class="fixed inset-0 bg-black/50 dark:bg-black/70" @click="closeImportPreview"></div>

                    <!-- Modal Content -->
                    <div class="relative z-10 w-full max-w-5xl max-h-[90vh] m-4 bg-white dark:bg-zinc-900 rounded-lg shadow-xl flex flex-col">
                        <!-- Modal Header -->
                        <div class="flex items-center justify-between p-4 border-b border-gray-200 dark:border-zinc-700">
                            <div>
                                <h3 class="text-lg font-semibold">{{ t('input.import_preview') }}</h3>
                                <p class="text-sm text-muted-foreground">{{ t('input.review_before_import') }}</p>
                            </div>
                            <button @click="closeImportPreview" class="p-1 hover:bg-gray-100 dark:hover:bg-zinc-800 rounded">
                                <X class="size-5" />
                            </button>
                        </div>

                        <!-- Summary -->
                        <div v-if="importSummary" class="p-4 border-b border-gray-200 dark:border-zinc-700">
                            <div class="flex flex-wrap gap-4">
                                <div class="flex items-center gap-2 px-3 py-2 bg-gray-100 dark:bg-zinc-800 rounded-lg">
                                    <span class="text-sm text-muted-foreground">{{ t('input.total_rows') }}:</span>
                                    <span class="font-semibold">{{ importSummary.total_rows }}</span>
                                </div>
                                <div class="flex items-center gap-2 px-3 py-2 bg-green-100 dark:bg-green-900/30 rounded-lg">
                                    <Check class="size-4 text-green-600 dark:text-green-400" />
                                    <span class="text-sm text-green-700 dark:text-green-300">{{ t('input.valid') }}:</span>
                                    <span class="font-semibold text-green-700 dark:text-green-300">{{ importSummary.valid_rows }}</span>
                                </div>
                                <div v-if="importSummary.invalid_rows > 0" class="flex items-center gap-2 px-3 py-2 bg-red-100 dark:bg-red-900/30 rounded-lg">
                                    <AlertTriangle class="size-4 text-red-600 dark:text-red-400" />
                                    <span class="text-sm text-red-700 dark:text-red-300">{{ t('input.invalid') }}:</span>
                                    <span class="font-semibold text-red-700 dark:text-red-300">{{ importSummary.invalid_rows }}</span>
                                </div>
                            </div>

                            <div v-if="!importSummary.can_import" class="mt-3 p-3 bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800 rounded-lg">
                                <div class="flex items-start gap-2">
                                    <AlertTriangle class="size-5 text-amber-600 dark:text-amber-400 shrink-0 mt-0.5" />
                                    <div>
                                        <p class="font-medium text-amber-800 dark:text-amber-200">{{ t('input.cannot_import') }}</p>
                                        <p class="text-sm text-amber-700 dark:text-amber-300">
                                            {{ t('input.fix_errors') }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Table -->
                        <div class="flex-1 overflow-auto p-4">
                            <table class="w-full table-auto border-collapse text-sm">
                                <thead class="sticky top-0 bg-white dark:bg-zinc-900">
                                    <tr class="text-left border-b border-gray-200 dark:border-zinc-700">
                                        <th class="px-3 py-2 font-medium w-16">{{ t('input.row') }}</th>
                                        <th class="px-3 py-2 font-medium w-10">{{ t('input.status') }}</th>
                                        <th class="px-3 py-2 font-medium">{{ t('input.datetime') }}</th>
                                        <th class="px-3 py-2 font-medium">{{ t('input.production') }}</th>
                                        <th class="px-3 py-2 font-medium">{{ t('input.machine_group') }}</th>
                                        <th class="px-3 py-2 font-medium">{{ t('input.normal_qty') }}</th>
                                        <th class="px-3 py-2 font-medium">{{ t('input.reject_qty') }}</th>
                                        <th class="px-3 py-2 font-medium">{{ t('input.notes') }}</th>
                                        <th class="px-3 py-2 font-medium min-w-[200px]">{{ t('input.errors') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="row in importPreviewData"
                                        :key="row.row_number"
                                        class="border-b border-gray-100 dark:border-zinc-800"
                                        :class="{
                                            'bg-red-50 dark:bg-red-950/20': !row.is_valid,
                                            'hover:bg-gray-50 dark:hover:bg-zinc-800/50': row.is_valid
                                        }"
                                    >
                                        <td class="px-3 py-2 font-mono text-muted-foreground">{{ row.row_number }}</td>
                                        <td class="px-3 py-2">
                                            <Check v-if="row.is_valid" class="size-4 text-green-600 dark:text-green-400" />
                                            <AlertTriangle v-else class="size-4 text-red-600 dark:text-red-400" />
                                        </td>
                                        <td class="px-3 py-2 font-mono">
                                            <span v-if="row.datetime_parsed">{{ row.datetime_parsed }}</span>
                                            <span v-else class="text-muted-foreground italic">{{ row.datetime_raw || '-' }}</span>
                                        </td>
                                        <td class="px-3 py-2">{{ row.production_name || '-' }}</td>
                                        <td class="px-3 py-2">{{ row.machine_group_name || '-' }}</td>
                                        <td class="px-3 py-2">{{ row.qty_normal ?? '-' }}</td>
                                        <td class="px-3 py-2">{{ row.qty_reject ?? '-' }}</td>
                                        <td class="px-3 py-2 max-w-[150px] truncate" :title="row.keterangan || ''">{{ row.keterangan || '-' }}</td>
                                        <td class="px-3 py-2">
                                            <ul v-if="row.errors && row.errors.length > 0" class="text-xs text-red-600 dark:text-red-400 space-y-0.5">
                                                <li v-for="(err, idx) in row.errors" :key="idx" class="flex items-start gap-1">
                                                    <span class="shrink-0">•</span>
                                                    <span>{{ err }}</span>
                                                </li>
                                            </ul>
                                            <span v-else class="text-green-600 dark:text-green-400 text-xs">{{ t('input.ok') }}</span>
                                        </td>
                                    </tr>
                                    <tr v-if="importPreviewData.length === 0">
                                        <td colspan="9" class="px-3 py-8 text-center text-muted-foreground">
                                            No data found in the uploaded file.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div v-if="importPagination.last_page > 1" class="p-4 border-t border-gray-200 dark:border-zinc-700">
                            <div class="flex items-center justify-center gap-2">
                                <button
                                    class="hover:cursor-pointer btn btn-sm"
                                    :disabled="importPagination.current_page <= 1"
                                    @click="goImportPreviewPage(importPagination.current_page - 1)"
                                >
                                    {{ t('app.previous') }}
                                </button>

                                <template v-for="item in importPreviewPagesList" :key="typeof item === 'number' ? `ip-${item}` : `ie-${Math.random()}`">
                                    <button
                                        v-if="typeof item === 'number'"
                                        class="hover:cursor-pointer btn btn-sm"
                                        :class="{ 'ring-2 ring-offset-2 ring-black/10 dark:ring-white/20': item === importPagination.current_page }"
                                        :disabled="item === importPagination.current_page"
                                        @click="goImportPreviewPage(item)"
                                    >
                                        {{ item }}
                                    </button>
                                    <span v-else class="px-2 text-sm text-muted-foreground">{{ item }}</span>
                                </template>

                                <button
                                    class="hover:cursor-pointer btn btn-sm"
                                    :disabled="importPagination.current_page >= importPagination.last_page"
                                    @click="goImportPreviewPage(importPagination.current_page + 1)"
                                >
                                    {{ t('app.next') }}
                                </button>
                            </div>
                        </div>

                        <!-- Modal Footer -->
                        <div class="flex items-center justify-end gap-3 p-4 border-t border-gray-200 dark:border-zinc-700 bg-gray-50 dark:bg-zinc-800/50 rounded-b-lg">
                            <button @click="closeImportPreview" class="hover:cursor-pointer btn-secondary">
                                {{ t('app.cancel') }}
                            </button>
                            <button
                                @click="executeImport"
                                :disabled="!importSummary?.can_import || importExecuting"
                                class="hover:cursor-pointer btn flex items-center gap-2"
                                :class="{ 'opacity-50 cursor-not-allowed': !importSummary?.can_import || importExecuting }"
                            >
                                <Upload v-if="!importExecuting" class="size-4" />
                                <Spinner v-else class="size-4" />
                                {{ importExecuting ? t('input.importing') : t('input.import_records', { count: importSummary?.valid_rows ?? 0 }) }}
                            </button>
                        </div>
                    </div>
                </div>
            </transition>
        </teleport>

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
