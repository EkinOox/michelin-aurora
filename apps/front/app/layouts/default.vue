<script setup lang="ts">
const route = useRoute()
const showTab = computed(() => Boolean(route.meta.tabbar))
</script>

<template>
  <div class="aurora-shell">
    <div :class="['aurora-app', { 'has-sidebar': showTab }]">
      <slot />
      <TabBar v-if="showTab" />
      <Toast />
      <RetailerSheet />
      <ArticleSheet />
    </div>
  </div>
</template>
