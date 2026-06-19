<script setup lang="ts">
import { imageFor } from '~/data/images'

definePageMeta({ tabbar: true })

useSeoMeta({
  title: 'Événements cyclistes',
  description: 'Participez aux courses et événements partenaires Michelin — Gravel, route, VTT, VAE. Trouvez l\'événement fait pour votre profil cycliste.',
  ogTitle: 'Événements cyclistes — Aurora by Michelin',
  ogDescription: 'Courses, randonnées, events partenaires Michelin. Inscrivez-vous et laissez Aurora vous préparer le pneu idéal.',
  ogImage: '/icons/icon-512.png',
  twitterCard: 'summary',
})

interface EventDto {
  id: string
  name: string
  date: string
  place: string
  distance_label: string
  type: string
  km_away: number
  riders: number
  image_key: string | null
}

const router = useRouter()
const toast = useToast()
const retailerSheet = useRetailerSheet()

const { data: events } = await useApiFetch<EventDto[]>('/api/events', { key: 'events', default: () => [] })

const TYPE_LABEL: Record<string, string> = { endurance: 'Endurance', gravel: 'Gravel', route: 'Route' }

const types = ['Tous', 'Route', 'Gravel', 'Endurance']
const f = ref('Tous')
const list = computed(() => {
  if (f.value === 'Tous') return events.value ?? []
  return (events.value ?? []).filter(e => TYPE_LABEL[e.type] === f.value)
})

function dateParts(d: string) {
  const [day, month] = d.split(' ')
  return { day, month }
}

function register(e: EventDto) {
  toast.show(`Inscription enregistrée · ${e.name}`)
}
function favorite() {
  toast.show('Ajouté à vos favoris', 'star')
}
function suggestedTire(e: EventDto) {
  retailerSheet.open({ name: `Michelin Power ${e.type === 'gravel' ? 'Gravel' : 'Road'}` })
}
</script>

<template>
  <div class="screen">
    <div class="screen-scroll">
      <AppHeader title="Events & Live" sub="Michelin Experience · le cyclisme réel." :on-back="() => router.push('/home')" />
      <div class="pad">
        <div style="display: flex; gap: 8px; overflow-x: auto; padding-bottom: 14px">
          <button v-for="t in types" :key="t" :class="['chip', f === t ? 'is-active' : '']" @click="f = t">{{ t }}</button>
        </div>
        <div v-for="e in list" :key="e.id" class="card rise" style="overflow: hidden; margin-bottom: 14px">
          <Photo :src="imageFor(e.image_key)" :alt="e.name" style="height: 118px">
            <div style="position: absolute; top: 12px; left: 12px; background: var(--ink); color: #fff; border-radius: 12px; padding: 7px 11px; text-align: center">
              <div class="num" style="font-size: 16px; font-weight: 800; line-height: 1">{{ dateParts(e.date).day }}</div>
              <div class="tiny" style="color: rgba(255,255,255,.7)">{{ dateParts(e.date).month }}</div>
            </div>
            <div style="position: absolute; top: 12px; right: 12px"><span class="badge badge-yellow">{{ e.distance_label }}</span></div>
          </Photo>
          <div style="padding: 16px">
            <div class="between">
              <span class="badge" style="background: var(--surface-2); color: var(--ink-2)">{{ TYPE_LABEL[e.type] ?? e.type }}</span>
              <span class="tiny"><Icon name="users" :size="12" style="vertical-align: -2px" /> {{ e.riders }} riders</span>
            </div>
            <div class="h-sm" style="margin-top: 10px">{{ e.name }}</div>
            <div class="small" style="margin-top: 3px"><Icon name="loc" :size="13" color="var(--mute)" style="vertical-align: -2px" /> {{ e.place }} · à {{ e.km_away }} km</div>
            <div class="row" style="gap: 10px; margin-top: 14px">
              <button class="btn btn-blue" style="flex: 1; height: 46px; font-size: 14px" @click="register(e)">S'inscrire</button>
              <button class="iconbtn" @click="favorite"><Icon name="star" :size="18" /></button>
            </div>
            <button class="card" style="width: 100%; padding: 10px; margin-top: 12px; display: flex; gap: 9px; align-items: center; background: var(--lime-soft); border-color: transparent; text-align: left" @click="suggestedTire(e)">
              <Icon name="bike" :size="18" color="var(--lime-600)" />
              <span class="tiny" style="color: var(--ink-2); flex: 1">Pneus conseillés : <b>Michelin Power {{ e.type === 'gravel' ? 'Gravel' : 'Road' }}</b></span>
              <Icon name="chev" :size="15" color="var(--lime-600)" />
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
