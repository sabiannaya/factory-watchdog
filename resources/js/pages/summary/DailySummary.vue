<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { FileDown, Info } from 'lucide-vue-next';
import TooltipProvider from '@/components/ui/tooltip/TooltipProvider.vue'
import Tooltip from '@/components/ui/tooltip/Tooltip.vue'
import TooltipTrigger from '@/components/ui/tooltip/TooltipTrigger.vue'
import TooltipContent from '@/components/ui/tooltip/TooltipContent.vue'
import { DropdownMenu, DropdownMenuTrigger, DropdownMenuContent, DropdownMenuItem } from '@/components/ui/dropdown-menu';
import { computed, ref, nextTick } from 'vue';
import { type BreadcrumbItem } from '@/types';

interface SummaryRow {
    production_machine_group_id: number;
    production_name: string;
    machine_group_name: string;
    machine_count: number;
    target_qty_normal: number;
    target_qty_reject: number;
    target_total: number;
    actual_qty_normal: number;
    actual_qty_reject: number;
    actual_total: number;
    variance: number;
    achievement_percentage: number;
    status: 'achieved' | 'below';
}

interface Production {
    production_id: number;
    production_name: string;
}

const props = defineProps<{
    date: string;
    productions: Production[];
    selectedProductionId: number | null;
    summaryData: SummaryRow[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Summary', href: '/summary/daily' },
    { title: 'Daily Summary', href: window.location.pathname },
];

const dateInput = ref(props.date);
const productionFilter = ref(props.selectedProductionId ? props.selectedProductionId.toString() : 'all');

const handleDateChange = () => {
    const params: Record<string, string> = { date: dateInput.value };
    if (productionFilter.value !== 'all') {
        params.production_id = productionFilter.value;
    }
    router.get('/summary/daily', params, { preserveState: false });
};

const handleProductionChange = () => {
    const params: Record<string, string> = { date: dateInput.value };
    if (productionFilter.value !== 'all') {
        params.production_id = productionFilter.value;
    }
    router.get('/summary/daily', params, { preserveState: false });
};

const previousDay = () => {
    const current = new Date(dateInput.value);
    current.setDate(current.getDate() - 1);
    dateInput.value = current.toISOString().split('T')[0];
    handleDateChange();
};

const nextDay = () => {
    const current = new Date(dateInput.value);
    current.setDate(current.getDate() + 1);
    dateInput.value = current.toISOString().split('T')[0];
    handleDateChange();
};

// Trigger ref used to size dropdown content to match trigger width
const triggerRef = ref<HTMLElement | null>(null);

const adjustDropdownWidth = async () => {
    await nextTick();
    setTimeout(() => {
        const trigger = triggerRef.value;
        if (!trigger) return;

        // Find a visible dropdown menu element that contains our expected text
        const candidates = Array.from(document.querySelectorAll('body *')).filter((el) => {
            if (!(el instanceof HTMLElement)) return false;
            const rect = el.getBoundingClientRect();
            if (rect.width === 0 && rect.height === 0) return false;
            const text = (el.innerText || '').trim();
            return text.includes('All Productions');
        });

        if (candidates.length === 0) return;
        const menuEl = candidates[candidates.length - 1] as HTMLElement;
        // set explicit width to match trigger
        menuEl.style.width = `${trigger.offsetWidth}px`;
    }, 50);
};

window.addEventListener('resize', () => {
    // adjust if open
    adjustDropdownWidth();
});

const exportToExcel = () => {
    const params = new URLSearchParams();
    params.append('date', dateInput.value);
    if (productionFilter.value !== 'all') {
        params.append('production_id', productionFilter.value);
    }
    window.location.href = `/summary/daily/export?${params.toString()}`;
};

const totals = computed(() => {
    return props.summaryData.reduce(
        (acc, row) => {
            const tNormal = Number(row.target_qty_normal ?? 0);
            const tReject = Number(row.target_qty_reject ?? 0);
            const tTotal = tNormal + tReject;

            const aNormal = Number(row.actual_qty_normal ?? 0);
            const aReject = Number(row.actual_qty_reject ?? 0);
            const aTotal = Number(row.actual_total ?? (aNormal + aReject));

            // Contextual variance: (Qty Normal - Target Normal) + (Target Reject - Qty Reject)
            const variance = (aNormal - tNormal) + (tReject - aReject);

            return {
                target_total: acc.target_total + tTotal,
                target_normal: acc.target_normal + tNormal,
                target_reject: acc.target_reject + tReject,
                actual_total: acc.actual_total + aTotal,
                variance: acc.variance + variance,
            };
        },
        { target_total: 0, target_normal: 0, target_reject: 0, actual_total: 0, variance: 0 }
    );
});

const overallAchievement = computed(() => {
    return totals.value.target_total > 0
        ? Math.round((totals.value.actual_total / totals.value.target_total) * 100 * 10) / 10
        : 0;
});
</script>

<template>

    <Head title="Daily Summary" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-4 space-y-6">
            <div class="flex items-start justify-between">
                <div>
                    <h1 class="text-2xl font-bold">Daily Summary</h1>
                    <p class="text-muted-foreground">Compare daily targets with actual outputs</p>
                </div>
                <button @click="exportToExcel" class="btn-secondary flex items-center gap-2">
                    <FileDown class="size-4" />
                    Export to Excel
                </button>
            </div>

            <!-- Filters -->
            <div class="rounded-lg border border-sidebar-border/70 bg-card p-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Date Filter -->
                    <div class="space-y-2">
                        <label class="text-sm font-medium">Date</label>
                        <div class="flex items-center gap-2">
                            <button @click="previousDay"
                                class="px-3 py-2 rounded-md border border-input hover:bg-muted transition">
                                ‹
                            </button>
                            <input type="date" v-model="dateInput" @change="handleDateChange" class="input flex-1 ml-2 date-input" />
                            <button @click="nextDay"
                                class="px-3 py-2 rounded-md border border-input hover:bg-muted transition">
                                ›
                            </button>
                        </div>
                    </div>

                    <!-- Production Filter -->
                    <div class="space-y-2">
                        <label class="text-sm font-medium">Production</label>
                        <DropdownMenu>
                            <DropdownMenuTrigger asChild>
                                <button ref="triggerRef" @click="adjustDropdownWidth" class="input w-full text-left flex items-center justify-between">
                                    <span v-if="productionFilter === 'all'">All Productions</span>
                                    <span v-else>{{ props.productions.find(p => p.production_id.toString() === productionFilter)?.production_name }}</span>
                                    <svg class="ml-2 h-4 w-4 opacity-70" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M6 8l4 4 4-4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                </button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent class="w-full min-w-full">
                                <DropdownMenuItem class="block w-full text-left px-3 py-2" @click.prevent="(productionFilter = 'all', handleProductionChange())">All Productions</DropdownMenuItem>
                                <DropdownMenuItem v-for="production in props.productions" :key="production.production_id" class="block w-full text-left px-3 py-2" @click.prevent="(productionFilter = production.production_id.toString(), handleProductionChange())">
                                    {{ production.production_name }}
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </div>
                </div>
            </div>

            <!-- Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="rounded-lg border border-sidebar-border/70 bg-card p-4">
                    <p class="text-sm text-muted-foreground">Total Target</p>
                    <p class="text-2xl font-bold">{{ Number(totals.target_total).toLocaleString() }}</p>
                    <p class="text-xs text-muted-foreground mt-1">normal: {{
                        Number(totals.target_normal).toLocaleString() }} | reject: {{
                            Number(totals.target_reject).toLocaleString() }}</p>
                </div>
                <div class="rounded-lg border border-sidebar-border/70 bg-card p-4">
                    <p class="text-sm text-muted-foreground">Total Actual</p>
                    <p class="text-2xl font-bold">{{ Number(totals.actual_total).toLocaleString() }}</p>
                    <!-- <p class="text-xs text-muted-foreground mt-1">
                        {{ overallAchievement >= 100 ? 'Achieved' : 'Below' }}
                        <span :class="overallAchievement >= 100 ? 'text-emerald-600' : 'text-red-600'">{{
                            overallAchievement }}%</span>
                    </p> -->
                </div>
            </div>

            <!-- Summary Table -->
            <div class="rounded-lg border border-sidebar-border/70 bg-card overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-muted/50 border-b border-sidebar-border/70">
                            <tr>
                                <th class="px-4 py-3 text-left text-sm font-medium">Production</th>
                                <th class="px-4 py-3 text-left text-sm font-medium">Machine Group</th>
                                <th class="px-4 py-3 text-right text-sm font-medium">Target Normal</th>
                                <th class="px-4 py-3 text-right text-sm font-medium">Target Reject</th>
                                <th class="px-4 py-3 text-right text-sm font-medium">Output Normal</th>
                                <th class="px-4 py-3 text-right text-sm font-medium">Output Reject</th>
                                <th class="px-4 py-3 text-right text-sm font-medium">
                                    <TooltipProvider>
                                        <Tooltip>
                                            <TooltipTrigger asChild>
                                                <span class="inline-flex items-center gap-1 cursor-help select-none">
                                                    Variance
                                                    <Info class="size-3.5 opacity-70" />
                                                </span>
                                            </TooltipTrigger>
                                            <TooltipContent side="top" class="text-left">
                                                <div class="font-semibold mb-1">Variance</div>
                                                <div>Formula: (Qty Normal - Target Normal) + (Target Reject - Qty Reject)</div>
                                                <div class="mt-1 text-muted-foreground">Positive = above target, Negative = below target</div>
                                            </TooltipContent>
                                        </Tooltip>
                                    </TooltipProvider>
                                </th>
                                <th class="px-4 py-3 text-center text-sm font-medium">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="row in props.summaryData" :key="row.production_machine_group_id"
                                class="border-b border-sidebar-border/70 hover:bg-muted/20 transition">
                                <td class="px-4 py-3 text-sm">{{ row.production_name }}</td>
                                <td class="px-4 py-3 text-sm">{{ row.machine_group_name }}</td>
                                <td class="px-4 py-3 text-sm text-right font-medium">{{ Number(row.target_qty_normal ??
                                    0).toLocaleString() }}</td>
                                <td class="px-4 py-3 text-sm text-right text-red-600">{{ Number(row.target_qty_reject ??
                                    0).toLocaleString() }}</td>
                                <td class="px-4 py-3 text-sm text-right font-medium">{{ Number(row.actual_qty_normal ??
                                    0).toLocaleString() }}</td>
                                <td class="px-4 py-3 text-sm text-right text-red-600">{{ Number(row.actual_qty_reject ??
                                    0).toLocaleString() }}</td>
                                <td class="px-4 py-3 text-sm text-right">
                                    <!-- Contextual variance: (Qty Normal - Target Normal) + (Target Reject - Qty Reject) -->
                                    <span :class="(((row.actual_qty_normal ?? 0) - (row.target_qty_normal ?? 0)) + ((row.target_qty_reject ?? 0) - (row.actual_qty_reject ?? 0))) >= 0
                                            ? 'text-emerald-600 font-medium'
                                            : 'text-red-600 font-medium'
                                        ">
                                        {{ ((((row.actual_qty_normal ?? 0) - (row.target_qty_normal ?? 0)) + ((row.target_qty_reject ?? 0) - (row.actual_qty_reject ?? 0))) >= 0) ? '+' : '' }}
                                        {{ Number(((row.actual_qty_normal ?? 0) - (row.target_qty_normal ?? 0)) + ((row.target_qty_reject ?? 0) - (row.actual_qty_reject ?? 0))).toLocaleString() }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                        :class="row.status === 'achieved'
                                                ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-400'
                                                : 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-400'
                                            ">
                                        {{ row.status === 'achieved' ? 'Achieved' : 'Below Target' }}
                                    </span>
                                </td>
                            </tr>
                            <tr v-if="props.summaryData.length === 0">
                                <td colspan="8" class="px-4 py-8 text-center text-sm text-muted-foreground">
                                    No data available for this date
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style>
/* style native date picker icon to be visible in dark mode */
.date-input::-webkit-calendar-picker-indicator {
    filter: invert(1) brightness(1.6);
}
.dark .date-input::-webkit-calendar-picker-indicator {
    filter: invert(1) brightness(1.6);
}
.date-input::-moz-calendar-picker-indicator {
    filter: invert(1) brightness(1.6);
}
</style>
