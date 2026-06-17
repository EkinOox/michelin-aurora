<script setup lang="ts">
withDefaults(defineProps<{
  height?: number
  style?: Record<string, any>
}>(), {
  height: 24,
})
</script>

<template>
  <img
    src="/michelin-logo.png" alt="Michelin"
    class="michelin-logo-img"
    :style="{ height: `${height}px`, width: 'auto', display: 'block', flexShrink: 0, alignSelf: 'flex-start', ...style }"
  >
</template>
