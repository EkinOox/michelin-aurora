<script setup lang="ts">
const props = withDefaults(defineProps<{
  data: number[]
  w?: number
  h?: number
  color?: string
}>(), {
  w: 80,
  h: 26,
  color: 'var(--lime)',
})

const max = Math.max(...props.data)
const min = Math.min(...props.data)
const points = props.data
  .map((v, i) => `${(i / (props.data.length - 1)) * props.w},${props.h - ((v - min) / (max - min || 1)) * (props.h - 4) - 2}`)
  .join(' ')
</script>

<template>
  <svg :width="w" :height="h">
    <polyline :points="points" fill="none" :stroke="color" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
  </svg>
</template>
