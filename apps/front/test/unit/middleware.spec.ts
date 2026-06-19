import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mockNuxtImport } from '@nuxt/test-utils/runtime'
import authMiddleware from '~/middleware/auth.global'
import type { RouteLocationNormalized } from 'vue-router'

// navigateTo doit etre mocke pour capturer les redirections sans
// declencher de vraie navigation.
const { navigateToMock } = vi.hoisted(() => ({ navigateToMock: vi.fn() }))
mockNuxtImport('navigateTo', () => navigateToMock)

// Helper : appelle le middleware avec un simple `to` partiel.
function run(path: string) {
  return authMiddleware(
    { path } as RouteLocationNormalized,
    { path: '/' } as RouteLocationNormalized,
  )
}

// ─────────────────────────────────────────────────────────
// auth.global — garde de route : redirige vers /login si pas de
// token, sauf sur les pages publiques.
// ─────────────────────────────────────────────────────────
describe('middleware auth.global', () => {
  beforeEach(() => {
    navigateToMock.mockClear()
    useAuthToken().value = null
  })

  // ── Test 1 : page publique accessible sans token ──
  it('laisse passer une page publique sans token', () => {
    run('/login')
    expect(navigateToMock).not.toHaveBeenCalled()
  })

  // ── Test 2 : page protegee sans token → redirection /login ──
  it('redirige vers /login sur une page protegee sans token', () => {
    run('/home')
    expect(navigateToMock).toHaveBeenCalledWith('/login')
  })

  // ── Test 3 : page protegee avec token → acces autorise ──
  it('laisse passer une page protegee quand le token est present', () => {
    useAuthToken().value = 'jwt-valide'
    run('/profile')
    expect(navigateToMock).not.toHaveBeenCalled()
  })
})
