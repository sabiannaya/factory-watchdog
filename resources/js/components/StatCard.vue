<script setup lang="ts">
import { ChevronUp, ChevronDown, ChevronRight } from 'lucide-vue-next';

const props = defineProps<{
    title: string;
    value: string | number;
    subtitle?: string;
    trend?: 'up' | 'down' | 'neutral';
    trendValue?: string;
}>();
</script>

<template>
    <div class="h-full rounded-xl border border-sidebar-border/70 p-6 bg-card">
        <div class="flex items-center justify-between">
            <div class="flex-1">
                <p class="text-sm font-medium text-muted-foreground">{{ title }}</p>
                <h3 class="text-3xl font-bold mt-2">{{ value }}</h3>
                <p v-if="subtitle" class="text-xs text-muted-foreground mt-1">{{ subtitle }}</p>
            </div>
            <div v-if="trend" class="text-right">
                <span
                    :class="{
                        'text-green-600': trend === 'up',
                        'text-red-600': trend === 'down',
                        'text-gray-600': trend === 'neutral',
                    }"
                    class="text-sm font-medium inline-flex items-center gap-2"
                >
                    <component :is="trend === 'up' ? ChevronUp : (trend === 'down' ? ChevronDown : ChevronRight)" class="w-4 h-4" />
                    <span v-if="trendValue">{{ trendValue }}</span>
                </span>
            </div>
        </div>
    </div>
</template>
