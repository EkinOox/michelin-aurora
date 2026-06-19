export interface ActiveRouteInfo {
  id: string
  name: string
  distance_km: number
}

export function useActiveRoute() {
  return useState<ActiveRouteInfo | null>('aurora-active-route', () => null)
}
