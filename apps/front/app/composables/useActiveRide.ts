export function useActiveRide() {
  return useState<string | null>('aurora-active-ride', () => null)
}
