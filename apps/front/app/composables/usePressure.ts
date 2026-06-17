interface PressureResult {
  front_bar: number
  rear_bar: number
  rain: boolean
}

const PRESSURE_KEY = 'aurora-pressure'
const PRESSURE_TTL = 30 * 60 * 1000 // 30 min
const POS_KEY = 'aurora-position'

export const usePressureState = () => useState<PressureResult | null>('pressure-data', () => null)

export function usePressure() {
  const state = usePressureState()
  const loading = ref(!state.value)
  const { token } = useAuth()
  const config = useRuntimeConfig()

  onMounted(async () => {
    if (state.value) { loading.value = false; return }

    try {
      const cached = localStorage.getItem(PRESSURE_KEY)
      if (cached) {
        const { ts, data } = JSON.parse(cached)
        if (Date.now() - ts < PRESSURE_TTL) {
          state.value = data
          loading.value = false
          return
        }
      }
    }
    catch { /* ignore */ }

    // Position (cached → no permission prompt)
    let lat = 43.5297, lon = 5.4474
    try {
      const pos = localStorage.getItem(POS_KEY)
      if (pos) {
        const p = JSON.parse(pos)
        lat = p.lat; lon = p.lon
      }
      else {
        const gps = await new Promise<GeolocationPosition>((resolve, reject) =>
          navigator.geolocation
            ? navigator.geolocation.getCurrentPosition(resolve, reject, { timeout: 5000 })
            : reject(new Error('no geo')),
        )
        lat = gps.coords.latitude; lon = gps.coords.longitude
        localStorage.setItem(POS_KEY, JSON.stringify({ lat, lon }))
      }
    }
    catch { /* use default */ }

    // Weather
    const query: Record<string, string | number> = {}
    try {
      const w = await $fetch<{ current: { temperature_2m: number, relative_humidity_2m: number, wind_speed_10m: number, precipitation: number, weather_code: number } }>(
        `https://api.open-meteo.com/v1/forecast?latitude=${lat}&longitude=${lon}&current=temperature_2m,wind_speed_10m,relative_humidity_2m,precipitation,weather_code&timezone=auto`,
      )
      const c = w.current
      query.temp = Math.round(c.temperature_2m)
      query.humidity = Math.round(c.relative_humidity_2m)
      query.wind = Math.round(c.wind_speed_10m)
      query.precip = c.precipitation
      query.code = c.weather_code
    }
    catch { /* no weather, backend uses defaults */ }

    try {
      const result = await $fetch<PressureResult>('/api/pressure', {
        baseURL: config.public.apiBase,
        query,
        headers: token.value ? { Authorization: `Bearer ${token.value}` } : {},
      })
      state.value = result
      localStorage.setItem(PRESSURE_KEY, JSON.stringify({ ts: Date.now(), data: result }))
    }
    catch { /* keep null */ }
    finally { loading.value = false }
  })

  const fmt = (n: number) => n.toFixed(1).replace('.', ',')

  return {
    pressure: state,
    loading,
    frontStr: computed(() => state.value ? fmt(state.value.front_bar) : '—'),
    rearStr: computed(() => state.value ? fmt(state.value.rear_bar) : '—'),
    isRain: computed(() => state.value?.rain ?? false),
  }
}
