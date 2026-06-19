<script setup lang="ts">
import { imageFor } from '~/data/images'

definePageMeta({ tabbar: true })

useSeoMeta({
  title: 'Boutique Michelin',
  description: 'Découvrez le catalogue de pneus Michelin — Power Road, Power Gravel, Wild Enduro. Trouvez le pneu parfait pour votre discipline et votre niveau.',
  ogTitle: 'Boutique pneus Michelin — Aurora',
  ogDescription: 'Catalogue complet de pneus Michelin pour vélos. Scorés et recommandés selon votre profil cycliste Aurora.',
  ogImage: '/icons/icon-512.png',
  twitterCard: 'summary',
})

interface TireDto {
  id: string
  name: string
  bike_type: string
  scores: Record<string, number> | null
  price_eur: number
  avg_km_lifetime: number
  image_key: string | null
  subtitle: string | null
  tag: string | null
  color_token: string | null
}

const router = useRouter()
const retailerSheet = useRetailerSheet()

const { data: tires } = await useApiFetch<TireDto[]>('/api/tires', { key: 'store-tires', default: () => [] })

function openRetailer(t?: TireDto) {
  retailerSheet.open(t ? { name: t.name } : undefined)
}
</script>

<template>
  <div class="screen">
    <div class="screen-scroll">
      <AppHeader title="Boutique" sub="Recommandations selon votre roulage." :on-back="() => router.push('/home')" />
      <div class="pad">
        <div class="card-lg rise" style="overflow: hidden; margin-bottom: 16px">
          <div class="mapbg" style="height: 120px; position: relative">
            <svg viewBox="0 0 360 120" style="position: absolute; inset: 0; width: 100%; height: 100%">
              <circle cx="180" cy="62" r="9" fill="var(--lime)" stroke="#fff" stroke-width="3" />
              <circle cx="180" cy="62" r="20" fill="none" stroke="var(--lime)" stroke-width="1.5" opacity=".4" />
            </svg>
          </div>
          <div style="padding: 16px">
            <div class="between">
              <div>
                <div class="small" style="font-weight: 700; color: var(--ink)">Cycles Auvergne · Revendeur agréé</div>
                <div class="tiny" style="margin-top: 2px">à 2,4 km · ouvert jusqu'à 19h</div>
              </div>
              <span class="badge badge-lime">Stock OK</span>
            </div>
            <button class="btn btn-blue btn-block" style="height: 46px; font-size: 14px; margin-top: 14px" @click="openRetailer()">
              <Icon name="loc" :size="16" /> Click & Collect urgence
            </button>
          </div>
        </div>

        <div class="rise d1">
          <div class="h-sm" style="margin-bottom: 10px">Pneus pour vous</div>
          <div v-for="s in tires" :key="s.id" class="card" style="padding: 12px; display: flex; gap: 12px; align-items: center; margin-bottom: 10px; cursor: pointer" @click="openRetailer(s)">
            <Photo :src="imageFor(s.image_key)" :alt="s.name" :radius="14" style="width: 62px; height: 62px; flex: 0 0 auto" />
            <div style="flex: 1">
              <span class="badge" :style="{ background: 'rgba(0,0,0,.04)', color: s.color_token ?? 'var(--ink)' }">{{ s.tag }}</span>
              <div class="h-sm" style="margin-top: 5px">{{ s.name }}</div>
              <div class="tiny" style="margin-top: 2px">{{ s.subtitle }}</div>
            </div>
            <div style="text-align: right">
              <div class="num" style="font-weight: 800">{{ s.price_eur }} €</div>
              <button class="btn btn-dark" style="height: 34px; font-size: 12px; margin-top: 8px; padding: 0 14px" @click.stop="openRetailer(s)">Acheter</button>
            </div>
          </div>
        </div>

        <div class="card-lg rise d2" style="padding: 18px; margin-top: 8px; background: linear-gradient(135deg,#FCE500 0%, #FFD400 100%); border-color: transparent">
          <div class="between">
            <span class="badge badge-ink">Bundle Performer</span>
            <Icon name="bolt" :size="22" color="#1a1a00" />
          </div>
          <div class="h-md" style="margin-top: 12px; color: #1a1a00">Pneus + montage + entrée Sunset Gravel</div>
          <div class="row" style="gap: 10px; margin-top: 14px; align-items: baseline">
            <span class="num" style="font-size: 26px; font-weight: 800; color: #1a1a00">118 €</span>
            <span class="num" style="font-size: 15px; color: #7a6f00; text-decoration: line-through">149 €</span>
            <span class="badge badge-ink" style="margin-left: auto">−21%</span>
          </div>
          <button class="btn btn-dark btn-block" style="margin-top: 16px; height: 48px" @click="openRetailer()">Débloquer avec mes points</button>
        </div>

        <div class="card rise d3" style="padding: 16px; margin-top: 16px">
          <div class="h-sm" style="margin-bottom: 12px">Coût raisonné</div>
          <div style="display: flex; gap: 8px">
            <div style="flex: 1; text-align: center; padding: 10px 4px; background: var(--surface-2); border-radius: 14px">
              <div class="num" style="font-size: 15px; font-weight: 800; color: var(--green)">0,012 €</div>
              <div class="tiny" style="margin-top: 3px">Coût / km</div>
            </div>
            <div style="flex: 1; text-align: center; padding: 10px 4px; background: var(--surface-2); border-radius: 14px">
              <div class="num" style="font-size: 15px; font-weight: 800; color: var(--blue)">6 200 km</div>
              <div class="tiny" style="margin-top: 3px">Durée vie</div>
            </div>
            <div style="flex: 1; text-align: center; padding: 10px 4px; background: var(--surface-2); border-radius: 14px">
              <div class="num" style="font-size: 15px; font-weight: 800; color: var(--lime-600)">+200 pts</div>
              <div class="tiny" style="margin-top: 3px">Recyclage</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
