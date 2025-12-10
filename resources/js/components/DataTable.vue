<script setup lang="ts" generic="T extends Record<string, any>">
import { computed, ref } from 'vue';
import { ArrowUp, ArrowDown, ArrowUpDown, Search } from 'lucide-vue-next';
import { Input } from '@/components/ui/input';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Button } from '@/components/ui/button';

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

const props = withDefaults(defineProps<Props>(), {
    perPage: 10,
});

// Search state
const searchQuery = ref('');

// Sorting state
const sortColumn = ref<string | null>(null);
const sortDirection = ref<'asc' | 'desc'>('asc');

// Pagination state
const currentPage = ref(1);

// Sort function
const toggleSort = (columnKey: string) => {
    if (sortColumn.value === columnKey) {
        sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortColumn.value = columnKey;
        sortDirection.value = 'asc';
    }
};

// Filter data by search query
const filteredData = computed(() => {
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

            return String(value).toLowerCase().includes(query);
        });
    });
});

// Sort filtered data
const sortedData = computed(() => {
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

// Paginate sorted data
const paginatedData = computed(() => {
    const start = (currentPage.value - 1) * props.perPage;
    const end = start + props.perPage;
    return sortedData.value.slice(start, end);
});

// Total pages
const totalPages = computed(() => {
    return Math.ceil(sortedData.value.length / props.perPage);
});

// Reset to first page when search or sort changes
const resetPagination = () => {
    currentPage.value = 1;
};

// Watch for search changes
const handleSearch = () => {
    resetPagination();
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
                />
            </div>
        </div>

        <!-- Table -->
        <div class="rounded-md border">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead
                            v-for="column in columns"
                            :key="column.key"
                            :class="[
                                column.sortable !== false
                                    ? 'cursor-pointer select-none'
                                    : '',
                            ]"
                            @click="
                                column.sortable !== false
                                    ? toggleSort(column.key)
                                    : null
                            "
                        >
                            <div class="flex items-center gap-2">
                                <span>{{ column.label }}</span>
                                <component
                                    :is="getSortIcon(column.key)"
                                    v-if="column.sortable !== false"
                                    class="size-4"
                                    :class="[
                                        sortColumn === column.key
                                            ? 'text-foreground'
                                            : 'text-muted-foreground',
                                    ]"
                                />
                            </div>
                        </TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow
                        v-if="paginatedData.length === 0"
                        class="hover:bg-transparent"
                    >
                        <TableCell
                            :colspan="columns.length"
                            class="h-24 text-center"
                        >
                            No results found.
                        </TableCell>
                    </TableRow>
                    <TableRow v-for="(row, index) in paginatedData" :key="index">
                        <TableCell v-for="column in columns" :key="column.key">
                            {{ formatValue(row[column.key], column.type) }}
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>

        <!-- Pagination -->
        <div
            v-if="totalPages > 1"
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
