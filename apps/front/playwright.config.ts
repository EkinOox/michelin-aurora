import { defineConfig, devices } from '@playwright/test'

// Tests end-to-end. La cible par défaut est le serveur Nuxt du conteneur (port 3000).
// Surcharge possible : E2E_BASE_URL=http://localhost:3001 (port hôte) task front:test:e2e
export default defineConfig({
  testDir: './test/e2e',
  fullyParallel: true,
  use: {
    baseURL: process.env.E2E_BASE_URL ?? 'http://localhost:3000',
    trace: 'on-first-retry',
  },
  projects: [
    { name: 'chromium', use: { ...devices['Desktop Chrome'] } },
  ],
})
