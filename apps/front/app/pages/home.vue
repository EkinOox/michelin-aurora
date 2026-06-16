<script setup lang="ts">
import { imageFor } from '~/data/images'
import type { RouteDto } from '~/components/RouteRow.vue'

definePageMeta({ tabbar: true })

interface ProfileDto {
  name: string
  city: string
  rewards_level: string
  total_points: number
  profile: { bike_type: string } | null
}
interface TireDto {
  id: string
  name: string
  subtitle: string | null
  image_key: string | null
  avg_km_lifetime: number
}
interface RewardsDto { points: number, rank: string, next_at: number | null }

const apiBase = useApiBase()

const { data: profile } = await useFetch<ProfileDto>(() => `${apiBase}/api/profile`, { key: 'home-profile' })
const { data: tires } = await useFetch<TireDto[]>(() => `${apiBase}/api/tires`, { key: 'home-tires', default: () => [] })
const { data: rewards } = await useFetch<RewardsDto>(() => `${apiBase}/api/rewards`, { key: 'home-rewards' })
const { data: routes } = await useFetch<RouteDto[]>(() => `${apiBase}/api/routes`, { key: 'home-routes', default: () => [] })

const initials = computed(() => {
  const n = profile.value?.name ?? ''
  return n.split(' ').map(p => p[0]).join('').slice(0, 2).toUpperCase() || 'TG'
})
const firstName = computed(() => profile.value?.name?.split(' ')[0] ?? '')
const rankLabel = computed(() => (profile.value?.rewards_level ?? '').replace(/_/g, ' '))

const activeBike = computed(() => tires.value?.[0] ?? null)
const topRoutes = computed(() => (routes.value ?? []).slice(0, 2))

const rewardsProgress = computed(() => {
  if (!rewards.value || !rewards.value.next_at) return 100
  return Math.min(100, (rewards.value.points / rewards.value.next_at) * 100)
})
</script>

<template>
  <div class="screen">
    <div class="screen-scroll">
      <div class="pad safe-top rise" style="padding-top: 40px; padding-bottom: 6px">
        <div class="between">
          <div class="row" style="gap: 11px">
            <Avatar :name="initials" :size="42" />
            <div>
              <div class="tiny">Bonjour,</div>
              <div class="h-sm">{{ firstName }} · <span style="color: var(--lime-600)">{{ rankLabel }}</span></div>
            </div>
          </div>
          <div class="row" style="gap: 10px">
            <MichelinLogo :height="20" style="opacity: .92" />
            <NuxtLink to="/ride/alert" class="iconbtn" style="position: relative">
              <Icon name="bell" :size="20" />
              <span style="position: absolute; top: 9px; right: 10px; width: 8px; height: 8px; border-radius: 99px; background: var(--red); border: 2px solid var(--card)" />
            </NuxtLink>
          </div>
        </div>
      </div>

      <div class="pad rise d1">
        <div class="card-lg" style="overflow: hidden; position: relative">
          <div class="mapbg" style="position: absolute; inset: 0; opacity: .5" />
          <div style="position: relative; padding: 18px 18px 16px">
            <div class="between">
              <div class="row" style="gap: 7px">
                <Icon name="loc" :size="15" color="var(--ink-3)" />
                <span class="small" style="font-weight: 600">{{ profile?.city }}</span>
              </div>
              <span class="badge badge-lime"><span style="width: 6px; height: 6px; border-radius: 99px; background: var(--lime-600)" /> Connecté</span>
            </div>
            <div class="row" style="justify-content: space-between; align-items: flex-end; margin-top: 14px">
              <div>
                <div class="eyebrow">Vélo actif</div>
                <div class="h-md" style="margin-top: 4px">{{ activeBike?.name }}</div>
                <div class="small" style="margin-top: 3px"><Icon name="leaf" :size="13" color="var(--lime-600)" style="vertical-align: -2px" /> {{ activeBike?.subtitle }}</div>
              </div>
              <Photo :src="imageFor(activeBike?.image_key)" alt="Vélo actif" :radius="14" style="width: 84px; height: 64px; flex: 0 0 auto" />
            </div>
            <div class="divider" style="margin: 15px 0" />
            <div class="row" style="gap: 6px">
              <div style="flex: 1; min-width: 0">
                <div class="row" style="gap: 5px; color: var(--ink-3)"><Icon name="route" :size="14" /><span class="tiny">Durée vie</span></div>
                <div class="num" style="font-size: 20px; font-weight: 700; margin-top: 3px">{{ activeBike?.avg_km_lifetime }}<span style="font-size: 12px; color: var(--mute); margin-left: 2px">km</span></div>
              </div>
              <div style="flex: 1; min-width: 0">
                <div class="row" style="gap: 5px; color: var(--ink-3)"><Icon name="gauge" :size="14" color="var(--lime-600)" /><span class="tiny">Pression Av</span></div>
                <div class="num" style="font-size: 20px; font-weight: 700; margin-top: 3px">2,6<span style="font-size: 12px; color: var(--mute); margin-left: 2px">bar</span></div>
              </div>
              <div style="flex: 1; min-width: 0">
                <div class="row" style="gap: 5px; color: var(--ink-3)"><Icon name="recycle" :size="14" /><span class="tiny">Usure</span></div>
                <div class="num" style="font-size: 20px; font-weight: 700; margin-top: 3px">18<span style="font-size: 12px; color: var(--mute); margin-left: 2px">%</span></div>
              </div>
            </div>
            <NuxtLink to="/ride" class="btn btn-blue btn-block" style="margin-top: 16px; height: 50px">
              <Icon name="play" :size="17" /> Démarrer une sortie
            </NuxtLink>
          </div>
        </div>
      </div>

      <div class="pad rise d2" style="margin-top: 14px; display: grid; grid-template-columns: 1fr 1fr; gap: 12px">
        <NuxtLink to="/pressure" class="card" style="padding: 16px; text-align: left; display: flex; flex-direction: column; gap: 8px">
          <div class="between">
            <Icon name="gauge" :size="20" color="var(--blue)" />
            <Icon name="chev" :size="16" color="var(--mute)" />
          </div>
          <div>
            <div class="eyebrow">Pression idéale</div>
            <div class="num" style="font-size: 24px; font-weight: 800; margin-top: 2px">2,6<span style="font-size: 12px; color: var(--mute)"> bar</span></div>
          </div>
          <div class="row" style="gap: 5px">
            <span class="badge badge-blue"><Icon name="cloud" :size="12" /> Pluie −10%</span>
          </div>
        </NuxtLink>

        <NuxtLink to="/rewards" class="card" style="padding: 16px; text-align: left; display: flex; flex-direction: column; gap: 8px">
          <div class="between">
            <Icon name="gift" :size="20" color="var(--lime-600)" />
            <Icon name="chev" :size="16" color="var(--mute)" />
          </div>
          <div>
            <div class="eyebrow">Ride Points</div>
            <div class="num" style="font-size: 24px; font-weight: 800; margin-top: 2px">{{ rewards?.points?.toLocaleString('fr') }}</div>
          </div>
          <div class="track"><i :style="{ width: `${rewardsProgress}%` }" /></div>
        </NuxtLink>
      </div>

      <div class="pad rise d3" style="margin-top: 14px">
        <div class="between" style="margin-bottom: 10px">
          <span class="h-sm">Explorer</span>
        </div>
        <div style="display: grid; grid-template-columns: repeat(4,1fr); gap: 10px">
          <NuxtLink to="/routes" class="card" style="padding: 14px 12px; display: flex; flex-direction: column; align-items: flex-start; gap: 10px">
            <div style="width: 38px; height: 38px; border-radius: 11px; background: var(--lime); display: flex; align-items: center; justify-content: center">
              <Icon name="route" :size="20" color="#fff" />
            </div>
            <span class="small" style="color: var(--ink); font-weight: 600">Routes</span>
          </NuxtLink>
          <NuxtLink to="/events" class="card" style="padding: 14px 12px; display: flex; flex-direction: column; align-items: flex-start; gap: 10px">
            <div style="width: 38px; height: 38px; border-radius: 11px; background: var(--blue); display: flex; align-items: center; justify-content: center">
              <Icon name="cal" :size="20" color="#fff" />
            </div>
            <span class="small" style="color: var(--ink); font-weight: 600">Events</span>
          </NuxtLink>
          <NuxtLink to="/community" class="card" style="padding: 14px 12px; display: flex; flex-direction: column; align-items: flex-start; gap: 10px">
            <div style="width: 38px; height: 38px; border-radius: 11px; background: #582C83; display: flex; align-items: center; justify-content: center">
              <Icon name="users" :size="20" color="#fff" />
            </div>
            <span class="small" style="color: var(--ink); font-weight: 600">Riders</span>
          </NuxtLink>
          <NuxtLink to="/store" class="card" style="padding: 14px 12px; display: flex; flex-direction: column; align-items: flex-start; gap: 10px">
            <div style="width: 38px; height: 38px; border-radius: 11px; background: var(--ink); display: flex; align-items: center; justify-content: center">
              <Icon name="cart" :size="20" color="#fff" />
            </div>
            <span class="small" style="color: var(--ink); font-weight: 600">Store</span>
          </NuxtLink>
        </div>
      </div>

      <div class="pad rise d4" style="margin-top: 18px">
        <div class="between" style="margin-bottom: 10px">
          <span class="h-sm">Actualités Michelin</span>
        </div>
        <NewsRail />
      </div>

      <div class="pad rise d5" style="margin-top: 18px">
        <div class="between" style="margin-bottom: 10px">
          <span class="h-sm">Routes recommandées</span>
          <NuxtLink to="/routes" class="small" style="color: var(--lime-600); font-weight: 700">Voir tout</NuxtLink>
        </div>
        <RouteRow v-for="r in topRoutes" :key="r.id" :route="r" />
      </div>
    </div>
  </div>
</template>
