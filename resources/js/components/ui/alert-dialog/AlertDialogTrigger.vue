<script setup lang="ts">
import type { DialogTriggerProps } from "reka-ui"
import { DialogTrigger, useForwardPropsEmits } from "reka-ui"
import { reactiveOmit } from "@vueuse/core"

const props = defineProps<DialogTriggerProps>()

const delegatedProps = reactiveOmit(props, "class")
const forwarded = useForwardPropsEmits(delegatedProps, {})
</script>

<template>
  <DialogTrigger v-bind="forwarded" as-child>
    <slot />
  </DialogTrigger>
</template>
