<script setup lang="ts">
interface RiderDetail {
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
  elite_cyclist: 'Elite Cyclist', michelin_ambassador: 'Ambassador',
}
const RANK_COLORS: Record<string, string> = {
  explorer: '#8e9aaf', rider: '#27509B', performer: '#84BD00',
  elite_cyclist: '#C4AA00', michelin_ambassador: '#E71D36',
}
const BIKE_LABEL: Record<string, string>  = { route: 'Route', gravel: 'Gravel', vtt: 'VTT', vae: 'VAE' }
const LEVEL_LABEL: Record<string, string> = { beginner: 'Débutant', intermediate: 'Intermédiaire', advanced: 'Avancé', expert: 'Expert' }
const USAGE_LABEL: Record<string, string> = { leisure: 'Loisir', sport: 'Performance', competition: 'Compétition', commute: 'Transport' }

const { riderId, close } = useRiderSheet()
const toast = useToast()
const apiBase = useApiBase()

const rider   = ref<RiderDetail | null>(null)
const loading = ref(false)

function photoUrl(url: string | null): string | null {
  if (!url) return null
  return url.startsWith('/') ? apiBase + url : url
}

const initials = computed(() => {
  const n = rider.value?.name ?? ''
  return n.split(' ').map(p => p[0]).join('').slice(0, 2).toUpperCase()
})

const rankColor = computed(() =>
  RANK_COLORS[rider.value?.rewards_level ?? ''] ?? '#27509B'
)

watch(riderId, async (id) => {
  if (!id) { rider.value = null; return }
  loading.value = true
  try {
    rider.value = await $apiFetch<RiderDetail>(`/api/community/users/${id}`)
  } finally {
    loading.value = false
  }
})

async function toggleFriend() {
  if (!rider.value) return
  if (rider.value.friendship_status === 'friends' || rider.value.friendship_status === 'pending_sent') {
    await $apiFetch(`/api/community/friends/${rider.value.id}`, { method: 'DELETE' })
    toast.show('Ami·e retiré·e', 'close')
  } else {
    await $apiFetch(`/api/community/friends/${rider.value.id}`, { method: 'POST' })
    toast.show(rider.value.friendship_status === 'pending_received' ? 'Demande acceptée !' : 'Demande envoyée', 'check')
  }
  if (riderId.value) {
    rider.value = await $apiFetch<RiderDetail>(`/api/community/users/${riderId.value}`)
  }
}
</script>

<template>
  <Sheet :open="!!riderId" @close="close">
    <div v-if="loading" class="rs-loading">
      <div class="tiny" style="color: var(--mute)">Chargement…</div>
    </div>

    <div v-else-if="rider">

      <!-- Hero -->
      <div class="rs-hero">
        <div v-if="photoUrl(rider.bike_photo_url)" class="rs-photo">
          <img :src="photoUrl(rider.bike_photo_url)!" :alt="`Vélo de ${rider.name}`" />
          <div class="rs-photo-overlay" />
          <div class="rs-photo-info">
            <Avatar :name="initials" :size="44" :bg="rider.color_hex" style="border: 2.5px solid #fff; flex-shrink: 0" />
            <div>
              <div class="rs-name" style="color: #fff">{{ rider.name }}</div>
              <div class="tiny" style="color: rgba(255,255,255,.75)">{{ rider.city }}</div>
            </div>
          </div>
          <div class="rs-photo-badges">
            <span class="badge badge-yellow">{{ RANK_LABEL[rider.rewards_level] ?? rider.rewards_level }}</span>
            <span class="badge badge-lime">{{ rider.total_points.toLocaleString('fr') }} pts</span>
          </div>
        </div>

        <div v-else class="rs-hero-plain">
          <Avatar :name="initials" :size="56" :bg="rider.color_hex" />
          <div style="flex: 1; min-width: 0">
            <div class="rs-name">{{ rider.name }}</div>
            <div class="tiny" style="margin-top: 3px">{{ rider.city }}</div>
            <div style="display: flex; gap: 6px; margin-top: 8px; flex-wrap: wrap">
              <span class="badge badge-yellow">{{ RANK_LABEL[rider.rewards_level] ?? rider.rewards_level }}</span>
              <span class="badge badge-lime">{{ rider.total_points.toLocaleString('fr') }} pts</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Ride stats -->
      <div class="rs-stats-row">
        <div class="rs-stat">
          <div class="rs-stat-val">{{ rider.total_km !== undefined ? rider.total_km.toLocaleString('fr', { maximumFractionDigits: 0 }) : '—' }}</div>
          <div class="rs-stat-lbl">km total</div>
        </div>
        <div class="rs-stat-sep" />
        <div class="rs-stat">
          <div class="rs-stat-val">{{ rider.rides_count ?? '—' }}</div>
          <div class="rs-stat-lbl">sorties</div>
        </div>
        <div class="rs-stat-sep" />
        <div class="rs-stat">
          <div class="rs-stat-val" style="color: #5E8700">{{ rider.km_this_month !== undefined ? rider.km_this_month.toLocaleString('fr', { maximumFractionDigits: 0 }) : '—' }}</div>
          <div class="rs-stat-lbl">km ce mois</div>
        </div>
      </div>

      <!-- Profil cycliste -->
      <div v-if="rider.profile" class="rs-profile">
        <div class="eyebrow" style="margin-bottom: 10px; color: var(--ink-3)">Profil cycliste</div>
        <div class="rs-profile-grid">
          <div class="rs-profile-cell">
            <div class="tiny">Vélo</div>
            <div class="small" style="font-weight: 700; color: var(--ink)">{{ BIKE_LABEL[rider.profile.bike_type] ?? rider.profile.bike_type }}</div>
          </div>
          <div class="rs-profile-cell">
            <div class="tiny">Niveau</div>
            <div class="small" style="font-weight: 700; color: var(--ink)">{{ LEVEL_LABEL[rider.profile.rider_level] ?? rider.profile.rider_level }}</div>
          </div>
          <div class="rs-profile-cell">
            <div class="tiny">Usage</div>
            <div class="small" style="font-weight: 700; color: var(--ink)">{{ USAGE_LABEL[rider.profile.usage_type] ?? rider.profile.usage_type }}</div>
          </div>
        </div>
      </div>

      <!-- Action ami -->
      <div class="rs-action">
        <!-- Demande reçue -->
        <template v-if="rider.friendship_status === 'pending_received'">
          <button class="btn btn-blue btn-block rs-btn" @click="toggleFriend">
            ✓ Accepter la demande
          </button>
          <button class="btn btn-ghost btn-block rs-btn-sm" style="color: var(--red)" @click="toggleFriend">
            Refuser
          </button>
        </template>

        <!-- Demande envoyée -->
        <template v-else-if="rider.friendship_status === 'pending_sent'">
          <button class="btn btn-ghost btn-block rs-btn rs-btn-disabled" disabled>
            Demande envoyée…
          </button>
          <button class="btn btn-ghost btn-block rs-btn-sm" style="color: var(--red)" @click="toggleFriend">
            Annuler la demande
          </button>
        </template>

        <!-- Amis -->
        <template v-else-if="rider.friendship_status === 'friends'">
          <button class="btn btn-block rs-btn rs-btn-friends" @click="toggleFriend">
            ✓ Ami·e
          </button>
          <button class="btn btn-ghost btn-block rs-btn-sm" style="color: var(--red)" @click="toggleFriend">
            Retirer de mes amis
          </button>
        </template>

        <!-- Aucun lien -->
        <template v-else>
          <button class="btn btn-blue btn-block rs-btn" @click="toggleFriend">
            + Ajouter en ami·e
          </button>
        </template>
      </div>
    </div>
  </Sheet>
</template>

<style scoped>
.rs-loading {
  display: flex;
  align-items: center;
  justify-content: center;
  height: 200px;
}

/* ── Root scroll wrapper ───────────────────────────────── */
.rs-loading,
div[v-else-if] { overflow-x: hidden; width: 100%; box-sizing: border-box; }

/* ── Hero ──────────────────────────────────────────────── */
.rs-hero { margin: -10px -20px 0; overflow: hidden; }

.rs-photo {
  position: relative;
  height: 200px;
  overflow: hidden;
}
.rs-photo img { width: 100%; height: 100%; object-fit: cover; display: block; }
.rs-photo-overlay {
  position: absolute;
  inset: 0;
  background: linear-gradient(to top, rgba(0,0,0,.65) 0%, transparent 55%);
}
.rs-photo-info {
  position: absolute;
  bottom: 14px; left: 16px;
  display: flex; align-items: center; gap: 10px;
}
.rs-photo-badges {
  position: absolute;
  top: 10px; right: 12px;
  display: flex; gap: 6px; flex-wrap: wrap; justify-content: flex-end;
}

.rs-hero-plain {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 4px 20px 16px;
}
.rs-name {
  font-size: 18px;
  font-weight: 800;
  letter-spacing: -.01em;
  color: var(--ink);
  line-height: 1.1;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

/* ── Stats ─────────────────────────────────────────────── */
.rs-stats-row {
  display: flex;
  align-items: center;
  padding: 16px 0 12px;
  border-bottom: 1px solid var(--line-2);
  margin-bottom: 14px;
}
.rs-stat { flex: 1; text-align: center; }
.rs-stat-val {
  font-size: 20px;
  font-weight: 800;
  color: var(--ink);
  letter-spacing: -.02em;
  line-height: 1;
}
.rs-stat-lbl {
  font-size: 10.5px;
  color: var(--mute);
  margin-top: 3px;
}
.rs-stat-sep {
  width: 1px; height: 36px;
  background: var(--line-2);
  flex-shrink: 0;
}

/* ── Profil ────────────────────────────────────────────── */
.rs-profile { margin-bottom: 16px; }
.rs-profile-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 8px;
}
.rs-profile-cell {
  background: var(--surface-2);
  border-radius: 12px;
  padding: 10px 10px 8px;
  text-align: center;
}

/* ── Actions ───────────────────────────────────────────── */
.rs-action { display: flex; flex-direction: column; gap: 8px; margin-top: 4px; }
.rs-btn { height: 50px; font-size: 15px; }
.rs-btn-sm { height: 40px; font-size: 13px; }
.rs-btn-disabled { opacity: .55; cursor: default; }
.rs-btn-friends {
  background: #5E8700;
  color: #fff;
  border: none;
}
</style>
