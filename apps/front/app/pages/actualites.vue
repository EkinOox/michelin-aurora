<script setup lang="ts">
import { imageFor } from '~/data/images'

definePageMeta({ tabbar: true })

interface NewsArticleDto {
  id: string
  cat: string
  tag: string
  title: string
  date: string
  read_time: string | null
  image_key: string
  body: string
  url?: string | null
}

const router = useRouter()
const { open } = useArticleSheet()

const { data: news } = await useApiFetch<NewsArticleDto[]>('/api/news', { key: 'news-all', default: () => [] })

// Événements à venir : section agenda dédiée, triés du plus proche au plus lointain.
const events = computed(() => (news.value ?? []).filter(n => n.cat === 'event').slice().reverse())

// Filtres volontairement peu nombreux ; les événements ont leur propre section.
const CATS: { key: string, label: string }[] = [
  { key: 'all', label: 'Tous' },
  { key: 'route', label: 'Route' },
  { key: 'vtt', label: 'VTT' },
  { key: 'michelin', label: 'Michelin' },
]
const filters = computed(() => CATS.filter(c => c.key === 'all' || (news.value ?? []).some(n => n.cat === c.key)))
const active = ref('all')
const list = computed(() => {
  const all = (news.value ?? []).filter(n => n.cat !== 'event')
  return active.value === 'all' ? all : all.filter(n => n.cat === active.value)
})

function onOpen(n: NewsArticleDto) {
  open({ id: n.id, tag: n.tag, title: n.title, date: n.date, read: n.read_time, img: imageFor(n.image_key), body: n.body, url: n.url })
}

function dateParts(d: string) {
  const [day, ...rest] = d.split(' ')
  return { day, month: rest.join(' ') }
}

// Molette/trackpad vertical → défilement horizontal des rails.
function wheelToHorizontal(e: WheelEvent) {
  const el = e.currentTarget as HTMLElement
  if (Math.abs(e.deltaY) <= Math.abs(e.deltaX)) return
  el.scrollLeft += e.deltaY
  e.preventDefault()
}
</script>

<template>
  <div class="screen">
    <div class="screen-scroll">
      <AppHeader title="Actualités" sub="Cyclisme & Michelin · l'actu sportive en continu." :on-back="() => router.push('/home')" />
      <div class="pad">
        <!-- Agenda des événements à venir -->
        <template v-if="active === 'all' && events.length">
          <div class="between" style="margin-bottom: 10px">
            <span class="h-sm">À venir</span>
          </div>
          <div class="event-rail" @wheel="wheelToHorizontal">
            <button v-for="e in events" :key="e.id" class="card rise event-card" @click="onOpen(e)">
              <div class="event-date">
                <span class="event-day">{{ dateParts(e.date).day }}</span>
                <span class="event-month">{{ dateParts(e.date).month }}</span>
              </div>
              <div style="min-width: 0">
                <div class="event-name">{{ e.title }}</div>
                <div class="tiny event-when">{{ e.body }}</div>
              </div>
            </button>
          </div>
          <div class="between" style="margin: 18px 0 10px">
            <span class="h-sm">Toute l'actu</span>
          </div>
        </template>

        <div class="news-filters" @wheel="wheelToHorizontal">
          <button v-for="f in filters" :key="f.key" :class="['chip', active === f.key ? 'is-active' : '']" @click="active = f.key">{{ f.label }}</button>
        </div>

        <div class="news-grid">
          <button v-for="n in list" :key="n.id" class="card rise news-card" @click="onOpen(n)">
            <Photo :src="imageFor(n.image_key)" :alt="n.title" :style="{ height: '120px', width: '100%' }" />
            <div class="news-body">
              <div class="row" style="gap: 8px">
                <span class="news-tag">{{ n.tag }}</span>
                <span class="tiny" style="margin-left: auto; flex: 0 0 auto; white-space: nowrap">{{ n.date }}</span>
              </div>
              <div class="news-title">{{ n.title }}</div>
            </div>
          </button>
        </div>

        <div v-if="!list.length" class="small" style="text-align: center; padding: 40px 0; color: var(--mute)">
          Aucune actualité pour ce filtre.
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* ---- Rails horizontaux (filtres + agenda) ---- */
.news-filters,
.event-rail {
  display: flex;
  flex-wrap: nowrap;
  gap: 10px;
  margin: 0 -20px;
  padding: 2px 20px 12px;
  overflow-x: auto;
  -webkit-overflow-scrolling: touch;
  scrollbar-width: none;
  -ms-overflow-style: none;
}
.news-filters { gap: 8px; margin-bottom: 16px; }
.news-filters::-webkit-scrollbar,
.event-rail::-webkit-scrollbar { display: none; }
.news-filters .chip { flex: 0 0 auto; }

/* ---- Carte événement (agenda) ---- */
.event-card {
  flex: 0 0 230px;
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px;
  text-align: left;
}
.event-date {
  flex: 0 0 auto;
  width: 46px;
  height: 46px;
  border-radius: 12px;
  background: var(--blue);
  color: #fff;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  line-height: 1;
}
.event-day { font-size: 17px; font-weight: 800; }
.event-month { font-size: 9px; opacity: 0.8; margin-top: 2px; }
.event-name {
  font-size: 13.5px;
  font-weight: 700;
  color: var(--ink);
  line-height: 1.25;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
.event-when { margin-top: 4px; }

/* ---- Grille des actus ---- */
.news-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
  gap: 12px;
}
.news-card {
  padding: 0;
  overflow: hidden;
  text-align: left;
  display: flex;
  flex-direction: column;
}
.news-body {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 7px;
  padding: 11px 13px 13px;
}
.news-tag {
  min-width: 0;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  color: var(--blue);
  font-weight: 700;
  font-size: 11px;
  letter-spacing: 0.02em;
  text-transform: uppercase;
}
.news-title {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  min-height: 2.6em;
  font-size: 14px;
  font-weight: 700;
  line-height: 1.3;
  color: var(--ink);
}
</style>
