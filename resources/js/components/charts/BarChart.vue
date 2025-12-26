<script setup lang="ts">
import { computed, ref } from 'vue';

export interface BarChartData {
    label: string;
    value: number;
}

const props = defineProps<{
    data: BarChartData[] | null | undefined;
    title?: string;
    labelKey?: string;
    valueKey?: string;
}>();

const normalized = computed(() => (props.data ?? []).map(d => ({
    label: d.label,
    value: Number(d.value ?? 0),
})));

const maxVal = computed(() => {
    const vals = normalized.value.map(d => d.value);
    return Math.max(...vals, 1);
});

const totalOutput = computed(() => normalized.value.reduce((sum, d) => sum + d.value, 0));

const heights = computed(() => normalized.value.map(d => {
    const minHeight = 20;
    const maxHeight = 100;
    const ratio = maxVal.value === 0 ? 0 : d.value / maxVal.value;
    const scaledHeight = minHeight + (ratio * (maxHeight - minHeight));
    return Math.round(scaledHeight);
}));

const hoveredBar = ref<number | null>(null);
const tooltipPos = ref<{ left: number; top: number } | null>(null);
const tooltipRef = ref<HTMLElement | null>(null);
const containerRef = ref<HTMLElement | null>(null);

const showBarTooltip = (index: number, event: MouseEvent) => {
    hoveredBar.value = index;
    
    const container = containerRef.value;
    const tip = tooltipRef.value;
    if (!container || !tip) return;
    
    const containerRect = container.getBoundingClientRect();
    const target = event.currentTarget as HTMLElement;
    const targetRect = target.getBoundingClientRect();
    
    const margin = 8;
    const offset = 16;
    
    // Try to position to the right of the bar
    let left = (targetRect.right - containerRect.left) + offset;
    let top = (targetRect.top - containerRect.top) + (targetRect.height / 2) - (tip.offsetHeight / 2);
    
    // If no room on the right, position to the left
    if (left + tip.offsetWidth + margin > containerRect.width) {
        left = (targetRect.left - containerRect.left) - tip.offsetWidth - offset;
    }
    
    // If still no room, center above/below
    if (left < margin) {
        left = (targetRect.left - containerRect.left) + (targetRect.width / 2) - (tip.offsetWidth / 2);
        top = (targetRect.top - containerRect.top) - tip.offsetHeight - offset;
        
        // If no room above, show below
        if (top < margin) {
            top = (targetRect.bottom - containerRect.top) + offset;
        }
    }
    
    // Final clamping
    left = Math.max(margin, Math.min(containerRect.width - tip.offsetWidth - margin, left));
    top = Math.max(margin, Math.min(containerRect.height - tip.offsetHeight - margin, top));
    
    tooltipPos.value = { left: Math.round(left), top: Math.round(top) };
};

const hideBarTooltip = () => {
    hoveredBar.value = null;
    tooltipPos.value = null;
};

const hoveredData = computed(() => {
    if (hoveredBar.value === null) return null;
    return normalized.value[hoveredBar.value] ?? null;
});
</script>

<template>
    <div class="rounded-xl border border-sidebar-border/70 p-6 bg-card relative" ref="containerRef">
        <h3 v-if="title" class="text-lg font-semibold mb-6">{{ title }}</h3>
        <div v-if="normalized.length === 0" class="text-sm text-muted-foreground py-24 text-center">
            No data
        </div>
        <div v-else class="chart-wrapper">
            <div v-for="(d, i) in normalized" :key="i" class="bar-item">
                <div class="bar-container">
                    <div 
                        :style="{ height: `${heights[i]}%` }" 
                        class="bar"
                        @mouseenter="(e) => showBarTooltip(i, e)"
                        @mouseleave="hideBarTooltip"
                    ></div>
                </div>
                <div class="mt-4 flex flex-col items-center gap-1">
                    <div class="text-sm font-semibold text-foreground text-center">{{ d.label }}</div>
                    <div class="text-lg font-bold text-blue-600">{{ d.value }}</div>
                </div>
            </div>
        </div>
        
        <div
            ref="tooltipRef"
            class="absolute rounded-lg border border-border bg-white dark:bg-zinc-900 text-foreground px-4 py-3 shadow-xl pointer-events-none transition-opacity duration-150 z-50 min-w-[180px]"
            :class="{ 'opacity-0': !tooltipPos, 'opacity-100': tooltipPos }"
            :style="{ left: tooltipPos ? `${tooltipPos.left}px` : '-9999px', top: tooltipPos ? `${tooltipPos.top}px` : '-9999px' }"
        >
            <div v-if="hoveredData" class="space-y-2">
                <div class="font-semibold text-sm border-b border-border/50 pb-2">{{ hoveredData.label }}</div>
                <div class="space-y-1.5">
                    <div class="flex justify-between items-center text-xs">
                        <span class="text-muted-foreground">Total Output</span>
                        <span class="font-bold text-blue-600">{{ hoveredData.value.toLocaleString() }}</span>
                    </div>
                    <div v-if="totalOutput > 0" class="flex justify-between items-center text-xs">
                        <span class="text-muted-foreground">Share of Total</span>
                        <span class="font-bold">{{ ((hoveredData.value / totalOutput) * 100).toFixed(1) }}%</span>
                    </div>
                    <div v-if="maxVal > 0" class="flex justify-between items-center text-xs">
                        <span class="text-muted-foreground">vs Highest</span>
                        <span class="font-bold" :class="hoveredData.value === maxVal ? 'text-emerald-600' : 'text-muted-foreground'">{{ ((hoveredData.value / maxVal) * 100).toFixed(1) }}%</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.chart-wrapper {
    display: flex;
    align-items: flex-end;
    justify-content: center;
    gap: 4rem;
    padding: 0 1rem;
    height: 280px;
}

.bar-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    height: 100%;
}

.bar-container {
    flex: 1;
    display: flex;
    align-items: flex-end;
    width: 80px;
}

.bar {
    width: 100%;
    background: linear-gradient(to top, rgb(37 99 235), rgb(96 165 250));
    border-radius: 0.5rem 0.5rem 0 0;
    box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);
    transition: all 0.2s;
    cursor: pointer;
}

.bar:hover {
    background: linear-gradient(to top, rgb(29 78 216), rgb(59 130 246));
    box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.1);
}
</style>