import { describe, it, expect, vi, afterEach } from 'vitest'
import { fetchRoutes } from '~/composables/useRoutes'

// ─────────────────────────────────────────────────────────
// useRoutes — fetchRoutes() transforme les relations Overpass
// (OpenStreetMap) en RouteDto exploitables par l'app.
// On stub le $fetch global pour controler la reponse Overpass.
// ─────────────────────────────────────────────────────────
function stubOverpass(elements: { id: number, tags: Record<string, string> }[]) {
  const fetchMock = vi.fn().mockResolvedValue({ elements })
  vi.stubGlobal('$fetch', fetchMock)
  return fetchMock
}

describe('fetchRoutes', () => {
  afterEach(() => {
    vi.unstubAllGlobals()
  })

  // ── Test 1 : mapping complet d'un parcours route bitume ──
  it('transforme une relation asphalte en parcours route exploitable', async () => {
    stubOverpass([
      { id: 10, tags: { name: 'Voie Verte', route: 'bicycle', surface: 'asphalt', distance: '42 km', ascent: '350', network: 'rcn' } },
    ])

    const routes = await fetchRoutes(45.77, 3.08)

    expect(routes).toHaveLength(1)
    const r = routes[0]!
    expect(r.name).toBe('Voie Verte')
    expect(r.bike_type).toBe('route')
    expect(r.distance_km).toBe(42)
    expect(r.elevation_gain_m).toBe(350)
    expect(r.duration_label).toBe('1h45') // 42km @ 24km/h
    expect(r.difficulty).toBe('advanced') // 40 < 42 <= 80
    expect(r.surface).toBe('Bitume')
    expect(r.tag).toBe('Régional') // network rcn
  })

  // ── Test 2 : filtre les relations sans nom ou sans distance ──
  it('ignore les relations sans nom ou sans distance', async () => {
    stubOverpass([
      { id: 1, tags: { name: 'Sans distance' } }, // pas de distance → exclu
      { id: 2, tags: { distance: '10 km' } }, // pas de nom → exclu
      { id: 3, tags: { name: 'Valide', distance: '20 km' } }, // conserve
    ])

    const routes = await fetchRoutes(45.77, 3.08)

    expect(routes).toHaveLength(1)
    expect(routes[0]!.name).toBe('Valide')
  })

  // ── Test 3 : detection VTT et calcul de difficulte/duree courte ──
  it('detecte un parcours VTT et calcule une difficulte debutant', async () => {
    stubOverpass([
      { id: 5, tags: { name: 'Tour VTT', route: 'mtb', distance: '12 km' } },
    ])

    const routes = await fetchRoutes(45.77, 3.08)

    const r = routes[0]!
    expect(r.bike_type).toBe('vtt')
    expect(r.difficulty).toBe('beginner') // 12km <= 15
    expect(r.duration_label).toBe('42min') // 12km @ 17km/h, < 1h
    expect(r.surface).toBe('Mixte') // aucune info surface
  })
})
