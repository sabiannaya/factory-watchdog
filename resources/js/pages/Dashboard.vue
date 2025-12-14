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
        machine_index: number;
        recorded_at: string;
        output: number;
        target: number;
    }>;
    groupDistribution?: Array<{ machine_group: string; total_output: number }>;
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

const groupDistribution = ref(props.groupDistribution ?? []);
const productionWeekly = ref(props.productionWeekly ?? []);

onMounted(async () => {
    if (!groupDistribution.value?.length || !productionWeekly.value?.length) {
        try {
            const res = await fetch('/api/dashboard/aggregates');
            if (res.ok) {
                const json = await res.json();
                if (!groupDistribution.value?.length) groupDistribution.value = json.groupDistribution ?? [];
                if (!productionWeekly.value?.length) productionWeekly.value = json.productionWeekly ?? [];
            }
        } catch (e) {
            // Silently fail, use empty arrays
        }
    }
});

const groupDistributionMapped = computed(() => 
    (groupDistribution.value || []).map((d: any) => ({ label: d.machine_group, value: d.total_output }))
);

const productionWeeklyMapped = computed(() => 
    (productionWeekly.value || []).map((d: any) => ({ label: d.production, value: d.total_output }))
);



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
                <StatCard 
                    title="Today's Performance" 
                    :value="`${stats.today_performance}%`"
                    :trend="stats.today_performance >= 100 ? 'up' : stats.today_performance >= 90 ? 'neutral' : 'down'"
                    :trend-value="`${stats.today_actual} / ${stats.today_target}`"
                />
                <StatCard 
                    title="Today's Output" 
                    :value="stats.today_actual"
                    :subtitle="`Target: ${stats.today_target}`"
                />
            </div>

            <!-- Charts Row (responsive) -->
            <div class="grid md:grid-cols-3 gap-4">
                <div class="md:col-span-2">
                    <LineChart :data="dailyTrends" title="7-Day Production Trend" />
                </div>
                <div class="md:col-span-1 flex justify-center">
                    <PieChart 
                        :data="groupDistributionMapped" 
                        title="Group Output Distribution (24h)"
                    />
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
                                <th class="px-4 py-2 text-left text-sm font-medium">Machine #</th>
                                <th class="px-4 py-2 text-left text-sm font-medium">Time</th>
                                <th class="px-4 py-2 text-right text-sm font-medium">Output</th>
                                <th class="px-4 py-2 text-right text-sm font-medium">Target</th>
                                <th class="px-4 py-2 text-right text-sm font-medium">Variance</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-sidebar-border/70">
                            <tr v-for="(log, idx) in recentLogs" :key="idx" class="hover:bg-sidebar-border/5">
                                <td class="px-4 py-2 text-sm">{{ log.production }}</td>
                                <td class="px-4 py-2 text-sm">{{ log.machine_group }}</td>
                                <td class="px-4 py-2 text-sm">{{ log.machine_index }}</td>
                                <td class="px-4 py-2 text-sm text-muted-foreground">{{ log.recorded_at }}</td>
                                <td class="px-4 py-2 text-sm text-right">{{ log.output }}</td>
                                <td class="px-4 py-2 text-sm text-right">{{ log.target }}</td>
                                <td 
                                    class="px-4 py-2 text-sm text-right font-medium"
                                    :class="{
                                        'text-green-600': log.output >= log.target,
                                        'text-red-600': log.output < log.target,
                                    }"
                                >
                                    {{ log.output - log.target >= 0 ? '+' : '' }}{{ log.output - log.target }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
