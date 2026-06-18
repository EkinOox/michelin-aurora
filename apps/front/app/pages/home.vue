<script setup lang="ts">
import { imageFor } from '~/data/images'
import type { RouteDto } from '~/components/RouteRow.vue'


definePageMeta({ tabbar: true })

interface ProfileDto {
  name: string
  city: string
  rewards_level: string
  total_points: number
  profile: { bike_type: string, bike_photo_url: string | null } | null
}
interface TireDto {
  id: string
  name: string
  subtitle: string | null
  image_key: string | null
  avg_km_lifetime: number
}
interface RewardsDto { points: number, rank: string, next_at: number | null }

const { data: profile } = await useApiFetch<ProfileDto>('/api/profile', { key: 'home-profile' })
const { data: tires } = await useApiFetch<TireDto[]>('/api/tires', { key: 'home-tires', default: () => [] })
const { data: rewards } = await useApiFetch<RewardsDto>('/api/rewards', { key: 'home-rewards' })
const { data: routes } = await useApiFetch<RouteDto[]>('/api/routes', { key: 'home-routes', default: () => [] })

const { frontStr, rearStr, isRain } = usePressure()
const notifSheet = useNotificationsSheet()
const apiBase = useApiBase()
const { data: notifCount } = await useApiFetch<{ unread: number }>('/api/notifications/count', { key: 'home-notif-count' })

const initials = computed(() => {
  const n = profile.value?.name ?? ''
  return n.split(' ').map(p => p[0]).join('').slice(0, 2).toUpperCase() || 'TG'
})
const firstName = computed(() => profile.value?.name?.split(' ')[0] ?? '')
const rankLabel = computed(() => (profile.value?.rewards_level ?? '').replace(/_/g, ' '))

const activeBike = computed(() => tires.value?.[0] ?? null)

const bikePhotoSrc = computed(() => {
  const url = profile.value?.profile?.bike_photo_url
  if (url) return url.startsWith('/') ? apiBase + url : url
  return imageFor(activeBike.value?.image_key)
})
const topRoutes = computed(() => (routes.value ?? []).slice(0, 2))

const rewardsProgress = computed(() => {
  if (!rewards.value || !rewards.value.next_at) return 100
  return Math.min(100, (rewards.value.points / rewards.value.next_at) * 100)
})
</script>

<template>
  <div class="screen home-screen">
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
          <button class="home-bell iconbtn" @click="notifSheet.open()">
            <Icon name="bell" :size="20" />
            <span v-if="(notifCount?.unread ?? 0) > 0" class="home-bell-badge">{{ notifCount!.unread }}</span>
          </button>
        </div>
      </div>

      <div class="pad rise d1">
        <div class="card-lg" style="overflow: hidden; position: relative">
          <div class="mapbg" style="position: absolute; inset: 0; opacity: .5" />
          <div class="bike-inner" style="position: relative; padding: 18px 18px 16px">
            <div class="between">
              <div class="row" style="gap: 7px">
                <Icon name="loc" :size="15" color="var(--ink-3)" />
                <span class="small" style="font-weight: 600">{{ profile?.city }}</span>
              </div>
              <span class="badge badge-lime"><span style="width: 6px; height: 6px; border-radius: 99px; background: var(--lime-600)" /> Connecté</span>
            </div>
            <div class="bike-main row" style="justify-content: space-between; align-items: center; margin-top: 14px; gap: 12px">
              <div class="bike-info" style="flex: 1; min-width: 0">
                <div class="eyebrow">Vélo actif</div>
                <div class="h-md" style="margin-top: 4px">{{ activeBike?.name }}</div>
                <div class="small" style="margin-top: 3px"><Icon name="leaf" :size="13" color="var(--lime-600)" style="vertical-align: -2px" /> {{ activeBike?.subtitle }}</div>
              </div>
              <Photo
                class="bike-photo"
                :src="bikePhotoSrc"
                alt="Vélo actif"
                :radius="16"
                style="width: 130px; height: 96px; flex: 0 0 auto; object-fit: cover"
              />
            </div>
            <div class="divider bike-divider" style="margin: 15px 0" />
            <div class="bike-stats row" style="gap: 6px">
              <div class="bike-stat" style="flex: 1; min-width: 0">
                <div class="row" style="gap: 5px; color: var(--ink-3)"><Icon name="route" :size="14" /><span class="tiny">Durée vie</span></div>
                <div class="num bike-stat-val" style="font-size: 20px; font-weight: 700; margin-top: 3px">{{ activeBike?.avg_km_lifetime }}<span class="bike-stat-unit" style="font-size: 12px; color: var(--mute); margin-left: 2px">km</span></div>
              </div>
              <div class="bike-stat" style="flex: 1; min-width: 0">
                <div class="row" style="gap: 5px; color: var(--ink-3)"><Icon name="gauge" :size="14" color="var(--lime-600)" /><span class="tiny">Pression Av</span></div>
                <div class="num" style="font-size: 20px; font-weight: 700; margin-top: 3px">{{ frontStr }}<span style="font-size: 12px; color: var(--mute); margin-left: 2px">bar</span></div>
              </div>
              <div class="bike-stat" style="flex: 1; min-width: 0">
                <div class="row" style="gap: 5px; color: var(--ink-3)"><Icon name="recycle" :size="14" /><span class="tiny">Usure</span></div>
                <div class="num bike-stat-val" style="font-size: 20px; font-weight: 700; margin-top: 3px">18<span class="bike-stat-unit" style="font-size: 12px; color: var(--mute); margin-left: 2px">%</span></div>
              </div>
            </div>
            <NuxtLink to="/ride" class="btn btn-blue btn-block ride-btn" style="margin-top: 16px; height: 50px">
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
            <div class="num" style="font-size: 24px; font-weight: 800; margin-top: 2px">{{ rearStr }}<span style="font-size: 12px; color: var(--mute)"> bar</span></div>
          </div>
          <div class="row" style="gap: 5px">
            <span v-if="isRain" class="badge badge-blue"><Icon name="cloud" :size="12" /> Pluie −10%</span>
            <span v-else class="badge badge-lime"><Icon name="sun" :size="12" /> Sec</span>
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
        <div class="between" style="margin-bottom: 12px">
          <span class="h-sm">Explorer</span>
        </div>
        <div class="explore-grid">
          <NuxtLink to="/routes" class="explore-card explore-routes">
            <div class="explore-icon"><Icon name="route" :size="24" color="#fff" /></div>
            <div class="explore-body">
              <span class="explore-title">Routes</span>
              <span class="explore-sub">Itinéraires sur-mesure</span>
            </div>
            <div class="explore-deco explore-deco-a" aria-hidden="true" />
            <div class="explore-deco explore-deco-b" aria-hidden="true" />
          </NuxtLink>
          <NuxtLink to="/events" class="explore-card explore-events">
            <div class="explore-icon"><Icon name="cal" :size="24" color="#fff" /></div>
            <div class="explore-body">
              <span class="explore-title">Events</span>
              <span class="explore-sub">Sorties &amp; Compétitions</span>
            </div>
            <div class="explore-deco explore-deco-a" aria-hidden="true" />
            <div class="explore-deco explore-deco-b" aria-hidden="true" />
          </NuxtLink>
          <NuxtLink to="/community" class="explore-card explore-riders">
            <div class="explore-icon"><Icon name="users" :size="24" color="#fff" /></div>
            <div class="explore-body">
              <span class="explore-title">Riders</span>
              <span class="explore-sub">La Communauté</span>
            </div>
            <div class="explore-deco explore-deco-a" aria-hidden="true" />
            <div class="explore-deco explore-deco-b" aria-hidden="true" />
          </NuxtLink>
          <NuxtLink to="/store" class="explore-card explore-store">
            <div class="explore-icon"><Icon name="cart" :size="24" color="#fff" /></div>
            <div class="explore-body">
              <span class="explore-title">Store</span>
              <span class="explore-sub">Boutique Michelin</span>
            </div>
            <div class="explore-deco explore-deco-a" aria-hidden="true" />
            <div class="explore-deco explore-deco-b" aria-hidden="true" />
          </NuxtLink>
        </div>
      </div>

      <div class="pad rise d4" style="margin-top: 18px">
        <div class="between" style="margin-bottom: 10px">
          <span class="h-sm">Actualités Michelin</span>
          <NuxtLink to="/actualites" class="small" style="color: var(--lime-600); font-weight: 700">Voir tout</NuxtLink>
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

<style scoped>
/* ── Bell badge ──────────────────────────────────────── */
.home-bell { position: relative; }
.home-bell-badge {
  position: absolute;
  top: 6px; right: 6px;
  min-width: 16px; height: 16px;
  border-radius: 99px;
  background: var(--red);
  color: #fff;
  font-size: 9px; font-weight: 800;
  display: flex; align-items: center; justify-content: center;
  padding: 0 4px;
  border: 2px solid var(--card);
  line-height: 1;
  pointer-events: none;
}

/* ── Desktop : thème navy Michelin ── */
@media (min-width: 900px) {
  .home-screen {
    /* Override des variables CSS pour le thème sombre */
    --ink:      #ffffff;
    --ink-2:    rgba(255,255,255,.85);
    --ink-3:    rgba(255,255,255,.55);
    --mute:     rgba(255,255,255,.35);
    --line:     rgba(255,255,255,.10);
    --line-2:   rgba(255,255,255,.06);
    --surface-2: rgba(255,255,255,.10);
    --card:     rgba(255,255,255,.96);
    --surface:  rgba(255,255,255,.96);

    background:
      radial-gradient(130% 55% at 50% -5%, #2a5db0 0%, #00205B 38%, #000C34 70%, #000818 100%);
  }

  /* Cards blanches qui ressortent sur le fond navy */
  .home-screen :deep(.card),
  .home-screen :deep(.card-lg) {
    background: rgba(255,255,255,.96);
    border-color: rgba(255,255,255,.18);
    /* Réinitialise les variables pour l'intérieur des cards */
    --ink:    #1A1A1A;
    --ink-2:  #404040;
    --ink-3:  #53565A;
    --mute:   #999999;
    --line:   #E5E5E5;
    --line-2: #F2F2F2;
    --surface-2: #F2F2F2;
  }

  /* Bouton icône (cloche) en dark glass */
  .home-screen :deep(.iconbtn) {
    background: rgba(255,255,255,.12);
    border-color: rgba(255,255,255,.20);
    color: #fff;
  }
  .home-screen :deep(.iconbtn):hover {
    background: rgba(255,255,255,.20);
  }

  /* Michelin logo en blanc */
  .home-screen :deep(.michelin-logo-img) {
    filter: brightness(0) invert(1);
    opacity: .85;
  }

  /* Titres de section (hors cards) */
  .home-screen :deep(.h-sm) { color: #fff; }
  .home-screen :deep(.tiny) { color: rgba(255,255,255,.55); }

  /* Badge "Connecté" reste vert */
  .home-screen :deep(.badge-lime) {
    background: rgba(132,189,0,.18);
    color: #84BD00;
  }

  /* Liens "Voir tout" */
  .home-screen :deep(a.small[style*="lime"]) {
    color: var(--lime) !important;
  }

  /* Cards : restauration des couleurs sombres pour tous les éléments typographiques.
     Les règles générales ci-dessus colorient tout en blanc (thème navy),
     ce qui rend le texte illisible à l'intérieur des cards blanches. */
  .home-screen :deep(.card .h-sm),
  .home-screen :deep(.card-lg .h-sm)    { color: var(--ink); }
  .home-screen :deep(.card .tiny),
  .home-screen :deep(.card-lg .tiny)    { color: var(--mute); }
  .home-screen :deep(.card .small),
  .home-screen :deep(.card-lg .small)   { color: var(--ink-3); }
  .home-screen :deep(.card .eyebrow),
  .home-screen :deep(.card-lg .eyebrow) { color: var(--ink-3); }
  .home-screen :deep(.card .num),
  .home-screen :deep(.card-lg .num)     { color: var(--ink); }

  /* ── Card vélo actif : layout desktop ────────────────────────── */
  .bike-inner { padding: 24px 28px 22px; }

  /* Photo : plus grande */
  .home-screen :deep(.bike-photo) {
    width: 220px !important;
    height: 162px !important;
    border-radius: 20px !important;
  }

  /* Infos vélo : typographie plus aérée */
  .bike-info .eyebrow { font-size: 12px; letter-spacing: .2em; }
  .bike-info .h-md    { font-size: 26px; margin-top: 6px; }
  .bike-info .small   { margin-top: 5px; font-size: 14px; }

  /* Statistiques : séparateurs verticaux + valeurs plus grosses */
  .bike-divider { margin: 18px 0; }
  .bike-stats   { gap: 0; }
  .bike-stat    { padding: 0 24px; border-right: 1px solid var(--line); }
  .bike-stat:first-child { padding-left: 0; }
  .bike-stat:last-child  { border-right: none; }
  .bike-stat-val  { font-size: 28px !important; font-weight: 800 !important; margin-top: 5px !important; }
  .bike-stat-unit { font-size: 14px !important; }

  /* Bouton "Démarrer une sortie" : caché sur desktop */
  .ride-btn { display: none; }
}

/* ── Section Explorer ─────────────────────────────────────────────────── */
.explore-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 10px;
}

.explore-card {
  position: relative;
  overflow: hidden;
  border-radius: 20px;
  padding: 14px 14px 13px;
  display: flex;
  flex-direction: row;
  align-items: center;
  gap: 12px;
  min-height: 72px;
  text-decoration: none;
  border: none;
  box-shadow: 0 4px 18px rgba(0, 0, 0, .18), 0 1px 0 rgba(255,255,255,.08) inset;
  transition: transform .22s cubic-bezier(.34,1.56,.64,1), box-shadow .22s ease;
}
.explore-card:active { transform: scale(.97); }

/* Couleurs par section */
.explore-routes { background: linear-gradient(145deg, #72C93A 0%, #4A8A1C 100%); }
.explore-events { background: linear-gradient(145deg, #3F78E0 0%, #1D3FAD 100%); }
.explore-riders { background: linear-gradient(145deg, #8B4EC4 0%, #4A1E82 100%); }
.explore-store  { background: linear-gradient(145deg, #353545 0%, #111120 100%); }

/* Icône glassmorphism */
.explore-icon {
  width: 42px; height: 42px;
  border-radius: 12px;
  background: rgba(255, 255, 255, .18);
  border: 1px solid rgba(255, 255, 255, .22);
  backdrop-filter: blur(6px);
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
}

/* Texte */
.explore-body {
  display: flex;
  flex-direction: column;
  gap: 2px;
  min-width: 0;
}
.explore-title {
  font-size: 14px;
  font-weight: 800;
  color: #fff;
  letter-spacing: -.02em;
  line-height: 1;
  white-space: nowrap;
}
.explore-sub {
  font-size: 10px;
  font-weight: 500;
  color: rgba(255, 255, 255, .62);
  letter-spacing: .01em;
  line-height: 1.3;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

/* Cercles décoratifs */
.explore-deco {
  position: absolute;
  border-radius: 50%;
  pointer-events: none;
  background: rgba(255, 255, 255, .07);
}
.explore-deco-a { width: 110px; height: 110px; right: -28px; top: -28px; }
.explore-deco-b { width: 60px;  height: 60px;  right:  14px; top:  30px; background: rgba(255,255,255,.05); }

/* Desktop : cartes plus hautes avec hover lift */
@media (min-width: 900px) {
  .explore-grid { grid-template-columns: repeat(4, 1fr); gap: 14px; }

  .explore-card {
    flex-direction: column;
    align-items: flex-start;
    gap: 0;
    min-height: 156px;
    padding: 20px 20px 18px;
    border-radius: 24px;
    box-shadow: 0 6px 28px rgba(0, 0, 0, .22), 0 1px 0 rgba(255,255,255,.08) inset;
  }
  .explore-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 14px 40px rgba(0, 0, 0, .28), 0 1px 0 rgba(255,255,255,.1) inset;
  }

  .explore-icon { width: 52px; height: 52px; border-radius: 16px; }

  .explore-body   { margin-top: auto; padding-top: 18px; }
  .explore-title  { font-size: 17px; white-space: normal; }
  .explore-sub    { font-size: 11.5px; white-space: normal; overflow: visible; text-overflow: unset; }

  .explore-deco-a { width: 140px; height: 140px; right: -36px; top: -36px; }
  .explore-deco-b { width: 80px;  height: 80px;  right:  18px; top:  38px; }
}

@media (display-mode: standalone) {
  .home-screen {
    /* Réinitialise tout en mode PWA — fond bleu-blanc Michelin */
    --ink: #1A1A1A; --ink-2: #404040; --ink-3: #53565A;
    --mute: #999999; --line: #E5E5E5; --line-2: #F2F2F2;
    --surface-2: #F2F2F2; --card: #FFFFFF; --surface: #FFFFFF;
    color: var(--ink);
    background: radial-gradient(140% 80% at 50% -10%, #D8E4F5 0%, #EEF2FA 40%, #E8EDF8 100%);
  }
  .home-screen :deep(.card),
  .home-screen :deep(.card-lg) {
    background: #FFFFFF;
    border-color: rgba(210,222,240,.7);
  }
}
</style>
