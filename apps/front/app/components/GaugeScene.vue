<script setup lang="ts">
const props = withDefaults(defineProps<{
  value?: number
  color?: string
  label?: string      // formatted pressure, e.g. "3,6"
  loading?: boolean
}>(), { value: 0.5, color: '#FFD100', label: '—', loading: false })

// viewBox 280 x 240 — full circular dial fits with margin
const CX = 140, CY = 120, R = 92
const MIN_DEG = 210
const SPAN    = 240

function pt(deg: number, r = R): [number, number] {
  const a = deg * Math.PI / 180
  return [CX + r * Math.cos(a), CY - r * Math.sin(a)]
}

const [tsx, tsy] = pt(MIN_DEG)
const [tex, tey] = pt(MIN_DEG - SPAN)
const TRACK = `M ${tsx.toFixed(1)} ${tsy.toFixed(1)} A ${R} ${R} 0 1 1 ${tex.toFixed(1)} ${tey.toFixed(1)}`

const TICKS = Array.from({ length: 13 }, (_, i) => {
  const deg = MIN_DEG - (i / 12) * SPAN
  const big = i % 3 === 0
  const [ox, oy] = pt(deg, R + 10)
  const [ix, iy] = pt(deg, R - (big ? 19 : 10))
  return { ox, oy, ix, iy, big }
})

const LABELS = [
  { deg: 210, t: '1,5' }, { deg: 150, t: '2,3' }, { deg: 90, t: '3,0' },
  { deg: 30,  t: '3,8' }, { deg: -30, t: '4,5' },
].map(({ deg, t }) => { const [x, y] = pt(deg, R - 26); return { x: x.toFixed(1), y: y.toFixed(1), t } })

const shown = ref(0)
let raf = 0
onMounted(() => {
  const loop = () => { shown.value += (props.value - shown.value) * 0.09; raf = requestAnimationFrame(loop) }
  raf = requestAnimationFrame(loop)
})
onUnmounted(() => cancelAnimationFrame(raf))

const ARC = computed(() => {
  const v = Math.max(0.001, Math.min(shown.value, 0.9995))
  const [ax, ay] = pt(MIN_DEG - v * SPAN)
  const large = v * SPAN > 180 ? 1 : 0
  return `M ${tsx.toFixed(1)} ${tsy.toFixed(1)} A ${R} ${R} 0 ${large} 1 ${ax.toFixed(1)} ${ay.toFixed(1)}`
})

const NEEDLE = computed(() => {
  const deg = MIN_DEG - shown.value * SPAN
  const a = deg * Math.PI / 180
  const [tx, ty] = pt(deg, 74)
  const px = Math.sin(a), py = Math.cos(a)
  const W = 3.4, CW = 13
  return `M ${(CX+W*px).toFixed(1)} ${(CY+W*py).toFixed(1)} L ${tx.toFixed(1)} ${ty.toFixed(1)} L ${(CX-W*px).toFixed(1)} ${(CY-W*py).toFixed(1)} L ${(CX-CW*Math.cos(a)).toFixed(1)} ${(CY+CW*Math.sin(a)).toFixed(1)} Z`
})
</script>

<template>
  <svg viewBox="0 0 280 240" style="width:100%;height:100%;display:block" preserveAspectRatio="xMidYMid meet" aria-hidden="true">
    <defs>
      <radialGradient id="gp" cx="45%" cy="30%" r="72%">
        <stop offset="0%"   stop-color="#ffffff" />
        <stop offset="62%"  stop-color="#eef2f9" />
        <stop offset="100%" stop-color="#dbe2ef" />
      </radialGradient>
      <linearGradient id="grim" x1="0.15" y1="0.1" x2="0.85" y2="0.9">
        <stop offset="0%"   stop-color="#ffffff" stop-opacity="0.95" />
        <stop offset="100%" stop-color="#6f80a8" stop-opacity="0.7" />
      </linearGradient>
      <filter id="fg" x="-80%" y="-80%" width="260%" height="260%">
        <feGaussianBlur stdDeviation="5" />
      </filter>
    </defs>

    <!-- Plate shadow + face + bevel (r=116, center 140,120 → fits 0..240) -->
    <circle :cx="CX" :cy="CY+4" r="116" fill="rgba(60,80,130,0.16)" />
    <circle :cx="CX" :cy="CY"   r="116" fill="url(#gp)" />
    <circle :cx="CX" :cy="CY"   r="115" fill="none" stroke="url(#grim)" stroke-width="4" />
    <circle :cx="CX" :cy="CY"   r="110" fill="none" stroke="rgba(255,255,255,0.7)" stroke-width="1.5" />

    <!-- Track arc -->
    <path :d="TRACK" fill="none" stroke="#bfc9dd" stroke-width="16" stroke-linecap="round" />
    <path :d="TRACK" fill="none" stroke="#dde4f0" stroke-width="11" stroke-linecap="round" />

    <!-- Active arc -->
    <path v-if="shown > 0.01" :d="ARC" fill="none" :stroke="color"
          stroke-width="17" stroke-linecap="round" opacity="0.25" filter="url(#fg)" />
    <path v-if="shown > 0.01" :d="ARC" fill="none" :stroke="color"
          stroke-width="13" stroke-linecap="round" />
    <path v-if="shown > 0.01" :d="ARC" fill="none"
          stroke="rgba(255,255,255,0.35)" stroke-width="3.5" stroke-linecap="round" />

    <!-- Ticks -->
    <line v-for="(t, i) in TICKS" :key="i"
          :x1="t.ox" :y1="t.oy" :x2="t.ix" :y2="t.iy"
          :stroke="t.big ? '#253047' : '#6a7d9e'"
          :stroke-width="t.big ? 3 : 1.6"
          stroke-linecap="round" />

    <!-- Scale labels -->
    <text v-for="l in LABELS" :key="l.t"
          :x="l.x" :y="l.y"
          text-anchor="middle" dominant-baseline="central"
          font-size="12" font-weight="800"
          font-family="ui-rounded, system-ui, -apple-system, sans-serif"
          fill="#1c2a42">
      {{ l.t }}
    </text>

    <!-- Pressure value inside the dial mouth -->
    <text v-if="!loading" :x="CX" y="178" text-anchor="middle"
          font-family="ui-rounded, system-ui, -apple-system, sans-serif"
          font-weight="900" font-size="40" letter-spacing="-1.5" fill="#0c0f14">{{ label }}<tspan
          font-size="16" font-weight="700" fill="#8b94a6" dx="4">bar</tspan></text>

    <!-- Needle -->
    <path :d="NEEDLE" fill="rgba(0,0,0,0.2)" style="transform:translate(1.5px,2.5px)" />
    <path :d="NEEDLE" fill="#090c11" />

    <!-- Hub -->
    <circle :cx="CX+1" :cy="CY+2" r="13"  fill="rgba(0,0,0,0.26)" />
    <circle :cx="CX"   :cy="CY"   r="12"  fill="#090c11" />
    <circle :cx="CX"   :cy="CY"   r="8"   :fill="color" />
    <circle :cx="CX"   :cy="CY"   r="8"   fill="none" stroke="rgba(255,255,255,0.4)" stroke-width="1.5" />
    <circle :cx="CX"   :cy="CY"   r="4"   fill="#090c11" />
    <circle :cx="CX-2.6" :cy="CY-2.6" r="1.9" fill="rgba(255,255,255,0.7)" />
  </svg>
</template>
