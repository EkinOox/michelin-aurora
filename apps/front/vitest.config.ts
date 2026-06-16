import { defineVitestConfig } from '@nuxt/test-utils/config'

// Tests unitaires / composants avec l'environnement Nuxt.
// Doc : https://nuxt.com/docs/getting-started/testing
export default defineVitestConfig({
  test: {
    environment: 'nuxt',
    include: ['test/unit/**/*.{test,spec}.ts'],
  },
})
