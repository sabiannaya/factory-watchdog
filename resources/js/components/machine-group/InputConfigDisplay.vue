<script setup lang="ts">
import type { InputConfig } from '@/composables/useInputConfig';

const props = defineProps<{ config?: InputConfig | null }>();
type Props = typeof props;
</script>

<template>
    <div v-if="config" class="mt-6">
        <h3 class="text-sm font-medium">Input Configuration</h3>
        <div class="mt-2 p-4 rounded-md border bg-muted/10">
            <div class="space-y-2">
                <div>
                    <span class="text-xs text-muted-foreground">Type:</span>
                    <span class="ml-2 text-sm font-medium">{{ config.type || 'custom' }}</span>
                </div>
                <div>
                    <span class="text-xs text-muted-foreground">Fields:</span>
                    <div class="mt-1 flex flex-wrap gap-2">
                        <span
                            v-for="field in config.fields || []"
                            :key="field"
                            class="inline-flex items-center px-2 py-1 rounded-full bg-primary/10 text-primary text-xs"
                        >
                            {{ field }}
                        </span>
                    </div>
                </div>
                <div v-if="config.grade_types && config.grade_types.length > 0">
                    <span class="text-xs text-muted-foreground">Grade Types:</span>
                    <div class="mt-1 flex flex-wrap gap-2">
                        <span
                            v-for="type in config.grade_types"
                            :key="type"
                            class="inline-flex items-center px-2 py-1 rounded-full bg-sky-600/10 text-sky-600 text-xs"
                        >
                            {{ type }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

