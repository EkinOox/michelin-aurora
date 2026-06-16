<template>
  <div class="flex flex-col items-center min-h-screen gap-6 p-8">
    <div class="text-center">
      <p class="text-[#FCE500] font-mono text-xs tracking-widest uppercase mb-2">Module 03</p>
      <h1 class="text-3xl font-black italic">Live Telemetry Dashboard</h1>
    </div>

    <div class="flex items-center gap-2 text-xs font-mono">
      <span
        class="w-2 h-2 rounded-full"
        :class="connectionStatus === 'connected' ? 'bg-green-400' : 'bg-red-400 animate-pulse'"
      />
      <span class="text-gray-400">
        {{ connectionStatus === 'connected' ? 'Connecté au flux live' : 'En attente de connexion…' }}
      </span>
    </div>

    <div
      v-if="lastReading?.alert_triggered"
      class="bg-red-600 text-white font-bold rounded-lg px-6 py-3 text-center w-full max-w-md animate-pulse"
    >
      ⚠ ALERTE PRESSION — vérifiez vos pneus
    </div>

    <div class="grid grid-cols-2 gap-4 w-full max-w-md">
      <div class="bg-[#27509B] rounded-lg px-4 py-5 text-center">
        <p class="font-mono text-[10px] text-[#FCE500] uppercase tracking-widest mb-2">Pression avant</p>
        <p class="text-3xl font-black">{{ lastReading?.pressure_front_bar ?? '—' }}<span class="text-sm font-normal"> bar</span></p>
      </div>
      <div class="bg-[#27509B] rounded-lg px-4 py-5 text-center">
        <p class="font-mono text-[10px] text-[#FCE500] uppercase tracking-widest mb-2">Pression arrière</p>
        <p class="text-3xl font-black">{{ lastReading?.pressure_rear_bar ?? '—' }}<span class="text-sm font-normal"> bar</span></p>
      </div>
      <div class="bg-[#27509B] rounded-lg px-4 py-5 text-center col-span-2">
        <p class="font-mono text-[10px] text-[#FCE500] uppercase tracking-widest mb-2">Vitesse</p>
        <p class="text-3xl font-black">{{ lastReading?.speed_kmh ?? '—' }}<span class="text-sm font-normal"> km/h</span></p>
      </div>
    </div>

    <p class="text-gray-500 text-xs font-mono">
      Dernière lecture : {{ lastReading?.recorded_at ?? '—' }}
    </p>

    <NuxtLink to="/" class="text-[#FCE500] text-sm font-mono hover:underline">← Retour à l'accueil</NuxtLink>
  </div>
</template>

<script setup lang="ts">
interface TelemetryReading {
  pressure_front_bar: number
  pressure_rear_bar: number
  speed_kmh: number
  alert_triggered: boolean
  recorded_at: string
}

const config = useRuntimeConfig()
const lastReading = ref<TelemetryReading | null>(null)
const connectionStatus = ref<'connecting' | 'connected'>('connecting')

let eventSource: EventSource | null = null

onMounted(async () => {
  const apiBase = config.public.apiBase

  const rideRes = await $fetch<{ id: string }>(`${apiBase}/api/rides/demo`)

  eventSource = new EventSource(`${apiBase}/api/telemetry/stream?ride_id=${rideRes.id}`)

  eventSource.onopen = () => {
    connectionStatus.value = 'connected'
  }

  eventSource.onmessage = (event) => {
    lastReading.value = JSON.parse(event.data) as TelemetryReading
  }

  eventSource.onerror = () => {
    connectionStatus.value = 'connecting'
  }
})

onBeforeUnmount(() => {
  eventSource?.close()
})
</script>
