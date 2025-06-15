import { ref } from 'vue'

interface Toast {
  id: string
  type: 'success' | 'error' | 'warning' | 'info'
  title: string
  message?: string
  duration?: number
}

const toasts = ref<Toast[]>([])

export function useToast() {
  const show = (toast: Omit<Toast, 'id'>) => {
    const id = Date.now().toString()
    const newToast: Toast = {
      id,
      duration: 5000,
      ...toast,
    }
    
    toasts.value.push(newToast)
    
    if (newToast.duration && newToast.duration > 0) {
      setTimeout(() => {
        remove(id)
      }, newToast.duration)
    }
  }

  const remove = (id: string) => {
    const index = toasts.value.findIndex(t => t.id === id)
    if (index > -1) {
      toasts.value.splice(index, 1)
    }
  }

  const showSuccess = (title: string, message?: string) => {
    show({ type: 'success', title, message })
  }

  const showError = (title: string, message?: string) => {
    show({ type: 'error', title, message })
  }

  const showWarning = (title: string, message?: string) => {
    show({ type: 'warning', title, message })
  }

  const showInfo = (title: string, message?: string) => {
    show({ type: 'info', title, message })
  }

  return {
    toasts,
    show,
    remove,
    showSuccess,
    showError,
    showWarning,
    showInfo,
  }
}