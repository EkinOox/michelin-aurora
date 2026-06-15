export default defineNuxtConfig({
  compatibilityDate: '2025-07-15',
  devtools: { enabled: true },
  future: { compatibilityVersion: 4 },
  modules: ['@nuxtjs/tailwindcss'],
  runtimeConfig: {
    // URL interne Docker (SSR server-side, non exposée au navigateur)
    apiBaseInternal: process.env.NUXT_API_BASE_INTERNAL ?? 'http://back:8080',
    public: {
      // URL exposée au navigateur (client-side fetch)
      apiBase: process.env.NUXT_PUBLIC_API_BASE ?? 'http://localhost:8081',
    },
  },
})
