import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { $apiFetch } from '~/composables/useApiFetch'

// ─────────────────────────────────────────────────────────
// $apiFetch — injecte automatiquement l'en-tete Authorization
// quand un token est present (point sensible cote securite).
// ─────────────────────────────────────────────────────────
function stubFetch() {
  const fetchMock = vi.fn().mockResolvedValue({})
  vi.stubGlobal('$fetch', fetchMock)
  return fetchMock
}

// Recupere les headers passes au dernier appel $fetch.
function lastHeaders(mock: ReturnType<typeof vi.fn>): Record<string, string> {
  const opts = mock.mock.calls.at(-1)?.[1] as { headers?: Record<string, string> } | undefined
  return opts?.headers ?? {}
}

describe('$apiFetch - en-tete Authorization', () => {
  beforeEach(() => {
    useAuthToken().value = null
  })

  afterEach(() => {
    vi.unstubAllGlobals()
  })

  // ── Test 1 : token present → header Bearer ajoute ──
  it('ajoute le Bearer token quand l utilisateur est connecte', async () => {
    const fetchMock = stubFetch()
    useAuthToken().value = 'jwt-abc'

    await $apiFetch('/api/profile')

    expect(lastHeaders(fetchMock).Authorization).toBe('Bearer jwt-abc')
  })

  // ── Test 2 : aucun token → pas de header Authorization ──
  it('n ajoute pas d Authorization quand aucun token n est present', async () => {
    const fetchMock = stubFetch()

    await $apiFetch('/api/public')

    expect(lastHeaders(fetchMock).Authorization).toBeUndefined()
  })

  // ── Test 3 : les headers personnalises sont preserves ──
  it('fusionne les headers custom avec l auth', async () => {
    const fetchMock = stubFetch()
    useAuthToken().value = 'jwt-abc'

    await $apiFetch('/api/profile', { headers: { 'X-Custom': 'valeur' } })

    const headers = lastHeaders(fetchMock)
    expect(headers.Authorization).toBe('Bearer jwt-abc')
    expect(headers['X-Custom']).toBe('valeur')
  })
})
