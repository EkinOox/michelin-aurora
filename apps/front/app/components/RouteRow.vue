<script setup lang="ts">
export interface RouteDto {
  id: string
  name: string
  bike_type: string
  distance_km: number
  elevation_gain_m: number
  match_score: number
  safety_score: number
}

const props = defineProps<{ route: RouteDto }>()

const TYPE_LABEL: Record<string, string> = { route: 'Route', gravel: 'Gravel', vtt: 'VTT', vae: 'VAE', triathlon: 'Triathlon' }
const TAG_COLOR: Record<string, string> = { route: 'var(--blue)', gravel: 'var(--lime-600)', vtt: 'var(--ink)', vae: '#582C83' }

const typeLabel = computed(() => TYPE_LABEL[props.route.bike_type] ?? props.route.bike_type)
const tagColor = computed(() => TAG_COLOR[props.route.bike_type] ?? 'var(--ink)')
</script>

<template>
  <NuxtLink :to="`/routes/${route.id}`" class="card" style="width: 100%; padding: 12px; display: flex; gap: 12px; align-items: center; margin-bottom: 10px; text-align: left">
    <div class="mapbg" style="width: 58px; height: 58px; border-radius: 14px; flex: 0 0 auto; position: relative; overflow: hidden">
      <svg viewBox="0 0 58 58" style="position: absolute; inset: 0">
        <path d="M6 44 C18 30, 24 38, 34 22 S50 12, 52 14" fill="none" stroke="var(--lime)" stroke-width="2.4" stroke-linecap="round" />
        <circle cx="6" cy="44" r="3" fill="var(--yellow)" /><circle cx="52" cy="14" r="3" fill="var(--lime-600)" />
      </svg>
    </div>
    <div style="flex: 1; min-width: 0">
      <div class="row" style="gap: 7px">
        <span class="badge" :style="{ background: 'rgba(0,0,0,.04)', color: tagColor }">{{ typeLabel }}</span>
        <span class="tiny" style="color: var(--lime-600); font-weight: 700">Match {{ route.match_score }}%</span>
      </div>
      <div class="h-sm" style="margin-top: 5px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis">{{ route.name }}</div>
      <div class="row" style="gap: 12px; margin-top: 3px; flex-wrap: nowrap">
        <span class="tiny num" style="white-space: nowrap">{{ route.distance_km }} km</span>
        <span class="tiny num" style="white-space: nowrap">↑ {{ route.elevation_gain_m }} m</span>
        <span class="tiny" style="white-space: nowrap"><Icon name="shield" :size="11" color="var(--green)" style="vertical-align: -1px" /> {{ route.safety_score }}</span>
      </div>
    </div>
    <Icon name="chev" :size="18" color="var(--mute)" />
  </NuxtLink>
</template>
