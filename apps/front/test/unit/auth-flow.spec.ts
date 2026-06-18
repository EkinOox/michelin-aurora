import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'

// ─────────────────────────────────────────────────────────
// useAuth — flux reseau login / register / fetchMe.
// (isLoggedIn et logout sont deja couverts par auth.spec.ts.)
// On stub le $fetch global pour simuler le backend.
// ─────────────────────────────────────────────────────────
const ME = {
  id: '1',
  email: 'cycliste@michelin.fr',
  name: 'Test User',
  city: 'Clermont-Ferrand',
  rewards_level: 'gold',
  total_points: 1500,
}

function stubBackend() {
  const fetchMock = vi.fn(async (url: string) => {
    if (url.endsWith('/api/auth/login')) return { token: 'tok-123' }
    if (url.endsWith('/api/auth/me')) return ME
    if (url.endsWith('/api/auth/register')) return {}
    throw new Error(`URL inattendue: ${url}`)
  })
  vi.stubGlobal('$fetch', fetchMock)
  return fetchMock
}

describe('useAuth - flux reseau', () => {
  beforeEach(() => {
    // Etat propre entre chaque test.
    useAuthToken().value = null
    useAuthUser().value = null
  })

  afterEach(() => {
    vi.unstubAllGlobals()
  })

  // ── Test 1 : login() stocke le token puis charge l'utilisateur ──
  it('login() enregistre le token et peuple user via fetchMe', async () => {
    const fetchMock = stubBackend()
    const { token, user, login } = useAuth()

    await login({ email: 'cycliste@michelin.fr', password: 'secret123' })

    expect(token.value).toBe('tok-123')
    expect(user.value?.email).toBe('cycliste@michelin.fr')

    // login appele POST /login puis GET /me
    const urls = fetchMock.mock.calls.map(c => c[0] as string)
    expect(urls.some(u => u.endsWith('/api/auth/login'))).toBe(true)
    expect(urls.some(u => u.endsWith('/api/auth/me'))).toBe(true)
  })

  // ── Test 2 : register() cree le compte PUIS enchaine sur login() ──
  it('register() appelle /register puis connecte automatiquement', async () => {
    const fetchMock = stubBackend()
    const { token, register } = useAuth()

    await register({ email: 'cycliste@michelin.fr', password: 'secret123', name: 'Test User', city: 'Clermont-Ferrand' })

    const urls = fetchMock.mock.calls.map(c => c[0] as string)
    expect(urls.some(u => u.endsWith('/api/auth/register'))).toBe(true)
    expect(urls.some(u => u.endsWith('/api/auth/login'))).toBe(true)
    // login ayant suivi, le token est bien renseigne.
    expect(token.value).toBe('tok-123')
  })

  // ── Test 3 : fetchMe() sans token remet user a null sans appel reseau ──
  it('fetchMe() sans token vide user et n appelle pas le backend', async () => {
    const fetchMock = stubBackend()
    const { user, fetchMe } = useAuth()
    user.value = ME // valeur residuelle

    await fetchMe()

    expect(user.value).toBeNull()
    expect(fetchMock).not.toHaveBeenCalled()
  })
})
