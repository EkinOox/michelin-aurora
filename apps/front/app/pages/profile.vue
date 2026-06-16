<script setup lang="ts">
definePageMeta({ tabbar: true })

interface ProfileDto {
  id: string
  name: string
  city: string
  rewards_level: string
  total_points: number
  profile: { bike_type: string, rider_level: string, usage_type: string, preferences: string[] } | null
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

const router = useRouter()
const toast = useToast()
const retailerSheet = useRetailerSheet()
const { logout: logoutAuth } = useAuth()

const { data: profile } = await useApiFetch<ProfileDto>('/api/profile', { key: 'profile' })

const initials = computed(() => {
  const n = profile.value?.name ?? ''
  return n.split(' ').map(p => p[0]).join('').slice(0, 2).toUpperCase()
})

const segments = computed(() => [
  { icon: 'bike', k: 'Vélo', v: BIKE_LABEL[profile.value?.profile?.bike_type ?? ''] ?? '—' },
  { icon: 'target', k: 'Niveau', v: LEVEL_LABEL[profile.value?.profile?.rider_level ?? ''] ?? '—' },
  { icon: 'bolt', k: 'Usage', v: USAGE_LABEL[profile.value?.profile?.usage_type ?? ''] ?? '—' },
  { icon: 'loc', k: 'Région', v: profile.value?.city ?? '—' },
])

function editProfile() {
  router.push('/onboarding')
}
function recycleProgram() {
  retailerSheet.open({ name: 'Programme retour & recyclage pneus' })
}
function co2() {
  toast.show('142 kg CO₂ économisés', 'leaf')
}
function notifications() {
  toast.show('Notifications activées', 'bell')
}
function security() {
  toast.show('Sécurité & confidentialité', 'shield')
}
function settings() {
  toast.show('Réglages · bientôt', 'settings')
}
function logout() {
  logoutAuth()
  router.push('/')
}
</script>

<template>
  <div v-if="profile" class="screen">
    <div class="screen-scroll">
      <AppHeader title="Profil" :on-back="() => router.push('/home')">
        <template #right>
          <button class="iconbtn" @click="settings"><Icon name="settings" :size="20" /></button>
        </template>
      </AppHeader>
      <div class="pad">
        <div class="card-lg rise" style="padding: 20px; display: flex; gap: 16px; align-items: center">
          <Avatar :name="initials" :size="64" bg="var(--blue)" />
          <div style="flex: 1">
            <div class="h-md">{{ profile.name }}</div>
            <div class="row" style="gap: 7px; margin-top: 6px">
              <span class="badge badge-yellow"><Icon name="trophy" :size="12" /> {{ RANK_LABEL[profile.rewards_level] ?? profile.rewards_level }}</span>
              <span class="badge badge-lime">{{ profile.total_points.toLocaleString('fr') }} pts</span>
            </div>
          </div>
        </div>

        <div class="rise d1" style="margin-top: 18px">
          <div class="eyebrow" style="margin-bottom: 10px">Mon profil cycliste</div>
          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px">
            <div v-for="s in segments" :key="s.k" class="card" style="padding: 14px">
              <Icon :name="s.icon" :size="18" color="var(--lime-600)" />
              <div class="tiny" style="margin-top: 8px">{{ s.k }}</div>
              <div class="small" style="font-weight: 700; color: var(--ink); margin-top: 2px">{{ s.v }}</div>
            </div>
          </div>
          <button class="btn btn-ghost btn-block" style="height: 44px; font-size: 14px; margin-top: 12px" @click="editProfile">
            <Icon name="edit" :size="16" /> Modifier mon profil
          </button>
        </div>

        <div class="card rise d2" style="padding: 4px 16px; margin-top: 18px">
          <button class="row" style="width: 100%; gap: 13px; padding: 14px 0; text-align: left" @click="recycleProgram">
            <div style="width: 38px; height: 38px; border-radius: 11px; background: var(--surface-2); display: flex; align-items: center; justify-content: center; flex: 0 0 auto">
              <Icon name="recycle" :size="19" color="var(--green)" />
            </div>
            <span class="small" style="flex: 1; font-weight: 600; color: var(--ink)">Programme retour pneus</span>
            <span class="small" style="color: var(--ink-3)">2 recyclés</span>
            <Icon name="chev" :size="16" color="var(--mute)" />
          </button>
          <button class="row" style="width: 100%; gap: 13px; padding: 14px 0; border-top: 1px solid var(--line-2); text-align: left" @click="co2">
            <div style="width: 38px; height: 38px; border-radius: 11px; background: var(--surface-2); display: flex; align-items: center; justify-content: center; flex: 0 0 auto">
              <Icon name="leaf" :size="19" color="var(--lime-600)" />
            </div>
            <span class="small" style="flex: 1; font-weight: 600; color: var(--ink)">Mobilité sans voiture</span>
            <span class="small" style="color: var(--ink-3)">142 kg CO₂</span>
            <Icon name="chev" :size="16" color="var(--mute)" />
          </button>
          <button class="row" style="width: 100%; gap: 13px; padding: 14px 0; border-top: 1px solid var(--line-2); text-align: left" @click="notifications">
            <div style="width: 38px; height: 38px; border-radius: 11px; background: var(--surface-2); display: flex; align-items: center; justify-content: center; flex: 0 0 auto">
              <Icon name="bell" :size="19" color="var(--ink-2)" />
            </div>
            <span class="small" style="flex: 1; font-weight: 600; color: var(--ink)">Alertes & notifications</span>
            <Icon name="chev" :size="16" color="var(--mute)" />
          </button>
          <button class="row" style="width: 100%; gap: 13px; padding: 14px 0; border-top: 1px solid var(--line-2); text-align: left" @click="security">
            <div style="width: 38px; height: 38px; border-radius: 11px; background: var(--surface-2); display: flex; align-items: center; justify-content: center; flex: 0 0 auto">
              <Icon name="shield" :size="19" color="var(--ink-2)" />
            </div>
            <span class="small" style="flex: 1; font-weight: 600; color: var(--ink)">Sécurité & confidentialité</span>
            <Icon name="chev" :size="16" color="var(--mute)" />
          </button>
        </div>

        <button class="btn btn-block rise d3" style="height: 52px; margin-top: 14px; background: var(--red-soft); color: var(--red)" @click="logout">
          <Icon name="logout" :size="19" /> Se déconnecter
        </button>

        <div class="tiny rise d3" style="text-align: center; margin-top: 18px">
          Aurora v1.0 · Michelin LB 2 Wheels · Hackathon Skolae 2026
        </div>
      </div>
    </div>
  </div>
</template>
