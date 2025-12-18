<script setup lang="ts">
import type { ToastTitleProps } from "reka-ui"
import { ToastTitle } from "reka-ui"
import { cn } from "@/lib/utils"

defineProps<ToastTitleProps & { class?: string }>()
</script>

<template>
  <ToastTitle
    :class="cn('text-sm font-semibold', $props.class)"
  >
    <slot />
  </ToastTitle>
</template>
