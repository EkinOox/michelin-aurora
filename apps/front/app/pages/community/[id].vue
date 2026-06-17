<script setup lang="ts">
interface RiderDto {
  id: string
  name: string
  city: string
  rewards_level: string
  total_points: number
  color_hex: string
  bike_photo_url: string | null
  profile: { bike_type: string; rider_level: string; usage_type: string } | null
  friendship_status: 'none' | 'pending_sent' | 'pending_received' | 'friends'
  total_km?: number
  rides_count?: number
  km_this_month?: number
}

const RANK_LABEL: Record<string, string> = {
  explorer: 'Explorer', rider: 'Rider', performer: 'Performer',
  elite_cyclist: 'Elite Cyclist', michelin_ambassador: 'Michelin Ambassador',
}
const BIKE_LABEL: Record<string, string> = { route: 'Route', gravel: 'Gravel', vtt: 'VTT', vae: 'VAE' }
const LEVEL_LABEL: Record<string, string> = { beginner: 'Débutant', intermediate: 'Intermédiaire', advanced: 'Avancé', expert: 'Expert' }
const USAGE_LABEL: Record<string, string> = { leisure: 'Loisir', sport: 'Performance', competition: 'Compétition', commute: 'Transport' }

const route = useRoute()
const router = useRouter()
const apiBase = useApiBase()

function photoUrl(url: string | null): string | null {
  if (!url) return null
  return url.startsWith('/') ? apiBase + url : url
}

const { data: rider, refresh } = await useApiFetch<RiderDto>(`/api/community/users/${route.params.id}`, {
  key: `community-user-${route.params.id}`,
})

const initials = computed(() => {
  const n = rider.value?.name ?? ''
  return n.split(' ').map(p => p[0]).join('').slice(0, 2).toUpperCase()
})

async function toggleFriend() {
  if (!rider.value) return
  if (rider.value.friendship_status === 'friends' || rider.value.friendship_status === 'pending_sent') {
    await $apiFetch(`/api/community/friends/${rider.value.id}`, { method: 'DELETE' })
  } else {
    await $apiFetch(`/api/community/friends/${rider.value.id}`, { method: 'POST' })
  }
  await refresh()
}

async function rejectFriend() {
  if (!rider.value) return
  await $apiFetch(`/api/community/friends/${rider.value.id}`, { method: 'DELETE' })
  await refresh()
}
</script>

<template>
  <div class="screen">
    <div class="screen-scroll">
      <AppHeader :on-back="() => router.push('/community')" />

      <div v-if="rider" class="pad">

        <!-- Hero -->
        <div class="rider-hero rise">
          <!-- Avec photo vélo -->
          <div v-if="photoUrl(rider.bike_photo_url)" class="rider-hero-photo">
            <img :src="photoUrl(rider.bike_photo_url)!" :alt="`Vélo de ${rider.name}`" />
            <div class="rider-hero-overlay" />
            <!-- Badges rang en haut à droite -->
            <div class="rider-hero-badges">
              <span class="badge badge-yellow">{{ RANK_LABEL[rider.rewards_level] ?? rider.rewards_level }}</span>
              <span class="badge badge-lime">{{ rider.total_points.toLocaleString('fr') }} pts</span>
            </div>
            <!-- Nom + avatar en bas à gauche -->
            <div class="rider-hero-info">
              <Avatar :name="initials" :size="56" :bg="rider.color_hex" style="border: 3px solid #fff" />
              <div>
                <div class="h-md" style="color: #fff; margin-top: 0">{{ rider.name }}</div>
                <div class="tiny" style="color: rgba(255,255,255,.75)">{{ rider.city }}</div>
              </div>
            </div>
          </div>

          <!-- Sans photo vélo -->
          <div v-else class="card-lg" style="padding: 20px; display: flex; gap: 16px; align-items: center; position: relative">
            <Avatar :name="initials" :size="64" :bg="rider.color_hex" />
            <div style="flex: 1">
              <div class="h-md">{{ rider.name }}</div>
              <div class="tiny" style="margin-top: 4px">{{ rider.city }}</div>
              <div style="display: flex; gap: 6px; margin-top: 8px; flex-wrap: wrap">
                <span class="badge badge-yellow">{{ RANK_LABEL[rider.rewards_level] ?? rider.rewards_level }}</span>
                <span class="badge badge-lime">{{ rider.total_points.toLocaleString('fr') }} pts</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Stats rides (grille 3 colonnes) -->
        <div
          v-if="rider.total_km !== undefined || rider.rides_count !== undefined || rider.km_this_month !== undefined"
          class="rise d1"
          style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin-top: 16px"
        >
          <div class="card stat-ride-card">
            <div class="num" style="font-size: 20px; font-weight: 800; color: var(--ink); line-height: 1">
              {{ rider.total_km !== undefined ? rider.total_km.toLocaleString('fr', { maximumFractionDigits: 0 }) : '—' }}
            </div>
            <div class="tiny" style="color: var(--mute); margin-top: 2px">km total</div>
          </div>
          <div class="card stat-ride-card">
            <div class="num" style="font-size: 20px; font-weight: 800; color: var(--ink); line-height: 1">
              {{ rider.rides_count !== undefined ? rider.rides_count : '—' }}
            </div>
            <div class="tiny" style="color: var(--mute); margin-top: 2px">sorties</div>
          </div>
          <div class="card stat-ride-card">
            <div class="num" style="font-size: 20px; font-weight: 800; color: var(--lime-600); line-height: 1">
              {{ rider.km_this_month !== undefined ? rider.km_this_month.toLocaleString('fr', { maximumFractionDigits: 0 }) : '—' }}
            </div>
            <div class="tiny" style="color: var(--mute); margin-top: 2px">km ce mois</div>
          </div>
        </div>

        <!-- Stats niveau / points (si pas de stats rides) -->
        <div
          v-else
          class="rise d1"
          style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-top: 16px"
        >
          <div class="card" style="padding: 14px">
            <div class="tiny" style="color: var(--mute)">Niveau</div>
            <div class="small" style="font-weight: 700; color: var(--ink); margin-top: 4px">{{ RANK_LABEL[rider.rewards_level] ?? rider.rewards_level }}</div>
          </div>
          <div class="card" style="padding: 14px">
            <div class="tiny" style="color: var(--mute)">Points</div>
            <div class="num" style="font-size: 18px; font-weight: 800; color: var(--lime-600); margin-top: 4px">{{ rider.total_points.toLocaleString('fr') }}</div>
          </div>
        </div>

        <!-- Profil cycliste -->
        <div v-if="rider.profile" class="rise d2" style="margin-top: 16px">
          <div class="eyebrow" style="margin-bottom: 10px">Profil cycliste</div>
          <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px">
            <div class="card" style="padding: 12px; text-align: center">
              <div class="tiny" style="color: var(--mute)">Vélo</div>
              <div class="small" style="font-weight: 700; color: var(--ink); margin-top: 4px">{{ BIKE_LABEL[rider.profile.bike_type] ?? rider.profile.bike_type }}</div>
            </div>
            <div class="card" style="padding: 12px; text-align: center">
              <div class="tiny" style="color: var(--mute)">Niveau</div>
              <div class="small" style="font-weight: 700; color: var(--ink); margin-top: 4px">{{ LEVEL_LABEL[rider.profile.rider_level] ?? rider.profile.rider_level }}</div>
            </div>
            <div class="card" style="padding: 12px; text-align: center">
              <div class="tiny" style="color: var(--mute)">Usage</div>
              <div class="small" style="font-weight: 700; color: var(--ink); margin-top: 4px">{{ USAGE_LABEL[rider.profile.usage_type] ?? rider.profile.usage_type }}</div>
            </div>
          </div>
        </div>

        <!-- Section ami -->
        <div class="rise d3" style="margin-top: 20px">

          <!-- Demande reçue -->
          <template v-if="rider.friendship_status === 'pending_received'">
            <button class="btn btn-blue btn-block" style="height: 52px; font-size: 15px" @click="toggleFriend">
              Accepter la demande
            </button>
            <button
              class="btn btn-ghost btn-block"
              style="height: 44px; font-size: 13px; margin-top: 8px; color: var(--red)"
              @click="rejectFriend"
            >
              Refuser
            </button>
          </template>

          <!-- Demande envoyée -->
          <template v-else-if="rider.friendship_status === 'pending_sent'">
            <button class="btn btn-ghost btn-block friend-sent-btn" style="height: 52px; font-size: 15px" disabled>
              Demande envoyée…
            </button>
            <div class="tiny friend-sent-hint">En attente d'acceptation. Annuler ?
              <button class="friend-cancel-link" @click="toggleFriend">Annuler la demande</button>
            </div>
          </template>

          <!-- Déjà amis -->
          <template v-else-if="rider.friendship_status === 'friends'">
            <button class="btn btn-block friend-ok-btn" style="height: 52px; font-size: 15px" @click="toggleFriend">
              ✓ Ami·e
            </button>
            <button
              class="btn btn-ghost btn-block"
              style="height: 40px; font-size: 12px; margin-top: 8px; color: var(--red)"
              @click="toggleFriend"
            >
              Retirer de mes amis
            </button>
          </template>

          <!-- Aucun lien -->
          <template v-else>
            <button class="btn btn-blue btn-block" style="height: 52px; font-size: 15px" @click="toggleFriend">
              + Ajouter en ami·e
            </button>
          </template>

        </div>

      </div>

      <div v-else class="pad" style="text-align: center; padding-top: 60px">
        <div class="small" style="color: var(--mute)">Rider introuvable.</div>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* ── Hero ──────────────────────────────────────────── */
.rider-hero-photo {
  position: relative;
  width: 100%;
  height: 220px;
  border-radius: 20px;
  overflow: hidden;
}
.rider-hero-photo img {
  width: 100%; height: 100%;
  object-fit: cover;
  display: block;
}
.rider-hero-overlay {
  position: absolute;
  inset: 0;
  background: linear-gradient(to top, rgba(0,0,0,.65) 0%, transparent 55%);
}
.rider-hero-badges {
  position: absolute;
  top: 12px; right: 12px;
  display: flex;
  gap: 6px;
  flex-wrap: wrap;
  justify-content: flex-end;
}
.rider-hero-info {
  position: absolute;
  bottom: 16px; left: 16px;
  display: flex;
  align-items: center;
  gap: 12px;
}

/* ── Stats rides ───────────────────────────────────── */
.stat-ride-card {
  padding: 14px 10px;
  text-align: center;
}

/* ── Boutons ami ───────────────────────────────────── */
.friend-sent-btn {
  opacity: .65;
  cursor: default;
}
.friend-sent-hint {
  color: var(--mute);
  margin-top: 8px;
  text-align: center;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
}
.friend-cancel-link {
  background: none;
  border: none;
  padding: 0;
  font-size: inherit;
  font-weight: 600;
  color: var(--red);
  cursor: pointer;
  text-decoration: underline;
}
.friend-ok-btn {
  background: var(--lime-600);
  color: #fff;
  border: none;
}
</style>
