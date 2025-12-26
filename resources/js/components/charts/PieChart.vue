<script setup lang="ts">
import { computed, ref, nextTick } from 'vue';

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

const isSingleSlice = computed(() => normalized.value.length === 1 && total.value > 0);

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
        
        // Calculate midpoint for tooltip positioning
        const midAngle = (start + end) / 2;
        const labelRadius = r * 0.6;
        const midX = cx + labelRadius * Math.cos(midAngle - Math.PI / 2);
        const midY = cy + labelRadius * Math.sin(midAngle - Math.PI / 2);
        
        start = end;
        return { path, label: d.label, value, midX, midY, percentage: total.value > 0 ? ((value / total.value) * 100).toFixed(1) : '0' };
    });
});

const hoveredSlice = ref<number | null>(null);
const tooltipPos = ref<{ left: number; top: number } | null>(null);
const tooltipRef = ref<HTMLElement | null>(null);
const svgRef = ref<SVGElement | null>(null);
const containerRef = ref<HTMLElement | null>(null);

const showSliceTooltip = async (index: number, event: MouseEvent) => {
    hoveredSlice.value = index;
    
    await nextTick();
    
    const container = containerRef.value;
    const svg = svgRef.value;
    const tip = tooltipRef.value;
    if (!container || !svg || !tip) return;
    
    const containerRect = container.getBoundingClientRect();
    const svgRect = svg.getBoundingClientRect();
    
    // Position tooltip to the right of the pie chart
    let left = (svgRect.right - containerRect.left) + 12;
    let top = (svgRect.top - containerRect.top) + (svgRect.height / 2) - (tip.offsetHeight / 2);
    
    // If not enough space on right, position on left
    if (left + tip.offsetWidth > containerRect.width - 8) {
        left = (svgRect.left - containerRect.left) - tip.offsetWidth - 12;
    }
    
    // Clamp vertically
    const margin = 8;
    top = Math.max(margin, Math.min(containerRect.height - tip.offsetHeight - margin, top));
    left = Math.max(margin, left);
    
    tooltipPos.value = { left: Math.round(left), top: Math.round(top) };
};

const hideSliceTooltip = () => {
    hoveredSlice.value = null;
    tooltipPos.value = null;
};

const hoveredArc = computed(() => {
    if (hoveredSlice.value === null) return null;
    return arcs.value[hoveredSlice.value] ?? null;
});
</script>

<template>
    <div class="rounded-xl border border-sidebar-border/70 p-6 bg-card w-full">
        <h3 v-if="title" class="text-lg font-semibold">{{ title }}</h3>
        <div v-if="total === 0" class="text-sm text-muted-foreground">No data</div>
        <div v-else class="flex flex-col items-center justify-center h-full relative" ref="containerRef">
            <div class="flex justify-center w-full mb-6">
                <svg ref="svgRef" width="200" height="200" viewBox="0 0 160 160" class="flex-shrink-0 block">
                    <g>
                        <circle cx="80" cy="80" r="60" fill="var(--card)" />
                        <g>
                            <template v-if="isSingleSlice">
                                <circle 
                                    cx="80" 
                                    cy="80" 
                                    r="60" 
                                    :fill="chartColors[0]" 
                                    class="cursor-pointer transition-opacity hover:opacity-80"
                                    @mouseenter="(e) => showSliceTooltip(0, e)"
                                    @mouseleave="hideSliceTooltip"
                                />
                            </template>
                            <template v-else>
                                <path 
                                    v-for="(a, i) in arcs" 
                                    :key="i" 
                                    :d="a.path" 
                                    :fill="chartColors[i % chartColors.length]" 
                                    class="cursor-pointer transition-opacity hover:opacity-80"
                                    @mouseenter="(e) => showSliceTooltip(i, e)"
                                    @mouseleave="hideSliceTooltip"
                                />
                            </template>
                        </g>
                    </g>
                </svg>
            </div>
            
            <div
                ref="tooltipRef"
                class="absolute rounded-lg border border-border bg-white dark:bg-zinc-900 text-foreground px-4 py-3 shadow-xl pointer-events-none transition-opacity duration-150 z-50 min-w-[160px]"
                :class="{ 'opacity-0': !tooltipPos, 'opacity-100': tooltipPos }"
                :style="{ left: tooltipPos ? `${tooltipPos.left}px` : '-9999px', top: tooltipPos ? `${tooltipPos.top}px` : '-9999px' }"
            >
                <div v-if="hoveredArc" class="space-y-2">
                    <div class="flex items-center gap-2">
                        <span class="w-3 h-3 rounded-full flex-shrink-0" :style="{ background: chartColors[hoveredSlice! % chartColors.length] }"></span>
                        <span class="font-semibold text-sm">{{ hoveredArc.label }}</span>
                    </div>
                    <div class="border-t border-border/50 pt-2 space-y-1">
                        <div class="flex justify-between items-center text-xs">
                            <span class="text-muted-foreground">Output</span>
                            <span class="font-bold text-foreground">{{ hoveredArc.value.toLocaleString() }}</span>
                        </div>
                        <div class="flex justify-between items-center text-xs">
                            <span class="text-muted-foreground">Share</span>
                            <span class="font-bold text-foreground">{{ hoveredArc.percentage }}%</span>
                        </div>
                    </div>
                </div>
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