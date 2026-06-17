import { test, expect } from '@playwright/test'

// Les pages ont un double layout (mobile + desktop) dans le DOM.
// On scoped les selectors sur le conteneur mobile qui est toujours present.
test.use({ viewport: { width: 390, height: 844 } })

// ─────────────────────────────────────────────────────────
// Test E2E 1 : Page d'accueil — contenu critique present
// Verifie que le landing page charge et expose les CTA
// permettant a un visiteur de s'inscrire ou se connecter.
// ─────────────────────────────────────────────────────────
test("landing page - affiche AURORA et les deux CTA", async ({ page }) => {
  await page.goto('/')

  // Le wordmark AURORA est visible sur mobile
  await expect(page.locator('.lp-mobile').getByText('AURORA')).toBeVisible()

  // CTA principal vers l'inscription (layout mobile)
  const ctaRegister = page.locator('.lp-mobile').getByRole('link', { name: /créer mon profil/i })
  await expect(ctaRegister).toBeVisible()
  await expect(ctaRegister).toHaveAttribute('href', '/register')

  // Lien secondaire vers la connexion
  const ctaLogin = page.locator('.lp-mobile').getByRole('link', { name: /déjà un compte/i })
  await expect(ctaLogin).toBeVisible()
})

// ─────────────────────────────────────────────────────────
// Test E2E 2 : Login reussi — redirection vers /home
// Le happy path est le test le plus critique : on verifie
// que la chaine complete auth (login → fetchMe → redirect) fonctionne.
// Les deux appels API sont moques pour que le test soit autonome.
// ─────────────────────────────────────────────────────────
test("login reussi - redirige vers /home apres authentification", async ({ page }) => {
  // Mock login : retourne un token valide
  await page.route(/\/api\/auth\/login$/, route =>
    route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify({ token: 'eyJhbGciOiJIUzI1NiJ9.mock' }),
    }),
  )
  // Mock fetchMe : retourne un profil utilisateur
  await page.route(/\/api\/auth\/me$/, route =>
    route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify({
        id: '1',
        email: 'cycliste@michelin.fr',
        name: 'Cycliste Test',
        city: 'Clermont-Ferrand',
        rewards_level: 'gold',
        total_points: 1200,
      }),
    }),
  )

  await page.goto('/login')
  // networkidle garantit que Vue a fini d'hydrater le DOM avant d'interagir.
  // Sans ça, @submit.prevent n'est pas encore attaché et le formulaire
  // déclenche un GET natif vers /login? au lieu d'appeler le handler Vue.
  await page.waitForLoadState('networkidle')

  const form = page.locator('.login-mobile')
  await form.locator('input[type="email"]').fill('cycliste@michelin.fr')
  await form.locator('input[type="password"]').fill('motdepasse123')
  await form.locator('button[type="submit"]').click()

  // Apres une auth reussie, le router pousse vers /home
  await expect(page).toHaveURL('/home', { timeout: 8000 })
})

// ─────────────────────────────────────────────────────────
// Test E2E 3 : Formulaire d'inscription — champs requis
// Verifie que les champs obligatoires sont presents et que
// la soumission vide reste bloquee sur /register.
// ─────────────────────────────────────────────────────────
test("register - champs obligatoires presents et soumission vide bloquee", async ({ page }) => {
  await page.goto('/register')

  const form = page.locator('.reg-mobile')

  // Les 3 champs requis sont visibles
  await expect(form.locator('input[autocomplete="name"]')).toBeVisible()
  await expect(form.locator('input[type="email"]')).toBeVisible()
  await expect(form.locator('input[type="password"]')).toBeVisible()

  // Soumission sans remplir -> validation HTML native, reste sur la page
  await form.locator('button[type="submit"]').click()
  await expect(page).toHaveURL('/register')
  await expect(form.locator('form')).toBeVisible()
})
