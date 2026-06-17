<script setup lang="ts">
import { imageFor } from '~/data/images'

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

const { open } = useArticleSheet()

const { data: news } = await useApiFetch<NewsArticleDto[]>('/api/news', { key: 'news', default: () => [] })

const preview = computed(() => (news.value ?? []).slice(0, 10))

function onOpen(n: NewsArticleDto) {
  open({ id: n.id, tag: n.tag, title: n.title, date: n.date, read: n.read_time, img: imageFor(n.image_key), body: n.body, url: n.url })
}
</script>

<template>
  <div style="display: flex; gap: 12px; overflow-x: auto; margin: 0 -20px; padding: 2px 20px 6px">
    <button
      v-for="n in preview" :key="n.id" class="card"
      style="flex: 0 0 230px; padding: 0; overflow: hidden; text-align: left; display: flex; flex-direction: column"
      @click="onOpen(n)"
    >
      <Photo :src="imageFor(n.image_key)" style="height: 120px">
        <span class="badge badge-blue" style="position: absolute; top: 10px; left: 10px">{{ n.tag }}</span>
      </Photo>
      <div style="padding: 13px">
        <div class="small" style="font-weight: 700; color: var(--ink); line-height: 1.25">{{ n.title }}</div>
        <div class="row" style="gap: 6px; margin-top: 8px">
          <span class="tiny">{{ n.date }}</span>
          <span class="tiny" style="color: var(--lime-600); font-weight: 700; margin-left: auto">Lire <Icon name="arrow" :size="11" style="vertical-align: -1px" /></span>
        </div>
      </div>
    </button>
  </div>
</template>
