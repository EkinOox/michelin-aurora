export function useRiderSheet() {
  const riderId = useState<string | null>('aurora-rider-sheet', () => null)

  function open(id: string) { riderId.value = id }
  function close() { riderId.value = null }

  return { riderId, open, close }
}
