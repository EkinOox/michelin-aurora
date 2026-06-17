import { mkdirSync, writeFileSync, existsSync } from 'fs'
import { resolve } from 'path'

// Fallback: ensures the dev service-worker stub exists when Nitro starts.
// The primary fix is in nuxt.config.ts (runs at config-load time, before
// the server); this plugin is a secondary safety net for hot-reloads.
export default defineNitroPlugin(() => {
  if (process.env.NODE_ENV === 'production') return
  const dir = resolve('.nuxt/dev-sw-dist')
  const file = `${dir}/sw.js`
  if (existsSync(file)) return
  mkdirSync(dir, { recursive: true })
  writeFileSync(
    file,
    `self.addEventListener('install',()=>self.skipWaiting());self.addEventListener('activate',()=>clients.claim());self.addEventListener('fetch',()=>{});`,
  )
})
