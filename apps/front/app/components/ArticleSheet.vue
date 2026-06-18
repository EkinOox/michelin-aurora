<script setup lang="ts">
const { item, close } = useArticleSheet()

function readMore() {
  try {
    window.open('https://www.michelin.fr', '_blank', 'noopener')
  } catch {
    // ignore popup blockers
  }
  close()
}
</script>

<template>
  <Sheet :open="!!item" @close="close">
    <div v-if="item" class="screen-scroll" style="padding-bottom: 30px; margin: 0 -20px; padding-left: 20px; padding-right: 20px">
      <Photo :src="item.img" :radius="18" style="height: 180px; margin-bottom: 16px" />
      <div class="row" style="gap: 8px">
        <span class="badge badge-blue">{{ item.tag }}</span>
        <span class="tiny">{{ item.date }} · {{ item.read }} de lecture</span>
      </div>
      <div class="h-md" style="margin-top: 12px">{{ item.title }}</div>
      <div class="body" style="margin-top: 12px">{{ item.body }}</div>
      <button class="btn btn-blue btn-block" style="margin-top: 20px; height: 50px" @click="readMore">
        Lire sur michelin.fr <Icon name="arrow" :size="18" />
      </button>
    </div>
  </Sheet>
</template>
