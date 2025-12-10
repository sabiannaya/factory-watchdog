<script setup lang="ts">
import { computed } from 'vue'
import PlaceholderPattern from '@/components/PlaceholderPattern.vue'
import Skeleton from '@/components/ui/skeleton/Skeleton.vue'

interface Props {
  title?: string
  value?: string | number
  delta?: number
  deltaDirection?: 'up' | 'down' | null
  small?: string | number
  subtitle?: string
  loading?: boolean
}

const props = defineProps<Props>()

const deltaSign = computed(() => {
  if (props.deltaDirection === 'up') return '+'
  if (props.deltaDirection === 'down') return '-'
  return ''
})
</script>

<template>
  <div class="relative flex h-full flex-col gap-4 overflow-hidden rounded-xl border border-sidebar-border/70 bg-card p-4 text-card-foreground dark:border-sidebar-border">
    <PlaceholderPattern v-if="props.loading" />

    <template v-else>
      <div class="flex items-start justify-between">
        <div class="text-sm font-medium">{{ props.title ?? '—' }}</div>
        <div class="text-xs text-muted-foreground">{{ props.subtitle ?? '' }}</div>
      </div>

      <div class="flex items-center gap-6">
        <div class="flex flex-col items-start">
          <div class="text-3xl font-semibold leading-tight">{{ props.value ?? '—' }}</div>
          <div class="text-sm text-muted-foreground">{{ props.small ?? '' }}</div>
        </div>

        <div class="ml-auto flex flex-col items-end">
          <div class="flex items-center gap-2">
            <span v-if="props.deltaDirection === 'up'" class="inline-flex items-center gap-1 text-green-400">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
              </svg>
            </span>
            <span v-else-if="props.deltaDirection === 'down'" class="inline-flex items-center gap-1 text-red-400">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
              </svg>
            </span>

            <div class="text-sm font-medium">{{ deltaSign }}{{ props.delta ?? '' }}</div>
          </div>
          <div class="text-xs text-muted-foreground">trend vs target</div>
        </div>
      </div>

      <div class="mt-auto flex w-full items-center gap-3">
        <div class="flex-1 text-sm text-muted-foreground">More details</div>
        <div class="text-xs text-muted-foreground">Updated: {{ props.subtitle ?? '—' }}</div>
      </div>
    </template>
  </div>
</template>
