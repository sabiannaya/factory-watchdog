<script setup lang="ts">
import { computed, ref } from 'vue';

export interface LineChartPoint {
    date: string;
    target: number;
    actual: number;
}

const props = defineProps<{
    data: LineChartPoint[] | null | undefined;
    title?: string;
}>();

const safeData = computed(() => props.data ?? []);

const hoveredPoint = ref<{ series: 'target' | 'actual'; index: number } | null>(null);

const rawMaxValue = computed(() => {
    const allValues = safeData.value.flatMap(d => [Number(d.target ?? 0), Number(d.actual ?? 0)]);
    return Math.max(...allValues, 100);
});

const niceMaxValue = computed(() => {
    const v = rawMaxValue.value;
    if (v <= 0) return 100;
    const magnitude = Math.pow(10, Math.floor(Math.log10(v)));
    const normalized = v / magnitude;
    let niceNormalized = Math.ceil(normalized);
    if (niceNormalized <= 2) niceNormalized = 2;
    else if (niceNormalized <= 5) niceNormalized = 5;
    return niceNormalized * magnitude;
});

const chartHeight = 300;
const chartWidth = 800;
const padding = { top: 20, right: 20, bottom: 40, left: 50 };

const xScale = (index: number) => padding.left + (index * (chartWidth - padding.left - padding.right) / (Math.max(safeData.value.length - 1, 1)));

const yScale = (value: number) => chartHeight - padding.bottom - ((value / niceMaxValue.value) * (chartHeight - padding.top - padding.bottom));

const targetPath = computed(() => {
    if (safeData.value.length === 0) return '';
    const points = safeData.value.map((d, i) => `${xScale(i)},${yScale(Number(d.target ?? 0))}`);
    return `M ${points.join(' L ')}`;
});

const actualPath = computed(() => {
    if (safeData.value.length === 0) return '';
    const points = safeData.value.map((d, i) => `${xScale(i)},${yScale(Number(d.actual ?? 0))}`);
    return `M ${points.join(' L ')}`;
});

const gridLines = computed(() => {
    const steps = 5;
    const arr: Array<{ y: number; label: number }> = [];
    for (let i = 1; i <= steps; i++) {
        const val = (niceMaxValue.value * i) / steps;
        arr.push({ y: yScale(val), label: Math.round(val) });
    }
    return arr;
});

const hoveredInfo = computed(() => {
    if (!hoveredPoint.value) {
        return null;
    }

    const { index } = hoveredPoint.value;
    const row = safeData.value[index];

    if (!row) {
        return null;
    }

    return {
        date: row.date,
        target: row.target,
        actual: row.actual,
        series: hoveredPoint.value.series,
    };
});

const containerRef = ref<HTMLElement | null>(null);
const tooltipRef = ref<HTMLElement | null>(null);
const tooltipPos = ref<{ left: number; top: number } | null>(null);

const showTooltip = (series: 'target' | 'actual', index: number, event: MouseEvent) => {
    hoveredPoint.value = { series, index };
    
    const container = containerRef.value;
    const tooltip = tooltipRef.value;
    if (!container || !tooltip) return;
    
    const containerRect = container.getBoundingClientRect();
    const target = event.target as SVGCircleElement;
    const targetRect = target.getBoundingClientRect();
    
    const margin = 8;
    const offset = 16;
    
    // Try to position to the right of the point
    let left = (targetRect.right - containerRect.left) + offset;
    let top = (targetRect.top - containerRect.top) + (targetRect.height / 2) - (tooltip.offsetHeight / 2);
    
    // If no room on the right, position to the left
    if (left + tooltip.offsetWidth + margin > containerRect.width) {
        left = (targetRect.left - containerRect.left) - tooltip.offsetWidth - offset;
    }
    
    // If still no room, center above/below
    if (left < margin) {
        left = (targetRect.left - containerRect.left) + (targetRect.width / 2) - (tooltip.offsetWidth / 2);
        top = (targetRect.top - containerRect.top) - tooltip.offsetHeight - offset;
        
        // If no room above, show below
        if (top < margin) {
            top = (targetRect.bottom - containerRect.top) + offset;
        }
    }
    
    // Final clamping
    left = Math.max(margin, Math.min(containerRect.width - tooltip.offsetWidth - margin, left));
    top = Math.max(margin, Math.min(containerRect.height - tooltip.offsetHeight - margin, top));
    
    tooltipPos.value = { left: Math.round(left), top: Math.round(top) };
};

const hideTooltip = () => {
    hoveredPoint.value = null;
    tooltipPos.value = null;
};
</script>

<template>
    <div class="rounded-xl border border-sidebar-border/70 p-6 bg-card">
        <h3 v-if="title" class="text-lg font-semibold mb-4">{{ title }}</h3>
        <div v-if="safeData.length === 0" class="text-center text-muted-foreground py-8">
            No data available
        </div>
        <div v-else class="relative w-full" ref="containerRef">
            <svg :viewBox="`0 0 ${chartWidth} ${chartHeight}`" width="100%" :height="chartHeight">
                <line 
                    v-for="(g, idx) in gridLines" 
                    :key="`grid-${idx}`"
                    :x1="padding.left" 
                    :y1="g.y" 
                    :x2="chartWidth - padding.right" 
                    :y2="g.y" 
                    stroke="#e5e7eb" 
                    stroke-width="1"
                />
                
                <text 
                    v-for="(g, idx) in gridLines" 
                    :key="`ylabel-${idx}`"
                    :x="padding.left - 10" 
                    :y="g.y + 4" 
                    text-anchor="end" 
                    class="text-xs fill-muted-foreground"
                >
                    {{ g.label }}
                </text>

                <path 
                    :d="targetPath" 
                    fill="none" 
                    stroke="#3b82f6" 
                    stroke-width="2"
                />

                <path 
                    :d="actualPath" 
                    fill="none" 
                    stroke="#10b981" 
                    stroke-width="2"
                />

                <circle 
                    v-for="(d, i) in safeData" 
                    :key="`target-${i}`"
                    :cx="xScale(i)" 
                    :cy="yScale(Number(d.target ?? 0))" 
                    r="4" 
                    fill="#3b82f6"
                    class="cursor-pointer"
                    @mouseenter="(e) => showTooltip('target', i, e)"
                    @mouseleave="hideTooltip"
                />

                <circle 
                    v-for="(d, i) in safeData" 
                    :key="`actual-${i}`"
                    :cx="xScale(i)" 
                    :cy="yScale(Number(d.actual ?? 0))" 
                    r="4" 
                    fill="#10b981"
                    class="cursor-pointer"
                    @mouseenter="(e) => showTooltip('actual', i, e)"
                    @mouseleave="hideTooltip"
                />

                <text 
                    v-for="(d, i) in safeData" 
                    :key="`xlabel-${i}`"
                    :x="xScale(i)" 
                    :y="chartHeight - padding.bottom + 20" 
                    text-anchor="middle" 
                    class="text-xs fill-muted-foreground"
                >
                    {{ d.date }}
                </text>
            </svg>

            <div
                ref="tooltipRef"
                class="absolute rounded-lg border border-border bg-white dark:bg-zinc-900 text-foreground px-4 py-3 shadow-xl pointer-events-none transition-opacity duration-150 z-50 min-w-[180px]"
                :class="{ 'opacity-0': !tooltipPos, 'opacity-100': tooltipPos }"
                :style="{ left: tooltipPos ? `${tooltipPos.left}px` : '-9999px', top: tooltipPos ? `${tooltipPos.top}px` : '-9999px' }"
                :aria-hidden="!tooltipPos"
            >
                <div v-if="hoveredInfo" class="space-y-2">
                    <div class="font-semibold text-sm border-b border-border/50 pb-2">{{ hoveredInfo.date }}</div>
                    <div class="space-y-1.5">
                        <div class="flex justify-between items-center text-xs">
                            <div class="flex items-center gap-2">
                                <span class="w-2.5 h-2.5 rounded-full bg-blue-500"></span>
                                <span class="text-muted-foreground">Target (Normal)</span>
                            </div>
                            <span class="font-bold">{{ hoveredInfo.target.toLocaleString() }}</span>
                        </div>
                        <div class="flex justify-between items-center text-xs">
                            <div class="flex items-center gap-2">
                                <span class="w-2.5 h-2.5 rounded-full bg-green-500"></span>
                                <span class="text-muted-foreground">Actual (Normal)</span>
                            </div>
                            <span class="font-bold">{{ hoveredInfo.actual.toLocaleString() }}</span>
                        </div>
                        <div class="flex justify-between items-center text-xs pt-1 border-t border-border/50">
                            <span class="text-muted-foreground">Variance</span>
                            <span 
                                class="font-bold"
                                :class="hoveredInfo.actual >= hoveredInfo.target ? 'text-emerald-600' : 'text-red-600'"
                            >
                                {{ hoveredInfo.actual >= hoveredInfo.target ? '+' : '' }}{{ (hoveredInfo.actual - hoveredInfo.target).toLocaleString() }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex items-center justify-center gap-6 mt-4">
            <div class="flex items-center gap-2">
                <div class="w-4 h-4 bg-blue-500 rounded-full"></div>
                <span class="text-sm text-muted-foreground">Target</span>
            </div>
            <div class="flex items-center gap-2">
                <div class="w-4 h-4 bg-green-500 rounded-full"></div>
                <span class="text-sm text-muted-foreground">Actual</span>
            </div>
        </div>
    </div>
</template>