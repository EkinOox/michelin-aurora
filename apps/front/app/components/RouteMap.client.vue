<script setup lang="ts">
interface Pt { lat: number, lon: number }

const props = defineProps<{ segments: Pt[][] }>()
const mapEl = ref<HTMLDivElement>()

onMounted(async () => {
  if (!mapEl.value) return
  const all = props.segments.flat()
  if (all.length < 2) return

  // Load Leaflet from CDN — no bundling, no SSR issues
  if (!document.querySelector('#leaflet-css')) {
    const link = document.createElement('link')
    link.id = 'leaflet-css'
    link.rel = 'stylesheet'
    link.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css'
    document.head.appendChild(link)
  }

  const L: typeof import('leaflet').default = await new Promise((resolve, reject) => {
    if ((window as any).L) { resolve((window as any).L); return }
    const s = document.createElement('script')
    s.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js'
    s.onload = () => resolve((window as any).L)
    s.onerror = reject
    document.head.appendChild(s)
  })

  const map = L.map(mapEl.value, { zoomControl: false, attributionControl: false })

  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map)

  props.segments.forEach(seg =>
    L.polyline(seg.map(p => [p.lat, p.lon] as [number, number]), {
      color: '#84cc16', weight: 4, opacity: 0.9, lineCap: 'round', lineJoin: 'round',
    }).addTo(map),
  )

  map.fitBounds(L.latLngBounds(all.map(p => [p.lat, p.lon])), { padding: [24, 24] })

  const s = all[0]
  const e = all[all.length - 1]
  L.circleMarker([s.lat, s.lon], { radius: 7, color: '#10140c', weight: 2, fillColor: '#facc15', fillOpacity: 1 }).addTo(map)
  L.circleMarker([e.lat, e.lon], { radius: 7, color: '#10140c', weight: 2, fillColor: '#65a30d', fillOpacity: 1 }).addTo(map)
})
</script>

<template>
  <div ref="mapEl" style="width: 100%; height: 100%" />
</template>
