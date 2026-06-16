<script setup lang="ts">
import type { RouteDto } from '~/components/RouteRow.vue'

definePageMeta({ tabbar: true })

const { data: routes } = await useApiFetch<RouteDto[]>('/api/routes', { key: 'routes-list', default: () => [] })
const { data: profile } = await useApiFetch<{ profile: { bike_type: string, rider_level: string } | null }>('/api/profile', { key: 'routes-profile' })

const TYPE_LABEL: Record<string, string> = { route: 'Route', gravel: 'Gravel', vtt: 'VTT', vae: 'VAE' }
const LEVEL_LABEL: Record<string, string> = { beginner: 'Débutant', intermediate: 'Intermédiaire', advanced: 'Avancé', expert: 'Expert' }

const types = ['Tous', 'Route', 'Gravel', 'VTT', 'VAE']
const filter = ref('Tous')
const list = computed(() => {
  if (filter.value === 'Tous') return routes.value ?? []
  return (routes.value ?? []).filter(r => TYPE_LABEL[r.bike_type] === filter.value)
})

const bikeLabel = computed(() => TYPE_LABEL[profile.value?.profile?.bike_type ?? ''] ?? '—')
const levelLabel = computed(() => LEVEL_LABEL[profile.value?.profile?.rider_level ?? ''] ?? '—')
</script>

<template>
  <div class="screen">
    <div class="screen-scroll">
      <div style="position: relative; height: 300px; background: linear-gradient(180deg,#10182e 0%, #182447 60%, #1d2c52 100%)">
        <div style="position: absolute; inset: 0">
          <GlobeScene />
        </div>
        <div class="pad safe-top" style="position: absolute; top: 0; left: 0; right: 0; padding-top: 58px">
          <div class="between">
            <NuxtLink to="/home" class="iconbtn iconbtn-dark"><Icon name="chevL" :size="20" /></NuxtLink>
            <span class="badge" style="background: rgba(255,255,255,.14); color: #fff"><Icon name="layers" :size="12" /> IA Michelin</span>
          </div>
        </div>
        <div class="pad" style="position: absolute; bottom: 18px; left: 0; right: 0">
          <div class="eyebrow" style="color: var(--yellow)">Curated Routes 2.0</div>
          <div class="h-lg" style="color: #fff; margin-top: 6px">Parcours conçus<br>pour votre setup.</div>
        </div>
      </div>

      <div class="pad" style="margin-top: -2px; padding-top: 16px">
        <div class="card" style="padding: 14px; display: flex; align-items: center; gap: 8px; margin-bottom: 16px">
          <div style="flex: 1; text-align: center">
            <div class="tiny">Vélo</div>
            <div class="small" style="font-weight: 700; color: var(--ink)">{{ bikeLabel }}</div>
          </div>
          <Icon name="plus" :size="14" color="var(--mute)" />
          <div style="flex: 1; text-align: center">
            <div class="tiny">Pneu</div>
            <div class="small" style="font-weight: 700; color: var(--ink)">Power Gravel</div>
          </div>
          <Icon name="plus" :size="14" color="var(--mute)" />
          <div style="flex: 1; text-align: center">
            <div class="tiny">Niveau</div>
            <div class="small" style="font-weight: 700; color: var(--ink)">{{ levelLabel }}</div>
          </div>
        </div>

        <div style="display: flex; gap: 8px; overflow-x: auto; padding: 0 0 14px">
          <button v-for="t in types" :key="t" :class="['chip', filter === t ? 'is-active' : '']" @click="filter = t">{{ t }}</button>
        </div>

        <RouteRow v-for="r in list" :key="r.id" :route="r" />

        <div class="card" style="padding: 16px; margin-top: 6px; display: flex; gap: 12px; align-items: center; background: var(--surface-2); border-color: transparent">
          <Icon name="star" :size="22" color="var(--yellow)" />
          <div class="small">L'<b>indice de plaisir Michelin</b> combine fun, sécurité et performance pour noter chaque parcours selon votre profil.</div>
        </div>
      </div>
    </div>
  </div>
</template>
