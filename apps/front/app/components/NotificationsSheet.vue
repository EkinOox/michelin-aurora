<script setup lang="ts">
interface FriendFrom { id: string; name: string; color_hex: string }
interface NotifDto {
  id: string
  type: 'friend_request' | 'tire_alert' | 'info' | 'system'
  title: string
  body: string
  read: boolean
  created_at: string
  from?: FriendFrom
  data?: { ride_id?: string; pressure_bar?: number } | null
}

const { isOpen, close } = useNotificationsSheet()
const retailerSheet = useRetailerSheet()
const toast = useToast()

// immediate: false → only fetches when explicitly refresh()-ed
const { data: notifs, refresh, status } = useApiFetch<NotifDto[]>('/api/notifications', {
  key: 'notif-sheet-list',
  default: () => [] as NotifDto[],
  immediate: false,
})

const friendRequests = computed(() => (notifs.value ?? []).filter(n => n.type === 'friend_request'))
const alertNotifs    = computed(() => (notifs.value ?? []).filter(n => n.type !== 'friend_request'))
const unread         = computed(() => (notifs.value ?? []).filter(n => !n.read).length)
const loading        = computed(() => status.value === 'pending')

function timeAgo(iso: string): string {
  const diff = Date.now() - new Date(iso).getTime()
  const min  = Math.floor(diff / 60000)
  if (min < 1)  return 'à l\'instant'
  if (min < 60) return `il y a ${min} min`
  const h = Math.floor(min / 60)
  if (h < 24)   return `il y a ${h} h`
  return `il y a ${Math.floor(h / 24)} j`
}

const initials = (name: string) =>
  name.split(' ').map(p => p[0]).join('').slice(0, 2).toUpperCase()

async function accept(n: NotifDto) {
  if (!n.from?.id) return
  await $apiFetch(`/api/community/friends/${n.from.id}`, { method: 'POST' })
  toast.show('Ami·e ajouté·e !', 'check')
  await refresh()
}

async function reject(n: NotifDto) {
  if (!n.from?.id) return
  await $apiFetch(`/api/community/friends/${n.from.id}`, { method: 'DELETE' })
  await refresh()
}

async function remove(n: NotifDto) {
  await $apiFetch(`/api/notifications/${n.id}`, { method: 'DELETE' }).catch(() => {})
  await refresh()
}

async function markRead(n: NotifDto) {
  if (n.read || n.type === 'friend_request') return
  await $apiFetch(`/api/notifications/${n.id}/read`, { method: 'POST' }).catch(() => {})
  await refresh()
}

async function readAll() {
  await $apiFetch('/api/notifications/read-all', { method: 'POST' })
  await refresh()
}

// Fetch each time the sheet opens
watch(isOpen, (v) => {
  if (v) refresh()
}, { immediate: true })
</script>

<template>
  <Sheet :open="isOpen" @close="close">

    <!-- En-tête -->
    <div class="ns-header">
      <div class="ns-title">
        Notifications
        <span v-if="unread > 0" class="ns-badge">{{ unread }}</span>
      </div>
      <button v-if="unread > 0" class="ns-read-all" @click="readAll">Tout lire</button>
    </div>

    <!-- Loader -->
    <div v-if="loading && !notifs?.length" class="ns-loader">
      <div class="tiny" style="color: var(--mute)">Chargement…</div>
    </div>

    <div v-else class="ns-scroll">

      <!-- ── Demandes d'ami·e·s ──────────────────────────── -->
      <div v-if="friendRequests.length" class="ns-section">
        <div class="ns-section-label">
          <span class="ns-dot ns-dot-blue" />
          Demandes d'ami·e·s
          <span class="ns-count ns-count-blue">{{ friendRequests.length }}</span>
        </div>

        <div v-for="n in friendRequests" :key="n.id" class="ns-friend-card">
          <!-- Ligne 1 : avatar + nom + fermer -->
          <div class="ns-friend-top">
            <Avatar :name="initials(n.from?.name ?? '?')" :size="40" :bg="n.from?.color_hex ?? '#27509B'" />
            <div class="ns-body">
              <div class="ns-notif-title">{{ n.from?.name }}</div>
              <div class="ns-notif-sub">{{ n.body }}</div>
              <div class="ns-notif-time">{{ timeAgo(n.created_at) }}</div>
            </div>
            <button class="ns-delete-btn" title="Supprimer" @click="remove(n)">×</button>
          </div>
          <!-- Ligne 2 : boutons pleine largeur -->
          <div class="ns-friend-btns">
            <button class="ns-accept" @click="accept(n)">Accepter</button>
            <button class="ns-reject" @click="reject(n)">Refuser</button>
          </div>
        </div>
      </div>

      <!-- ── Alertes pneus ───────────────────────────────── -->
      <div v-if="alertNotifs.filter(n => n.type === 'tire_alert').length" class="ns-section">
        <div class="ns-section-label">
          <span class="ns-dot ns-dot-red" />
          Alertes pneus
        </div>
        <div
          v-for="n in alertNotifs.filter(n => n.type === 'tire_alert')"
          :key="n.id"
          class="ns-card ns-alert-card"
          :class="{ 'is-unread': !n.read }"
          @click="markRead(n)"
        >
          <div v-if="!n.read" class="ns-unread-dot" />
          <div class="ns-alert-icon ns-icon-red">
            <Icon name="shield" :size="18" color="#fff" />
          </div>
          <div class="ns-body">
            <div class="ns-notif-title">{{ n.title }}</div>
            <div class="ns-notif-sub">{{ n.body }}</div>
            <div v-if="n.data?.pressure_bar" class="ns-pressure">
              {{ n.data.pressure_bar.toFixed(1).replace('.', ',') }} bar
            </div>
            <div class="ns-notif-time">{{ timeAgo(n.created_at) }}</div>
          </div>
          <div class="ns-alert-actions">
            <button
              class="ns-retailer-btn"
              title="Trouver un revendeur"
              @click.stop="retailerSheet.open({ name: 'Réparation · revendeur Michelin' })"
            >
              <Icon name="loc" :size="14" color="var(--blue)" />
            </button>
            <button class="ns-delete-btn" title="Supprimer" @click.stop="remove(n)">×</button>
          </div>
        </div>
      </div>

      <!-- ── Infos & système ────────────────────────────── -->
      <div v-if="alertNotifs.filter(n => n.type !== 'tire_alert').length" class="ns-section">
        <div class="ns-section-label">
          <span class="ns-dot ns-dot-muted" />
          Informations
        </div>
        <div
          v-for="n in alertNotifs.filter(n => n.type !== 'tire_alert')"
          :key="n.id"
          class="ns-card ns-alert-card"
          :class="{ 'is-unread': !n.read }"
          @click="markRead(n)"
        >
          <div v-if="!n.read" class="ns-unread-dot" />
          <div class="ns-alert-icon ns-icon-muted">
            <Icon name="bell" :size="18" color="#fff" />
          </div>
          <div class="ns-body">
            <div class="ns-notif-title">{{ n.title }}</div>
            <div class="ns-notif-sub">{{ n.body }}</div>
            <div class="ns-notif-time">{{ timeAgo(n.created_at) }}</div>
          </div>
          <button class="ns-delete-btn" title="Supprimer" @click.stop="remove(n)">×</button>
        </div>
      </div>

      <!-- ── État vide ─────────────────────────────────── -->
      <div v-if="!friendRequests.length && !alertNotifs.length" class="ns-empty">
        <div class="ns-empty-icon">✓</div>
        <div class="small" style="font-weight: 700; margin-top: 12px; color: var(--ink)">Tout est à jour</div>
        <div class="tiny" style="margin-top: 4px; color: var(--mute)">Pas de nouvelles notifications</div>
      </div>

    </div>
  </Sheet>
</template>

<style scoped>
/* ── Header ─────────────────────────────────────────── */
.ns-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 16px;
}
.ns-title {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 19px;
  font-weight: 800;
  letter-spacing: -.02em;
  color: var(--ink);
}
.ns-badge {
  min-width: 22px; height: 22px;
  border-radius: 99px;
  background: var(--red);
  color: #fff;
  font-size: 11px; font-weight: 800;
  display: flex; align-items: center; justify-content: center;
  padding: 0 6px;
}
.ns-read-all {
  font-size: 13px; font-weight: 600;
  color: var(--blue);
  background: none; border: none;
  padding: 0; cursor: pointer;
}

/* ── Loader ─────────────────────────────────────────── */
.ns-loader {
  display: flex; align-items: center; justify-content: center;
  height: 120px;
}

/* ── Scroll container ───────────────────────────────── */
.ns-scroll {
  overflow-x: hidden;
  padding-bottom: 20px;
  width: 100%;
  box-sizing: border-box;
}

/* ── Section ────────────────────────────────────────── */
.ns-section { margin-bottom: 20px; }
.ns-section-label {
  display: flex;
  align-items: center;
  gap: 7px;
  font-size: 10.5px;
  font-weight: 700;
  letter-spacing: .1em;
  text-transform: uppercase;
  color: var(--mute);
  margin-bottom: 10px;
}
.ns-dot {
  width: 7px; height: 7px;
  border-radius: 50%;
  flex-shrink: 0;
}
.ns-dot-blue  { background: var(--blue); }
.ns-dot-red   { background: var(--red); }
.ns-dot-muted { background: var(--mute); }

.ns-count {
  min-width: 18px; height: 18px;
  border-radius: 99px;
  font-size: 10px; font-weight: 800;
  display: flex; align-items: center; justify-content: center;
  padding: 0 5px;
  color: #fff;
}
.ns-count-blue { background: var(--blue); }

/* ── Friend card (two-row layout) ───────────────────── */
.ns-friend-card {
  padding: 12px;
  border-radius: 16px;
  background: var(--surface-2);
  margin-bottom: 8px;
  width: 100%;
  box-sizing: border-box;
}
.ns-friend-top {
  display: flex;
  align-items: center;
  gap: 10px;
  min-width: 0;
}
.ns-friend-btns {
  display: flex;
  gap: 8px;
  margin-top: 10px;
}
.ns-friend-btns .ns-accept,
.ns-friend-btns .ns-reject { flex: 1; }

/* ── Alert / info card ──────────────────────────────── */
.ns-card {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 12px;
  border-radius: 16px;
  background: var(--surface-2);
  margin-bottom: 8px;
  position: relative;
  width: 100%;
  box-sizing: border-box;
  min-width: 0;
}

/* Alert cards */
.ns-alert-card { cursor: pointer; transition: opacity .15s; }
.ns-alert-card:active { opacity: .8; }
.ns-alert-card.is-unread { background: color-mix(in srgb, var(--blue) 6%, var(--surface-2)); }
.ns-unread-dot {
  position: absolute;
  top: 13px; left: 13px;
  width: 7px; height: 7px;
  border-radius: 50%;
  background: var(--blue);
}

/* Icon */
.ns-alert-icon {
  width: 40px; height: 40px;
  border-radius: 12px;
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
}
.ns-icon-red   { background: var(--red); }
.ns-icon-muted { background: var(--mute); }

/* Body */
.ns-body { flex: 1; min-width: 0; overflow: hidden; }
.ns-notif-title { font-size: 13px; font-weight: 700; color: var(--ink); line-height: 1.2; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.ns-notif-sub   { font-size: 12px; color: var(--mute); margin-top: 2px; line-height: 1.4; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; }
.ns-pressure {
  display: inline-block;
  margin-top: 4px;
  font-size: 11px; font-weight: 700;
  color: var(--red);
  background: color-mix(in srgb, var(--red) 10%, transparent);
  padding: 2px 8px;
  border-radius: 99px;
}
.ns-notif-time { font-size: 11px; color: var(--mute); margin-top: 4px; }

/* Friend action buttons (now in .ns-friend-btns row) */
.ns-accept {
  height: 38px;
  border-radius: 11px;
  border: none;
  background: var(--blue);
  color: #fff;
  font-size: 13px; font-weight: 700;
  cursor: pointer;
}
.ns-reject {
  height: 38px;
  border-radius: 11px;
  border: 1.5px solid var(--line);
  background: transparent;
  color: var(--mute);
  font-size: 13px; font-weight: 600;
  cursor: pointer;
}

/* Alert quick actions */
.ns-alert-actions { display: flex; flex-direction: column; gap: 6px; flex-shrink: 0; align-items: center; }
.ns-retailer-btn {
  width: 32px; height: 32px;
  border-radius: 9px;
  border: 1.5px solid var(--line);
  background: var(--surface);
  display: flex; align-items: center; justify-content: center;
  cursor: pointer;
}

/* Delete button */
.ns-delete-btn {
  width: 28px; height: 28px;
  border-radius: 8px;
  border: none;
  background: transparent;
  color: var(--mute);
  font-size: 18px; line-height: 1;
  display: flex; align-items: center; justify-content: center;
  cursor: pointer;
  flex-shrink: 0;
  transition: color .15s, background .15s;
}
.ns-delete-btn:hover { color: var(--red); background: color-mix(in srgb, var(--red) 10%, transparent); }

/* ── Empty ──────────────────────────────────────────── */
.ns-empty {
  text-align: center;
  padding: 40px 20px 20px;
}
.ns-empty-icon {
  width: 56px; height: 56px;
  border-radius: 50%;
  background: color-mix(in srgb, #84BD00 15%, transparent);
  color: #84BD00;
  font-size: 24px; font-weight: 800;
  display: flex; align-items: center; justify-content: center;
  margin: 0 auto;
}
</style>
