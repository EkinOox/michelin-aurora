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

const { data: notifs, refresh } = await useApiFetch<NotifDto[]>('/api/notifications', {
  key: 'notif-sheet-list',
  default: () => [],
})

const friendRequests = computed(() => (notifs.value ?? []).filter(n => n.type === 'friend_request'))
const alertNotifs    = computed(() => (notifs.value ?? []).filter(n => n.type !== 'friend_request'))
const unread         = computed(() => (notifs.value ?? []).filter(n => !n.read).length)

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

async function markRead(n: NotifDto) {
  if (n.read || n.type === 'friend_request') return
  await $apiFetch(`/api/notifications/${n.id}/read`, { method: 'POST' }).catch(() => {})
  await refresh()
}

async function readAll() {
  await $apiFetch('/api/notifications/read-all', { method: 'POST' })
  await refresh()
}

watch(isOpen, async (v) => {
  if (v) await refresh()
})
</script>

<template>
  <Sheet :open="isOpen" height="85dvh" @close="close">
    <!-- En-tête -->
    <div class="ns-header">
      <div class="ns-title">
        Notifications
        <span v-if="unread > 0" class="ns-badge">{{ unread }}</span>
      </div>
      <button v-if="unread > 0" class="ns-read-all" @click="readAll">Tout lire</button>
    </div>

    <div class="ns-scroll">

      <!-- Demandes d'ami·e·s -->
      <div v-if="friendRequests.length" class="ns-section">
        <div class="ns-section-label">
          <span class="ns-dot" style="background: var(--blue)" />
          Demandes d'ami·e·s
          <span class="ns-count">{{ friendRequests.length }}</span>
        </div>

        <div class="ns-card" v-for="n in friendRequests" :key="n.id">
          <Avatar :name="initials(n.from?.name ?? '?')" :size="42" :bg="n.from?.color_hex ?? '#27509B'" />
          <div class="ns-body">
            <div class="ns-notif-title">{{ n.from?.name }}</div>
            <div class="ns-notif-sub">{{ n.body }}</div>
            <div class="ns-notif-time">{{ timeAgo(n.created_at) }}</div>
          </div>
          <div class="ns-friend-actions">
            <button class="ns-accept" @click="accept(n)">Accepter</button>
            <button class="ns-reject" @click="reject(n)">Refuser</button>
          </div>
        </div>
      </div>

      <!-- Alertes & infos -->
      <div v-if="alertNotifs.length" class="ns-section">
        <div class="ns-section-label">
          <span class="ns-dot" style="background: var(--red)" />
          Alertes & informations
        </div>

        <div
          v-for="n in alertNotifs"
          :key="n.id"
          class="ns-card ns-alert-card"
          :class="{ 'is-unread': !n.read }"
          @click="markRead(n)"
        >
          <div v-if="!n.read" class="ns-unread-dot" />
          <div class="ns-alert-icon" :class="n.type">
            <Icon v-if="n.type === 'tire_alert'" name="shield" :size="18" color="#fff" />
            <Icon v-else name="bell" :size="18" color="#fff" />
          </div>
          <div class="ns-body" :style="!n.read ? { paddingLeft: '12px' } : {}">
            <div class="ns-notif-title">{{ n.title }}</div>
            <div class="ns-notif-sub">{{ n.body }}</div>
            <div v-if="n.type === 'tire_alert' && n.data?.pressure_bar" class="ns-pressure">
              {{ n.data.pressure_bar.toFixed(1).replace('.', ',') }} bar
            </div>
            <div class="ns-notif-time">{{ timeAgo(n.created_at) }}</div>
          </div>
          <button
            v-if="n.type === 'tire_alert'"
            class="ns-retailer-btn"
            @click.stop="retailerSheet.open({ name: 'Réparation · revendeur Michelin' })"
          >
            <Icon name="loc" :size="14" color="var(--blue)" />
          </button>
        </div>
      </div>

      <!-- État vide -->
      <div v-if="!friendRequests.length && !alertNotifs.length" class="ns-empty">
        <div class="ns-empty-icon">✓</div>
        <div class="small" style="font-weight: 700; margin-top: 12px; color: var(--ink)">Tout est à jour</div>
        <div class="tiny" style="margin-top: 4px">Pas de nouvelles notifications</div>
      </div>

    </div>
  </Sheet>
</template>

<style scoped>
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
  min-width: 20px; height: 20px;
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

.ns-scroll {
  overflow-y: auto;
  max-height: calc(85dvh - 80px);
  padding-bottom: 12px;
}

.ns-section { margin-bottom: 18px; }
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
.ns-count {
  min-width: 18px; height: 18px;
  border-radius: 99px;
  background: var(--blue);
  color: #fff;
  font-size: 10px; font-weight: 800;
  display: flex; align-items: center; justify-content: center;
  padding: 0 5px;
}

.ns-card {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 13px;
  border-radius: 16px;
  background: var(--surface-2);
  margin-bottom: 8px;
  position: relative;
}

/* Alert card */
.ns-alert-card { cursor: pointer; transition: opacity .15s; }
.ns-alert-card:active { opacity: .8; }
.ns-alert-card.is-unread { background: color-mix(in srgb, var(--blue) 7%, var(--surface-2)); }
.ns-unread-dot {
  position: absolute;
  top: 13px; left: 13px;
  width: 7px; height: 7px;
  border-radius: 50%;
  background: var(--blue);
}
.ns-alert-icon {
  width: 40px; height: 40px;
  border-radius: 12px;
  background: var(--blue);
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
}
.ns-alert-icon.tire_alert { background: var(--red); }
.ns-alert-icon.system     { background: var(--mute); }

.ns-body { flex: 1; min-width: 0; }
.ns-notif-title { font-size: 13px; font-weight: 700; color: var(--ink); line-height: 1.2; }
.ns-notif-sub   { font-size: 12px; color: var(--mute); margin-top: 2px; line-height: 1.4; }
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

.ns-friend-actions { display: flex; flex-direction: column; gap: 6px; flex-shrink: 0; }
.ns-accept {
  padding: 7px 14px;
  border-radius: 10px;
  border: none;
  background: var(--blue);
  color: #fff;
  font-size: 12px; font-weight: 700;
  cursor: pointer;
}
.ns-reject {
  padding: 7px 14px;
  border-radius: 10px;
  border: 1.5px solid var(--line);
  background: transparent;
  color: var(--mute);
  font-size: 12px; font-weight: 600;
  cursor: pointer;
}
.ns-retailer-btn {
  width: 32px; height: 32px;
  border-radius: 9px;
  border: 1.5px solid var(--line);
  background: var(--card);
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
  cursor: pointer;
}

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
