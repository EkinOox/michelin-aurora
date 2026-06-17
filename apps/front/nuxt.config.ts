import { mkdirSync, writeFileSync, existsSync } from 'fs'
import { resolve } from 'path'

// Pre-create the PWA dev service-worker stub so Nitro never hits ENOENT on
// first cold-start (the PWA module generates the real file asynchronously,
// but requests can arrive before it's ready).
if (process.env.NODE_ENV !== 'production') {
  const swDir = resolve('.nuxt/dev-sw-dist')
  const swFile = `${swDir}/sw.js`
  if (!existsSync(swFile)) {
    mkdirSync(swDir, { recursive: true })
    writeFileSync(
      swFile,
      `self.addEventListener('install',()=>self.skipWaiting());self.addEventListener('activate',()=>clients.claim());self.addEventListener('fetch',()=>{});`,
    )
  }
}

export default defineNuxtConfig({
  compatibilityDate: '2025-07-15',
  devtools: { enabled: true },
  future: { compatibilityVersion: 4 },
  modules: ['@nuxtjs/tailwindcss', '@vite-pwa/nuxt'],
  vite: {
    optimizeDeps: {
      include: ['three', 'workbox-window', '@vue/devtools-core', '@vue/devtools-kit'],
    },
  },
  css: ['~/assets/css/main.css'],
  app: {
    head: {
      link: [
        { rel: 'icon', type: 'image/svg+xml', href: '/icons/michelin_aurora_fiveicon.svg' },
        { rel: 'preconnect', href: 'https://fonts.googleapis.com' },
        { rel: 'preconnect', href: 'https://fonts.gstatic.com', crossorigin: '' },
        {
          rel: 'stylesheet',
          href: 'https://fonts.googleapis.com/css2?family=Noto+Sans:ital,wght@0,400;0,500;0,600;0,700;0,800;1,400&family=Noto+Sans+Mono:wght@400;500;700&display=swap',
        },
      ],
    },
  },
  runtimeConfig: {
    // URL interne Docker (SSR server-side, non exposée au navigateur)
    apiBaseInternal: process.env.NUXT_API_BASE_INTERNAL ?? 'http://back:8080',
    public: {
      // URL exposée au navigateur (client-side fetch)
      apiBase: process.env.NUXT_PUBLIC_API_BASE ?? 'http://localhost:8081',
      orsApiKey: process.env.NUXT_PUBLIC_ORS_API_KEY ?? '',
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
      globPatterns: ['**/*.{js,css,html,png,svg,ico}'],
    },
    devOptions: {
      enabled: false,
      type: 'module',
    },
  },
})
