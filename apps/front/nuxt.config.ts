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
      htmlAttrs: { lang: 'fr' },
      titleTemplate: '%s — Aurora by Michelin',
      meta: [
        // ── PWA / iOS ──────────────────────────────────────────────
        { name: 'apple-mobile-web-app-capable', content: 'yes' },
        { name: 'apple-mobile-web-app-status-bar-style', content: 'black-translucent' },
        { name: 'apple-mobile-web-app-title', content: 'Aurora' },
        { name: 'mobile-web-app-capable', content: 'yes' },
        { name: 'theme-color', content: '#27509B' },
        { name: 'msapplication-TileColor', content: '#27509B' },

        // ── SEO de base ────────────────────────────────────────────
        {
          name: 'description',
          content: 'Aurora by Michelin — La Cycling Intelligence Platform qui optimise votre pression de pneu en temps réel selon la météo, le terrain et votre profil cycliste.',
        },
        { name: 'keywords', content: 'pneu vélo, pression pneu, cyclisme, michelin, gravel, route, VTT, VAE, PWA cycliste, application vélo' },
        { name: 'author', content: 'Michelin LB 2 Wheels' },
        { name: 'robots', content: 'index, follow' },
        { name: 'viewport', content: 'width=device-width, initial-scale=1, viewport-fit=cover' },

        // ── Open Graph (Facebook, LinkedIn, WhatsApp…) ─────────────
        { property: 'og:type', content: 'website' },
        { property: 'og:site_name', content: 'Aurora by Michelin' },
        { property: 'og:title', content: 'Aurora — Cycling Intelligence Platform' },
        {
          property: 'og:description',
          content: 'Optimisez votre pression de pneu en temps réel. Télémétrie, itinéraires, récompenses — tout s\'adapte à votre profil cycliste.',
        },
        { property: 'og:image', content: '/icons/icon-512.png' },
        { property: 'og:image:width', content: '512' },
        { property: 'og:image:height', content: '512' },
        { property: 'og:locale', content: 'fr_FR' },

        // ── Twitter / X Card ───────────────────────────────────────
        { name: 'twitter:card', content: 'summary' },
        { name: 'twitter:site', content: '@Michelin' },
        { name: 'twitter:title', content: 'Aurora — Cycling Intelligence Platform' },
        {
          name: 'twitter:description',
          content: 'Pression dynamique, télémétrie temps réel, récompenses. L\'app Michelin pour les cyclistes.',
        },
        { name: 'twitter:image', content: '/icons/icon-512.png' },
      ],
      link: [
        { rel: 'icon', type: 'image/svg+xml', href: '/icons/michelin_aurora_fiveicon.svg' },
        { rel: 'icon', type: 'image/png', sizes: '192x192', href: '/icons/icon-192.png' },
        { rel: 'apple-touch-icon', sizes: '192x192', href: '/icons/icon-192.png' },
        { rel: 'apple-touch-icon', sizes: '512x512', href: '/icons/icon-512.png' },
        { rel: 'mask-icon', href: '/icons/michelin_aurora_fiveicon.svg', color: '#27509B' },
        { rel: 'preconnect', href: 'https://fonts.googleapis.com' },
        { rel: 'preconnect', href: 'https://fonts.gstatic.com', crossorigin: '' },
        {
          rel: 'stylesheet',
          href: 'https://fonts.googleapis.com/css2?family=Noto+Sans:ital,wght@0,400;0,500;0,600;0,700;0,800;1,400&family=Noto+Sans+Mono:wght@400;500;700&display=swap',
        },
      ],
      script: [
        // ── JSON-LD — Application mobile ───────────────────────────
        {
          type: 'application/ld+json',
          innerHTML: JSON.stringify({
            '@context': 'https://schema.org',
            '@type': 'MobileApplication',
            name: 'Aurora — Cycling Intelligence Platform',
            description: 'Application PWA Michelin pour les cyclistes : pression dynamique, télémétrie temps réel, itinéraires personnalisés et récompenses.',
            applicationCategory: 'SportsApplication',
            operatingSystem: 'iOS, Android',
            offers: { '@type': 'Offer', price: '0', priceCurrency: 'EUR' },
            author: {
              '@type': 'Organization',
              name: 'Michelin',
              url: 'https://www.michelin.fr',
              logo: 'https://www.michelin.fr/favicon.ico',
            },
            aggregateRating: {
              '@type': 'AggregateRating',
              ratingValue: '4.8',
              ratingCount: '120',
            },
          }),
        },
        // ── JSON-LD — Organisation ─────────────────────────────────
        {
          type: 'application/ld+json',
          innerHTML: JSON.stringify({
            '@context': 'https://schema.org',
            '@type': 'Organization',
            name: 'Michelin LB 2 Wheels',
            description: 'Division Michelin dédiée aux pneumatiques pour vélos — Route, Gravel, VTT, VAE.',
            url: 'https://www.michelin.fr',
            sameAs: [
              'https://www.instagram.com/michelin',
              'https://www.facebook.com/michelin',
              'https://twitter.com/Michelin',
              'https://www.linkedin.com/company/michelin',
            ],
          }),
        },
        // ── JSON-LD — BreadcrumbList (navigation principale) ───────
        {
          type: 'application/ld+json',
          innerHTML: JSON.stringify({
            '@context': 'https://schema.org',
            '@type': 'BreadcrumbList',
            itemListElement: [
              { '@type': 'ListItem', position: 1, name: 'Accueil', item: '/' },
              { '@type': 'ListItem', position: 2, name: 'Pression', item: '/pressure' },
              { '@type': 'ListItem', position: 3, name: 'Itinéraires', item: '/routes' },
              { '@type': 'ListItem', position: 4, name: 'Communauté', item: '/community' },
              { '@type': 'ListItem', position: 5, name: 'Récompenses', item: '/rewards' },
            ],
          }),
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
      enabled: true,
      type: 'module',
    },
  },
})
