<script setup lang="ts">
import { computed } from 'vue';
import type { Component } from 'vue';

const props = defineProps<{
    label?: string;
    icon?: Component;
    color?: 'blue' | 'amber' | 'red' | string;
    onClick?: () => void;
}>();

const emit = defineEmits<{
    (e: 'click'): void;
}>();

const computedClasses = computed(() => {
    switch (props.color) {
        case 'amber':
            return 'text-amber-700 bg-amber-50 hover:bg-amber-200 dark:text-amber-300 dark:bg-amber-900/40 dark:hover:bg-amber-800/60 focus:ring-amber-400/60';
        case 'red':
            return 'text-red-700 bg-red-50 hover:bg-red-200 dark:text-red-300 dark:bg-red-900/40 dark:hover:bg-red-800/60 focus:ring-red-400/60';
        case 'blue':
        default:
            return 'text-blue-600 bg-blue-50 hover:bg-blue-200 dark:text-blue-300 dark:bg-blue-900/40 dark:hover:bg-blue-800/60 focus:ring-blue-400/60';
    }
});

function handleClick(): void {
    if (typeof props.onClick === 'function') {
        props.onClick();
    }

    emit('click');
}
</script>

<template>
    <button
        type="button"
        @click="handleClick"
        class="inline-flex items-center gap-2 px-3 py-1.5 rounded-md text-sm font-medium transition transform active:scale-95 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-offset-1 cursor-pointer"
        :class="computedClasses"
    >
        <component v-if="props.icon" :is="props.icon" class="h-4 w-4" />
        <span v-if="props.label">{{ props.label }}</span>
    </button>
</template>
