<script setup lang="ts">
definePageMeta({ tabbar: true })

interface CatalogItem { id: string, cost: number, title: string, sub: string, icon: string }
interface RewardCode { id: string, code: string, discount_eur: number, used: boolean, generated_at: string }
interface RewardsDto { points: number, rank: string, next_at: number | null, catalog: CatalogItem[], codes: RewardCode[] }

const RANKS = [
  { key: 'explorer', label: 'Explorer', min: 0, color: 'var(--ink-3)' },
  { key: 'rider', label: 'Rider', min: 1000, color: 'var(--blue)' },
  { key: 'performer', label: 'Performer', min: 2500, color: 'var(--lime-600)' },
  { key: 'elite_cyclist', label: 'Elite Cyclist', min: 4000, color: 'var(--yellow)' },
  { key: 'michelin_ambassador', label: 'Michelin Ambassador', min: 8000, color: 'var(--red)' },
]

const apiBase = useApiBase()
const toast = useToast()
const router = useRouter()

const { data: rewards, refresh } = await useFetch<RewardsDto>(() => `${apiBase}/api/rewards`, { key: 'rewards' })

const curIdx = computed(() => Math.max(0, RANKS.findIndex(r => r.key === rewards.value?.rank)))
const next = computed(() => RANKS[curIdx.value + 1] ?? RANKS[RANKS.length - 1])
const prog = computed(() => {
  if (!rewards.value) return 0
  const cur = RANKS[curIdx.value]
  const span = next.value.min - cur.min
  if (span <= 0) return 1
  return Math.min(1, Math.max(0, (rewards.value.points - cur.min) / span))
})
const rankLabel = computed(() => RANKS.find(r => r.key === rewards.value?.rank)?.label ?? rewards.value?.rank)
const ptsToNext = computed(() => Math.max(0, (rewards.value?.next_at ?? 0) - (rewards.value?.points ?? 0)))

const MULTIPLIERS = [
  { icon: 'cloud', t: 'Météo difficile', s: 'Pluie · froid', m: '×1,3', c: 'var(--blue)' },
  { icon: 'mtn', t: 'Terrain technique', s: 'Single & gravel', m: '×1,2', c: 'var(--ink)' },
  { icon: 'route', t: 'Endurance +100 km', s: 'Sortie longue', m: '×1,5', c: 'var(--lime-600)' },
]

const redeeming = ref<string | null>(null)
async function redeem(item: CatalogItem) {
  if ((rewards.value?.points ?? 0) < item.cost) return
  redeeming.value = item.id
  try {
    await $fetch(`${apiBase}/api/rewards/redeem/${item.id}`, { method: 'POST' })
    await refresh()
    toast.show(`Récompense échangée · ${item.title}`, 'gift')
  } catch {
    toast.show('Échange impossible', 'shield')
  } finally {
    redeeming.value = null
  }
}
</script>

<template>
  <div v-if="rewards" class="screen">
    <div class="screen-scroll">
      <AppHeader title="Ride Rewards" sub="Chaque kilomètre génère de la valeur." :on-back="() => router.push('/home')" />
      <div class="pad">
        <div class="card-lg rise" style="padding: 20px; background: linear-gradient(135deg,#15171A 0%, #23262b 100%); border-color: transparent; color: #fff">
          <div class="between">
            <div>
              <div class="eyebrow" style="color: rgba(255,255,255,.6)">Solde Ride Points</div>
              <div class="num" style="font-size: 42px; font-weight: 800; margin-top: 4px">{{ rewards.points.toLocaleString('fr') }}</div>
            </div>
            <div style="width: 54px; height: 54px; border-radius: 16px; background: var(--yellow); display: flex; align-items: center; justify-content: center">
              <Icon name="trophy" :size="28" color="#1a1a00" />
            </div>
          </div>
          <div class="between" style="margin-top: 18px; margin-bottom: 7px">
            <span class="small" style="color: #fff; font-weight: 700">{{ rankLabel }}</span>
            <span class="tiny" style="color: rgba(255,255,255,.6)">{{ ptsToNext }} pts → {{ next.label }}</span>
          </div>
          <div class="track" style="background: rgba(255,255,255,.14)"><i :style="{ width: `${prog * 100}%`, background: 'var(--yellow)' }" /></div>
        </div>

        <div class="rise d1" style="margin-top: 18px">
          <div class="eyebrow" style="margin-bottom: 10px">Rangs</div>
          <div style="display: flex; gap: 8px; overflow-x: auto; padding-bottom: 4px">
            <div v-for="(r, i) in RANKS" :key="r.key" style="flex: 0 0 auto; text-align: center" :style="{ opacity: i <= curIdx ? 1 : 0.5 }">
              <div style="width: 52px; height: 52px; border-radius: 50%; display: flex; align-items: center; justify-content: center" :style="{ background: i <= curIdx ? r.color : 'var(--surface-2)', border: i === curIdx ? '3px solid var(--ink)' : 'none' }">
                <Icon :name="i === 4 ? 'crown' : i === curIdx ? 'medal' : 'star'" :size="22" :color="i <= curIdx ? '#fff' : 'var(--mute)'" />
              </div>
              <div class="tiny" style="margin-top: 6px; width: 64px; font-weight: 600" :style="{ color: i === curIdx ? 'var(--ink)' : 'var(--mute)' }">{{ r.label }}</div>
            </div>
          </div>
        </div>

        <div class="card rise d2" style="padding: 16px; margin-top: 18px">
          <div class="h-sm" style="margin-bottom: 12px">Multiplicateurs actifs</div>
          <div v-for="(m, i) in MULTIPLIERS" :key="m.t" class="row" style="gap: 12px; padding: 9px 0" :style="{ borderTop: i ? '1px solid var(--line-2)' : 'none' }">
            <div style="width: 36px; height: 36px; border-radius: 10px; background: var(--surface-2); display: flex; align-items: center; justify-content: center; flex: 0 0 auto">
              <Icon :name="m.icon" :size="18" :color="m.c" />
            </div>
            <div style="flex: 1">
              <div class="small" style="font-weight: 700; color: var(--ink)">{{ m.t }}</div>
              <div class="tiny">{{ m.s }}</div>
            </div>
            <span class="num" style="font-weight: 800" :style="{ color: m.c }">{{ m.m }}</span>
          </div>
        </div>

        <div class="rise d3" style="margin-top: 18px">
          <div class="between" style="margin-bottom: 10px">
            <span class="h-sm">Boutique Rewards</span>
            <span class="badge badge-lime"><Icon name="recycle" :size="12" /> Bonus durable</span>
          </div>
          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px">
            <div v-for="rw in rewards.catalog" :key="rw.id" class="card" style="padding: 14px" :style="{ opacity: rewards.points >= rw.cost ? 1 : 0.6 }">
              <div style="width: 38px; height: 38px; border-radius: 11px; display: flex; align-items: center; justify-content: center" :style="{ background: rewards.points >= rw.cost ? 'var(--lime-soft)' : 'var(--surface-2)' }">
                <Icon :name="rw.icon" :size="20" :color="rewards.points >= rw.cost ? 'var(--lime-600)' : 'var(--mute)'" />
              </div>
              <div class="small" style="font-weight: 700; color: var(--ink); margin-top: 10px">{{ rw.title }}</div>
              <div class="tiny" style="margin-top: 2px">{{ rw.sub }}</div>
              <button :class="['btn', rewards.points >= rw.cost ? 'btn-dark' : 'btn-ghost']" style="height: 38px; font-size: 13px; margin-top: 12px; width: 100%" :disabled="rewards.points < rw.cost || redeeming === rw.id" @click="redeem(rw)">
                {{ rw.cost.toLocaleString('fr') }} pts
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
