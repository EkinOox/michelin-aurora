<script setup lang="ts">
definePageMeta({ tabbar: false })

useSeoMeta({
  title: 'Pression intelligente',
  description: 'Calculez votre pression de pneu idéale en temps réel — météo, discipline (route, gravel, VTT, VAE) et profil rider pris en compte par l\'algorithme Michelin.',
  ogTitle: 'Pression intelligente — Aurora by Michelin',
  ogDescription: 'Algorithme Michelin : pression optimale calculée selon la météo live, la température et votre discipline cycliste.',
  ogImage: '/icons/icon-512.png',
  twitterCard: 'summary',
})

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

  try {
    const res = await $fetch<WeatherDto>(
      `https://api.open-meteo.com/v1/forecast?latitude=${lat}&longitude=${lon}&current=temperature_2m,wind_speed_10m,relative_humidity_2m,precipitation,weather_code&timezone=auto`,
    )
    weather.value = res.current
  }
  catch { /* keep null */ }
  finally { weatherLoading.value = false }

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
const gaugeColor = computed(() => isRain.value ? '#27509B' : '#FFD100')
const trackColor = computed(() => isRain.value ? 'var(--blue)' : 'var(--yellow)')

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
    <div class="screen-scroll pad" style="padding-top: 4px; padding-bottom: 100px">

      <!-- Gauge card -->
      <div class="gauge-card rise" style="margin-bottom: 16px">
        <div style="position: absolute; top: 14px; left: 14px; z-index: 2">
          <span class="badge badge-live"><span class="ld" /> Live</span>
        </div>

        <!-- Gauge (value rendered inside the dial) -->
        <div style="height: 250px; position: relative; padding-top: 12px">
          <GaugeScene :value="val" :color="gaugeColor" :label="pressure ? fmt(rear) : '—'" :loading="weatherLoading || !pressure" />
          <div v-if="weatherLoading || !pressure" class="spinner" style="position:absolute; left:50%; top:55%; transform:translate(-50%,-50%)" />
        </div>

        <!-- Condition pill -->
        <div style="text-align: center; padding: 0 16px 20px; margin-top: -10px">
          <div v-if="pressure" class="condition-pill" :class="isRain ? 'condition-rain' : 'condition-dry'">
            <Icon :name="isRain ? 'cloud' : 'sun'" :size="12" />
            {{ isRain ? 'Mode pluie — chaussée mouillée' : 'Conditions sèches' }}
          </div>
        </div>
      </div>

      <!-- Front / Rear cards -->
      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 16px">
        <div class="card pressure-wheel-card rise">
          <div class="wheel-label">Avant</div>
          <div class="num wheel-value">
            <template v-if="pressure">{{ fmt(front) }}<span class="wheel-unit"> bar</span></template>
            <span v-else style="color: var(--mute)">—</span>
          </div>
          <div class="track" style="margin-top: 10px">
            <i :style="{ width: `${(front / 4.5) * 100}%`, background: trackColor }" />
          </div>
        </div>
        <div class="card pressure-wheel-card rise" style="animation-delay:.05s">
          <div class="wheel-label">Arrière</div>
          <div class="num wheel-value">
            <template v-if="pressure">{{ fmt(rear) }}<span class="wheel-unit"> bar</span></template>
            <span v-else style="color: var(--mute)">—</span>
          </div>
          <div class="track" style="margin-top: 10px">
            <i :style="{ width: `${(rear / 4.5) * 100}%`, background: trackColor }" />
          </div>
        </div>
      </div>

      <!-- Weather card -->
      <div class="card rise" style="padding: 16px; margin-bottom: 14px">
        <div class="between" style="margin-bottom: 14px">
          <span class="h-sm">Météo actuelle</span>
          <span class="badge" :class="isRain ? 'badge-blue' : 'badge-yellow-soft'">
            <Icon :name="isRain ? 'cloud' : 'sun'" :size="12" />
            {{ isRain ? 'Pluie' : 'Sec' }}
          </span>
        </div>

        <div class="weather-grid">
          <!-- Temperature -->
          <div class="weather-tile">
            <div class="weather-icon-box" style="background: #FFF3CD">
              <Icon name="thermo" :size="20" color="#E67E00" />
            </div>
            <div class="num weather-value">{{ temperature }}</div>
            <div class="weather-label">Température</div>
          </div>

          <!-- Humidity -->
          <div class="weather-tile">
            <div class="weather-icon-box" style="background: #DBEAFE">
              <Icon name="drop" :size="20" color="#2563EB" />
            </div>
            <div class="num weather-value">{{ humidity }}</div>
            <div class="weather-label">Humidité</div>
          </div>

          <!-- Wind -->
          <div class="weather-tile">
            <div class="weather-icon-box" style="background: #F3F4F6">
              <Icon name="wind" :size="20" color="#6B7280" />
            </div>
            <div class="num weather-value">{{ windSpeed }}</div>
            <div class="weather-label">Vent</div>
          </div>

          <!-- Precipitation -->
          <div class="weather-tile">
            <div class="weather-icon-box" :style="{ background: isRain ? '#DBEAFE' : '#F3F4F6' }">
              <Icon name="cloud" :size="20" :color="isRain ? '#27509B' : '#9CA3AF'" />
            </div>
            <div class="num weather-value">{{ precipitation }}</div>
            <div class="weather-label">Précip.</div>
          </div>
        </div>
      </div>

      <!-- Algorithm breakdown -->
      <div v-if="factors.length" class="card rise" style="padding: 16px; margin-bottom: 14px">
        <div class="h-sm" style="margin-bottom: 12px">Facteurs de calcul</div>
        <div v-for="(f, i) in factors" :key="f.label"
             style="display: flex; align-items: center; justify-content: space-between; padding: 8px 0"
             :style="{ borderTop: i ? '1px solid var(--line-2)' : 'none' }">
          <div>
            <div class="small" style="font-weight: 600">{{ f.label }}</div>
            <div v-if="f.note" class="tiny" style="color: var(--mute)">{{ f.note }}</div>
          </div>
          <span class="factor-badge"
                :class="f.value.startsWith('-') ? 'factor-blue' : f.value.startsWith('+') ? 'factor-yellow' : 'factor-neutral'">
            {{ f.value }} bar
          </span>
        </div>
      </div>

      <!-- Info banner -->
      <div class="card rise" style="padding: 14px; display: flex; gap: 12px; align-items: flex-start; background: var(--surface-2); border-color: transparent">
        <div style="flex: 0 0 auto; width: 34px; height: 34px; border-radius: 10px; background: rgba(255,209,0,.15); display: flex; align-items: center; justify-content: center">
          <Icon name="bolt" :size="18" color="#E67E00" />
        </div>
        <div class="small" style="flex: 1; margin-top: 6px">L'algorithme Michelin croise <b>type de vélo, niveau, température, précipitations et vent</b> pour calculer la pression optimale au dixième de bar.</div>
      </div>
    </div>

    <!-- Bottom CTA -->
    <div class="pad glass" style="position: absolute; left: 0; right: 0; bottom: 0; padding-top: 14px; padding-bottom: 26px">
      <button class="btn btn-blue btn-block" @click="router.back()">
        <Icon name="check" :size="18" /> Appliquer ces pressions
      </button>
    </div>
  </div>
</template>

<style scoped>
/* ── Gauge card ─────────────────────────────────────── */
.gauge-card {
  position: relative;
  background: #ffffff;
  border-radius: 22px;
  border: 1px solid var(--line-2);
  overflow: hidden;
}

/* ── Condition pill ─────────────────────────────────── */
.condition-pill {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  font-size: 11px;
  font-weight: 700;
  letter-spacing: .06em;
  text-transform: uppercase;
  padding: 5px 12px;
  border-radius: 99px;
  margin-top: 8px;
}
.condition-dry {
  background: rgba(255, 209, 0, .15);
  color: #B06000;
}
.condition-rain {
  background: rgba(39, 80, 155, .12);
  color: var(--blue);
}

/* ── Wheel cards ────────────────────────────────────── */
.pressure-wheel-card {
  padding: 18px 16px 14px;
}
.wheel-label {
  font-size: 11px;
  font-weight: 600;
  color: var(--mute);
  text-transform: uppercase;
  letter-spacing: .06em;
  margin-bottom: 6px;
}
.wheel-value {
  font-size: 30px;
  font-weight: 800;
}
.wheel-unit {
  font-size: 13px;
  font-weight: 500;
  color: var(--mute);
}

/* ── Weather grid ───────────────────────────────────── */
.weather-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 10px;
}
.weather-tile {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 6px;
  padding: 12px 8px;
  background: var(--surface-2);
  border-radius: 14px;
  text-align: center;
}
.weather-icon-box {
  width: 40px;
  height: 40px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
}
.weather-value {
  font-size: 17px;
  font-weight: 800;
  line-height: 1;
  color: var(--ink);
}
.weather-label {
  font-size: 10px;
  font-weight: 600;
  color: var(--mute);
  text-transform: uppercase;
  letter-spacing: .05em;
}

/* ── Factor badges ──────────────────────────────────── */
.factor-badge {
  font-size: 12px;
  font-weight: 700;
  padding: 4px 10px;
  border-radius: 8px;
}
.factor-yellow { background: rgba(255,209,0,.15); color: #A05000; }
.factor-blue   { background: rgba(39,80,155,.1);  color: var(--blue); }
.factor-neutral { background: var(--surface-2); color: var(--ink-3); }

/* ── Spinner ────────────────────────────────────────── */
.spinner {
  width: 32px;
  height: 32px;
  border: 3px solid rgba(0,0,0,.08);
  border-top-color: #FFD100;
  border-radius: 50%;
  animation: spin .8s linear infinite;
  margin: 12px auto;
}
@keyframes spin { to { transform: rotate(360deg) } }

/* ── Badge variants ─────────────────────────────────── */
.badge-yellow-soft {
  background: rgba(255,209,0,.18);
  color: #8A5000;
}
</style>
