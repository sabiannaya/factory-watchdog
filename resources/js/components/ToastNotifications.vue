<script setup lang="ts">
import { useToast } from '@/composables/useToast'
import Toast from '@/components/ui/toast/Toast.vue'
import ToastClose from '@/components/ui/toast/ToastClose.vue'
import ToastTitle from '@/components/ui/toast/ToastTitle.vue'
import ToastDescription from '@/components/ui/toast/ToastDescription.vue'
import Toaster from '@/components/ui/toast/Toaster.vue'
import { CheckCircle2, XCircle } from 'lucide-vue-next'

const { toasts } = useToast()
</script>

<template>
  <Toaster>
    <Toast
      v-for="toast in toasts"
      :key="toast.id"
      v-model:open="toast.open"
      :duration="toast.duration"
      :class="{
        'bg-green-50 border-green-200 dark:bg-green-950 dark:border-green-800': toast.variant === 'success',
        'bg-red-50 border-red-200 dark:bg-red-950 dark:border-red-800': toast.variant === 'destructive',
      }"
    >
      <div class="flex items-start gap-3">
        <CheckCircle2 v-if="toast.variant === 'success'" :size="20" class="text-green-600 dark:text-green-400 flex-shrink-0 mt-0.5" />
        <XCircle v-else-if="toast.variant === 'destructive'" :size="20" class="text-red-600 dark:text-red-400 flex-shrink-0 mt-0.5" />
        
        <div class="flex-1">
          <ToastTitle :class="{
            'text-green-900 dark:text-green-100': toast.variant === 'success',
            'text-red-900 dark:text-red-100': toast.variant === 'destructive',
          }">
            {{ toast.title }}
          </ToastTitle>
          <ToastDescription v-if="toast.description" :class="{
            'text-green-700 dark:text-green-300': toast.variant === 'success',
            'text-red-700 dark:text-red-300': toast.variant === 'destructive',
          }">
            {{ toast.description }}
          </ToastDescription>
        </div>
      </div>
      <ToastClose />
    </Toast>
  </Toaster>
</template>
