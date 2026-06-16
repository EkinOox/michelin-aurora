<script setup lang="ts">
withDefaults(defineProps<{
  src: string
  alt?: string
  radius?: number
  style?: Record<string, any>
  imgStyle?: Record<string, any>
}>(), {
  alt: '',
  radius: 0,
})
</script>

<template>
  <div
    :style="{ position: 'relative', overflow: 'hidden', borderRadius: `${radius}px`, background: 'var(--surface-2)', ...style }"
  >
    <img
      :src="src" :alt="alt" loading="lazy"
      :style="{ width: '100%', height: '100%', objectFit: 'cover', display: 'block', ...imgStyle }"
    >
    <slot />
  </div>
</template>
