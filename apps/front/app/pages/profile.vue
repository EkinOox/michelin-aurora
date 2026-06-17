<script setup lang="ts">
definePageMeta({ tabbar: true })

interface ProfileDto {
  id: string
  name: string
  city: string
  rewards_level: string
  total_points: number
  profile: { bike_type: string, rider_level: string, usage_type: string, preferences: string[], bike_photo_url: string | null } | null
}

const RANK_LABEL: Record<string, string> = {
  explorer: 'Explorer',
  rider: 'Rider',
  performer: 'Performer',
  elite_cyclist: 'Elite Cyclist',
  michelin_ambassador: 'Michelin Ambassador',
}
const BIKE_LABEL: Record<string, string> = { route: 'Route', gravel: 'Gravel', vtt: 'VTT', vae: 'VAE' }
const LEVEL_LABEL: Record<string, string> = { beginner: 'Débutant', intermediate: 'Intermédiaire', advanced: 'Avancé', expert: 'Expert' }
const USAGE_LABEL: Record<string, string> = { leisure: 'Loisir', sport: 'Performance', competition: 'Compétition', commute: 'Transport' }

const RANKS = [
  { key: 'explorer',            label: 'Explorer',   short: 'EXP', min: 0 },
  { key: 'rider',               label: 'Rider',      short: 'RDR', min: 1000 },
  { key: 'performer',           label: 'Performer',  short: 'PRF', min: 2500 },
  { key: 'elite_cyclist',       label: 'Elite',      short: 'ELT', min: 4000 },
  { key: 'michelin_ambassador', label: 'Ambassador', short: 'AMB', min: 8000 },
]
const RANK_COLORS: Record<string, string> = {
  explorer:            '#8e9aaf',
  rider:               '#27509B',
  performer:           '#84BD00',
  elite_cyclist:       '#C4AA00',
  michelin_ambassador: '#E71D36',
}

const router = useRouter()
const toast = useToast()
const retailerSheet = useRetailerSheet()
const { logout: logoutAuth } = useAuth()
const apiBase = useApiBase()

const notifSheet = useNotificationsSheet()
const { frontStr, rearStr, isRain, loading: pressureLoading } = usePressure()
const { data: profile, refresh: refreshProfile } = await useApiFetch<ProfileDto>('/api/profile', { key: 'profile' })
const { data: notifCount } = await useApiFetch<{ unread: number }>('/api/notifications/count', { key: 'notif-count' })

const currentPhotoUrl = computed(() => {
  const url = profile.value?.profile?.bike_photo_url
  if (!url) return null
  return url.startsWith('http') ? url : apiBase + url
})

const uploading = ref(false)
const fileInput = ref<HTMLInputElement | null>(null)

async function onFileChange(e: Event) {
  const file = (e.target as HTMLInputElement).files?.[0]
  if (!file) return
  uploading.value = true
  try {
    const form = new FormData()
    form.append('photo', file)
    const res = await $apiFetch<{ url: string }>('/api/profile/bike-photo', { method: 'POST', body: form })
    if (res?.url) await refreshProfile()
    toast.show('Photo mise à jour', 'check')
  } catch {
    toast.show('Erreur lors de l\'upload', 'close')
  } finally {
    uploading.value = false
    if (fileInput.value) fileInput.value.value = ''
  }
}

async function removePhoto() {
  await $apiFetch('/api/profile', {
    method: 'PUT',
    body: {
      bike_type: profile.value?.profile?.bike_type ?? 'gravel',
      rider_level: profile.value?.profile?.rider_level ?? 'expert',
      usage_type: profile.value?.profile?.usage_type ?? 'sport',
      preferences: profile.value?.profile?.preferences ?? [],
      bike_photo_url: '',
    },
  })
  await refreshProfile()
}

const initials = computed(() => {
  const n = profile.value?.name ?? ''
  return n.split(' ').map(p => p[0]).join('').slice(0, 2).toUpperCase()
})

const curRankIdx = computed(() => Math.max(0, RANKS.findIndex(r => r.key === profile.value?.rewards_level)))
const nextRank   = computed(() => RANKS[curRankIdx.value + 1] ?? null)
const rankProg   = computed(() => {
  if (!profile.value) return 0
  const cur  = RANKS[curRankIdx.value]
  const span = nextRank.value ? nextRank.value.min - cur.min : 1
  return Math.min(1, Math.max(0, (profile.value.total_points - cur.min) / span))
})
const ptsToNext = computed(() =>
  nextRank.value ? Math.max(0, nextRank.value.min - (profile.value?.total_points ?? 0)) : 0
)
const rankColor = computed(() => RANK_COLORS[profile.value?.rewards_level ?? ''] ?? '#27509B')

function editProfile() { router.push('/onboarding') }
function recycleProgram() { retailerSheet.open({ name: 'Programme retour & recyclage pneus' }) }
function co2() { toast.show('142 kg CO₂ économisés', 'leaf') }
function logout() { logoutAuth(); router.push('/') }
</script>

<template>
  <div v-if="profile" class="screen">
    <div class="screen-scroll">

      <!-- Hero avec dégradé dynamique selon le rang -->
      <div class="profile-hero rise">
        <div class="profile-hero-bg" :style="{ background: `linear-gradient(150deg, ${rankColor}28 0%, ${rankColor}06 100%)` }" />
        <div class="profile-hero-content pad">
          <div class="profile-avatar-wrap">
            <Avatar :name="initials" :size="72" bg="var(--blue)" />
            <div class="profile-avatar-ring" :style="{ borderColor: rankColor }" />
          </div>
          <div class="profile-hero-text">
            <div class="h-md">{{ profile.name }}</div>
            <div class="tiny" style="color: var(--mute); margin-top: 3px">{{ profile.city }}</div>
          </div>
          <button class="notif-bell" :class="{ 'has-notif': (notifCount?.unread ?? 0) > 0 }" @click="notifSheet.open()">
            <Icon name="bell" :size="20" color="var(--ink-2)" />
            <span v-if="(notifCount?.unread ?? 0) > 0" class="notif-dot">{{ notifCount!.unread }}</span>
          </button>
        </div>
      </div>

      <div class="pad">

        <!-- ── Rewards card ─────────────────────────────── -->
        <NuxtLink to="/rewards" class="rewards-card card rise d1">
          <div class="rw-accent" :style="{ background: rankColor }" />
          <div class="rw-body">

            <!-- Rang + points -->
            <div class="rw-top">
              <div class="rw-icon-wrap" :style="{ background: `color-mix(in srgb, ${rankColor} 15%, transparent)` }">
                <Icon name="trophy" :size="22" :color="rankColor" />
              </div>
              <div class="rw-rank-block">
                <div class="rw-rank-name" :style="{ color: rankColor }">
                  {{ RANK_LABEL[profile.rewards_level] ?? profile.rewards_level }}
                </div>
                <div class="rw-pts-label">{{ profile.total_points.toLocaleString('fr') }} points</div>
              </div>
              <Icon name="chev" :size="16" color="var(--mute)" style="flex-shrink:0" />
            </div>

            <!-- Stepper 5 rangs -->
            <div class="rw-stepper">
              <template v-for="(r, i) in RANKS" :key="r.key">
                <div
                  class="rw-step"
                  :class="{ 'is-done': i < curRankIdx, 'is-active': i === curRankIdx }"
                  :style="i <= curRankIdx ? { background: RANK_COLORS[r.key], borderColor: RANK_COLORS[r.key] } : {}"
                >
                  <span class="rw-step-lbl">{{ r.short }}</span>
                </div>
                <div
                  v-if="i < RANKS.length - 1"
                  class="rw-connector"
                  :style="i < curRankIdx ? { background: rankColor } : {}"
                />
              </template>
            </div>

            <!-- Barre de progression -->
            <div class="rw-track">
              <div class="rw-fill" :style="{ width: `${rankProg * 100}%`, background: rankColor }" />
            </div>
            <div class="rw-next-label">
              <span v-if="nextRank">{{ ptsToNext.toLocaleString('fr') }} pts pour atteindre {{ nextRank.label }}</span>
              <span v-else style="color: var(--lime-600); font-weight: 700">🏆 Rang maximum atteint !</span>
            </div>

          </div>
        </NuxtLink>

        <!-- ── Profil cycliste ──────────────────────────── -->
        <div class="rise d2" style="margin-top: 20px">
          <div class="section-hd">
            <span class="eyebrow">Mon profil cycliste</span>
            <button class="section-edit" @click="editProfile">Modifier</button>
          </div>
          <div class="info-card card">
            <div class="info-row">
              <div class="info-icon" style="background: color-mix(in srgb, var(--lime-600) 12%, transparent)">
                <Icon name="bike" :size="16" color="var(--lime-600)" />
              </div>
              <span class="info-key">Vélo</span>
              <span class="info-val">{{ BIKE_LABEL[profile.profile?.bike_type ?? ''] ?? '—' }}</span>
            </div>
            <div class="info-row">
              <div class="info-icon" style="background: color-mix(in srgb, var(--blue) 12%, transparent)">
                <Icon name="target" :size="16" color="var(--blue)" />
              </div>
              <span class="info-key">Niveau</span>
              <span class="info-val">{{ LEVEL_LABEL[profile.profile?.rider_level ?? ''] ?? '—' }}</span>
            </div>
            <div class="info-row">
              <div class="info-icon" style="background: color-mix(in srgb, #b8860b 12%, transparent)">
                <Icon name="bolt" :size="16" color="#b8860b" />
              </div>
              <span class="info-key">Usage</span>
              <span class="info-val">{{ USAGE_LABEL[profile.profile?.usage_type ?? ''] ?? '—' }}</span>
            </div>
            <div class="info-row last">
              <div class="info-icon" style="background: color-mix(in srgb, var(--red) 10%, transparent)">
                <Icon name="loc" :size="16" color="var(--red)" />
              </div>
              <span class="info-key">Région</span>
              <span class="info-val">{{ profile.city ?? '—' }}</span>
            </div>
          </div>
        </div>

        <!-- ── Photo de mon vélo ────────────────────────── -->
        <div class="rise d3" style="margin-top: 20px">
          <div class="section-hd">
            <span class="eyebrow">Photo de mon vélo</span>
          </div>

          <div v-if="currentPhotoUrl" class="photo-preview">
            <img :src="currentPhotoUrl" alt="Mon vélo" />
            <button class="photo-remove" @click="removePhoto">
              <Icon name="close" :size="14" color="#fff" />
            </button>
          </div>
          <NuxtLink to="/pressure" class="card" style="padding: 14px 16px; margin-top: 12px; display: flex; align-items: center; gap: 14px">
            <Icon name="gauge" :size="22" color="var(--blue)" />
            <div style="flex: 1">
              <div class="tiny">Pression recommandée · <span :style="{ color: isRain ? 'var(--blue)' : 'var(--lime-600)' }">{{ isRain ? 'Pluie' : 'Sec' }}</span></div>
              <div v-if="pressureLoading" class="small" style="color: var(--mute); margin-top: 2px">Calcul en cours…</div>
              <div v-else class="row" style="gap: 12px; margin-top: 2px">
                <span class="num" style="font-size: 15px; font-weight: 700">Av {{ frontStr }} bar</span>
                <span style="color: var(--line)">|</span>
                <span class="num" style="font-size: 15px; font-weight: 700">Ar {{ rearStr }} bar</span>
              </div>
            </div>
            <Icon name="chev" :size="16" color="var(--mute)" />
          </NuxtLink>
          <button class="btn btn-ghost btn-block" style="height: 44px; font-size: 14px; margin-top: 10px" @click="editProfile">
            <Icon name="edit" :size="16" /> Modifier mon profil

          <input ref="fileInput" type="file" accept="image/*" style="display:none" @change="onFileChange" />
          <button
            class="photo-upload-btn"
            :class="{ loading: uploading }"
            :disabled="uploading"
            @click="fileInput?.click()"
          >
            <Icon v-if="!uploading" name="edit" :size="18" color="var(--blue)" />
            <span>{{ uploading ? 'Envoi en cours…' : currentPhotoUrl ? 'Changer la photo' : 'Choisir une photo' }}</span>
          </button>
        </div>

        <!-- ── Actions ──────────────────────────────────── -->
        <div class="card rise d4" style="padding: 4px 16px; margin-top: 20px">
          <button class="action-row" @click="recycleProgram">
            <div class="action-icon" style="background: color-mix(in srgb, var(--green) 12%, transparent)">
              <Icon name="recycle" :size="19" color="var(--green)" />
            </div>
            <span class="small action-label">Programme retour pneus</span>
            <span class="small" style="color: var(--ink-3)">2 recyclés</span>
            <Icon name="chev" :size="16" color="var(--mute)" />
          </button>
          <button class="action-row" @click="co2">
            <div class="action-icon" style="background: color-mix(in srgb, var(--lime-600) 12%, transparent)">
              <Icon name="leaf" :size="19" color="var(--lime-600)" />
            </div>
            <span class="small action-label">Mobilité sans voiture</span>
            <span class="small" style="color: var(--ink-3)">142 kg CO₂</span>
            <Icon name="chev" :size="16" color="var(--mute)" />
          </button>
          <NuxtLink to="/ride/alert" class="action-row" style="text-decoration:none">
            <div class="action-icon" style="background: color-mix(in srgb, var(--ink-2) 10%, transparent)">
              <Icon name="bell" :size="19" color="var(--ink-2)" />
            </div>
            <span class="small action-label">Alertes & notifications</span>
            <span v-if="(notifCount?.unread ?? 0) > 0" class="action-badge">{{ notifCount!.unread }}</span>
            <Icon name="chev" :size="16" color="var(--mute)" />
          </NuxtLink>
        </div>

        <button
          class="btn btn-block rise d5"
          style="height: 52px; margin-top: 14px; background: var(--red-soft); color: var(--red)"
          @click="logout"
        >
          <Icon name="logout" :size="19" /> Se déconnecter
        </button>

        <div class="tiny rise d5" style="text-align: center; margin-top: 18px">
          Aurora v1.0 · Michelin LB 2 Wheels · Hackathon Skolae 2026
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* ── Hero ────────────────────────────────────────────── */
.profile-hero {
  position: relative;
  overflow: hidden;
}
.profile-hero-bg {
  position: absolute;
  inset: 0;
}
.profile-hero-content {
  position: relative;
  display: flex;
  align-items: center;
  gap: 14px;
  padding-top: max(env(safe-area-inset-top, 0px), 20px);
  padding-bottom: 20px;
}
.profile-avatar-wrap {
  position: relative;
  flex-shrink: 0;
}
.profile-avatar-ring {
  position: absolute;
  inset: -4px;
  border-radius: 50%;
  border: 2.5px solid;
  opacity: .55;
  pointer-events: none;
}
.profile-hero-text { flex: 1; min-width: 0; }

.notif-bell {
  position: relative;
  width: 42px; height: 42px;
  border-radius: 13px;
  background: var(--surface-2);
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
  text-decoration: none;
  transition: background .15s;
}
.notif-bell.has-notif { background: color-mix(in srgb, var(--red) 10%, var(--surface-2)); }
.notif-dot {
  position: absolute;
  top: 7px; right: 7px;
  min-width: 15px; height: 15px;
  border-radius: 99px;
  background: var(--red);
  color: #fff;
  font-size: 9px; font-weight: 800;
  display: flex; align-items: center; justify-content: center;
  padding: 0 4px;
  border: 2px solid var(--card);
  line-height: 1;
}

/* ── Rewards card ────────────────────────────────────── */
.rewards-card {
  display: flex;
  align-items: stretch;
  border-radius: 20px !important;
  border: 1.5px solid #E5E5E5 !important;
  background: #fff !important;
  text-decoration: none;
  margin-top: 14px;
  overflow: hidden;
  transition: opacity .15s;
  /* force light-mode vars inside regardless of screen context */
  --ink:      #1A1A1A;
  --ink-2:    #404040;
  --ink-3:    #53565A;
  --mute:     #999999;
  --line:     #E5E5E5;
  --line-2:   #F2F2F2;
  --surface-2: #F2F2F2;
}
.rewards-card:active { opacity: .82; }

.rw-accent {
  width: 5px;
  flex-shrink: 0;
  min-height: 100%;
}
.rw-body { flex: 1; padding: 16px 14px 14px; min-width: 0; }

.rw-top {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 16px;
}
.rw-icon-wrap {
  width: 44px; height: 44px;
  border-radius: 13px;
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
}
.rw-rank-block { flex: 1; min-width: 0; }
.rw-rank-name {
  font-size: 17px;
  font-weight: 800;
  letter-spacing: -.02em;
  line-height: 1;
}
.rw-pts-label {
  font-size: 11px;
  color: var(--mute);
  margin-top: 3px;
}

/* Stepper */
.rw-stepper {
  display: flex;
  align-items: center;
  margin-bottom: 12px;
}
.rw-step {
  width: 30px; height: 30px;
  border-radius: 50%;
  border: 2px solid var(--line);
  background: var(--surface-2);
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
}
.rw-step.is-active {
  box-shadow: 0 0 0 4px rgba(0,0,0,.08);
}
.rw-step-lbl {
  font-size: 7px; font-weight: 800;
  letter-spacing: .04em;
  color: #fff;
  line-height: 1;
}
.rw-step:not(.is-done):not(.is-active) .rw-step-lbl { color: var(--mute); }
.rw-connector {
  flex: 1;
  height: 2px;
  background: var(--line);
  transition: background .3s;
}

/* Progress */
.rw-track {
  height: 5px;
  border-radius: 99px;
  background: var(--line);
  overflow: hidden;
  margin-bottom: 6px;
}
.rw-fill {
  height: 100%;
  border-radius: 99px;
  transition: width .5s ease;
}
.rw-next-label {
  font-size: 11px;
  color: var(--mute);
  text-align: right;
}

/* ── Section header ──────────────────────────────────── */
.section-hd {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 10px;
}
.section-edit {
  font-size: 13px; font-weight: 600;
  color: var(--blue);
  background: none; border: none;
  padding: 0; cursor: pointer;
}

/* ── Info list ───────────────────────────────────────── */
.info-card { padding: 6px 14px; }
.info-row {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 0;
  border-bottom: 1px solid var(--line-2);
}
.info-row.last { border-bottom: none; }
.info-icon {
  width: 32px; height: 32px;
  border-radius: 9px;
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
}
.info-key { font-size: 13px; color: var(--mute); flex: 1; }
.info-val { font-size: 13px; font-weight: 700; color: var(--ink); }

/* ── Photo ───────────────────────────────────────────── */
.photo-preview {
  position: relative;
  width: 100%; height: 160px;
  border-radius: 18px;
  overflow: hidden;
  margin-bottom: 10px;
}
.photo-preview img { width: 100%; height: 100%; object-fit: cover; display: block; }
.photo-remove {
  position: absolute;
  top: 10px; right: 10px;
  width: 30px; height: 30px;
  border-radius: 50%;
  background: rgba(0,0,0,.55);
  border: none;
  display: flex; align-items: center; justify-content: center;
  cursor: pointer;
}
.photo-upload-btn {
  width: 100%; height: 52px;
  border-radius: 16px;
  border: 1.5px dashed var(--blue);
  background: color-mix(in srgb, var(--blue) 8%, transparent);
  display: flex; align-items: center; justify-content: center;
  gap: 10px;
  font-size: 14px; font-weight: 600;
  color: var(--blue);
  cursor: pointer;
  transition: background .15s;
}
.photo-upload-btn:active { background: color-mix(in srgb, var(--blue) 15%, transparent); }
.photo-upload-btn.loading { opacity: .6; cursor: default; }

/* ── Action list ─────────────────────────────────────── */
.action-row {
  display: flex;
  width: 100%;
  gap: 13px;
  padding: 14px 0;
  align-items: center;
  border-top: 1px solid var(--line-2);
  background: none;
  border-left: none; border-right: none; border-bottom: none;
  cursor: pointer;
}
.action-row:first-child { border-top: none; }
.action-icon {
  width: 38px; height: 38px;
  border-radius: 11px;
  display: flex; align-items: center; justify-content: center;
  flex: 0 0 auto;
}
.action-label {
  flex: 1;
  font-weight: 600;
  color: var(--ink);
}
.action-badge {
  min-width: 18px; height: 18px;
  border-radius: 99px;
  background: var(--red);
  color: #fff;
  font-size: 10px; font-weight: 800;
  display: flex; align-items: center; justify-content: center;
  padding: 0 5px;
}
</style>
