import { describe, it, expect } from 'vitest'
import { mountSuspended } from '@nuxt/test-utils/runtime'

// ─────────────────────────────────────────────────────────
// Test 1 : useAuth — isLoggedIn réactif au token
// ─────────────────────────────────────────────────────────
describe('useAuth - isLoggedIn', () => {
  it('retourne false quand aucun token nest present', () => {
    const { token, isLoggedIn } = useAuth()
    token.value = null
    expect(isLoggedIn.value).toBe(false)
  })

  it('retourne true des qu un token est defini', () => {
    const { token, isLoggedIn } = useAuth()
    token.value = 'eyJhbGciOiJIUzI1NiJ9.test'
    expect(isLoggedIn.value).toBe(true)
    token.value = null
  })
})

// ─────────────────────────────────────────────────────────
// Test 2 : useAuth — logout() vide token ET user
// ─────────────────────────────────────────────────────────
describe('useAuth - logout', () => {
  it('remet token et user a null apres logout()', () => {
    const { token, user, logout } = useAuth()

    token.value = 'fake-token-xyz'
    user.value = {
      id: '42',
      email: 'cycliste@michelin.fr',
      name: 'Test User',
      city: 'Clermont-Ferrand',
      rewards_level: 'gold',
      total_points: 1500,
    }

    logout()

    expect(token.value).toBeNull()
    expect(user.value).toBeNull()
  })
})

// ─────────────────────────────────────────────────────────
// Test 3 : Page login — structure du formulaire
// ─────────────────────────────────────────────────────────
describe('page /login - structure du formulaire', () => {
  it('contient un input email, un input password et un bouton submit', async () => {
    const LoginPage = await import('~/pages/login.vue')
    const wrapper = await mountSuspended(LoginPage.default)

    expect(wrapper.find('input[type="email"]').exists()).toBe(true)
    expect(wrapper.find('input[type="password"]').exists()).toBe(true)
    expect(wrapper.find('button[type="submit"]').exists()).toBe(true)
  })

  it('le formulaire reste visible apres une soumission sans credentials', async () => {
    const LoginPage = await import('~/pages/login.vue')
    const wrapper = await mountSuspended(LoginPage.default)

    await wrapper.find('form').trigger('submit')
    await wrapper.vm.$nextTick()

    expect(wrapper.find('form').exists()).toBe(true)
  })
})
