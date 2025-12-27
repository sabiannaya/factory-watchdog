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
        today_actual: number;
        yesterday_actual: number;
    };
    dailyTrends?: Array<{
        date: string;
        actual: number;
    }>;
    recentLogs?: Array<{
        production: string;
        machine_group: string;
        recorded_at: string;
        output_normal: number;
        output_reject: number;
    }>;
    groupDistributionNormal?: Array<{ machine_group: string; total_output: number }>;
    groupDistributionReject?: Array<{ machine_group: string; total_output: number }>;
    productionWeekly?: Array<{ production: string; total_output: number }>;
}>();

const stats = props.stats ?? {
    total_productions: 0,
    active_productions: 0,
    total_machine_groups: 0,
    today_actual: 0,
    yesterday_actual: 0,
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

// Delta helpers
const yesterdayActual = computed(() => stats.yesterday_actual ?? 0)
const todayActual = computed(() => stats.today_actual ?? 0)

const deltaUnits = computed(() => (todayActual.value - yesterdayActual.value))

const todayTrend = computed(() => {
    const dir = todayActual.value > yesterdayActual.value ? 'up' : (todayActual.value < yesterdayActual.value ? 'down' : 'neutral')
    let color = 'gray'
    if (dir === 'up') {
        color = 'green'
    } else if (dir === 'down') {
        color = 'red'
    }
    const deltaUnitsLabel = `${deltaUnits.value >= 0 ? '+' : ''}${deltaUnits.value}`
    return {
        direction: dir,
        color,
        deltaUnitsLabel,
    }
})
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <!-- Quick Stats -->
            <div class="grid gap-4 md:grid-cols-3">
                <StatCard
                    title="Total Productions"
                    :value="stats.total_productions"
                    :subtitle="`${stats.active_productions} active`"
                />
                <StatCard
                    title="Machine Groups"
                    :value="stats.total_machine_groups"
                />
                <StatCard
                    title="Today Output"
                    :value="todayActual"
                    :subtitle="`vs ${yesterdayActual} yesterday`"
                    :trend="todayTrend.direction"
                    :trendValue="todayTrend.deltaUnitsLabel"
                />
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
                                <th class="px-4 py-2 text-right text-sm font-medium">Output (Reject)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-sidebar-border/70">
                            <tr v-for="(log, idx) in recentLogs" :key="idx" class="hover:bg-sidebar-border/5">
                                <td class="px-4 py-2 text-sm">{{ log.production }}</td>
                                <td class="px-4 py-2 text-sm">{{ log.machine_group }}</td>
                                <td class="px-4 py-2 text-sm text-muted-foreground">{{ formatDateTime(log.recorded_at) }}</td>
                                <td class="px-4 py-2 text-sm text-right">{{ log.output_normal }}</td>
                                <td class="px-4 py-2 text-sm text-right">{{ log.output_reject }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
