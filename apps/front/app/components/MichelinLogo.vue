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
    :style="{ height: `${height}px`, width: 'auto', display: 'block', ...style }"
  >
</template>
