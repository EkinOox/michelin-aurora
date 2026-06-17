<script setup lang="ts">
import { imageFor } from '~/data/images'

definePageMeta({ tabbar: true })

interface NewsArticleDto {
  id: string
  tag: string
  title: string
  date: string
  read_time: string
  image_key: string
  body: string
  url?: string | null
}

const router = useRouter()
const { open } = useArticleSheet()

const { data: news } = await useApiFetch<NewsArticleDto[]>('/api/news', { key: 'news-all', default: () => [] })

const tags = computed(() => ['Tous', ...Array.from(new Set((news.value ?? []).map(n => n.tag)))])
const active = ref('Tous')
const list = computed(() => {
  const all = news.value ?? []
  return active.value === 'Tous' ? all : all.filter(n => n.tag === active.value)
})

function onOpen(n: NewsArticleDto) {
  open({ id: n.id, tag: n.tag, title: n.title, date: n.date, read: n.read_time, img: imageFor(n.image_key), body: n.body, url: n.url })
}
</script>

<template>
  <div class="screen">
    <div class="screen-scroll">
      <AppHeader title="Actualités" sub="Cyclisme & Michelin · l'actu sportive en continu." :on-back="() => router.push('/home')" />
      <div class="pad">
        <div style="display: flex; gap: 8px; overflow-x: auto; flex-wrap: nowrap; margin: 0 -20px 4px; padding: 2px 20px 14px; -webkit-overflow-scrolling: touch">
          <button v-for="t in tags" :key="t" :class="['chip', active === t ? 'is-active' : '']" style="flex: 0 0 auto" @click="active = t">{{ t }}</button>
        </div>

        <button
          v-for="n in list" :key="n.id" class="card rise"
          style="width: 100%; padding: 0; overflow: hidden; margin-bottom: 10px; text-align: left; display: flex; align-items: stretch"
          @click="onOpen(n)"
        >
          <Photo :src="imageFor(n.image_key)" :alt="n.title" style="flex: 0 0 92px; width: 92px" />
          <div style="flex: 1; min-width: 0; padding: 10px 12px; display: flex; flex-direction: column; gap: 5px">
            <div class="row" style="gap: 8px; align-items: center">
              <span class="tiny" style="color: var(--blue); font-weight: 700; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap">{{ n.tag }}</span>
              <span class="tiny" style="margin-left: auto; white-space: nowrap; flex: 0 0 auto">{{ n.date }}</span>
            </div>
            <div
              class="small"
              style="font-weight: 700; color: var(--ink); line-height: 1.3; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical"
            >
              {{ n.title }}
            </div>
          </div>
        </button>

        <div v-if="!list.length" class="small" style="text-align: center; padding: 40px 0; color: var(--mute)">
          Aucune actualité pour ce filtre.
        </div>
      </div>
    </div>
  </div>
</template>
