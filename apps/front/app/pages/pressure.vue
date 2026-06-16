<script setup lang="ts">
definePageMeta({ tabbar: false })

interface PressureDto { rain: boolean, front_bar: number, rear_bar: number }

const apiBase = useApiBase()
const router = useRouter()
const rain = ref(true)

const { data } = await useFetch<PressureDto>(() => `${apiBase}/api/pressure`, { key: 'pressure', query: { rain: rain.value ? 1 : 0 }, watch: [rain] })

const reco = computed(() => data.value?.rear_bar ?? 2.9)
const front = computed(() => data.value?.front_bar ?? 2.7)
const val = computed(() => (reco.value - 1.5) / (4.5 - 1.5))
const gaugeColor = computed(() => (rain.value ? 0x27509B : 0x84BD00))

function fmt(n: number) {
  return n.toFixed(1).replace('.', ',')
}

function toggleRain() {
  rain.value = !rain.value
}
</script>

<template>
  <div class="screen">
    <AppHeader title="Pressure Guide" sub="Pression idéale au dixième de bar, en temps réel." :on-back="() => router.back()" />
    <div class="screen-scroll pad" style="padding-top: 4px">
      <div class="card-lg" style="position: relative; height: 280px; overflow: hidden; margin-bottom: 16px">
        <div style="position: absolute; inset: 0">
          <GaugeScene :value="val" :color="gaugeColor" />
        </div>
        <div style="position: absolute; left: 0; right: 0; bottom: 34px; text-align: center; pointer-events: none">
          <div class="num" style="font-size: 48px; font-weight: 800; letter-spacing: -.03em">
            {{ fmt(reco) }}<span style="font-size: 18px; color: var(--mute); margin-left: 4px">bar</span>
          </div>
          <div class="eyebrow" :style="{ marginTop: '2px', color: rain ? 'var(--blue)' : 'var(--lime-600)' }">
            {{ rain ? 'Mode pluie · −10%' : 'Conditions sèches' }}
          </div>
        </div>
        <div style="position: absolute; top: 14px; left: 14px">
          <span class="badge badge-live"><span class="ld" /> Live</span>
        </div>
      </div>

      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 16px">
        <div class="card" style="padding: 16px">
          <div class="tiny">Avant</div>
          <div class="num" style="font-size: 26px; font-weight: 800; margin-top: 4px">{{ fmt(front) }}<span style="font-size: 12px; color: var(--mute)"> bar</span></div>
          <div class="track" style="margin-top: 10px"><i :style="{ width: `${(front / 4.5) * 100}%`, background: rain ? 'var(--blue)' : 'var(--lime)' }" /></div>
        </div>
        <div class="card" style="padding: 16px">
          <div class="tiny">Arrière</div>
          <div class="num" style="font-size: 26px; font-weight: 800; margin-top: 4px">{{ fmt(reco) }}<span style="font-size: 12px; color: var(--mute)"> bar</span></div>
          <div class="track" style="margin-top: 10px"><i :style="{ width: `${(reco / 4.5) * 100}%`, background: rain ? 'var(--blue)' : 'var(--lime)' }" /></div>
        </div>
      </div>

      <div class="card" style="padding: 16px; margin-bottom: 14px">
        <div class="between" style="margin-bottom: 14px">
          <span class="h-sm">Environnement</span>
          <span class="badge badge-blue"><Icon name="loc" :size="12" /> Clermont-Fd</span>
        </div>
        <div style="display: flex; gap: 6px">
          <div style="flex: 1; text-align: center">
            <Icon name="thermo" :size="20" color="var(--ink-3)" />
            <div class="num" style="font-size: 16px; font-weight: 700; margin-top: 6px">14°</div>
            <div class="tiny">Temp.</div>
          </div>
          <div style="flex: 1; text-align: center">
            <Icon name="drop" :size="20" color="var(--ink-3)" />
            <div class="num" style="font-size: 16px; font-weight: 700; margin-top: 6px">82%</div>
            <div class="tiny">Humidité</div>
          </div>
          <div style="flex: 1; text-align: center">
            <Icon name="wind" :size="20" color="var(--ink-3)" />
            <div class="num" style="font-size: 16px; font-weight: 700; margin-top: 6px">18 km/h</div>
            <div class="tiny">Vent</div>
          </div>
        </div>
        <div class="divider" style="margin: 14px 0" />
        <button class="between" style="width: 100%" @click="toggleRain">
          <div class="row" style="gap: 10px">
            <Icon :name="rain ? 'cloud' : 'sun'" :size="20" :color="rain ? 'var(--blue)' : 'var(--yellow)'" />
            <span class="small" style="font-weight: 600; color: var(--ink)">{{ rain ? 'Pluie détectée' : 'Temps sec' }}</span>
          </div>
          <div :style="{ width: '46px', height: '27px', borderRadius: '99px', background: rain ? 'var(--blue)' : 'var(--line)', position: 'relative', transition: 'background .2s' }">
            <div :style="{ position: 'absolute', top: '3px', left: rain ? '22px' : '3px', width: '21px', height: '21px', borderRadius: '99px', background: '#fff', transition: 'left .2s', boxShadow: '0 1px 3px rgba(0,0,0,.2)' }" />
          </div>
        </button>
      </div>

      <div class="card" style="padding: 14px; display: flex; gap: 12px; align-items: flex-start; background: var(--surface-2); border-color: transparent">
        <Icon name="bolt" :size="20" color="var(--yellow)" />
        <div class="small">L'algorithme Michelin croise <b>poids, profil, humidité et terrain</b>. En cas de pluie, la pression baisse de 10% pour maximiser la surface de contact et la sécurité.</div>
      </div>
    </div>
    <div class="pad glass" style="position: absolute; left: 0; right: 0; bottom: 0; padding-top: 14px; padding-bottom: 26px">
      <button class="btn btn-blue btn-block" @click="router.back()">
        <Icon name="check" :size="18" /> Appliquer ces pressions
      </button>
    </div>
  </div>
</template>
