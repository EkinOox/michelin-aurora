import { describe, it, expect } from 'vitest'
import { mountSuspended } from '@nuxt/test-utils/runtime'

// ─────────────────────────────────────────────────────────
// Page /register — structure et validation du formulaire.
// (Pendant de la page /login deja couverte dans auth.spec.ts.)
// Note : register.vue rend 2 layouts (mobile + desktop) toujours
// presents dans le DOM ; on utilise donc find() (1re occurrence).
// ─────────────────────────────────────────────────────────
async function mountRegister() {
  const RegisterPage = await import('~/pages/register.vue')
  return mountSuspended(RegisterPage.default)
}

describe('page /register - formulaire', () => {
  // ── Test 1 : champs nom / email / mot de passe + bouton submit ──
  it('contient les champs nom, email, mot de passe et un bouton submit', async () => {
    const wrapper = await mountRegister()

    expect(wrapper.find('input[autocomplete="name"]').exists()).toBe(true)
    expect(wrapper.find('input[type="email"]').exists()).toBe(true)
    expect(wrapper.find('input[type="password"]').exists()).toBe(true)
    expect(wrapper.find('button[type="submit"]').exists()).toBe(true)
  })

  // ── Test 2 : validation cote client du mot de passe ──
  it('impose un mot de passe requis d au moins 8 caracteres', async () => {
    const wrapper = await mountRegister()

    const pwd = wrapper.find('input[type="password"]')
    expect(pwd.attributes('required')).toBeDefined()
    expect(pwd.attributes('minlength')).toBe('8')
  })

  // ── Test 3 : lien de bascule vers la connexion ──
  it('propose un lien vers /login pour les comptes existants', async () => {
    const wrapper = await mountRegister()

    const loginLink = wrapper.findAll('a').find(a => a.attributes('href') === '/login')
    expect(loginLink).toBeTruthy()
  })
})
