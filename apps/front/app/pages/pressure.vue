<script setup lang="ts">
definePageMeta({ tabbar: false })

interface WeatherCurrent {
  temperature_2m: number
  wind_speed_10m: number
  relative_humidity_2m: number
  precipitation: number
  weather_code: number
}
interface WeatherDto { current: WeatherCurrent }

interface PressureDto {
  rain: boolean
  front_bar: number
  rear_bar: number
  bike_type: string
  factors: {
    base_rear: number
    temp_adj: number
    rain_adj: number
    wind_adj: number
    level_adj: number
  }
}

const router = useRouter()
const { token } = useAuth()

const weather = ref<WeatherCurrent | null>(null)
const pressure = ref<PressureDto | null>(null)
const weatherLoading = ref(true)

const POS_KEY = 'aurora-position'

async function load() {
  // 1. Get position (cached → no permission prompt)
  let lat = 43.5297, lon = 5.4474
  try {
    const cached = localStorage.getItem(POS_KEY)
    if (cached) {
      const pos = JSON.parse(cached)
      lat = pos.lat; lon = pos.lon
    }
    else {
      const pos = await new Promise<GeolocationPosition>((resolve, reject) =>
        navigator.geolocation
          ? navigator.geolocation.getCurrentPosition(resolve, reject, { timeout: 5000 })
          : reject(new Error('no geo')),
      )
      lat = pos.coords.latitude; lon = pos.coords.longitude
      localStorage.setItem(POS_KEY, JSON.stringify({ lat, lon }))
    }
  }
  catch { /* use default */ }

  // 2. Fetch weather from Open-Meteo
  try {
    const res = await $fetch<WeatherDto>(
      `https://api.open-meteo.com/v1/forecast?latitude=${lat}&longitude=${lon}&current=temperature_2m,wind_speed_10m,relative_humidity_2m,precipitation,weather_code&timezone=auto`,
    )
    weather.value = res.current
  }
  catch { /* keep null */ }
  finally { weatherLoading.value = false }

  // 3. Call backend with real weather data
  const w = weather.value
  const query: Record<string, string | number> = {}
  if (w) {
    query.temp = Math.round(w.temperature_2m)
    query.humidity = Math.round(w.relative_humidity_2m)
    query.wind = Math.round(w.wind_speed_10m)
    query.precip = w.precipitation
    query.code = w.weather_code
  }

  try {
    pressure.value = await $fetch<PressureDto>('/api/pressure', {
      baseURL: useRuntimeConfig().public.apiBase,
      query,
      headers: token.value ? { Authorization: `Bearer ${token.value}` } : {},
    })
  }
  catch { /* keep null */ }
}

onMounted(load)

const isRain = computed(() => pressure.value?.rain ?? false)
const front = computed(() => pressure.value?.front_bar ?? 0)
const rear = computed(() => pressure.value?.rear_bar ?? 0)
const val = computed(() => rear.value ? (rear.value - 1.5) / (4.5 - 1.5) : 0)
const gaugeColor = computed(() => isRain.value ? 0x27509B : 0x84BD00)

const temperature = computed(() => weather.value ? `${Math.round(weather.value.temperature_2m)}°C` : '—')
const humidity = computed(() => weather.value ? `${weather.value.relative_humidity_2m}%` : '—')
const windSpeed = computed(() => weather.value ? `${Math.round(weather.value.wind_speed_10m)} km/h` : '—')
const precipitation = computed(() => weather.value ? `${weather.value.precipitation} mm` : '—')

function fmt(n: number) {
  return n.toFixed(1).replace('.', ',')
}

function fmtAdj(n: number) {
  if (n === 0) return '0'
  return (n > 0 ? '+' : '') + n.toFixed(2).replace('.', ',')
}

const factors = computed(() => {
  const f = pressure.value?.factors
  if (!f) return []
  return [
    { label: 'Base', value: fmtAdj(f.base_rear), note: `Pneu ${pressure.value?.bike_type}`, neutral: true },
    { label: 'Température', value: fmtAdj(f.temp_adj), note: temperature.value, neutral: f.temp_adj === 0 },
    { label: 'Pluie / humidité', value: fmtAdj(f.rain_adj), note: isRain.value ? 'Chaussée mouillée' : `${humidity.value}`, neutral: f.rain_adj === 0 },
    { label: 'Vent', value: fmtAdj(f.wind_adj), note: windSpeed.value, neutral: f.wind_adj === 0 },
    { label: 'Niveau cycliste', value: fmtAdj(f.level_adj), note: '', neutral: f.level_adj === 0 },
  ].filter(x => !x.neutral || x.label === 'Base')
})
</script>

<template>
  <div class="screen">
    <AppHeader title="Pressure Guide" sub="Pression idéale au dixième de bar, en temps réel." :on-back="() => router.back()" />
    <div class="screen-scroll pad" style="padding-top: 4px">

      <!-- Gauge -->
      <div class="card-lg" style="position: relative; height: 280px; overflow: hidden; margin-bottom: 16px">
        <div style="position: absolute; inset: 0">
          <GaugeScene :value="val" :color="gaugeColor" />
        </div>
        <div style="position: absolute; left: 0; right: 0; bottom: 34px; text-align: center; pointer-events: none">
          <div v-if="weatherLoading || !pressure" class="spinner" />
          <template v-else>
            <div class="num" style="font-size: 48px; font-weight: 800; letter-spacing: -.03em">
              {{ fmt(rear) }}<span style="font-size: 18px; color: var(--mute); margin-left: 4px">bar</span>
            </div>
            <div class="eyebrow" :style="{ marginTop: '2px', color: isRain ? 'var(--blue)' : 'var(--lime-600)' }">
              {{ isRain ? 'Mode pluie — chaussée mouillée' : 'Conditions sèches' }}
            </div>
          </template>
        </div>
        <div style="position: absolute; top: 14px; left: 14px">
          <span class="badge badge-live"><span class="ld" /> Live</span>
        </div>
      </div>

      <!-- Front / Rear cards -->
      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 16px">
        <div class="card" style="padding: 16px">
          <div class="tiny">Avant</div>
          <div class="num" style="font-size: 26px; font-weight: 800; margin-top: 4px">
            <template v-if="pressure">{{ fmt(front) }}<span style="font-size: 12px; color: var(--mute)"> bar</span></template>
            <span v-else style="color: var(--mute)">—</span>
          </div>
          <div class="track" style="margin-top: 10px">
            <i :style="{ width: `${(front / 4.5) * 100}%`, background: isRain ? 'var(--blue)' : 'var(--lime)' }" />
          </div>
        </div>
        <div class="card" style="padding: 16px">
          <div class="tiny">Arrière</div>
          <div class="num" style="font-size: 26px; font-weight: 800; margin-top: 4px">
            <template v-if="pressure">{{ fmt(rear) }}<span style="font-size: 12px; color: var(--mute)"> bar</span></template>
            <span v-else style="color: var(--mute)">—</span>
          </div>
          <div class="track" style="margin-top: 10px">
            <i :style="{ width: `${(rear / 4.5) * 100}%`, background: isRain ? 'var(--blue)' : 'var(--lime)' }" />
          </div>
        </div>
      </div>

      <!-- Weather card -->
      <div class="card" style="padding: 16px; margin-bottom: 14px">
        <div class="between" style="margin-bottom: 14px">
          <span class="h-sm">Météo actuelle</span>
          <span class="badge" :class="isRain ? 'badge-blue' : ''">
            <Icon :name="isRain ? 'cloud' : 'sun'" :size="12" />
            {{ isRain ? 'Pluie' : 'Sec' }}
          </span>
        </div>
        <div style="display: flex; gap: 6px">
          <div style="flex: 1; text-align: center">
            <Icon name="thermo" :size="20" color="var(--ink-3)" />
            <div class="num" style="font-size: 16px; font-weight: 700; margin-top: 6px">{{ temperature }}</div>
            <div class="tiny">Temp.</div>
          </div>
          <div style="flex: 1; text-align: center">
            <Icon name="drop" :size="20" color="var(--ink-3)" />
            <div class="num" style="font-size: 16px; font-weight: 700; margin-top: 6px">{{ humidity }}</div>
            <div class="tiny">Humidité</div>
          </div>
          <div style="flex: 1; text-align: center">
            <Icon name="wind" :size="20" color="var(--ink-3)" />
            <div class="num" style="font-size: 16px; font-weight: 700; margin-top: 6px">{{ windSpeed }}</div>
            <div class="tiny">Vent</div>
          </div>
          <div style="flex: 1; text-align: center">
            <Icon name="drop" :size="20" color="var(--blue)" />
            <div class="num" style="font-size: 16px; font-weight: 700; margin-top: 6px">{{ precipitation }}</div>
            <div class="tiny">Préc.</div>
          </div>
        </div>
      </div>

      <!-- Algorithm breakdown -->
      <div v-if="factors.length" class="card" style="padding: 16px; margin-bottom: 14px">
        <div class="h-sm" style="margin-bottom: 12px">Facteurs de calcul</div>
        <div v-for="f in factors" :key="f.label" style="display: flex; align-items: center; justify-content: space-between; padding: 6px 0; border-bottom: 1px solid var(--line-2)">
          <div>
            <div class="small" style="font-weight: 600">{{ f.label }}</div>
            <div v-if="f.note" class="tiny" style="color: var(--mute)">{{ f.note }}</div>
          </div>
          <span class="num" style="font-size: 13px; font-weight: 700" :style="{ color: f.value.startsWith('-') ? 'var(--blue)' : f.value.startsWith('+') ? 'var(--lime-600)' : 'var(--ink-3)' }">
            {{ f.value }} bar
          </span>
        </div>
      </div>

      <div class="card" style="padding: 14px; display: flex; gap: 12px; align-items: flex-start; background: var(--surface-2); border-color: transparent">
        <Icon name="bolt" :size="20" color="var(--yellow)" />
        <div class="small">L'algorithme Michelin croise <b>type de vélo, niveau, température, précipitations et vent</b> pour calculer la pression optimale au dixième de bar.</div>
      </div>
    </div>

    <div class="pad glass" style="position: absolute; left: 0; right: 0; bottom: 0; padding-top: 14px; padding-bottom: 26px">
      <button class="btn btn-blue btn-block" @click="router.back()">
        <Icon name="check" :size="18" /> Appliquer ces pressions
      </button>
    </div>
  </div>
</template>

<style scoped>
.spinner {
  width: 32px;
  height: 32px;
  border: 3px solid rgba(255,255,255,.15);
  border-top-color: #84cc16;
  border-radius: 50%;
  animation: spin .8s linear infinite;
  margin: 12px auto;
}
@keyframes spin { to { transform: rotate(360deg) } }
</style>
