<script setup lang="ts">
export interface RouteDto {
  id: string
  name: string
  bike_type: string
  distance_km: number | null
  elevation_gain_m: number | null
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
  <NuxtLink :to="`/routes/${route.id}`" class="route-row card" style="width: 100%; padding: 12px; display: flex; gap: 12px; align-items: center; margin-bottom: 10px; text-align: left">
    <div class="route-thumb mapbg" style="width: 58px; height: 58px; border-radius: 14px; flex: 0 0 auto; position: relative; overflow: hidden">
      <svg viewBox="0 0 58 58" style="position: absolute; inset: 0">
        <path d="M6 44 C18 30, 24 38, 34 22 S50 12, 52 14" fill="none" stroke="var(--lime)" stroke-width="2.4" stroke-linecap="round" />
        <circle cx="6" cy="44" r="3" fill="var(--yellow)" /><circle cx="52" cy="14" r="3" fill="var(--lime-600)" />
      </svg>
    </div>
    <div class="route-body" style="flex: 1; min-width: 0">
      <div class="row" style="gap: 7px">
        <span class="badge route-type-badge" :style="{ background: 'rgba(0,0,0,.06)', color: tagColor }">{{ typeLabel }}</span>
        <span class="route-match tiny" style="color: var(--lime-600); font-weight: 700">Match {{ route.match_score }}%</span>
      </div>
      <div class="h-sm" style="margin-top: 5px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis">{{ route.name }}</div>
      <div class="row" style="gap: 12px; margin-top: 3px; flex-wrap: nowrap">
        <span class="tiny num" style="white-space: nowrap">{{ route.distance_km != null ? route.distance_km + ' km' : '— km' }}</span>
        <span class="tiny num" style="white-space: nowrap">↑ {{ route.elevation_gain_m != null ? route.elevation_gain_m + ' m' : '—' }}</span>
        <span class="tiny" style="white-space: nowrap"><Icon name="shield" :size="11" color="var(--green)" style="vertical-align: -1px" /> {{ route.safety_score }}</span>
      </div>
    </div>
    <Icon name="chev" :size="18" color="var(--mute)" />
  </NuxtLink>
</template>

<style scoped>
@media (min-width: 900px) {
  .route-row {
    padding: 16px 20px;
    gap: 18px;
    margin-bottom: 12px;
  }

  .route-thumb {
    width: 72px;
    height: 72px;
    border-radius: 18px;
    flex-shrink: 0;
  }

  .route-name {
    font-size: 18px;
    margin-top: 7px;
    color: var(--ink);
  }

  .route-match {
    font-size: 12.5px;
  }

  .route-meta {
    margin-top: 5px;
    gap: 16px;
  }

  .route-meta .tiny,
  .route-meta .num {
    font-size: 12.5px;
    color: var(--ink-3);
  }
}
</style>
