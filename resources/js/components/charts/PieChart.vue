<script setup lang="ts">
import { computed } from 'vue';

export interface PieChartData {
    label: string;
    value: number;
}

const props = defineProps<{
    data: PieChartData[] | null | undefined;
    title?: string;
    colors?: string[];
}>();

const defaultColors = ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6'];
const chartColors = computed(() => props.colors ?? defaultColors);

const normalized = computed(() => (props.data ?? []).map(d => ({ 
    label: d.label, 
    value: Number(d.value ?? 0) 
})));

const total = computed(() => normalized.value.reduce((s, d) => s + d.value, 0));

const arcs = computed(() => {
    let start = 0;
    return normalized.value.map((d, idx) => {
        const value = Number(d.value || 0);
        const angle = total.value === 0 ? 0 : (value / total.value) * Math.PI * 2;
        const end = start + angle;
        const r = 60;
        const cx = 80;
        const cy = 80;
        const x1 = cx + r * Math.cos(start - Math.PI / 2);
        const y1 = cy + r * Math.sin(start - Math.PI / 2);
        const x2 = cx + r * Math.cos(end - Math.PI / 2);
        const y2 = cy + r * Math.sin(end - Math.PI / 2);
        const large = angle > Math.PI ? 1 : 0;
        const path = `M ${cx} ${cy} L ${x1} ${y1} A ${r} ${r} 0 ${large} 1 ${x2} ${y2} Z`;
        start = end;
        return { path, label: d.label, value };
    });
});
</script>

<template>
    <div class="rounded-xl border border-sidebar-border/70 p-6 bg-card w-full">
        <h3 v-if="title" class="text-lg font-semibold">{{ title }}</h3>
        <div v-if="normalized.length === 0" class="text-sm text-muted-foreground">No data</div>
        <div v-else class="flex flex-col items-center justify-center h-full">
            <div class="flex justify-center w-full mb-6">
                <svg width="200" height="200" viewBox="0 0 160 160" class="flex-shrink-0 block">
                    <g>
                        <circle cx="80" cy="80" r="60" fill="var(--card)" />
                        <g>
                            <path v-for="(a, i) in arcs" :key="i" :d="a.path" :fill="chartColors[i % chartColors.length]" />
                        </g>
                    </g>
                </svg>
            </div>
            <div class="w-full">
                <ul class="text-sm space-y-2">
                    <li v-for="(a, i) in arcs" :key="i" class="flex items-center justify-between gap-3">
                        <div class="flex items-center gap-2 min-w-0">
                            <span class="w-2.5 h-2.5 rounded-sm flex-shrink-0" :style="{background: chartColors[i % chartColors.length]}"></span>
                            <span class="truncate">{{ a.label }}</span>
                        </div>
                        <div class="text-muted-foreground flex-shrink-0">{{ a.value }}</div>
                    </li>
                </ul>
                <div class="text-xs text-muted-foreground mt-3 pt-3 border-t">Total: {{ total }}</div>
            </div>
        </div>
    </div>
</template>