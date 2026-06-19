<script setup lang="ts">
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
  weight_g: number | null
  diameter_label: string | null
}

const FILTERS = [
  { key: 'all',    label: 'Tous',   icon: 'bike'  },
  { key: 'route',  label: 'Route',  icon: 'route' },
  { key: 'gravel', label: 'Gravel', icon: 'mtn'   },
  { key: 'vtt',    label: 'VTT',    icon: 'mtn'   },
  { key: 'vae',    label: 'Ville',  icon: 'bolt'  },
] as const

const BIKE_ICON: Record<string, string> = {
  route: 'route', gravel: 'mtn', vtt: 'mtn', vae: 'bolt',
}

const PAGE_SIZE = 12

const router = useRouter()
const retailerSheet = useRetailerSheet()

const { data: tires } = await useApiFetch<TireDto[]>('/api/tires', { key: 'store-tires', default: () => [] })

const activeFilter = ref<string>('all')
const page = ref(1)

const filteredTires = computed(() =>
  activeFilter.value === 'all'
    ? (tires.value ?? [])
    : (tires.value ?? []).filter(t => t.bike_type === activeFilter.value),
)

const totalPages = computed(() => Math.ceil(filteredTires.value.length / PAGE_SIZE))

const pageTires = computed(() => {
  const start = (page.value - 1) * PAGE_SIZE
  return filteredTires.value.slice(start, start + PAGE_SIZE)
})

function setFilter(key: string) {
  activeFilter.value = key
  page.value = 1
}

function openRetailer(t?: TireDto) {
  retailerSheet.open(t
    ? { name: t.name, color_token: t.color_token, bike_type: t.bike_type }
    : undefined,
  )
}

function countFor(key: string) {
  if (key === 'all') return tires.value?.length ?? 0
  return tires.value?.filter(t => t.bike_type === key).length ?? 0
}
</script>

<template>
  <div class="screen">
    <div class="screen-scroll">
      <AppHeader title="Boutique" sub="Catalogue Michelin 2026." :on-back="() => router.push('/home')" />
      <div class="pad">



        <!-- ── Filter pills ───────────────────────────────── -->
        <div class="filter-row rise d1">
          <button
            v-for="f in FILTERS"
            :key="f.key"
            class="chip"
            :class="{ 'is-active': activeFilter === f.key }"
            @click="setFilter(f.key)"
          >
            <Icon :name="f.icon" :size="14" :color="activeFilter === f.key ? '#fff' : 'currentColor'" />
            {{ f.label }}
            <span class="pill-count" :class="{ 'pill-count-active': activeFilter === f.key }">
              {{ countFor(f.key) }}
            </span>
          </button>
        </div>

        <!-- ── Tire list ───────────────────────────────────── -->
        <div class="rise d2">
          <div class="section-hd">
            <span class="eyebrow">{{ activeFilter === 'all' ? 'Tous les pneus' : FILTERS.find(f => f.key === activeFilter)?.label }}</span>
            <span class="tiny" style="color: var(--mute)">
              {{ filteredTires.length }} produit{{ filteredTires.length > 1 ? 's' : '' }}
            </span>
          </div>

          <div
            v-for="s in pageTires"
            :key="s.id"
            class="card tire-card"
            @click="openRetailer(s)"
          >
            <!-- Left accent -->
            <div class="tc-accent" :style="{ background: s.color_token ?? 'var(--blue)' }" />

            <div class="tc-body">
              <!-- Top row: icon + name + price -->
              <div class="tc-top">
                <div
                  class="tc-icon"
                  :style="{ background: `color-mix(in srgb, ${s.color_token ?? 'var(--blue)'} 14%, transparent)` }"
                >
                  <Icon
                    :name="BIKE_ICON[s.bike_type] ?? 'bike'"
                    :size="18"
                    :color="s.color_token ?? 'var(--blue)'"
                  />
                </div>
                <div class="tc-title-wrap">
                  <div class="tc-name">{{ s.name }}</div>
                  <span
                    v-if="s.tag"
                    class="badge tc-tag"
                    :style="{ color: s.color_token ?? 'var(--ink)', background: `color-mix(in srgb, ${s.color_token ?? 'var(--blue)'} 10%, transparent)` }"
                  >{{ s.tag }}</span>
                </div>
                <div class="tc-price">{{ Number(s.price_eur).toFixed(2) }} €</div>
              </div>

              <!-- Divider -->
              <div class="tc-div" />

              <!-- Bottom row: specs + CTA -->
              <div class="tc-bottom">
                <div class="tc-specs">
                  <span v-if="s.diameter_label" class="tc-spec">
                    <span class="tc-spec-val">{{ s.diameter_label }}</span>
                    <span class="tc-spec-lbl">Taille</span>
                  </span>
                  <span v-if="s.weight_g" class="tc-spec">
                    <span class="tc-spec-val">{{ s.weight_g }}g</span>
                    <span class="tc-spec-lbl">Poids</span>
                  </span>
                  <span v-if="s.avg_km_lifetime" class="tc-spec">
                    <span class="tc-spec-val">{{ s.avg_km_lifetime.toLocaleString('fr') }}</span>
                    <span class="tc-spec-lbl">km est.</span>
                  </span>
                </div>
                <button
                  class="btn tc-buy"
                  :style="{ borderColor: s.color_token ?? '#27509B', color: s.color_token ?? '#27509B' }"
                  @click.stop="openRetailer(s)"
                >
                  Acheter <Icon name="chev" :size="13" :color="s.color_token ?? '#27509B'" />
                </button>
              </div>
            </div>
          </div>

          <!-- ── Pagination ──────────────────────────────── -->
          <div v-if="totalPages > 1" class="pagination">
            <button class="pg-arrow" :disabled="page === 1" @click="page--">
              <Icon name="chevL" :size="16" color="#404040" />
            </button>
            <div class="pg-numbers">
              <button
                v-for="p in totalPages"
                :key="p"
                class="pg-num"
                :class="{ active: p === page }"
                @click="page = p"
              >{{ p }}</button>
            </div>
            <button class="pg-arrow" :disabled="page === totalPages" @click="page++">
              <Icon name="chev" :size="16" color="#404040" />
            </button>
          </div>
        </div>

        <!-- ── Bundle promo ────────────────────────────────── -->
        <div class="card-lg rise d3" style="padding: 18px; margin-top: 8px; background: linear-gradient(135deg,#FCE500 0%, #FFD400 100%); border-color: transparent">
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

        <!-- ── Cost card ──────────────────────────────────── -->
        <div class="card rise d4" style="padding: 16px; margin-top: 16px">
          <div class="h-sm" style="margin-bottom: 12px">Coût raisonné</div>
          <div style="display: flex; gap: 8px">
            <div style="flex:1; text-align:center; padding: 10px 4px; background: var(--surface-2); border-radius: 14px">
              <div class="num" style="font-size:15px; font-weight:800; color: var(--green)">0,012 €</div>
              <div class="tiny" style="margin-top:3px">Coût / km</div>
            </div>
            <div style="flex:1; text-align:center; padding: 10px 4px; background: var(--surface-2); border-radius: 14px">
              <div class="num" style="font-size:15px; font-weight:800; color: var(--blue)">6 200 km</div>
              <div class="tiny" style="margin-top:3px">Durée vie</div>
            </div>
            <div style="flex:1; text-align:center; padding: 10px 4px; background: var(--surface-2); border-radius: 14px">
              <div class="num" style="font-size:15px; font-weight:800; color: var(--lime-600)">+200 pts</div>
              <div class="tiny" style="margin-top:3px">Recyclage</div>
            </div>
          </div>
        </div>

      </div>
    </div>
  </div>
</template>

<style scoped>
/* ── Section header ──────────────────────────────────── */
.section-hd {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 12px;
}

/* ── Filter row ──────────────────────────────────────── */
.filter-row {
  display: flex;
  gap: 8px;
  overflow-x: auto;
  padding-bottom: 14px;
  margin-bottom: 6px;
  scrollbar-width: none;
}
.filter-row::-webkit-scrollbar { display: none; }

/* Extend the global .chip to hold the count badge */
.filter-row .chip { gap: 7px; }

.pill-count {
  margin-left: 1px;
  min-width: 19px;
  height: 17px;
  padding: 0 5px;
  border-radius: 99px;
  background: rgba(0,0,0,.07);
  font-size: 10px;
  font-weight: 700;
  color: inherit;
  opacity: .7;
  display: flex;
  align-items: center;
  justify-content: center;
  line-height: 1;
}
.pill-count-active {
  background: rgba(255,255,255,.28);
  opacity: 1;
}

/* ── Tire card ───────────────────────────────────────── */
/* .card handles background, border, box-shadow and resets CSS vars on desktop */
.tire-card {
  display: flex;
  align-items: stretch;
  border-radius: 20px !important;
  margin-bottom: 10px;
  overflow: hidden;
  cursor: pointer;
  transition: opacity .15s;
}
.tire-card:active { opacity: .82; }

.tc-accent {
  width: 5px;
  flex-shrink: 0;
}

.tc-body {
  flex: 1;
  padding: 14px 14px 12px;
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.tc-top {
  display: flex;
  align-items: flex-start;
  gap: 10px;
}

.tc-icon {
  width: 38px;
  height: 38px;
  border-radius: 11px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.tc-title-wrap {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 5px;
}

.tc-name {
  font-size: 15px;
  font-weight: 800;
  letter-spacing: -.02em;
  color: var(--ink);
  line-height: 1.1;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.tc-tag {
  align-self: flex-start;
  font-size: 10px;
  font-weight: 700;
  letter-spacing: .03em;
  padding: 2px 8px;
  border-radius: 99px;
}

.tc-price {
  font-size: 17px;
  font-weight: 800;
  letter-spacing: -.02em;
  color: var(--ink);
  flex-shrink: 0;
  line-height: 1;
}

.tc-div {
  height: 1px;
  background: var(--line-2);
  margin: 0 -14px;
}

.tc-bottom {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
}

.tc-specs {
  display: flex;
  align-items: center;
  gap: 10px;
}

.tc-spec {
  display: flex;
  flex-direction: column;
  gap: 1px;
}
.tc-spec-val {
  font-size: 12px;
  font-weight: 700;
  color: var(--ink-2);
  line-height: 1;
}
.tc-spec-lbl {
  font-size: 9px;
  text-transform: uppercase;
  letter-spacing: .05em;
  color: var(--mute);
  line-height: 1;
}

.tc-buy {
  flex-shrink: 0;
  height: 32px;
  padding: 0 12px;
  border-radius: 10px;
  font-size: 12px;
  font-weight: 700;
  border-width: 1.5px;
  border-style: solid;
  background: transparent;
  display: inline-flex;
  align-items: center;
  gap: 4px;
}

/* ── Pagination ──────────────────────────────────────── */
/* Static light-mode colors — not CSS vars — so they work on both
   the light mobile background and the dark desktop sidebar context. */
.pagination {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  margin: 20px 0 8px;
}

.pg-arrow {
  width: 38px;
  height: 38px;
  border-radius: 12px;
  border: 1.5px solid #E5E5E5;
  background: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: background .15s;
  flex-shrink: 0;
  color: #404040;
}
.pg-arrow:disabled { opacity: .3; cursor: default; }
.pg-arrow:not(:disabled):active { background: #F2F2F2; }

.pg-numbers {
  display: flex;
  gap: 4px;
  align-items: center;
}

.pg-num {
  min-width: 36px;
  height: 36px;
  border-radius: 11px;
  border: 1.5px solid #E5E5E5;
  background: #fff;
  font-size: 13px;
  font-weight: 600;
  color: #999999;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 0 8px;
  transition: all .15s;
}
.pg-num.active {
  background: #27509B;
  border-color: #27509B;
  color: #fff;
  font-weight: 800;
}
</style>
