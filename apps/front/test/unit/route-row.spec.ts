import { describe, it, expect } from 'vitest'
import { mountSuspended } from '@nuxt/test-utils/runtime'
import RouteRow from '~/components/RouteRow.vue'

// RouteDto minimal attendu par le composant.
function makeRoute(over: Partial<Record<string, unknown>> = {}) {
  return {
    id: '7',
    name: 'Boucle des Volcans',
    bike_type: 'gravel',
    distance_km: 35,
    elevation_gain_m: 620,
    match_score: 88,
    safety_score: 91,
    ...over,
  } as any
}

// ─────────────────────────────────────────────────────────
// RouteRow — carte de parcours affichee dans les listes.
// ─────────────────────────────────────────────────────────
describe('RouteRow', () => {
  // ── Test 1 : libelle de type humanise + donnees principales ──
  it('affiche le libelle de type, le nom, la distance et le match', async () => {
    const wrapper = await mountSuspended(RouteRow, { props: { route: makeRoute() } })

    expect(wrapper.text()).toContain('Gravel') // gravel → "Gravel"
    expect(wrapper.text()).toContain('Boucle des Volcans')
    expect(wrapper.text()).toContain('35 km')
    expect(wrapper.text()).toContain('Match 88%')
  })

  // ── Test 2 : pointe vers la page de detail du parcours ──
  it('lie vers /routes/:id', async () => {
    const wrapper = await mountSuspended(RouteRow, { props: { route: makeRoute({ id: '42' }) } })

    expect(wrapper.find('a').attributes('href')).toBe('/routes/42')
  })

  // ── Test 3 : gere proprement les valeurs nulles (distance/denivele) ──
  it('affiche un placeholder quand distance et denivele sont absents', async () => {
    const wrapper = await mountSuspended(RouteRow, {
      props: { route: makeRoute({ distance_km: null, elevation_gain_m: null }) },
    })

    expect(wrapper.text()).toContain('— km')
    expect(wrapper.text()).toContain('↑ —')
  })
})
