<script setup lang="ts">
import type { ToastCloseProps } from "reka-ui"
import { ToastClose } from "reka-ui"
import { X } from "lucide-vue-next"
import { cn } from "@/lib/utils"

defineProps<ToastCloseProps & { class?: string }>()
</script>

<template>
  <ToastClose
    :class="cn(
      'absolute right-2 top-2 rounded-md p-1 text-slate-950/50 opacity-0 transition-opacity hover:text-slate-950 focus:opacity-100 focus:outline-none focus:ring-2 group-hover:opacity-100 group-[.destructive]:text-red-300 group-[.destructive]:hover:text-red-50 group-[.destructive]:focus:ring-red-400 group-[.destructive]:focus:ring-offset-red-600 dark:text-slate-50/50 dark:hover:text-slate-50',
      $props.class
    )"
  >
    <X :size="16" />
  </ToastClose>
</template>
