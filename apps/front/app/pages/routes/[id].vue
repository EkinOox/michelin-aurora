<script setup lang="ts">
definePageMeta({ tabbar: false })


const route = useRoute()
const router = useRouter()

const routes = useRoutesState()
const r = computed(() => routes.value.find(x => x.id === route.params.id) ?? null)

// GPS geometry for the map
interface Pt { lat: number, lon: number }
const segments = ref<Pt[][]>([])
const geoLoading = ref(true)

onMounted(async () => {
  if (!r.value) { router.replace('/routes'); return }

  try {
    const id = route.params.id as string
    const query = `[out:json];relation(${id});out geom;`
    const res = await $fetch<{ elements: { members?: { geometry?: Pt[] }[] }[] }>(
      `https://overpass-api.de/api/interpreter?data=${encodeURIComponent(query)}`,
      { headers: { 'User-Agent': 'michelin-aurora/1.0' } },
    )
    const el = res.elements[0]
    if (el?.members) {
      segments.value = el.members
        .map(m => m.geometry ?? [])
        .filter(g => g.length > 1)
    }
  }
  catch { /* keep empty */ }
  finally { geoLoading.value = false }
})

const compatible = computed(() => (r.value?.match_score ?? 0) >= 85)

const TYPE_LABEL: Record<string, string> = { route: 'Route', gravel: 'Gravel', vtt: 'VTT', vae: 'VAE' }
const typeLabel = computed(() => TYPE_LABEL[r.value?.bike_type ?? ''] ?? r.value?.bike_type)
const LEVEL_LABEL: Record<string, string> = { beginner: 'Débutant', intermediate: 'Intermédiaire', advanced: 'Avancé', expert: 'Expert' }
const levelLabel = computed(() => LEVEL_LABEL[r.value?.difficulty ?? ''] ?? r.value?.difficulty)

function startRide() {
  router.push('/ride')
}
</script>

<template>
  <div v-if="r" class="screen">
    <div class="screen-scroll" style="padding-bottom: 120px">
      <div style="position: relative; height: 280px; overflow: hidden; background: #e8e0d8">
        <div v-if="geoLoading" style="position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; background: #e8e0d8">
          <div class="map-spinner" />
        </div>
        <ClientOnly v-else-if="segments.length">
          <RouteMap :segments="segments" style="position: absolute; inset: 0" />
        </ClientOnly>
        <div v-else style="position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; background: #e8e0d8">
          <div class="tiny" style="color: #999">Tracé non disponible</div>
        </div>
        <div class="pad safe-top" style="position: absolute; top: 0; left: 0; right: 0; padding-top: 58px; z-index: 1000">
          <button class="iconbtn iconbtn-dark" @click="router.back()"><Icon name="chevL" :size="20" /></button>
        </div>
        <div class="pad" style="position: absolute; bottom: 14px; left: 0; right: 0">
          <span v-if="r.tag" class="badge badge-yellow">{{ r.tag }}</span>
        </div>
      </div>

      <div class="pad" style="padding-top: 18px">
        <div class="eyebrow" style="color: var(--lime-600)">{{ typeLabel }} · {{ r.surface }}</div>
        <div class="h-lg" style="margin-top: 6px">{{ r.name }}</div>
        <div class="body" style="margin-top: 8px">{{ r.description }}</div>

        <div class="card" style="padding: 14px 4px; margin-top: 16px; display: flex">
          <div style="flex: 1; text-align: center">
            <div class="num" style="font-size: 15px; font-weight: 700">{{ r.distance_km }} km</div>
            <div class="tiny" style="margin-top: 2px">Distance</div>
          </div>
          <div style="flex: 1; text-align: center; border-left: 1px solid var(--line-2)">
            <div class="num" style="font-size: 15px; font-weight: 700">{{ r.elevation_gain_m != null ? '↑' + r.elevation_gain_m + ' m' : '—' }}</div>
            <div class="tiny" style="margin-top: 2px">Dénivelé</div>
          </div>
          <div style="flex: 1; text-align: center; border-left: 1px solid var(--line-2)">
            <div class="num" style="font-size: 15px; font-weight: 700">{{ r.duration_label ?? '—' }}</div>
            <div class="tiny" style="margin-top: 2px">Durée</div>
          </div>
          <div style="flex: 1; text-align: center; border-left: 1px solid var(--line-2)">
            <div class="num" style="font-size: 15px; font-weight: 700">{{ levelLabel }}</div>
            <div class="tiny" style="margin-top: 2px">Niveau</div>
          </div>
        </div>

        <div style="display: flex; gap: 12px; margin-top: 14px">
          <div class="card" style="flex: 1; padding: 14px; display: flex; flex-direction: column; align-items: center; gap: 8px">
            <Ring :value="r.safety_score / 100" :size="74" :thick="8" color="var(--blue)">
              <div style="text-align: center"><div class="num" style="font-size: 21px; font-weight: 800">{{ r.safety_score }}</div></div>
            </Ring>
            <div class="small" style="font-weight: 600; color: var(--ink-2)">Sécurité Michelin</div>
          </div>
          <div class="card" style="flex: 1; padding: 14px; display: flex; flex-direction: column; align-items: center; gap: 8px">
            <Ring :value="r.fun_score / 100" :size="74" :thick="8" color="var(--lime)">
              <div style="text-align: center"><div class="num" style="font-size: 21px; font-weight: 800">{{ r.fun_score }}</div></div>
            </Ring>
            <div class="small" style="font-weight: 600; color: var(--ink-2)">Indice plaisir</div>
          </div>
          <div class="card" style="flex: 1; padding: 14px; display: flex; flex-direction: column; align-items: center; gap: 8px">
            <Ring :value="r.match_score / 100" :size="74" :thick="8" color="var(--yellow)">
              <div style="text-align: center"><div class="num" style="font-size: 21px; font-weight: 800">{{ r.match_score }}</div></div>
            </Ring>
            <div class="small" style="font-weight: 600; color: var(--ink-2)">Compatibilité</div>
          </div>
        </div>

        <div class="card" :style="{ padding: '14px', marginTop: '14px', display: 'flex', gap: '12px', alignItems: 'flex-start', background: compatible ? 'var(--lime-soft)' : '#FBE7D6', borderColor: 'transparent' }">
          <div :style="{ width: '34px', height: '34px', borderRadius: '10px', flex: '0 0 auto', display: 'flex', alignItems: 'center', justifyContent: 'center', background: compatible ? 'var(--lime)' : 'var(--warn)' }">
            <Icon :name="compatible ? 'check' : 'shield'" :size="18" :color="compatible ? '#1c2400' : '#fff'" />
          </div>
          <div class="small" style="color: var(--ink-2)">
            <template v-if="compatible">Votre <b>Power Gravel</b> est parfaitement adapté à ce terrain {{ r.surface.toLowerCase() }}.</template>
            <template v-else>Terrain <b>{{ r.surface.toLowerCase() }}</b> exigeant pour vos pneus actuels. Michelin suggère un passage en <b>Wild Enduro</b>.</template>
          </div>
        </div>

        <NuxtLink to="/pressure" class="card" style="width: 100%; padding: 14px; margin-top: 12px; display: flex; align-items: center; gap: 12px; text-align: left">
          <Icon name="gauge" :size="24" color="var(--blue)" />
          <div style="flex: 1">
            <div class="small" style="font-weight: 700; color: var(--ink)">Pression recommandée</div>
            <div class="tiny">Av 2,6 bar · Ar 2,8 bar · ajustée météo</div>
          </div>
          <Icon name="chev" :size="18" color="var(--mute)" />
        </NuxtLink>
      </div>
    </div>
    <div class="pad glass" style="position: absolute; left: 0; right: 0; bottom: 0; padding-top: 14px; padding-bottom: 26px">
      <button class="btn btn-blue btn-block" @click="startRide">
        <Icon name="play" :size="18" /> Lancer la sortie
      </button>
    </div>
  </div>
</template>

<style scoped>
.map-spinner {
  width: 32px;
  height: 32px;
  border: 3px solid rgba(0, 0, 0, .12);
  border-top-color: #84cc16;
  border-radius: 50%;
  animation: spin .8s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg) } }
</style>
