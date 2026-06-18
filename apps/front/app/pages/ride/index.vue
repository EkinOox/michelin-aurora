<script setup lang="ts">
definePageMeta({ tabbar: false })

interface TelemetryReading {
  pressure_front_bar: number
  pressure_rear_bar: number
  speed_kmh: number
  alert_triggered: boolean
  recorded_at: string
  km_remaining?: number
}

const apiBase = useApiBase()
const router = useRouter()
const activeRide = useActiveRide()
const activeRoute = useActiveRoute()

const running = ref(true)
const connectionStatus = ref<'connecting' | 'connected'>('connecting')
const lastReading = ref<TelemetryReading | null>(null)
const elapsedSec = ref(0)
const distanceKm = ref(0)
const points = ref(0)

let eventSource: EventSource | null = null
let elapsedTimer: ReturnType<typeof setInterval> | null = null

const speed = computed(() => lastReading.value?.speed_kmh ?? 0)
const pressureFront = computed(() => lastReading.value?.pressure_front_bar ?? 2.6)
const pressureRear = computed(() => lastReading.value?.pressure_rear_bar ?? 2.8)

const routeName = computed(() => activeRoute.value?.name ?? 'Sortie libre')
const routeTotalKm = computed(() => activeRoute.value?.distance_km ?? null)
const kmRemaining = computed(() => {
  if (!routeTotalKm.value) return null
  return Math.max(0, routeTotalKm.value - distanceKm.value)
})

const mm = computed(() => String(Math.floor(elapsedSec.value / 60)).padStart(2, '0'))
const ss = computed(() => String(elapsedSec.value % 60).padStart(2, '0'))

function fmt(n: number, d = 1) {
  return n.toFixed(d).replace('.', ',')
}

async function startRide() {
  if (!activeRide.value) {
    const ride = await $apiFetch<{ id: string }>('/api/rides', {
      method: 'POST',
      body: { terrain_type: 'mixed' },
    })
    activeRide.value = ride.id
  }

  eventSource = new EventSource(`${apiBase}/api/telemetry/stream?ride_id=${activeRide.value}`)
  eventSource.onopen = () => { connectionStatus.value = 'connected' }
  eventSource.onmessage = (event) => {
    const reading = JSON.parse(event.data) as TelemetryReading
    lastReading.value = reading
    if (reading.alert_triggered) {
      const wheel    = reading.pressure_front_bar < 1.5 ? 'avant' : 'arrière'
      const pressure = reading.pressure_front_bar < 1.5 ? reading.pressure_front_bar : reading.pressure_rear_bar
      router.push(`/ride/puncture?wheel=${wheel}&pressure=${pressure.toFixed(3)}`)
    }
  }
  eventSource.onerror = () => { connectionStatus.value = 'connecting' }

  elapsedTimer = setInterval(() => {
    if (!running.value) return
    elapsedSec.value += 1
    distanceKm.value += speed.value / 3600
    points.value += 0.4
  }, 1000)
}

function toggleRunning() {
  running.value = !running.value
}

onMounted(startRide)
onBeforeUnmount(() => {
  eventSource?.close()
  if (elapsedTimer) clearInterval(elapsedTimer)
})
</script>

<template>
  <div class="screen" style="background: linear-gradient(180deg,#0E1011 0%, #15191b 100%)">
    <div class="mapbg-dark" style="position: absolute; top: 0; left: 0; right: 0; height: 46%; opacity: .9">
      <svg viewBox="0 0 400 360" style="position: absolute; inset: 0; width: 100%; height: 100%">
        <path d="M-20 320 C100 280, 80 200, 200 190 S330 130, 420 150" fill="none" stroke="var(--lime)" stroke-width="4" stroke-linecap="round" />
        <circle r="9" fill="var(--yellow)" stroke="#0E1011" stroke-width="3">
          <animateMotion dur="9s" repeatCount="indefinite" path="M-20 320 C100 280, 80 200, 200 190 S330 130, 420 150" />
        </circle>
      </svg>
      <div style="position: absolute; inset: 0; background: linear-gradient(180deg, rgba(14,16,17,.2) 0%, rgba(14,16,17,.1) 40%, #15191b 100%)" />
    </div>

    <div class="screen-scroll" style="position: relative; padding-bottom: 34px">
      <div class="pad safe-top" style="padding-top: 58px">
        <div class="between">
          <NuxtLink to="/home" class="iconbtn iconbtn-dark"><Icon name="x" :size="20" /></NuxtLink>
          <span class="badge badge-live"><span class="ld" :class="{ 'is-off': connectionStatus !== 'connected' }" /> En sortie</span>
          <button class="iconbtn iconbtn-dark" @click="toggleRunning">
            <Icon :name="running ? 'pause' : 'play'" :size="18" />
          </button>
        </div>
        <div style="margin-top: 14px">
          <div class="eyebrow" style="color: var(--yellow)">Télémétrie live · ESP32</div>
          <div class="h-md" style="color: #fff; margin-top: 4px">{{ routeName }}</div>
        </div>
      </div>

      <div class="pad" style="margin-top: 34%; text-align: center">
        <div class="num" style="font-size: 88px; font-weight: 800; color: #fff; line-height: 1; letter-spacing: -.04em">
          {{ fmt(speed) }}
        </div>
        <div class="eyebrow" style="color: rgba(255,255,255,.6); margin-top: 2px">km/h · vitesse instantanée</div>
      </div>

      <div v-if="kmRemaining !== null" class="pad" style="margin-top: 16px">
        <div style="background: rgba(255,255,255,.05); border: 1px solid rgba(255,255,255,.09); border-radius: var(--r-md); padding: 12px 16px; display: flex; align-items: center; justify-content: space-between">
          <div class="row" style="gap: 7px">
            <Icon name="route" :size="15" color="var(--yellow)" />
            <span class="tiny" style="color: rgba(255,255,255,.55)">Km restants</span>
          </div>
          <div class="num" style="font-size: 20px; font-weight: 800; color: #fff">
            {{ fmt(kmRemaining, 1) }}<span style="font-size: 11px; color: rgba(255,255,255,.4); margin-left: 3px">/ {{ routeTotalKm }} km</span>
          </div>
        </div>
      </div>

      <div class="pad" style="margin-top: 22px; display: grid; grid-template-columns: 1fr 1fr; gap: 12px">
        <div style="background: rgba(255,255,255,.05); border: 1px solid rgba(255,255,255,.09); border-radius: var(--r-md); padding: 14px 16px">
          <div class="row" style="gap: 7px">
            <Icon name="route" :size="15" color="var(--lime)" />
            <span class="tiny" style="color: rgba(255,255,255,.55)">Distance</span>
          </div>
          <div class="num" style="font-size: 26px; font-weight: 800; color: #fff; margin-top: 6px">
            {{ fmt(distanceKm, 2) }}<span style="font-size: 12px; color: rgba(255,255,255,.4); margin-left: 3px">km</span>
          </div>
        </div>
        <div style="background: rgba(255,255,255,.05); border: 1px solid rgba(255,255,255,.09); border-radius: var(--r-md); padding: 14px 16px">
          <div class="row" style="gap: 7px">
            <Icon name="speed" :size="15" color="var(--lime)" />
            <span class="tiny" style="color: rgba(255,255,255,.55)">Temps</span>
          </div>
          <div class="num" style="font-size: 26px; font-weight: 800; color: #fff; margin-top: 6px">
            {{ mm }}:{{ ss }}
          </div>
        </div>
      </div>

      <div class="pad" style="margin-top: 12px">
        <div style="background: rgba(255,255,255,.05); border: 1px solid rgba(255,255,255,.09); border-radius: var(--r-md); padding: 16px">
          <div class="between" style="margin-bottom: 12px">
            <span class="small" style="color: #fff; font-weight: 700">Pression différentielle</span>
            <span class="badge" :style="{ background: lastReading?.alert_triggered ? 'rgba(200,16,46,.18)' : 'rgba(132,189,0,.16)', color: lastReading?.alert_triggered ? '#FF6B7E' : 'var(--lime)' }">
              {{ lastReading?.alert_triggered ? 'Alerte' : 'Nominal' }}
            </span>
          </div>
          <div style="margin-bottom: 10px">
            <div class="between" style="margin-bottom: 5px">
              <span class="tiny" style="color: rgba(255,255,255,.6)">Avant</span>
              <span class="num" style="color: #fff; font-size: 14px; font-weight: 700">{{ fmt(pressureFront) }} bar</span>
            </div>
            <div class="track" style="background: rgba(255,255,255,.1)"><i :style="{ width: `${(pressureFront / 4.5) * 100}%`, background: 'var(--lime)' }" /></div>
          </div>
          <div>
            <div class="between" style="margin-bottom: 5px">
              <span class="tiny" style="color: rgba(255,255,255,.6)">Arrière</span>
              <span class="num" style="color: #fff; font-size: 14px; font-weight: 700">{{ fmt(pressureRear) }} bar</span>
            </div>
            <div class="track" style="background: rgba(255,255,255,.1)"><i :style="{ width: `${(pressureRear / 4.5) * 100}%`, background: 'var(--lime)' }" /></div>
          </div>
        </div>
      </div>

      <div class="pad" style="margin-top: 12px; display: flex; gap: 12px">
        <div style="flex: 1; background: rgba(252,229,0,.12); border: 1px solid rgba(252,229,0,.25); border-radius: var(--r-md); padding: 14px 16px">
          <div class="row" style="gap: 7px"><Icon name="gift" :size="16" color="var(--yellow)" /><span class="tiny" style="color: var(--yellow)">Ride Points</span></div>
          <div class="num" style="font-size: 24px; font-weight: 800; color: #fff; margin-top: 4px">+{{ Math.round(points) }}</div>
        </div>
      </div>

      <div class="pad" style="margin-top: 14px">
        <div class="tiny" style="text-align: center; color: rgba(255,255,255,.4)">Détection IoT temps réel · alerte automatique</div>
      </div>
    </div>
  </div>
</template>
