<script setup lang="ts">
withDefaults(defineProps<{
  name?: string
  size?: number
  bg?: string
}>(), {
  name: 'TG',
  size: 40,
  bg: 'var(--blue)',
})
</script>

<template>
  <div
    :style="{
      width: `${size}px`, height: `${size}px`, borderRadius: '50%', background: bg, color: '#fff',
      display: 'flex', alignItems: 'center', justifyContent: 'center', fontWeight: 700,
      fontSize: `${size * 0.38}px`, letterSpacing: '-.02em', flex: '0 0 auto',
    }"
  >
    {{ name }}
  </div>
</template>
