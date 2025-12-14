<script setup lang="ts">
import { computed } from 'vue';

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

const heights = computed(() => normalized.value.map(d => {
    const minHeight = 20;
    const maxHeight = 100;
    const ratio = maxVal.value === 0 ? 0 : d.value / maxVal.value;
    const scaledHeight = minHeight + (ratio * (maxHeight - minHeight));
    return Math.round(scaledHeight);
}));
</script>

<template>
    <div class="rounded-xl border border-sidebar-border/70 p-6 bg-card">
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
                    ></div>
                </div>
                <div class="mt-4 flex flex-col items-center gap-1">
                    <div class="text-sm font-semibold text-foreground text-center">{{ d.label }}</div>
                    <div class="text-lg font-bold text-blue-600">{{ d.value }}</div>
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
}

.bar:hover {
    background: linear-gradient(to top, rgb(29 78 216), rgb(59 130 246));
    box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.1);
}
</style>