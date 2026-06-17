<script setup lang="ts">
definePageMeta({ tabbar: false })

interface FriendFrom {
  id: string
  name: string
  color_hex: string
}

interface NotifDto {
  id: string
  type: 'friend_request' | 'tire_alert' | 'info' | 'system'
  title: string
  body: string
  read: boolean
  created_at: string
  // friend_request only
  from?: FriendFrom
  friendship_id?: string
  // tire_alert / system
  data?: { ride_id?: string; pressure_bar?: number; session_at?: string } | null
}

const router = useRouter()
const retailerSheet = useRetailerSheet()
const toast = useToast()

const { data: notifs, refresh } = await useApiFetch<NotifDto[]>('/api/notifications', { key: 'notifications' })

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

async function acceptFriend(n: NotifDto) {
  const userId = n.from?.id
  if (!userId) return
  try {
    await $apiFetch(`/api/community/friends/${userId}`, { method: 'POST' })
    toast.show('Ami·e ajouté·e !', 'check')
    await refresh()
  } catch {
    toast.show('Erreur', 'close')
  }
}

async function rejectFriend(n: NotifDto) {
  const userId = n.from?.id
  if (!userId) return
  await $apiFetch(`/api/community/friends/${userId}`, { method: 'DELETE' })
  await refresh()
}

async function markRead(n: NotifDto) {
  if (n.read) return
  await $apiFetch(`/api/notifications/${n.id}/read`, { method: 'POST' }).catch(() => {})
  await refresh()
}

async function readAll() {
  await $apiFetch('/api/notifications/read-all', { method: 'POST' })
  await refresh()
}
</script>

<template>
  <div class="screen">
    <div class="screen-scroll">
      <AppHeader :on-back="() => router.push('/profile')">
        <template #right>
          <button v-if="unread > 0" class="mark-all-btn" @click="readAll">Tout lire</button>
          <span v-else style="width: 44px" />
        </template>
      </AppHeader>
      <!-- Titre manuel sous le header (même layout que les autres pages) -->
      <div class="pad" style="padding-top: 0; padding-bottom: 0">
        <div class="notif-page-title">
          Notifications
          <span v-if="unread > 0" class="notif-count-badge">{{ unread }}</span>
        </div>
      </div>

      <div class="pad">

        <!-- ── Demandes d'ami·e·s ──────────────────────── -->
        <div v-if="friendRequests.length" class="notif-section rise">
          <div class="notif-section-title">
            <span class="section-dot" style="background: var(--blue)" />
            Demandes d'ami·e·s
            <span class="section-count">{{ friendRequests.length }}</span>
          </div>

          <div class="notif-list">
            <div v-for="n in friendRequests" :key="n.id" class="notif-card friend-card">
              <NuxtLink :to="`/community/${n.from?.id}`" class="friend-card-link" @click.stop>
                <Avatar
                  :name="initials(n.from?.name ?? '?')"
                  :size="44"
                  :bg="n.from?.color_hex ?? '#27509B'"
                />
                <div class="notif-body">
                  <div class="notif-title">{{ n.from?.name }}</div>
                  <div class="notif-sub">{{ n.body }}</div>
                  <div class="notif-time">{{ timeAgo(n.created_at) }}</div>
                </div>
              </NuxtLink>
              <div class="friend-actions">
                <button class="friend-accept" @click="acceptFriend(n)">Accepter</button>
                <button class="friend-reject" @click="rejectFriend(n)">Refuser</button>
              </div>
            </div>
          </div>
        </div>

        <!-- ── Alertes & notifications système ────────── -->
        <div v-if="alertNotifs.length" class="notif-section rise" :class="friendRequests.length ? 'd1' : ''">
          <div class="notif-section-title">
            <span class="section-dot" style="background: var(--red)" />
            Alertes & informations
          </div>

          <div class="notif-list">
            <div
              v-for="n in alertNotifs"
              :key="n.id"
              class="notif-card alert-card"
              :class="{ unread: !n.read }"
              @click="markRead(n)"
            >
              <!-- Indicateur non-lu -->
              <div v-if="!n.read" class="unread-dot" />

              <div class="alert-icon-wrap" :class="n.type">
                <Icon v-if="n.type === 'tire_alert'" name="shield" :size="20" color="#fff" />
                <Icon v-else name="bell" :size="20" color="#fff" />
              </div>

              <div class="notif-body">
                <div class="notif-title">{{ n.title }}</div>
                <div class="notif-sub">{{ n.body }}</div>
                <div v-if="n.type === 'tire_alert' && n.data?.pressure_bar" class="alert-pressure">
                  {{ n.data.pressure_bar.toFixed(1).replace('.', ',') }} bar
                </div>
                <div class="notif-time">{{ timeAgo(n.created_at) }}</div>
              </div>

              <!-- Action rapide pour alertes pneus -->
              <button
                v-if="n.type === 'tire_alert'"
                class="retailer-btn"
                @click.stop="retailerSheet.open({ name: 'Réparation · revendeur Michelin' })"
              >
                <Icon name="loc" :size="14" color="var(--blue)" />
              </button>
            </div>
          </div>
        </div>

        <!-- ── État vide ──────────────────────────────── -->
        <div
          v-if="!friendRequests.length && !alertNotifs.length"
          class="empty-state rise"
        >
          <div class="empty-icon">✓</div>
          <div class="small" style="font-weight: 700; margin-top: 12px">Tout est à jour</div>
          <div class="tiny" style="margin-top: 4px; opacity: .6">Pas de nouvelles notifications</div>
        </div>

      </div>
    </div>
  </div>
</template>

<style scoped>
/* ── Header ──────────────────────────────────────────── */
.notif-page-title {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 27px;
  font-weight: 800;
  letter-spacing: -.02em;
  color: var(--ink);
  margin-bottom: 20px;
}
.notif-count-badge {
  min-width: 20px; height: 20px;
  border-radius: 99px;
  background: var(--red);
  color: #fff;
  font-size: 11px; font-weight: 800;
  display: flex; align-items: center; justify-content: center;
  padding: 0 6px;
}
.mark-all-btn {
  font-size: 13px; font-weight: 600;
  color: var(--blue);
  background: none; border: none;
  padding: 0; cursor: pointer;
}

/* ── Sections ────────────────────────────────────────── */
.notif-section { margin-bottom: 20px; }
.notif-section-title {
  display: flex;
  align-items: center;
  gap: 7px;
  font-size: 11px;
  font-weight: 700;
  letter-spacing: .08em;
  text-transform: uppercase;
  color: var(--mute);
  margin-bottom: 10px;
}
.section-dot {
  width: 7px; height: 7px;
  border-radius: 50%;
  flex-shrink: 0;
}
.section-count {
  min-width: 18px; height: 18px;
  border-radius: 99px;
  background: var(--blue);
  color: #fff;
  font-size: 10px; font-weight: 800;
  display: flex; align-items: center; justify-content: center;
  padding: 0 5px;
}

/* ── Cards ───────────────────────────────────────────── */
.notif-list { display: flex; flex-direction: column; gap: 8px; }

.notif-card {
  position: relative;
  border-radius: 18px;
  background: #fff;
  border: 1.5px solid #E5E5E5;
  overflow: hidden;
  /* force light-mode vars so text is readable on white bg in dark screen context */
  --ink:      #1A1A1A;
  --ink-2:    #404040;
  --ink-3:    #53565A;
  --mute:     #999999;
  --line:     #E5E5E5;
  --surface-2: #F2F2F2;
}

/* Friend card */
.friend-card-link {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 14px 14px 10px;
  text-decoration: none;
}
.friend-actions {
  display: flex;
  gap: 8px;
  padding: 0 14px 12px;
}
.friend-accept {
  flex: 1;
  height: 36px;
  border-radius: 10px;
  border: none;
  background: var(--blue);
  color: #fff;
  font-size: 13px; font-weight: 700;
  cursor: pointer;
  transition: opacity .15s;
}
.friend-accept:active { opacity: .8; }
.friend-reject {
  flex: 1;
  height: 36px;
  border-radius: 10px;
  border: 1.5px solid var(--line);
  background: transparent;
  color: var(--mute);
  font-size: 13px; font-weight: 600;
  cursor: pointer;
}

/* Alert card */
.alert-card {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  padding: 14px;
  cursor: pointer;
  transition: opacity .15s;
}
.alert-card:active { opacity: .8; }
.alert-card.unread {
  background: color-mix(in srgb, var(--blue) 4%, var(--card));
}

.unread-dot {
  position: absolute;
  top: 14px; left: 14px;
  width: 7px; height: 7px;
  border-radius: 50%;
  background: var(--blue);
}
.alert-card.unread .alert-icon-wrap { margin-left: 15px; }

.alert-icon-wrap {
  width: 44px; height: 44px;
  border-radius: 13px;
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
  background: var(--blue);
}
.alert-icon-wrap.tire_alert { background: var(--red); }
.alert-icon-wrap.system     { background: var(--ink-2); }

/* Body */
.notif-body { flex: 1; min-width: 0; }
.notif-title {
  font-size: 14px; font-weight: 700;
  color: var(--ink);
  line-height: 1.2;
}
.notif-sub {
  font-size: 12px;
  color: var(--mute);
  margin-top: 3px;
  line-height: 1.4;
}
.alert-pressure {
  display: inline-block;
  margin-top: 5px;
  font-size: 11px; font-weight: 700;
  color: var(--red);
  background: color-mix(in srgb, var(--red) 10%, transparent);
  padding: 2px 8px;
  border-radius: 99px;
}
.notif-time {
  font-size: 11px;
  color: var(--mute);
  margin-top: 5px;
}

/* Retailer quick button */
.retailer-btn {
  width: 34px; height: 34px;
  border-radius: 10px;
  border: 1.5px solid var(--line);
  background: var(--surface-2);
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
  cursor: pointer;
  align-self: center;
}

/* ── Empty state ─────────────────────────────────────── */
.empty-state {
  text-align: center;
  padding: 60px 20px 40px;
}
.empty-icon {
  width: 64px; height: 64px;
  border-radius: 50%;
  background: rgba(132,189,0,.2);
  color: #84BD00;
  font-size: 28px; font-weight: 800;
  display: flex; align-items: center; justify-content: center;
  margin: 0 auto;
}
</style>
