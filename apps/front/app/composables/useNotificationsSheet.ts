export function useNotificationsSheet() {
  const isOpen = useState<boolean>('aurora-notif-sheet', () => false)

  function open() { isOpen.value = true }
  function close() { isOpen.value = false }

  return { isOpen, open, close }
}
