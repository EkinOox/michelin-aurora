<script setup lang="ts">
definePageMeta({ tabbar: true })

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
}

interface StatsDto {
  total_riders: number
  my_rank: number
  my_points: number
  avg_points: number
  same_discipline_count: number
  my_bike_type: string | null
  my_total_km?: number
  my_rides_count?: number
  avg_km?: number
}

const RANK_LABEL: Record<string, string> = {
  explorer: 'Explorer', rider: 'Rider', performer: 'Performer',
  elite_cyclist: 'Elite Cyclist', michelin_ambassador: 'Michelin Ambassador',
}
const BIKE_LABEL: Record<string, string> = { route: 'Route', gravel: 'Gravel', vtt: 'VTT', vae: 'VAE' }
const FILTER_LABELS = ['Tous', 'Ma discipline', 'Ami·e·s'] as const
type Filter = typeof FILTER_LABELS[number]

const apiBase = useApiBase()
const router = useRouter()
const riderSheet = useRiderSheet()

const [{ data: riders, refresh }, { data: stats }] = await Promise.all([
  useApiFetch<RiderDto[]>('/api/community/users', { key: 'community-users', default: () => [] }),
  useApiFetch<StatsDto>('/api/community/stats', { key: 'community-stats' }),
])

const filter = ref<Filter>('Tous')
const search = ref('')

const pendingReceived = computed(() =>
  (riders.value ?? []).filter(r => r.friendship_status === 'pending_received')
)

const filtered = computed(() => {
  let list = riders.value ?? []

  if (filter.value === 'Ma discipline') {
    list = list.filter(r => r.profile?.bike_type === stats.value?.my_bike_type)
  } else if (filter.value === 'Ami·e·s') {
    list = list.filter(r => r.friendship_status === 'friends')
  }

  const q = search.value.trim().toLowerCase()
  if (q) {
    list = list.filter(r => r.name.toLowerCase().includes(q))
  }

  return list
})

const topPct = computed(() => {
  const s = stats.value
  if (!s || s.total_riders < 2) return null
  return Math.round((s.my_rank / s.total_riders) * 100)
})

function photoUrl(url: string | null): string | null {
  if (!url) return null
  return url.startsWith('/') ? apiBase + url : url
}

const initials = (name: string) =>
  name.split(' ').map(p => p[0]).join('').slice(0, 2).toUpperCase()

const friendLabel: Record<string, string> = {
  none: '+ Ajouter',
  pending_sent: 'En attente',
  pending_received: 'Accepter',
  friends: 'Ami·e ✓',
}
const friendCls: Record<string, string> = {
  none: 'friend-btn friend-none',
  pending_sent: 'friend-btn friend-wait',
  pending_received: 'friend-btn friend-accept',
  friends: 'friend-btn friend-ok',
}

async function toggleFriend(rider: RiderDto, e: Event) {
  e.preventDefault()
  e.stopPropagation()
  if (rider.friendship_status === 'friends') {
    await $apiFetch(`/api/community/friends/${rider.id}`, { method: 'DELETE' })
  } else {
    await $apiFetch(`/api/community/friends/${rider.id}`, { method: 'POST' })
  }
  await refresh()
}

async function rejectFriend(rider: RiderDto, e: Event) {
  e.preventDefault()
  e.stopPropagation()
  await $apiFetch(`/api/community/friends/${rider.id}`, { method: 'DELETE' })
  await refresh()
}
</script>

<template>
  <div class="screen">
    <div class="screen-scroll">
      <AppHeader title="Riders" :on-back="() => router.push('/home')" />

      <div class="pad">

        <!-- Demandes reçues -->
        <div v-if="pendingReceived.length" class="pending-alert rise">
          <div class="pending-alert-title">
            <span class="pending-icon">🔔</span>
            {{ pendingReceived.length }} demande{{ pendingReceived.length > 1 ? 's' : '' }} reçue{{ pendingReceived.length > 1 ? 's' : '' }}
          </div>
          <div
            v-for="r in pendingReceived"
            :key="r.id"
            class="pending-row"
          >
            <div class="pending-avatar">
              <img v-if="photoUrl(r.bike_photo_url)" :src="photoUrl(r.bike_photo_url)!" alt="" />
              <Avatar v-else :name="initials(r.name)" :size="36" :bg="r.color_hex" />
            </div>
            <div class="pending-name">{{ r.name }}</div>
            <button class="pending-accept" @click="toggleFriend(r, $event)">Accepter</button>
            <button class="pending-reject" @click="rejectFriend(r, $event)">×</button>
          </div>
        </div>

        <!-- Stats hero -->
        <div v-if="stats" class="stats-hero rise">
          <div class="stats-hero-label">Ta position dans la communauté</div>
          <div class="stats-grid">
            <div class="stat-block">
              <div class="stat-val">{{ stats.total_riders }}</div>
              <div class="stat-lbl">riders</div>
            </div>
            <div class="stat-sep" />
            <div class="stat-block">
              <div class="stat-val">#{{ stats.my_rank }}</div>
              <div class="stat-lbl">classement</div>
            </div>
            <div class="stat-sep" />
            <div class="stat-block">
              <div
                class="stat-val"
                :class="stats.my_points >= stats.avg_points ? 'stat-green' : 'stat-yellow'"
              >
                {{ stats.my_points >= stats.avg_points ? '+' : '' }}{{ (stats.my_points - stats.avg_points).toLocaleString('fr') }}
              </div>
              <div class="stat-lbl">vs moy. ({{ stats.avg_points.toLocaleString('fr') }} pts)</div>
            </div>
            <div class="stat-sep" />
            <div class="stat-block">
              <div class="stat-val">{{ stats.same_discipline_count }}</div>
              <div class="stat-lbl">{{ BIKE_LABEL[stats.my_bike_type ?? ''] ?? stats.my_bike_type ?? '—' }}</div>
            </div>
          </div>

          <!-- 2e ligne : km + sorties -->
          <div v-if="stats.my_total_km !== undefined || stats.my_rides_count !== undefined" class="stats-km-row">
            <div v-if="stats.my_total_km !== undefined" class="stats-km-block">
              <span class="stats-km-val">{{ stats.my_total_km.toLocaleString('fr', { maximumFractionDigits: 0 }) }}</span>
              <span class="stats-km-unit"> km</span>
              <div class="stats-km-lbl">parcourus</div>
            </div>
            <div class="stats-km-sep" />
            <div v-if="stats.my_rides_count !== undefined" class="stats-km-block">
              <span class="stats-km-val">{{ stats.my_rides_count }}</span>
              <div class="stats-km-lbl">sorties</div>
            </div>
          </div>

          <div v-if="topPct !== null" class="stats-bar-wrap">
            <div class="stats-bar-track">
              <div class="stats-bar-fill" :style="{ width: `${100 - topPct}%` }" />
            </div>
            <span class="stats-bar-label">Top {{ topPct }}%</span>
          </div>
        </div>

        <!-- Filtres + recherche -->
        <div class="filters-wrap rise d1" style="--ink: #1A1A1A; --ink-2: #404040; --mute: #999999; --line: #E5E5E5; --card: #fff; --surface-2: #F2F2F2">
          <div class="filters">
            <button
              v-for="f in FILTER_LABELS"
              :key="f"
              class="filter-pill"
              :class="{ active: filter === f }"
              @click="filter = f"
            >
              {{ f }}
              <span v-if="f === 'Ami·e·s'" class="filter-count">
                {{ (riders ?? []).filter(r => r.friendship_status === 'friends').length }}
              </span>
            </button>
          </div>
          <div class="search-wrap">
            <span class="search-icon">⌕</span>
            <input
              v-model="search"
              class="search-input"
              type="search"
              placeholder="Rechercher un rider…"
            />
          </div>
        </div>

        <!-- Liste -->
        <div class="rise d2">
          <div v-if="!filtered.length" class="card" style="padding: 24px; text-align: center; margin-top: 4px">
            <div class="small" style="color: var(--mute)">
              {{ filter === 'Ami·e·s' ? 'Pas encore d\'ami·e·s. Ajoutes-en !' : 'Aucun rider pour l\'instant.' }}
            </div>
          </div>

          <div
            v-for="r in filtered"
            :key="r.id"
            class="community-row card"
            @click="riderSheet.open(r.id)"
          >
            <!-- Avatar / photo vélo -->
            <div class="c-avatar">
              <img v-if="photoUrl(r.bike_photo_url)" :src="photoUrl(r.bike_photo_url)!" alt="" />
              <Avatar v-else :name="initials(r.name)" :size="44" :bg="r.color_hex" />
            </div>

            <!-- Infos -->
            <div class="c-body">
              <div class="c-name">{{ r.name }}</div>
              <div class="c-sub">
                {{ RANK_LABEL[r.rewards_level] ?? r.rewards_level }}
                <span v-if="r.profile"> · {{ BIKE_LABEL[r.profile.bike_type] ?? r.profile.bike_type }}</span>
                <span v-if="r.city"> · {{ r.city }}</span>
              </div>
              <div class="c-pts">{{ r.total_points.toLocaleString('fr') }} pts</div>
              <div v-if="r.total_km !== undefined || r.rides_count !== undefined" class="c-rides">
                <span v-if="r.total_km !== undefined">{{ r.total_km.toLocaleString('fr', { maximumFractionDigits: 0 }) }} km</span>
                <span v-if="r.total_km !== undefined && r.rides_count !== undefined"> · </span>
                <span v-if="r.rides_count !== undefined">{{ r.rides_count }} sorties</span>
              </div>
            </div>

            <!-- Bouton ami -->
            <button :class="friendCls[r.friendship_status]" @click.stop="toggleFriend(r, $event)">
              {{ friendLabel[r.friendship_status] }}
            </button>
          </div>
        </div>

      </div>
    </div>
  </div>
</template>

<style scoped>
/* ── Demandes reçues ─────────────────────────────────── */
.pending-alert {
  background: linear-gradient(135deg, #fff8e1 0%, #fff3cd 100%);
  border: 1.5px solid #f5c842;
  border-radius: 18px;
  padding: 14px 14px 10px;
  margin-bottom: 14px;
}
.pending-alert-title {
  font-size: 12px;
  font-weight: 700;
  color: #7a5c00;
  letter-spacing: .05em;
  text-transform: uppercase;
  margin-bottom: 10px;
  display: flex;
  align-items: center;
  gap: 6px;
}
.pending-icon { font-size: 14px; }
.pending-row {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 6px 0;
}
.pending-row + .pending-row {
  border-top: 1px solid rgba(245,200,66,.3);
}
.pending-avatar {
  width: 36px; height: 36px;
  border-radius: 10px;
  overflow: hidden;
  flex-shrink: 0;
  background: var(--surface-2);
}
.pending-avatar img { width: 100%; height: 100%; object-fit: cover; display: block; }
.pending-name {
  flex: 1;
  font-size: 13px;
  font-weight: 600;
  color: #5a3e00;
  min-width: 0;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.pending-accept {
  flex-shrink: 0;
  font-size: 12px;
  font-weight: 700;
  padding: 6px 12px;
  border-radius: 20px;
  border: none;
  background: #f5c842;
  color: #5a3e00;
  cursor: pointer;
}
.pending-reject {
  flex-shrink: 0;
  font-size: 16px;
  font-weight: 700;
  width: 28px; height: 28px;
  border-radius: 50%;
  border: 1.5px solid rgba(122,92,0,.3);
  background: transparent;
  color: #7a5c00;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  line-height: 1;
}

/* ── Stats hero ─────────────────────────────────────── */
.stats-hero {
  background: linear-gradient(145deg, #27509B 0%, #1b3873 100%);
  border-radius: 22px;
  padding: 18px 18px 14px;
  margin-bottom: 14px;
}
.stats-hero-label {
  font-size: 10.5px;
  font-weight: 700;
  letter-spacing: .12em;
  text-transform: uppercase;
  color: rgba(255,255,255,.55);
  margin-bottom: 14px;
}
.stats-grid {
  display: flex;
  align-items: center;
  gap: 0;
}
.stat-block {
  flex: 1;
  text-align: center;
}
.stat-val {
  font-size: 20px;
  font-weight: 800;
  color: #fff;
  line-height: 1;
  letter-spacing: -.02em;
}
.stat-lbl {
  font-size: 10px;
  color: rgba(255,255,255,.6);
  margin-top: 3px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.stat-sep {
  width: 1px;
  height: 36px;
  background: rgba(255,255,255,.15);
  flex-shrink: 0;
}
.stat-green { color: #84BD00 !important; }
.stat-yellow { color: #FCE500 !important; }

.stats-km-row {
  display: flex;
  align-items: center;
  margin-top: 14px;
  padding-top: 12px;
  border-top: 1px solid rgba(255,255,255,.12);
}
.stats-km-block {
  flex: 1;
  text-align: center;
}
.stats-km-val {
  font-size: 17px;
  font-weight: 800;
  color: #84BD00;
}
.stats-km-unit {
  font-size: 12px;
  font-weight: 600;
  color: rgba(255,255,255,.6);
}
.stats-km-lbl {
  font-size: 10px;
  color: rgba(255,255,255,.5);
  margin-top: 2px;
}
.stats-km-sep {
  width: 1px;
  height: 30px;
  background: rgba(255,255,255,.12);
  flex-shrink: 0;
}

.stats-bar-wrap {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-top: 14px;
}
.stats-bar-track {
  flex: 1;
  height: 5px;
  border-radius: 99px;
  background: rgba(255,255,255,.15);
  overflow: hidden;
}
.stats-bar-fill {
  height: 100%;
  border-radius: 99px;
  background: #84BD00;
  transition: width .6s ease;
}
.stats-bar-label {
  font-size: 11px;
  font-weight: 700;
  color: #84BD00;
  white-space: nowrap;
}

/* ── Filtres + recherche ─────────────────────────────── */
.filters-wrap {
  margin-bottom: 14px;
  display: flex;
  flex-direction: column;
  gap: 10px;
}
.filters {
  display: flex;
  gap: 8px;
}
.filter-pill {
  display: flex;
  align-items: center;
  gap: 5px;
  padding: 7px 14px;
  border-radius: 99px;
  font-size: 13px;
  font-weight: 600;
  border: 1.5px solid var(--line);
  background: var(--card);
  color: var(--mute);
  cursor: pointer;
  transition: all .15s ease;
  white-space: nowrap;
}
.filter-pill.active {
  background: #1A1A1A;
  border-color: #1A1A1A;
  color: #fff;
}
.filter-count {
  font-size: 11px;
  font-weight: 700;
  background: rgba(255,255,255,.2);
  border-radius: 99px;
  padding: 1px 6px;
  min-width: 18px;
  text-align: center;
}

.search-wrap {
  position: relative;
  display: flex;
  align-items: center;
}
.search-icon {
  position: absolute;
  left: 12px;
  font-size: 16px;
  color: var(--mute);
  pointer-events: none;
}
.search-input {
  width: 100%;
  padding: 9px 12px 9px 34px;
  border-radius: 12px;
  border: 1.5px solid var(--line);
  background: var(--card);
  color: var(--ink);
  font-size: 13px;
  font-weight: 500;
  outline: none;
  transition: border-color .15s ease;
}
.search-input:focus { border-color: var(--blue); }
.search-input::placeholder { color: var(--mute); }

/* ── Liste riders ────────────────────────────────────── */
.community-row {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px;
  margin-bottom: 10px;
  text-decoration: none;
  width: 100%;
  cursor: pointer;
  transition: opacity .15s;
}
.community-row:active { opacity: .8; }

.c-avatar {
  flex-shrink: 0;
  width: 46px;
  height: 46px;
  border-radius: 13px;
  overflow: hidden;
  background: var(--surface-2);
}
.c-avatar img { width: 100%; height: 100%; object-fit: cover; display: block; }

.c-body { flex: 1; min-width: 0; }
.c-name { font-size: 14px; font-weight: 700; color: var(--ink); }
.c-sub  { font-size: 11.5px; color: var(--mute); margin-top: 1px; }
.c-pts  { font-size: 11px; color: var(--lime-600); font-weight: 600; margin-top: 1px; }
.c-rides { font-size: 10.5px; color: var(--mute); margin-top: 1px; }

/* Bouton ami */
.friend-btn {
  flex-shrink: 0;
  font-size: 11.5px;
  font-weight: 700;
  padding: 6px 11px;
  border-radius: 20px;
  border: none;
  cursor: pointer;
  white-space: nowrap;
  transition: opacity .15s ease;
}
.friend-btn:active { opacity: .75; }
.friend-none   { background: var(--blue);       color: #fff; }
.friend-wait   { background: var(--surface-2);  color: var(--mute); }
.friend-accept { background: var(--lime-600);   color: #fff; }
.friend-ok     { background: var(--surface-2);  color: var(--lime-600); }
</style>
