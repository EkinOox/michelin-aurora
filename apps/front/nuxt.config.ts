export default defineNuxtConfig({
  compatibilityDate: '2025-07-15',
  devtools: { enabled: true },
  future: { compatibilityVersion: 4 },
  modules: ['@nuxtjs/tailwindcss', '@vite-pwa/nuxt'],
  runtimeConfig: {
    // URL interne Docker (SSR server-side, non exposée au navigateur)
    apiBaseInternal: process.env.NUXT_API_BASE_INTERNAL ?? 'http://back:8080',
    public: {
      // URL exposée au navigateur (client-side fetch)
      apiBase: process.env.NUXT_PUBLIC_API_BASE ?? 'http://localhost:8081',
    },
  },
  pwa: {
    registerType: 'autoUpdate',
    manifest: {
      name: 'Michelin Aurora — Cycling Intelligence Platform',
      short_name: 'Aurora',
      description: 'La Cycling Intelligence Platform de Michelin LB 2 Wheels.',
      theme_color: '#27509B',
      background_color: '#1A1A1A',
      display: 'standalone',
      start_url: '/',
      icons: [
        { src: 'icons/icon-192.png', sizes: '192x192', type: 'image/png' },
        { src: 'icons/icon-512.png', sizes: '512x512', type: 'image/png' },
        { src: 'icons/icon-512-maskable.png', sizes: '512x512', type: 'image/png', purpose: 'maskable' },
      ],
    },
    workbox: {
      navigateFallback: '/',
      globPatterns: ['**/*.{js,css,html,png,svg,ico}'],
    },
    devOptions: {
      enabled: true,
      type: 'module',
    },
  },
})
