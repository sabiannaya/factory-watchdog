<script setup lang="ts">
import { DialogClose } from "reka-ui"
import type { HTMLAttributes } from "vue"
import { cn } from "@/lib/utils"

defineProps<{
  class?: HTMLAttributes["class"]
}>()
</script>

<template>
  <DialogClose
    :class="cn(
      'inline-flex cursor-pointer h-10 items-center justify-center rounded-md border border-slate-200 bg-white px-4 py-2 text-sm font-medium ring-offset-white transition-colors hover:bg-slate-100 hover:text-slate-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-slate-950 focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 dark:border-slate-800 dark:bg-slate-950 dark:hover:bg-slate-800 dark:hover:text-slate-50 dark:ring-offset-slate-950 dark:focus-visible:ring-slate-300',
      $props.class
    )"
  >
    <slot />
  </DialogClose>
</template>
