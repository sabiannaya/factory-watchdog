<script setup lang="ts">
import type { InputConfig } from '@/composables/useInputConfig';

interface Props {
    config: InputConfig;
    showTitle?: boolean;
}

withDefaults(defineProps<Props>(), {
    showTitle: true,
});
</script>

<template>
    <div class="space-y-2 p-4 rounded-md border bg-muted/10">
        <h4 v-if="showTitle" class="text-sm font-medium">Configuration Preview</h4>
        <pre class="text-xs overflow-auto">{{ JSON.stringify(config, null, 2) }}</pre>
    </div>
</template>

