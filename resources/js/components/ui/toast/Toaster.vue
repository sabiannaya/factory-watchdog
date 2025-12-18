<script setup lang="ts">
import { ToastProvider, ToastViewport } from "reka-ui"
import { cn } from "@/lib/utils"

defineProps<{ class?: string }>()
</script>

<template>
  <ToastProvider>
    <slot />
    <ToastViewport
      :class="cn(
        'fixed top-0 z-[100] flex max-h-screen w-full flex-col-reverse p-4 sm:bottom-0 sm:right-0 sm:top-auto sm:flex-col md:max-w-[420px]',
        $props.class
      )"
    />
  </ToastProvider>
</template>
