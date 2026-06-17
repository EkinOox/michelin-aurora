import { test, expect } from '@playwright/test'

// Test e2e d'exemple : la page d'accueil affiche bien le titre Aurora.
// Nécessite que le serveur Nuxt tourne (task up).
test("la page d'accueil affiche le wordmark AURORA et le titre principal", async ({ page }) => {
  await page.goto('/')

  // Le h1 contient la tagline principale (visible sur tous les viewports)
  await expect(page.locator('h1')).toContainText('liaison au sol')

  // Le wordmark AURORA est présent dans le layout desktop (viewport par défaut)
  await expect(page.locator('.lp-desktop .lp-wordmark')).toContainText('AURORA')
})
