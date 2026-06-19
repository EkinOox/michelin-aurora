<script setup lang="ts">
definePageMeta({ tabbar: true })

useSeoMeta({
  title: 'Mon profil',
  description: 'Gérez votre profil cycliste Aurora — préférences, statistiques de sortie et récompenses Michelin.',
  robots: 'noindex',
})

interface ProfileDto {
  id: string
  name: string
  city: string
  rewards_level: string
  total_points: number
  profile: { bike_type: string, rider_level: string, usage_type: string, preferences: string[], bike_photo_url: string | null } | null
}

const RANK_LABEL: Record<string, string> = {
  explorer:            'Explorer',
  rider:               'Rider',
  performer:           'Performer',
  elite_cyclist:       'Elite Cyclist',
  michelin_ambassador: 'Michelin Ambassador',
}
const BIKE_LABEL:  Record<string, string> = { route: 'Route', gravel: 'Gravel', vtt: 'VTT', vae: 'VAE' }
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

const router        = useRouter()
const toast         = useToast()
const retailerSheet = useRetailerSheet()
const { logout: logoutAuth } = useAuth()
const apiBase       = useApiBase()
const notifSheet    = useNotificationsSheet()

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
      bike_type:      profile.value?.profile?.bike_type      ?? 'gravel',
      rider_level:    profile.value?.profile?.rider_level    ?? 'expert',
      usage_type:     profile.value?.profile?.usage_type     ?? 'sport',
      preferences:    profile.value?.profile?.preferences    ?? [],
      bike_photo_url: '',
    },
  })
  await refreshProfile()
}

const initials   = computed(() => (profile.value?.name ?? '').split(' ').map(p => p[0]).join('').slice(0, 2).toUpperCase())
const curRankIdx = computed(() => Math.max(0, RANKS.findIndex(r => r.key === profile.value?.rewards_level)))
const nextRank   = computed(() => RANKS[curRankIdx.value + 1] ?? null)
const rankProg   = computed(() => {
  if (!profile.value) return 0
  const cur  = RANKS[curRankIdx.value]
  const span = nextRank.value ? nextRank.value.min - cur.min : 1
  return Math.min(1, Math.max(0, (profile.value.total_points - cur.min) / span))
})
const ptsToNext  = computed(() => nextRank.value ? Math.max(0, nextRank.value.min - (profile.value?.total_points ?? 0)) : 0)
const rankColor  = computed(() => RANK_COLORS[profile.value?.rewards_level ?? ''] ?? '#27509B')

function editProfile()    { router.push('/onboarding') }
function recycleProgram() { retailerSheet.open({ name: 'Programme retour & recyclage pneus' }) }
function co2()            { toast.show('142 kg CO₂ économisés', 'leaf') }
function logout()         { logoutAuth(); router.push('/') }
</script>

<template>
  <div v-if="profile" class="screen">
    <div class="screen-scroll">

      <!-- ── Hero ──────────────────────────────────────── -->
      <div class="hero rise">
        <div class="hero-glow" :style="{ background: `radial-gradient(ellipse 90% 70% at 50% 0%, ${rankColor}22 0%, transparent 75%)` }" />

        <!-- Cloche notifications -->
        <div class="hero-topbar pad safe-top">
          <div style="flex:1" />
          <button class="notif-btn" :class="{ 'has-dot': (notifCount?.unread ?? 0) > 0 }" @click="notifSheet.open()">
            <Icon name="bell" :size="20" color="var(--ink-2)" />
            <span v-if="(notifCount?.unread ?? 0) > 0" class="notif-dot">{{ notifCount!.unread }}</span>
          </button>
        </div>

        <!-- Identité -->
        <div class="hero-identity">
          <div class="avatar-wrap" :style="{ '--rc': rankColor }">
            <Avatar :name="initials" :size="76" bg="var(--blue)" />
          </div>
          <div class="h-lg" style="margin-top:14px; text-align:center">{{ profile.name }}</div>
          <div class="tiny" style="color:var(--mute); margin-top:3px">{{ profile.city }}</div>
          <div class="rank-chip rise d1" :style="{ background: `${rankColor}18`, borderColor: `${rankColor}40`, color: rankColor }">
            <span class="rank-dot" :style="{ background: rankColor }" />
            {{ RANK_LABEL[profile.rewards_level] ?? profile.rewards_level }}
          </div>
        </div>
      </div>

      <div class="pad" style="padding-top:4px">

        <!-- ── Stats ──────────────────────────────────── -->
        <div class="stats-row rise d2">
          <div class="stat-card card">
            <div class="stat-val num">{{ profile.total_points.toLocaleString('fr') }}</div>
            <div class="stat-lbl">Points</div>
          </div>
          <div class="stat-card card">
            <div class="stat-val">{{ LEVEL_LABEL[profile.profile?.rider_level ?? '']?.split(' ')[0] ?? '—' }}</div>
            <div class="stat-lbl">Niveau</div>
          </div>
          <div class="stat-card card">
            <div class="stat-val">{{ BIKE_LABEL[profile.profile?.bike_type ?? ''] ?? '—' }}</div>
            <div class="stat-lbl">Vélo</div>
          </div>
        </div>

        <!-- ── Rewards ─────────────────────────────────── -->
        <NuxtLink to="/rewards" class="rw-card card rise d3">
          <div class="rw-accent" :style="{ background: rankColor }" />
          <div class="rw-body">
            <div class="rw-top">
              <div class="rw-icon" :style="{ background: `${rankColor}1A` }">
                <Icon name="trophy" :size="20" :color="rankColor" />
              </div>
              <div style="flex:1;min-width:0">
                <div class="rw-rank" :style="{ color: rankColor }">{{ RANK_LABEL[profile.rewards_level] ?? profile.rewards_level }}</div>
                <div class="rw-pts">{{ profile.total_points.toLocaleString('fr') }} points</div>
              </div>
              <Icon name="chev" :size="16" color="var(--mute)" />
            </div>

            <div class="rw-stepper">
              <template v-for="(r, i) in RANKS" :key="r.key">
                <div
                  class="rw-step"
                  :class="{ done: i < curRankIdx, active: i === curRankIdx }"
                  :style="i <= curRankIdx ? { background: RANK_COLORS[r.key], borderColor: RANK_COLORS[r.key] } : {}"
                >
                  <span class="rw-step-lbl">{{ r.short }}</span>
                </div>
                <div v-if="i < RANKS.length - 1" class="rw-conn" :style="i < curRankIdx ? { background: rankColor } : {}" />
              </template>
            </div>

            <div class="rw-track">
              <div class="rw-fill" :style="{ width: `${rankProg * 100}%`, background: rankColor }" />
            </div>
            <div class="rw-hint">
              <span v-if="nextRank">{{ ptsToNext.toLocaleString('fr') }} pts pour atteindre <b>{{ nextRank.label }}</b></span>
              <span v-else style="color:var(--lime-600);font-weight:700">🏆 Rang maximum atteint !</span>
            </div>
          </div>
        </NuxtLink>

        <!-- ── Profil cycliste ─────────────────────────── -->
        <div class="section rise d4">
          <div class="section-hd">
            <span class="eyebrow">Profil cycliste</span>
            <button class="section-btn" @click="editProfile">Modifier</button>
          </div>
          <div class="card" style="padding:6px 14px">
            <div class="info-row">
              <div class="info-icon" style="background:color-mix(in srgb,var(--blue) 12%,transparent)"><Icon name="bike" :size="15" color="var(--blue)" /></div>
              <span class="info-k">Vélo</span>
              <span class="info-v">{{ BIKE_LABEL[profile.profile?.bike_type ?? ''] ?? '—' }}</span>
            </div>
            <div class="info-row">
              <div class="info-icon" style="background:color-mix(in srgb,var(--lime-600) 12%,transparent)"><Icon name="target" :size="15" color="var(--lime-600)" /></div>
              <span class="info-k">Niveau</span>
              <span class="info-v">{{ LEVEL_LABEL[profile.profile?.rider_level ?? ''] ?? '—' }}</span>
            </div>
            <div class="info-row">
              <div class="info-icon" style="background:color-mix(in srgb,#b8860b 12%,transparent)"><Icon name="bolt" :size="15" color="#b8860b" /></div>
              <span class="info-k">Usage</span>
              <span class="info-v">{{ USAGE_LABEL[profile.profile?.usage_type ?? ''] ?? '—' }}</span>
            </div>
            <div class="info-row last">
              <div class="info-icon" style="background:color-mix(in srgb,var(--red) 10%,transparent)"><Icon name="loc" :size="15" color="var(--red)" /></div>
              <span class="info-k">Ville</span>
              <span class="info-v">{{ profile.city ?? '—' }}</span>
            </div>
          </div>
        </div>

        <!-- ── Mon vélo ────────────────────────────────── -->
        <div class="section rise d5">
          <div class="section-hd">
            <span class="eyebrow">Mon vélo</span>
          </div>

          <div v-if="currentPhotoUrl" class="bike-photo-wrap">
            <img :src="currentPhotoUrl" alt="Mon vélo" class="bike-photo" />
            <button class="photo-del" @click="removePhoto"><Icon name="close" :size="13" color="#fff" /></button>
          </div>

          <NuxtLink to="/pressure" class="card" style="padding:14px 16px;display:flex;align-items:center;gap:14px;text-decoration:none;margin-bottom:10px">
            <div style="width:38px;height:38px;border-radius:11px;background:color-mix(in srgb,var(--blue) 10%,transparent);display:flex;align-items:center;justify-content:center;flex-shrink:0">
              <Icon name="gauge" :size="19" color="var(--blue)" />
            </div>
            <div style="flex:1">
              <div class="small" style="font-weight:700;color:var(--ink)">Pression recommandée</div>
              <div class="tiny" style="margin-top:2px">
                <span :style="{ color: isRain ? 'var(--blue)' : 'var(--lime-600)', fontWeight: 600 }">{{ isRain ? 'Pluie' : 'Sec' }}</span>
                <template v-if="!pressureLoading"> · Av {{ frontStr }} bar · Ar {{ rearStr }} bar</template>
                <template v-else> · Calcul…</template>
              </div>
            </div>
            <Icon name="chev" :size="16" color="var(--mute)" />
          </NuxtLink>

          <input ref="fileInput" type="file" accept="image/*" style="display:none" @change="onFileChange" />
          <button class="upload-btn" :class="{ loading: uploading }" :disabled="uploading" @click="fileInput?.click()">
            <Icon v-if="!uploading" name="edit" :size="16" color="var(--blue)" />
            {{ uploading ? 'Envoi en cours…' : currentPhotoUrl ? 'Changer la photo' : 'Ajouter une photo de vélo' }}
          </button>
        </div>

        <!-- ── Actions ─────────────────────────────────── -->
        <div class="section rise d6">
          <div class="section-hd"><span class="eyebrow">Actions</span></div>
          <div class="card" style="padding:4px 14px">
            <button class="action-row" @click="recycleProgram">
              <div class="action-icon" style="background:color-mix(in srgb,var(--lime-600) 12%,transparent)"><Icon name="recycle" :size="18" color="var(--lime-600)" /></div>
              <div class="action-text">
                <span class="small" style="font-weight:600;color:var(--ink)">Programme retour pneus</span>
                <span class="tiny" style="color:var(--ink-3)">2 pneus recyclés</span>
              </div>
              <Icon name="chev" :size="16" color="var(--mute)" />
            </button>
            <div class="divider" />
            <button class="action-row" @click="co2">
              <div class="action-icon" style="background:color-mix(in srgb,#16a34a 12%,transparent)"><Icon name="leaf" :size="18" color="#16a34a" /></div>
              <div class="action-text">
                <span class="small" style="font-weight:600;color:var(--ink)">Mobilité sans voiture</span>
                <span class="tiny" style="color:var(--ink-3)">142 kg CO₂ économisés</span>
              </div>
              <Icon name="chev" :size="16" color="var(--mute)" />
            </button>
            <div class="divider" />
            <button class="action-row" @click="notifSheet.open()">
              <div class="action-icon" style="background:color-mix(in srgb,var(--ink-2) 10%,transparent)"><Icon name="bell" :size="18" color="var(--ink-2)" /></div>
              <div class="action-text">
                <span class="small" style="font-weight:600;color:var(--ink)">Alertes & notifications</span>
                <span class="tiny" style="color:var(--ink-3)">{{ (notifCount?.unread ?? 0) > 0 ? `${notifCount!.unread} non lue(s)` : 'Aucune nouvelle alerte' }}</span>
              </div>
              <span v-if="(notifCount?.unread ?? 0) > 0" class="notif-badge">{{ notifCount!.unread }}</span>
              <Icon v-else name="chev" :size="16" color="var(--mute)" />
            </button>
          </div>
        </div>

        <div class="tiny rise d6" style="text-align:center;margin-top:18px;color:var(--mute)">
          Aurora v1.0 · Michelin LB 2 Wheels · Hackathon Skolae 2026
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* ── Hero ─────────────────────────────────────────────── */
.hero {
  position: relative;
  padding-bottom: 8px;
}
.hero-glow {
  position: absolute;
  inset: 0;
  pointer-events: none;
}
.hero-topbar {
  display: flex;
  align-items: center;
  padding-bottom: 4px;
}
.notif-btn {
  position: relative;
  width: 42px; height: 42px;
  border-radius: 13px;
  background: var(--surface-2);
  border: 1px solid var(--line);
  display: flex; align-items: center; justify-content: center;
  cursor: pointer;
}
.notif-btn.has-dot { background: color-mix(in srgb, var(--red) 8%, var(--surface-2)); }
.notif-dot {
  position: absolute;
  top: 7px; right: 7px;
  min-width: 14px; height: 14px;
  border-radius: 99px;
  background: var(--red);
  color: #fff;
  font-size: 8px; font-weight: 800;
  display: flex; align-items: center; justify-content: center;
  padding: 0 3px;
  border: 2px solid var(--card);
}
.hero-identity {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 8px 24px 20px;
}
.avatar-wrap {
  position: relative;
}
.avatar-wrap::after {
  content: '';
  position: absolute;
  inset: -4px;
  border-radius: 50%;
  border: 2.5px solid var(--rc, #27509B);
  opacity: .45;
}
.rank-chip {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  margin-top: 12px;
  padding: 5px 13px;
  border-radius: 99px;
  border: 1px solid;
  font-size: 12px;
  font-weight: 700;
  letter-spacing: .01em;
}
.rank-dot { width: 6px; height: 6px; border-radius: 50%; flex-shrink: 0; }

/* ── Stats ────────────────────────────────────────────── */
.stats-row {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 10px;
  margin-bottom: 12px;
}
.stat-card {
  padding: 14px 10px;
  text-align: center;
}
.stat-val {
  font-size: 16px;
  font-weight: 800;
  color: var(--ink);
  letter-spacing: -.02em;
  line-height: 1;
}
.stat-lbl {
  font-size: 10px;
  font-weight: 600;
  letter-spacing: .06em;
  text-transform: uppercase;
  color: var(--ink-3);
  margin-top: 4px;
}

/* ── Rewards card ─────────────────────────────────────── */
.rw-card {
  display: flex;
  align-items: stretch;
  text-decoration: none;
  overflow: hidden;
  transition: opacity .15s;
  margin-bottom: 0;
}
.rw-card:active { opacity: .82; }
.rw-accent { width: 5px; flex-shrink: 0; }
.rw-body { flex: 1; padding: 16px 14px 14px; min-width: 0; }
.rw-top { display: flex; align-items: center; gap: 10px; margin-bottom: 14px; }
.rw-icon { width: 42px; height: 42px; border-radius: 12px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.rw-rank { font-size: 16px; font-weight: 800; letter-spacing: -.02em; line-height: 1; }
.rw-pts { font-size: 11px; color: var(--ink-3); margin-top: 3px; }

.rw-stepper { display: flex; align-items: center; margin-bottom: 10px; }
.rw-step {
  width: 28px; height: 28px; border-radius: 50%;
  border: 2px solid var(--line); background: var(--surface-2);
  display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}
.rw-step.active { box-shadow: 0 0 0 4px rgba(0,0,0,.07); }
.rw-step-lbl { font-size: 7px; font-weight: 800; letter-spacing: .04em; color: #fff; }
.rw-step:not(.done):not(.active) .rw-step-lbl { color: var(--mute); }
.rw-conn { flex: 1; height: 2px; background: var(--line); }

.rw-track { height: 4px; border-radius: 99px; background: var(--line); overflow: hidden; margin-bottom: 6px; }
.rw-fill { height: 100%; border-radius: 99px; transition: width .5s ease; }
.rw-hint { font-size: 11px; color: var(--ink-3); text-align: right; }

/* ── Sections ─────────────────────────────────────────── */
.section { margin-top: 22px; }
.section-hd { display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px; }
.section-btn { font-size: 13px; font-weight: 600; color: var(--blue); background: none; border: none; padding: 0; cursor: pointer; }

/* ── Info rows ────────────────────────────────────────── */
.info-row {
  display: flex; align-items: center; gap: 12px;
  padding: 12px 0;
  border-bottom: 1px solid var(--line-2);
}
.info-row.last { border-bottom: none; }
.info-icon { width: 30px; height: 30px; border-radius: 9px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.info-k { font-size: 13px; color: var(--ink-3); flex: 1; }
.info-v { font-size: 13px; font-weight: 700; color: var(--ink); }

/* ── Photo vélo ───────────────────────────────────────── */
.bike-photo-wrap {
  position: relative; width: 100%; height: 168px;
  border-radius: 18px; overflow: hidden; margin-bottom: 10px;
}
.bike-photo { width: 100%; height: 100%; object-fit: cover; display: block; }
.photo-del {
  position: absolute; top: 10px; right: 10px;
  width: 28px; height: 28px; border-radius: 50%;
  background: rgba(0,0,0,.5); border: none;
  display: flex; align-items: center; justify-content: center; cursor: pointer;
}
.upload-btn {
  width: 100%; height: 48px; border-radius: 14px;
  border: 1.5px dashed var(--blue);
  background: color-mix(in srgb, var(--blue) 7%, transparent);
  display: flex; align-items: center; justify-content: center; gap: 8px;
  font-size: 14px; font-weight: 600; color: var(--blue); cursor: pointer;
  transition: background .15s;
}
.upload-btn:active { background: color-mix(in srgb, var(--blue) 13%, transparent); }
.upload-btn.loading { opacity: .6; cursor: default; }

/* ── Action rows ──────────────────────────────────────── */
.action-row {
  display: flex; align-items: center; gap: 12px; width: 100%;
  padding: 13px 0; background: none; border: none; cursor: pointer; text-align: left;
}
.action-icon { width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.action-text { flex: 1; min-width: 0; display: flex; flex-direction: column; gap: 1px; }
.notif-badge {
  min-width: 18px; height: 18px; border-radius: 99px;
  background: var(--red); color: #fff;
  font-size: 10px; font-weight: 800;
  display: flex; align-items: center; justify-content: center; padding: 0 5px;
}
</style>
