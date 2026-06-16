import { test, expect } from '@playwright/test'

// Test e2e d'exemple : la page d'accueil affiche bien le titre Aurora.
// Nécessite que le serveur Nuxt tourne (task up).
test("la page d'accueil affiche le titre Aurora", async ({ page }) => {
  await page.goto('/')
  await expect(page.getByRole('heading')).toContainText('Aurora')
})
