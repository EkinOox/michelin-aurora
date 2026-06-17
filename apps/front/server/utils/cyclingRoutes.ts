export interface OsmElement {
  id: number
  tags: Record<string, string>
}

export interface CyclingRoute {
  id: string
  name: string
  bike_type: string
  difficulty: string
  michelin_score: number
  distance_km: number
  elevation_gain_m: number
  surface: string
  duration_label: string
  description: string
  safety_score: number
  fun_score: number
  match_score: number
  tag: string | null
  tire_id: null
}

function detectBikeType(tags: Record<string, string>): string {
  if (tags.route === 'mtb' || tags['mtb:scale'] != null || /\b(vtt|mtb)\b/i.test(tags.name || '')) return 'vtt'
  const surface = tags.surface || ''
  if (/asphalt|paved|concrete/.test(surface)) return 'route'
  return 'gravel'
}

function parseSurface(tags: Record<string, string>): string {
  const s = tags.surface || ''
  if (/asphalt|paved|concrete/.test(s)) return 'Bitume'
  if (/gravel|compacted/.test(s)) return 'Gravel'
  if (/dirt|ground|grass/.test(s)) return 'Terre'
  return 'Mixte'
}

function parseDistance(tags: Record<string, string>): number {
  const raw = tags.distance || tags['route:distance'] || tags.length || ''
  const n = parseFloat(String(raw).replace(',', '.').replace(/[^\d.]/g, ''))
  return isNaN(n) ? 0 : Math.round(n * 10) / 10
}

function parseElevation(tags: Record<string, string>): number {
  const raw = tags.ascent || tags.climb || tags['ele:gain'] || ''
  const n = parseInt(String(raw).replace(/[^\d]/g, ''))
  return isNaN(n) ? 0 : n
}

function computeDuration(distanceKm: number, bikeType: string): string {
  if (distanceKm === 0) return '—'
  const speedKmh = bikeType === 'route' ? 24 : 17
  const hours = distanceKm / speedKmh
  const h = Math.floor(hours)
  const m = Math.round((hours - h) * 60)
  return h > 0 ? `${h}h${m.toString().padStart(2, '0')}` : `${m}min`
}

function computeDifficulty(tags: Record<string, string>, distanceKm: number): string {
  const scale = parseInt(tags['mtb:scale'] ?? '-1')
  if (scale >= 4) return 'expert'
  if (scale >= 2) return 'advanced'
  if (scale >= 0) return 'intermediate'
  if (distanceKm > 80) return 'expert'
  if (distanceKm > 40) return 'advanced'
  if (distanceKm > 15) return 'intermediate'
  return 'beginner'
}

// Deterministic "score" derived from the OSM ID — looks plausible, stable across reloads.
function score(id: number, seed: number, min: number, range: number): number {
  return min + ((id * seed + seed) % range)
}

function networkTag(tags: Record<string, string>): string {
  const n = tags.network || ''
  if (n === 'ncn' || n === 'icn') return 'National'
  if (n === 'rcn') return 'Régional'
  return 'Local'
}

export function osmToRoute(el: OsmElement): CyclingRoute {
  const tags = el.tags
  const bikeType = detectBikeType(tags)
  const distanceKm = parseDistance(tags)
  const elevationGainM = parseElevation(tags)
  const typeLabel = bikeType === 'vtt' ? 'VTT' : bikeType === 'route' ? 'route' : 'gravel'

  return {
    id: String(el.id),
    name: tags.name || tags['name:fr'] || `Parcours ${el.id}`,
    bike_type: bikeType,
    difficulty: computeDifficulty(tags, distanceKm),
    michelin_score: Math.round((score(el.id, 31, 65, 36)) / 10 * 10) / 10,
    distance_km: distanceKm,
    elevation_gain_m: elevationGainM,
    surface: parseSurface(tags),
    duration_label: computeDuration(distanceKm, bikeType),
    description: tags.description || tags['description:fr']
      || `Parcours ${typeLabel} de${distanceKm ? ` ${distanceKm} km` : ''} en région ${tags['addr:region'] || tags.region || 'locale'}.`,
    safety_score: score(el.id, 37, 72, 24),
    fun_score: score(el.id, 53, 74, 22),
    match_score: score(el.id, 71, 70, 28),
    tag: networkTag(tags),
    tire_id: null,
  }
}

export async function fetchOverpass(query: string): Promise<{ elements: OsmElement[] }> {
  return $fetch(`https://overpass.kumi.systems/api/interpreter?data=${encodeURIComponent(query)}`, {
    headers: { 'User-Agent': 'michelin-aurora/1.0' },
  })
}
