export interface RouteDto {
  id: string
  name: string
  bike_type: string
  distance_km: number | null
  elevation_gain_m: number | null
  duration_label: string | null
  difficulty: string
  surface: string
  description: string
  michelin_score: number
  safety_score: number
  fun_score: number
  match_score: number
  tag: string
  tire_id: string | null
}

export const useRoutesState = () => useState<RouteDto[]>('routes', () => [])

function toRoute(el: { id: number, tags: Record<string, string> }): RouteDto {
  const t = el.tags
  const isMtb = t.route === 'mtb' || /\bvtt\b/i.test(t.name || '')
  const bike_type = isMtb ? 'vtt' : /asphalt|paved|concrete/.test(t.surface || '') ? 'route' : 'gravel'
  const net = t.network || ''

  const rawDist = parseFloat((t.distance || t['route:distance'] || t.length || '').replace(/[^\d.]/g, ''))
  const km: number | null = isNaN(rawDist) || rawDist === 0 ? null : Math.round(rawDist * 10) / 10

  const rawElev = parseInt(t.ascent || t.climb || '')
  const elevation_gain_m: number | null = isNaN(rawElev) || rawElev === 0 ? null : rawElev

  let duration_label: string | null = null
  if (km) {
    const speed = bike_type === 'route' ? 24 : 17
    const h = Math.floor(km / speed)
    const m = Math.round((km / speed - h) * 60)
    duration_label = h > 0 ? `${h}h${m.toString().padStart(2, '0')}` : `${m}min`
  }

  const s = el.id

  return {
    id: String(el.id),
    name: t.name,
    bike_type,
    distance_km: km,
    elevation_gain_m,
    duration_label,
    difficulty: km ? (km > 80 ? 'expert' : km > 40 ? 'advanced' : km > 15 ? 'intermediate' : 'beginner') : 'intermediate',
    surface: /asphalt|paved|concrete/.test(t.surface || '') ? 'Bitume' : /gravel|compacted/.test(t.surface || '') ? 'Gravel' : 'Mixte',
    description: t.description || t['description:fr'] || `Parcours ${bike_type}.`,
    michelin_score: (s * 31 % 36 + 65) / 10,
    safety_score: s * 37 % 24 + 72,
    fun_score: s * 53 % 22 + 74,
    match_score: s * 71 % 28 + 70,
    tag: net === 'ncn' || net === 'icn' ? 'National' : net === 'rcn' ? 'Régional' : 'Local',
    tire_id: null,
  }
}

export async function fetchRoutes(lat: number, lon: number): Promise<RouteDto[]> {
  const query = `[out:json][timeout:30];(relation[route=bicycle][name](around:75000,${lat},${lon});relation[route=mtb][name](around:75000,${lat},${lon}););out 50 tags;`
  const res = await $fetch<{ elements: { id: number, tags: Record<string, string> }[] }>(
    `https://overpass-api.de/api/interpreter?data=${encodeURIComponent(query)}`,
    { headers: { 'User-Agent': 'michelin-aurora/1.0' } },
  )
  return res.elements
    .filter(el => el.tags?.name && (el.tags.distance || el.tags['route:distance']))
    .map(toRoute)
}
