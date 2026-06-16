<script setup lang="ts">
definePageMeta({ tabbar: true })

interface RiderDto {
  name: string
  initials: string
  rank: string
  km_this_month: number
  match_percent: number
  color_hex: string
}

const RANK_LABEL: Record<string, string> = {
  explorer: 'Explorer',
  rider: 'Rider',
  performer: 'Performer',
  elite_cyclist: 'Elite Cyclist',
  michelin_ambassador: 'Michelin Ambassador',
}

const apiBase = useApiBase()
const router = useRouter()
const toast = useToast()
const retailerSheet = useRetailerSheet()

const { data: riders } = await useFetch<RiderDto[]>(() => `${apiBase}/api/community/riders`, { key: 'community-riders', default: () => [] })

function viewProfile(name: string) {
  toast.show(`Profil de ${name} · bientôt`, 'user')
}
function suggestTire() {
  retailerSheet.open({ name: 'Michelin Power Gravel TLR' })
}
</script>

<template>
  <div class="screen">
    <div class="screen-scroll">
      <AppHeader title="Riders Insights" sub="Les riders comme vous roulent ici." :on-back="() => router.push('/home')" />
      <div class="pad">
        <div class="card-lg rise" style="padding: 18px; background: linear-gradient(135deg,#27509B 0%, #1b3a73 100%); border-color: transparent; color: #fff">
          <div class="eyebrow" style="color: rgba(255,255,255,.65)">Votre cluster · Gravel Expert</div>
          <div class="h-md" style="color: #fff; margin-top: 8px">Top 18% en endurance</div>
          <div style="display: flex; gap: 8px; margin-top: 16px">
            <div style="flex: 1; background: rgba(255,255,255,.1); border-radius: 14px; padding: 12px 10px">
              <div class="num" style="font-size: 20px; font-weight: 800; color: var(--yellow)">268</div>
              <div class="tiny" style="color: rgba(255,255,255,.7)">Vous · km/sem</div>
            </div>
            <div style="flex: 1; background: rgba(255,255,255,.1); border-radius: 14px; padding: 12px 10px">
              <div class="num" style="font-size: 20px; font-weight: 800; color: #fff">214</div>
              <div class="tiny" style="color: rgba(255,255,255,.7)">Cluster · km/sem</div>
            </div>
            <div style="flex: 1; background: rgba(255,255,255,.1); border-radius: 14px; padding: 12px 10px">
              <div class="num" style="font-size: 20px; font-weight: 800; color: var(--lime)">+25%</div>
              <div class="tiny" style="color: rgba(255,255,255,.7)">Écart · vs moy.</div>
            </div>
          </div>
        </div>

        <div class="rise d1" style="margin-top: 18px">
          <div class="h-sm" style="margin-bottom: 10px">Riders similaires</div>
          <button v-for="c in riders" :key="c.name" class="card" style="width: 100%; padding: 12px; display: flex; gap: 12px; align-items: center; margin-bottom: 10px; text-align: left" @click="viewProfile(c.name)">
            <Avatar :name="c.initials" :size="44" :bg="c.color_hex" />
            <div style="flex: 1">
              <div class="small" style="font-weight: 700; color: var(--ink)">{{ c.name }}</div>
              <div class="tiny">{{ RANK_LABEL[c.rank] ?? c.rank }} · {{ c.km_this_month }} km ce mois</div>
            </div>
            <div style="text-align: right">
              <div class="num" style="font-weight: 800; color: var(--lime-600)">{{ c.match_percent }}%</div>
              <div class="tiny">affinité</div>
            </div>
          </button>
        </div>

        <button class="card rise d2" style="width: 100%; padding: 16px; margin-top: 8px; display: flex; gap: 12px; align-items: flex-start; background: var(--surface-2); border-color: transparent; text-align: left" @click="suggestTire">
          <Icon name="target" :size="22" color="var(--blue)" />
          <div class="small">Les riders de votre cluster roulent <b>+40% sur chemins blancs</b>. Michelin recommande la gamme <b>Power Gravel TLR</b> pour ce profil.</div>
          <Icon name="chev" :size="16" color="var(--mute)" style="flex: 0 0 auto; margin-top: 2px" />
        </button>
      </div>
    </div>
  </div>
</template>
