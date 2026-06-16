<script setup lang="ts">
definePageMeta({ tabbar: false })

const router = useRouter()
const activeRide = useActiveRide()
const retailerSheet = useRetailerSheet()

const p = ref(2.8)
const pct = computed(() => Math.max(0, (p.value - 0.5) / (2.8 - 0.5)))

function fmt(n: number) {
  return n.toFixed(1).replace('.', ',')
}

let dropTimer: ReturnType<typeof setInterval> | null = null
let posted = false

async function postReading(rear: number, alert: boolean) {
  if (!activeRide.value) return
  await $apiFetch('/api/telemetry', {
    method: 'POST',
    body: {
      ride_id: activeRide.value,
      pressure_front_bar: 2.6,
      pressure_rear_bar: rear,
      speed_kmh: 0,
      alert_triggered: alert,
    },
  }).catch(() => {})
}

onMounted(() => {
  postReading(p.value, true)

  dropTimer = setInterval(() => {
    if (p.value > 0.7) {
      p.value = +(p.value - 0.14).toFixed(2)
      if (p.value <= 0.7 && !posted) {
        posted = true
        postReading(p.value, true)
      }
    }
  }, 120)

  if (navigator.vibrate) navigator.vibrate([0, 200, 100, 200, 100, 400])
})

onBeforeUnmount(() => {
  if (dropTimer) clearInterval(dropTimer)
})

function safeStop() {
  activeRide.value = null
  router.push('/home')
}

function openRetailer() {
  retailerSheet.open({ name: 'Réparation · revendeur le plus proche' })
}
</script>

<template>
  <div class="screen alert-screen" style="background: radial-gradient(120% 80% at 50% 0%, #E2122F 0%, #B00C26 55%, #7A0A1B 100%); overflow: hidden">
    <div class="alert-flash" />
    <div class="screen-scroll" style="position: relative; display: flex; flex-direction: column; padding-bottom: 34px">
      <div class="pad safe-top" style="padding-top: 58px">
        <div class="between">
          <span class="badge" style="background: rgba(0,0,0,.25); color: #fff">
            <span class="ld" style="width: 6px; height: 6px; border-radius: 99px; background: #fff; display: inline-block; animation: pulse 1s infinite" /> Détection IoT
          </span>
          <NuxtLink to="/home" class="iconbtn" style="background: rgba(0,0,0,.22); color: #fff; border-color: transparent"><Icon name="x" :size="20" /></NuxtLink>
        </div>
      </div>

      <div class="pad" style="text-align: center; margin-top: 30px">
        <div class="alert-ring" style="margin: 0 auto 26px">
          <Icon name="shield" :size="56" color="#fff" />
        </div>
        <div class="eyebrow" style="color: rgba(255,255,255,.85)">Alerte Rouge · Sécurité active</div>
        <div style="font-size: 38px; font-weight: 800; color: #fff; letter-spacing: -.02em; margin-top: 10px; line-height: 1.04">
          Crevaison<br>détectée
        </div>
        <div class="body" style="color: rgba(255,255,255,.88); margin-top: 12px; max-width: 280px; margin-inline: auto">
          Chute de pression rapide sur la <b>roue arrière</b>. Ralentissez et arrêtez-vous en sécurité.
        </div>
      </div>

      <div class="pad" style="margin-top: 26px">
        <div style="background: rgba(0,0,0,.22); border-radius: var(--r-md); padding: 18px">
          <div class="between" style="margin-bottom: 12px">
            <span class="small" style="color: #fff; font-weight: 700">Pression roue arrière</span>
            <span class="num" style="font-size: 22px; font-weight: 800; color: #fff">{{ fmt(p) }} bar</span>
          </div>
          <div class="track" style="background: rgba(0,0,0,.3); height: 10px">
            <i :style="{ width: `${pct * 100}%`, background: '#fff', transition: 'width .12s linear' }" />
          </div>
          <div class="between" style="margin-top: 8px">
            <span class="tiny" style="color: rgba(255,255,255,.7)">−2,1 bar en 4 s</span>
            <span class="tiny" style="color: rgba(255,255,255,.7)">Seuil critique 0,8 bar</span>
          </div>
        </div>
      </div>

      <div class="pad" style="margin-top: auto; padding-top: 26px; display: flex; flex-direction: column; gap: 11px">
        <button class="btn btn-block" style="background: #fff; color: var(--red)" @click="safeStop">
          <Icon name="check" :size="18" /> Je me suis arrêté en sécurité
        </button>
        <button class="btn btn-block" style="background: rgba(0,0,0,.22); color: #fff" @click="openRetailer">
          <Icon name="loc" :size="18" /> Revendeur Michelin le plus proche
        </button>
      </div>
    </div>
  </div>
</template>
