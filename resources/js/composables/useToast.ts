import { ref } from 'vue'

export interface ToastOptions {
  title: string
  description?: string
  duration?: number
  variant?: 'default' | 'success' | 'destructive'
}

interface ToastItem extends ToastOptions {
  id: string
  open: boolean
}

const toasts = ref<ToastItem[]>([])

export function useToast() {
  const toast = (options: ToastOptions) => {
    const id = Math.random().toString(36).substring(2, 9)
    const item: ToastItem = {
      ...options,
      id,
      open: true,
      duration: options.duration ?? 3000,
    }
    
    toasts.value.push(item)

    if (item.duration > 0) {
      setTimeout(() => {
        dismiss(id)
      }, item.duration)
    }

    return id
  }

  const dismiss = (id: string) => {
    const index = toasts.value.findIndex((t) => t.id === id)
    if (index > -1) {
      toasts.value[index].open = false
      setTimeout(() => {
        toasts.value.splice(index, 1)
      }, 200)
    }
  }

  const success = (title: string, description?: string) => {
    return toast({ title, description, variant: 'success' })
  }

  const error = (title: string, description?: string) => {
    return toast({ title, description, variant: 'destructive' })
  }

  return {
    toasts,
    toast,
    success,
    error,
    dismiss,
  }
}
