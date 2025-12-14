<script setup lang="ts" generic="T extends Record<string, any>">
import { computed, ref, watch } from 'vue';
import { ArrowUp, ArrowDown, ArrowUpDown, Search } from 'lucide-vue-next';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';

export interface DataTableColumn {
    key: string;
    label: string;
    type?: 'string' | 'number' | 'boolean' | 'date';
    sortable?: boolean;
    findable?: boolean;
}

interface Props {
    data: T[];
    columns: DataTableColumn[];
    perPage?: number;
}

// add serverMode prop via defaults below

const props = withDefaults(defineProps<Props & { serverMode?: boolean; initialSort?: string | null; initialDirection?: 'asc' | 'desc'; loading?: boolean }>(), {
    perPage: 10,
    serverMode: false,
    initialSort: null,
    initialDirection: 'asc',
    loading: false,
});

// expose perPage directly for template use
const perPage = props.perPage;

// Search state
const searchQuery = ref('');
let searchTimeout: ReturnType<typeof setTimeout> | null = null;

const emit = defineEmits(['server-search', 'server-sort']);

// Sorting state
const sortColumn = ref<string | null>(props.initialSort ?? null);
const sortDirection = ref<'asc' | 'desc'>(props.initialDirection ?? 'asc');

// Watch for prop changes to update sort state when server provides meta
watch(() => props.initialSort, (s) => {
    sortColumn.value = s ?? null;
});
watch(() => props.initialDirection, (d) => {
    sortDirection.value = d ?? 'asc';
});

// Pagination state
const currentPage = ref(1);

// Sort function
const toggleSort = (columnKey: string) => {
    if (props.loading) return;
    if (sortColumn.value === columnKey) {
        sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortColumn.value = columnKey;
        sortDirection.value = 'asc';
    }
    // if in server mode, notify parent so it can request a new page
    if (props.serverMode) {
        emit('server-sort', { sort: sortColumn.value, direction: sortDirection.value });
    }
};

// Filter data by search query
const filteredData = computed(() => {
    // In server mode, don't filter locally - server handles it
    if (props.serverMode) {
        return props.data;
    }

    if (!searchQuery.value.trim()) {
        return props.data;
    }

    const query = searchQuery.value.toLowerCase();

    return props.data.filter((row) => {
        return props.columns.some((column) => {
            // Skip non-findable columns
            if (column.findable === false) {
                return false;
            }

            const value = row[column.key];
            if (value == null) return false;

            // Special handling for date types: check multiple string representations
            if (column.type === 'date') {
                const date = new Date(value);
                if (isNaN(date.getTime())) {
                    // fallback to raw string match
                    return String(value).toLowerCase().includes(query);
                }

                // ISO (yyyy-mm-ddThh:mm:ss.sssZ)
                const iso = date.toISOString().toLowerCase();
                if (iso.includes(query)) return true;

                // locale date (e.g., 12/1/2025) and locale datetime
                const localeDate = date.toLocaleDateString().toLowerCase();
                if (localeDate.includes(query)) return true;

                const localeString = date.toLocaleString().toLowerCase();
                if (localeString.includes(query)) return true;

                // timestamp numeric search
                const ts = String(date.getTime());
                if (ts.includes(query)) return true;

                return false;
            }

            return String(value).toLowerCase().includes(query);
        });
    });
});

// Sort filtered data
const sortedData = computed(() => {
    // In server mode, don't sort locally - server handles it
    if (props.serverMode) {
        return filteredData.value;
    }

    if (!sortColumn.value) {
        return filteredData.value;
    }

    const column = props.columns.find((col) => col.key === sortColumn.value);
    if (!column || column.sortable === false) {
        return filteredData.value;
    }

    return [...filteredData.value].sort((a, b) => {
        const aValue = a[sortColumn.value!];
        const bValue = b[sortColumn.value!];

        // Handle null/undefined values
        if (aValue == null && bValue == null) return 0;
        if (aValue == null) return 1;
        if (bValue == null) return -1;

        let comparison = 0;

        // Type-specific comparison
        if (column.type === 'number') {
            comparison = Number(aValue) - Number(bValue);
        } else if (column.type === 'date') {
            comparison =
                new Date(aValue).getTime() - new Date(bValue).getTime();
        } else {
            // String comparison (default)
            comparison = String(aValue).localeCompare(String(bValue));
        }

        return sortDirection.value === 'asc' ? comparison : -comparison;
    });
});

// Paginate sorted data (client-side only)
const paginatedData = computed(() => {
    if (props.serverMode) {
        // server provides pre-paginated data
        return props.data;
    }

    const start = (currentPage.value - 1) * props.perPage;
    const end = start + props.perPage;
    return sortedData.value.slice(start, end);
});

// Total pages
const totalPages = computed(() => {
    if (props.serverMode) return 1;
    return Math.ceil(sortedData.value.length / props.perPage);
});

// Reset to first page when search or sort changes
const resetPagination = () => {
    currentPage.value = 1;
};

// Watch for search changes with debounce
const handleSearch = () => {
    resetPagination();
    if (props.serverMode) {
        // Debounce server searches by 300ms
        if (searchTimeout) clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            emit('server-search', searchQuery.value);
        }, 300);
    }
};

// Format cell value based on type
const formatValue = (value: any, type?: string) => {
    if (value == null) return '-';

    switch (type) {
        case 'number':
            return Number(value).toLocaleString();
        case 'date':
            return new Date(value).toLocaleDateString();
        case 'boolean':
            return value ? 'Yes' : 'No';
        default:
            return String(value);
    }
};

// Get sort icon
const getSortIcon = (columnKey: string) => {
    if (sortColumn.value === columnKey) {
        return sortDirection.value === 'asc' ? ArrowUp : ArrowDown;
    }
    return ArrowUpDown;
};
</script>

<template>
    <div class="space-y-4">
        <!-- Search bar -->
        <div class="flex items-center gap-2">
            <div class="relative flex-1">
                <Search
                    class="absolute left-2 top-1/2 size-4 -translate-y-1/2 text-muted-foreground"
                />
                <Input
                    v-model="searchQuery"
                    placeholder="Search..."
                    class="pl-8"
                    @input="handleSearch"
                    :disabled="props.loading"
                />
                <Spinner v-if="props.loading" class="absolute right-2 top-1/2 -translate-y-1/2 text-muted-foreground" />
            </div>
        </div>

        <!-- Table -->
        <div class="rounded-md border overflow-x-auto">
            <table class="min-w-full divide-y divide-sidebar-border/70">
                <thead class="bg-background">
                    <tr>
                        <th
                            v-for="column in columns"
                            :key="column.key"
                            scope="col"
                            class="px-4 py-3 text-left text-sm font-medium text-sidebar-foreground/80"
                            :class="(column.sortable !== false && !props.loading) ? 'cursor-pointer select-none' : ''"
                                @click="(column.sortable !== false && !props.loading) ? toggleSort(column.key) : null"
                        >
                            <div class="flex items-center gap-2">
                                <span>{{ column.label }}</span>
                                <component
                                    :is="getSortIcon(column.key)"
                                    v-if="column.sortable !== false"
                                    class="size-4"
                                    :class="[ sortColumn === column.key ? 'text-foreground' : 'text-muted-foreground' ]"
                                />
                            </div>
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-card">
                    <tr v-if="paginatedData.length === 0">
                        <td :colspan="columns.length" class="h-24 text-center text-sm text-muted-foreground">
                            No results found.
                        </td>
                    </tr>
                    <tr v-for="(row, index) in paginatedData" :key="index" class="odd:bg-transparent even:bg-sidebar-border/5">
                        <td v-for="column in columns" :key="column.key" class="px-4 py-3 text-sm">
                            <slot name="cell" :row="row" :column="column">
                                {{ formatValue(row[column.key], column.type) }}
                            </slot>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination (client-side only) -->
        <div
            v-if="!props.serverMode && totalPages > 1"
            class="flex items-center justify-between px-2"
        >
            <div class="text-sm text-muted-foreground">
                Showing {{ (currentPage - 1) * perPage + 1 }} to
                {{ Math.min(currentPage * perPage, sortedData.length) }} of
                {{ sortedData.length }} results
            </div>
            <div class="flex items-center gap-2">
                <Button
                    variant="outline"
                    size="sm"
                    :disabled="currentPage === 1"
                    @click="currentPage--"
                >
                    Previous
                </Button>
                <div class="text-sm">
                    Page {{ currentPage }} of {{ totalPages }}
                </div>
                <Button
                    variant="outline"
                    size="sm"
                    :disabled="currentPage === totalPages"
                    @click="currentPage++"
                >
                    Next
                </Button>
            </div>
        </div>
    </div>
</template>
