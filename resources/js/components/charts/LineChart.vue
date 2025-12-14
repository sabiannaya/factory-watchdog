<script setup lang="ts">
import { computed, ref, nextTick, watch } from 'vue';

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

const targetPoints = computed(() => safeData.value.map((d, i) => ({
    x: xScale(i),
    y: yScale(Number(d.target ?? 0)),
})));

const actualPoints = computed(() => safeData.value.map((d, i) => ({
    x: xScale(i),
    y: yScale(Number(d.actual ?? 0)),
})));

const hoveredInfo = computed(() => {
    if (!hoveredPoint.value) {
        return null;
    }

    const { index } = hoveredPoint.value;
    const point = hoveredPoint.value.series === 'target' ? targetPoints.value[index] : actualPoints.value[index];
    const row = safeData.value[index];

    if (!point || !row) {
        return null;
    }

    return {
        x: point.x,
        y: point.y,
        date: row.date,
        target: row.target,
        actual: row.actual,
        series: hoveredPoint.value.series,
    };
});

const containerRef = ref<HTMLElement | null>(null);
const svgRef = ref<SVGElement | null>(null);
const tooltipRef = ref<HTMLElement | null>(null);
const tooltipPx = ref<{ left: number; top: number } | null>(null);

watch(hoveredInfo, async (val) => {
    if (!val) {
        tooltipPx.value = null;
        return;
    }

    await nextTick();

    const container = containerRef.value;
    const svgEl = svgRef.value;
    const tip = tooltipRef.value;
    if (!container || !svgEl || !tip) {
        tooltipPx.value = null;
        return;
    }

    const containerRect = container.getBoundingClientRect();
    const svgRect = svgEl.getBoundingClientRect();

    const relX = (val.x / chartWidth) * svgRect.width;
    const relY = (val.y / chartHeight) * svgRect.height;

    let left = (svgRect.left - containerRect.left) + relX;
    let top = (svgRect.top - containerRect.top) + relY;

    top = top - tip.offsetHeight - 8;

    const margin = 8;
    const minLeft = svgRect.left - containerRect.left + margin;
    const maxLeft = (svgRect.left - containerRect.left) + svgRect.width - tip.offsetWidth - margin;
    left = Math.max(minLeft, Math.min(maxLeft, left));

    const minTop = svgRect.top - containerRect.top + margin;
    const maxTop = (svgRect.top - containerRect.top) + svgRect.height - tip.offsetHeight - margin;
    top = Math.max(minTop, Math.min(maxTop, top));

    tooltipPx.value = { left: Math.round(left), top: Math.round(top) };
}, { immediate: true });

const showTooltip = (series: 'target' | 'actual', index: number) => {
    hoveredPoint.value = { series, index };
};

const hideTooltip = () => {
    hoveredPoint.value = null;
};
</script>

<template>
    <div class="rounded-xl border border-sidebar-border/70 p-6 bg-card">
        <h3 v-if="title" class="text-lg font-semibold mb-4">{{ title }}</h3>
        <div v-if="safeData.length === 0" class="text-center text-muted-foreground py-8">
            No data available
        </div>
        <div v-else class="relative w-full" ref="containerRef">
            <svg ref="svgRef" :viewBox="`0 0 ${chartWidth} ${chartHeight}`" width="100%" :height="chartHeight">
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
                    @mouseenter="showTooltip('target', i)"
                    @mouseleave="hideTooltip"
                />

                <circle 
                    v-for="(d, i) in safeData" 
                    :key="`actual-${i}`"
                    :cx="xScale(i)" 
                    :cy="yScale(Number(d.actual ?? 0))" 
                    r="4" 
                    fill="#10b981"
                    @mouseenter="showTooltip('actual', i)"
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
                class="absolute rounded-md border border-border bg-card text-card-foreground px-3 py-2 shadow-lg text-xs pointer-events-none transition-opacity duration-200 z-50"
                :class="{ 'opacity-0': !tooltipPx, 'opacity-100': tooltipPx }"
                :style="{ left: tooltipPx ? `${tooltipPx.left}px` : '-9999px', top: tooltipPx ? `${tooltipPx.top}px` : '-9999px' }"
                :aria-hidden="!tooltipPx"
            >
                <div class="font-semibold">{{ hoveredInfo ? hoveredInfo.date : '' }}</div>
                <div>Target: {{ hoveredInfo ? hoveredInfo.target : '' }}</div>
                <div>Actual: {{ hoveredInfo ? hoveredInfo.actual : '' }}</div>
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
