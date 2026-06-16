export interface ToastData {
  msg: string
  icon?: string
}

export function useToast() {
  const toast = useState<ToastData | null>('aurora-toast', () => null)
  let timer: ReturnType<typeof setTimeout> | undefined

  function show(msg: string, icon?: string) {
    toast.value = { msg, icon }
    clearTimeout(timer)
    timer = setTimeout(() => {
      toast.value = null
    }, 2200)
  }

  return { toast, show }
}
