import { test, expect } from '@playwright/test'

test.use({ viewport: { width: 390, height: 844 } })

// ─────────────────────────────────────────────────────────
// Test E2E 1 : Middleware — route protégée redirige vers /login
// Si l'utilisateur accède à /home sans token, le middleware global
// doit intercepter et rediriger vers /login.
// C'est le test de sécurité le plus critique du front.
// ─────────────────────────────────────────────────────────
test('middleware - /home sans token redirige vers /login', async ({ page }) => {
  // S'assure qu'aucun cookie auth n'est présent
  await page.context().clearCookies()

  await page.goto('/home')

  await expect(page).toHaveURL('/login', { timeout: 6000 })
})

// ─────────────────────────────────────────────────────────
// Test E2E 2 : Middleware — les routes publiques restent accessibles
// /, /login et /register ne doivent PAS déclencher de redirection
// même sans cookie auth. Vérifie que le middleware ne sur-protège pas.
// ─────────────────────────────────────────────────────────
test('middleware - /login et /register accessibles sans token', async ({ page }) => {
  await page.context().clearCookies()

  await page.goto('/login')
  await expect(page).toHaveURL('/login')
  await expect(page.locator('input[type="email"]').first()).toBeVisible()

  await page.goto('/register')
  await expect(page).toHaveURL('/register')
  await expect(page.locator('input[type="email"]').first()).toBeVisible()
})

// ─────────────────────────────────────────────────────────
// Test E2E 3 : Middleware — plusieurs routes protégées redirigent toutes
// Vérifie que /profile, /rewards et /pressure nécessitent aussi une auth.
// Empêche les oublis de protection lors de l'ajout de nouvelles pages.
// ─────────────────────────────────────────────────────────
test('middleware - toutes les routes protégées redirigent sans token', async ({ page }) => {
  await page.context().clearCookies()

  for (const path of ['/profile', '/rewards', '/pressure']) {
    // waitUntil: 'commit' évite ERR_ABORTED quand le middleware redirige
    // avant que des pages avec await useApiFetch finissent de charger.
    await page.goto(path, { waitUntil: 'commit' }).catch(() => {})
    await expect(page).toHaveURL('/login', { timeout: 6000 })
  }
})
