<script setup lang="ts">
import type { DialogDescriptionProps } from "reka-ui"
import type { HTMLAttributes } from "vue"
import { DialogDescription, useForwardPropsEmits } from "reka-ui"
import { reactiveOmit } from "@vueuse/core"
import { cn } from "@/lib/utils"

const props = defineProps<DialogDescriptionProps & { class?: HTMLAttributes["class"] }>()

const delegatedProps = reactiveOmit(props, "class")
const forwarded = useForwardPropsEmits(delegatedProps, {})
</script>

<template>
  <DialogDescription
    v-bind="forwarded"
    :class="cn('text-sm text-slate-500 dark:text-slate-400', $props.class)"
  >
    <slot />
  </DialogDescription>
</template>
