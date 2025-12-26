<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import StatCard from '@/components/StatCard.vue';
import LineChart from '@/components/charts/LineChart.vue';
import PieChart from '@/components/charts/PieChart.vue';
import BarChart from '@/components/charts/BarChart.vue';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
];

const props = defineProps<{
    stats?: {
        total_productions: number;
        active_productions: number;
        total_machine_groups: number;
        today_performance: number;
        today_actual: number;
        today_target: number;
    };
    dailyTrends?: Array<{
        date: string;
        target: number;
        actual: number;
    }>;
    recentLogs?: Array<{
        production: string;
        machine_group: string;
        recorded_at: string;
        output_normal: number;
        target_normal: number;
        output_reject: number;
        target_reject: number;
    }>;
    groupDistributionNormal?: Array<{ machine_group: string; total_output: number }>;
    groupDistributionReject?: Array<{ machine_group: string; total_output: number }>;
    productionWeekly?: Array<{ production: string; total_output: number }>;
}>();

const stats = props.stats ?? {
    total_productions: 0,
    active_productions: 0,
    total_machine_groups: 0,
    today_performance: 0,
    today_actual: 0,
    today_target: 0,
};

import { ref, onMounted, computed } from 'vue';

const dailyTrends = props.dailyTrends ?? [];
const recentLogs = props.recentLogs ?? [];

const groupDistributionNormal = ref(props.groupDistributionNormal ?? []);
const groupDistributionReject = ref(props.groupDistributionReject ?? []);
const productionWeekly = ref(props.productionWeekly ?? []);

onMounted(async () => {
    if ((!groupDistributionNormal.value?.length || !groupDistributionReject.value?.length) || !productionWeekly.value?.length) {
        try {
            const res = await fetch('/api/dashboard/aggregates');
            if (res.ok) {
                const json = await res.json();
                if (!groupDistributionNormal.value?.length) groupDistributionNormal.value = json.groupDistributionNormal ?? [];
                if (!groupDistributionReject.value?.length) groupDistributionReject.value = json.groupDistributionReject ?? [];
                if (!productionWeekly.value?.length) productionWeekly.value = json.productionWeekly ?? [];
            }
        } catch (e) {
            // Silently fail, use empty arrays
            return e;
        }
    }
});

const groupDistributionNormalMapped = computed(() =>
    (groupDistributionNormal.value || []).map((d: any) => ({ label: d.machine_group, value: d.total_output }))
);

const groupDistributionRejectMapped = computed(() => 
    (groupDistributionReject.value || []).map((d: any) => ({ label: d.machine_group, value: d.total_output }))
);

const productionWeeklyMapped = computed(() => 
    (productionWeekly.value || []).map((d: any) => ({ label: d.production, value: d.total_output }))
);


function formatDateTime(value: string | null | undefined): string {
    if (!value) return '';
    const d = new Date(value);
    if (Number.isNaN(d.getTime())) return value;
    return d.toLocaleString(undefined, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
    }).replace(/,\s*(\d{2})$/, ', $1:00');
}

// Performance / delta helpers
const yesterdayActual = computed(() => stats.yesterday_actual ?? 0)
const yesterdayTarget = computed(() => stats.yesterday_target ?? 0)
const todayActual = computed(() => stats.today_actual ?? 0)
const todayTarget = computed(() => stats.today_target ?? 0)

const fmtPct = (v: number | null | undefined) => `${Math.round((v ?? 0) * 10) / 10}%`

const yesterdayPercent = computed(() => fmtPct(stats.yesterday_performance ?? 0))
const todayPercent = computed(() => fmtPct(stats.today_performance ?? 0))

const yesterdayLabel = computed(() => `${yesterdayActual.value} / ${yesterdayTarget.value}`)
const todayLabel = computed(() => `${todayActual.value} / ${todayTarget.value}`)

const deltaUnits = computed(() => (todayActual.value - yesterdayActual.value))
const deltaPercent = computed(() => {
    const t = stats.today_performance ?? 0
    const y = stats.yesterday_performance ?? 0
    const diff = t - y
    return Math.round(diff * 10) / 10
})

const todayTrend = computed(() => {
    const tPerf = stats.today_performance ?? 0
    const yPerf = stats.yesterday_performance ?? 0
    const dir = tPerf > yPerf ? 'up' : (tPerf < yPerf ? 'down' : 'neutral')
    const meetsTarget = (todayActual.value >= todayTarget.value)
    let color = 'gray'
    if (dir === 'up') {
        color = meetsTarget ? 'green' : 'yellow'
    } else if (dir === 'down') {
        color = 'red'
    }
    const deltaLabel = `${deltaPercent.value >= 0 ? '+' : ''}${deltaPercent.value}%`
    const deltaPercentLabel = deltaLabel
    const deltaUnitsLabel = `${deltaUnits.value >= 0 ? '+' : ''}${deltaUnits.value}`
    return {
        direction: dir,
        color,
        deltaLabel,
        deltaPercentLabel,
        deltaUnitsLabel,
    }
})

const deltaColorClass = computed(() => {
    switch (todayTrend.value.color) {
        case 'green': return 'text-green-600'
        case 'yellow': return 'text-amber-600'
        case 'red': return 'text-red-600'
        default: return 'text-gray-600'
    }
})
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <!-- Quick Stats -->
            <div class="grid gap-4 md:grid-cols-4">
                <StatCard
                    title="Total Productions"
                    :value="stats.total_productions"
                    :subtitle="`${stats.active_productions} active`"
                />
                <StatCard
                    title="Machine Groups"
                    :value="stats.total_machine_groups"
                />
                <div :title="`Yesterday: ${yesterdayActual} / ${yesterdayTarget} — ${yesterdayPercent} performance`">
                        <div class="col-span-2">
                            <div class="relative group h-full">
                                <StatCard title="Yesterday Performance" :value="yesterdayPercent" :subtitle="yesterdayLabel" :trend="'neutral'" />
                                <div class="pointer-events-none opacity-0 group-hover:opacity-100 transition-opacity duration-150 absolute right-0 top-full mt-2 w-64 z-20">
                                    <div class="rounded-md bg-white dark:bg-zinc-900 border shadow-md p-3 text-xs text-muted-foreground">
                                        <div class="font-medium mb-1">Yesterday details</div>
                                        <div>Actual: <span class="font-semibold">{{ yesterdayActual }} / {{ yesterdayTarget }}</span></div>
                                        <div>Performance: <span class="font-semibold">{{ yesterdayPercent }}</span></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                </div>
                <div :title="`Today: ${todayActual} / ${todayTarget} — ${todayPercent} performance`">
                        <div class="col-span-2">
                            <div class="relative group h-full">
                                <StatCard title="Today Performance" :value="todayPercent" :subtitle="todayLabel" :trend="todayTrend.direction" :trendValue="todayTrend.deltaLabel" />
                                <div class="pointer-events-none opacity-0 group-hover:opacity-100 transition-opacity duration-150 absolute right-0 top-full mt-2 w-72 z-20">
                                    <div class="rounded-md bg-white dark:bg-zinc-900 border shadow-md p-3 text-xs text-muted-foreground">
                                        <div class="font-medium mb-1">Today details</div>
                                        <div>Actual: <span class="font-semibold">{{ todayActual }} / {{ todayTarget }}</span></div>
                                        <div>Delta vs Yesterday: <span :class="deltaColorClass" class="font-semibold">{{ todayTrend.deltaPercentLabel }} ({{ todayTrend.deltaUnitsLabel }})</span></div>
                                        <div class="text-2xs text-muted-foreground mt-1">Arrow shows direction vs yesterday. Color: green = improved and meeting target; yellow = improved but below target; red = worse than yesterday.</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                </div>
            </div>

            <!-- Charts Row (responsive) -->
            <div class="grid gap-4">
                <div>
                    <LineChart :data="dailyTrends" title="7-Day Production Trend (Qty Normal)" />
                </div>

                <div class="grid md:grid-cols-2 gap-4">
                    <div class="flex justify-center">
                        <PieChart 
                            :data="groupDistributionNormalMapped" 
                            title="Group Qty Normal Output Distribution (24h)"
                        />
                    </div>
                    <div class="flex justify-center">
                        <PieChart 
                            :data="groupDistributionRejectMapped" 
                            title="Group Qty Reject Output Distribution (24h)"
                        />
                    </div>
                </div>
            </div>

            <!-- Production Output Chart (full width) -->
            <div class="mt-2">
                <BarChart 
                    :data="productionWeeklyMapped" 
                    title="Production Output (7d)"
                />
            </div>

            <!-- Recent Hourly Logs -->
            <div class="rounded-xl border border-sidebar-border/70 p-6 bg-card">
                <h3 class="text-lg font-semibold mb-4">Recent Hourly Logs</h3>
                <div v-if="recentLogs.length === 0" class="text-center text-muted-foreground py-8">
                    No recent logs available
                </div>
                <div v-else class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-sidebar-border/70">
                        <thead>
                            <tr>
                                <th class="px-4 py-2 text-left text-sm font-medium">Production</th>
                                <th class="px-4 py-2 text-left text-sm font-medium">Machine Group</th>
                                <th class="px-4 py-2 text-left text-sm font-medium">Time</th>
                                <th class="px-4 py-2 text-right text-sm font-medium">Output (Normal)</th>
                                <th class="px-4 py-2 text-right text-sm font-medium">Target (Normal)</th>
                                <th class="px-4 py-2 text-right text-sm font-medium">Output (Reject)</th>
                                <th class="px-4 py-2 text-right text-sm font-medium">Target (Reject)</th>
                                <th class="px-4 py-2 text-right text-sm font-medium">Variance (Reject)</th>
                                <th class="px-4 py-2 text-right text-sm font-medium">Variance (Normal)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-sidebar-border/70">
                            <tr v-for="(log, idx) in recentLogs" :key="idx" class="hover:bg-sidebar-border/5">
                                <td class="px-4 py-2 text-sm">{{ log.production }}</td>
                                <td class="px-4 py-2 text-sm">{{ log.machine_group }}</td>
                                <td class="px-4 py-2 text-sm text-muted-foreground">{{ formatDateTime(log.recorded_at) }}</td>
                                <td class="px-4 py-2 text-sm text-right">{{ log.output_normal }}</td>
                                <td class="px-4 py-2 text-sm text-right">{{ log.target_normal }}</td>
                                <td class="px-4 py-2 text-sm text-right">{{ log.output_reject }}</td>
                                <td class="px-4 py-2 text-sm text-right">{{ log.target_reject }}</td>
                                <td 
                                    class="px-4 py-2 text-sm text-right font-medium"
                                    :class="{
                                        'text-green-600': log.output_reject <= log.target_reject,
                                        'text-red-600': log.output_reject > log.target_reject,
                                    }"
                                >
                                    {{ log.target_reject - log.output_reject >= 0 ? '+' : '' }}{{ log.target_reject - log.output_reject }}
                                </td>
                                <td 
                                    class="px-4 py-2 text-sm text-right font-medium"
                                    :class="{
                                        'text-green-600': log.output_normal >= log.target_normal,
                                        'text-red-600': log.output_normal < log.target_normal,
                                    }"
                                >
                                    {{ log.output_normal - log.target_normal >= 0 ? '+' : '' }}{{ log.output_normal - log.target_normal }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
