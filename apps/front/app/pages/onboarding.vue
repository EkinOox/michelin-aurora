<script setup lang="ts">
const apiBase = useApiBase()
const router = useRouter()

const BIKE_OPTIONS = [
  { label: 'Route', value: 'route' },
  { label: 'Gravel', value: 'gravel' },
  { label: 'VTT', value: 'vtt' },
  { label: 'VAE', value: 'vae' },
]
const LEVEL_OPTIONS = [
  { label: 'Débutant', value: 'beginner' },
  { label: 'Intermédiaire', value: 'intermediate' },
  { label: 'Avancé', value: 'advanced' },
  { label: 'Expert', value: 'expert' },
]
const USAGE_OPTIONS = [
  { label: 'Loisir', value: 'leisure' },
  { label: 'Performance', value: 'sport' },
  { label: 'Compétition', value: 'competition' },
  { label: 'Transport', value: 'commute' },
]
const PREF_OPTIONS = ['Confort', 'Performance', 'Sécurité', 'Durabilité']

const bike = ref('gravel')
const level = ref('expert')
const usage = ref('sport')
const prefs = ref<string[]>(['Performance', 'Sécurité'])
const saving = ref(false)

const bikeLabel = computed(() => BIKE_OPTIONS.find(o => o.value === bike.value)?.label ?? bike.value)
const levelLabel = computed(() => LEVEL_OPTIONS.find(o => o.value === level.value)?.label ?? level.value)

function togglePref(p: string) {
  prefs.value = prefs.value.includes(p) ? prefs.value.filter(x => x !== p) : [...prefs.value, p]
}

async function activate() {
  saving.value = true
  try {
    await $fetch(`${apiBase}/api/profile`, {
      method: 'PUT',
      body: {
        bike_type: bike.value,
        rider_level: level.value,
        usage_type: usage.value,
        preferences: prefs.value,
      },
    })
  } finally {
    saving.value = false
  }
  router.push('/home')
}
</script>

<template>
  <div class="screen">
    <AppHeader title="Profil cycliste" sub="Tout s'adapte à vous — routes, pression, recommandations." :on-back="() => $router.back()" />
    <div class="screen-scroll pad" style="padding-top: 8px">
      <div class="rise" style="margin-bottom: 24px">
        <div class="row" style="gap: 8px; margin-bottom: 12px">
          <span class="num" style="font-size: 12px; color: var(--lime-600); font-weight: 700">01</span>
          <span class="h-sm">Type de vélo</span>
        </div>
        <div style="display: flex; flex-wrap: wrap; gap: 9px">
          <button v-for="o in BIKE_OPTIONS" :key="o.value" :class="['chip', bike === o.value ? 'is-active' : '']" @click="bike = o.value">
            <Icon v-if="bike === o.value" name="check" :size="14" />{{ o.label }}
          </button>
        </div>
      </div>

      <div class="rise d1" style="margin-bottom: 24px">
        <div class="row" style="gap: 8px; margin-bottom: 12px">
          <span class="num" style="font-size: 12px; color: var(--lime-600); font-weight: 700">02</span>
          <span class="h-sm">Niveau</span>
        </div>
        <div style="display: flex; flex-wrap: wrap; gap: 9px">
          <button v-for="o in LEVEL_OPTIONS" :key="o.value" :class="['chip', level === o.value ? 'is-active' : '']" @click="level = o.value">
            <Icon v-if="level === o.value" name="check" :size="14" />{{ o.label }}
          </button>
        </div>
      </div>

      <div class="rise d2" style="margin-bottom: 24px">
        <div class="row" style="gap: 8px; margin-bottom: 12px">
          <span class="num" style="font-size: 12px; color: var(--lime-600); font-weight: 700">03</span>
          <span class="h-sm">Usage principal</span>
        </div>
        <div style="display: flex; flex-wrap: wrap; gap: 9px">
          <button v-for="o in USAGE_OPTIONS" :key="o.value" :class="['chip', usage === o.value ? 'is-active' : '']" @click="usage = o.value">
            <Icon v-if="usage === o.value" name="check" :size="14" />{{ o.label }}
          </button>
        </div>
      </div>

      <div class="rise d3" style="margin-bottom: 24px">
        <div class="row" style="gap: 8px; margin-bottom: 12px">
          <span class="num" style="font-size: 12px; color: var(--lime-600); font-weight: 700">04</span>
          <span class="h-sm">Priorités (plusieurs choix)</span>
        </div>
        <div style="display: flex; flex-wrap: wrap; gap: 9px">
          <button v-for="p in PREF_OPTIONS" :key="p" :class="['chip', prefs.includes(p) ? 'is-active' : '']" @click="togglePref(p)">
            <Icon v-if="prefs.includes(p)" name="check" :size="14" />{{ p }}
          </button>
        </div>
      </div>

      <div class="card rise" style="padding: 16px; display: flex; gap: 12px; align-items: center; margin-bottom: 18px; background: var(--lime-soft); border-color: transparent">
        <div style="width: 40px; height: 40px; border-radius: 12px; background: var(--lime); display: flex; align-items: center; justify-content: center; flex: 0 0 auto">
          <Icon name="target" :size="22" color="#1c2400" />
        </div>
        <div class="small" style="color: var(--ink-2)">
          Profil <b>{{ bikeLabel }} · {{ levelLabel }}</b> → Michelin recommande la gamme <b>Power Gravel</b> et une pression de base de <b class="num">2,6 bar</b>.
        </div>
      </div>
    </div>
    <div class="pad" style="position: absolute; left: 0; right: 0; bottom: 24px">
      <button class="btn btn-blue btn-block" :disabled="saving" @click="activate">
        Activer ma plateforme <Icon name="arrow" :size="20" />
      </button>
    </div>
  </div>
</template>
