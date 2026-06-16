<script setup lang="ts">
const props = withDefaults(defineProps<{
  value?: number
  size?: number
  thick?: number
  color?: string
  track?: string
}>(), {
  value: 0,
  size: 64,
  thick: 7,
  color: 'var(--lime)',
  track: 'var(--line)',
})

const r = (props.size - props.thick) / 2
const c = 2 * Math.PI * r
const offset = c * (1 - Math.min(1, props.value))
</script>

<template>
  <div :style="{ position: 'relative', width: `${size}px`, height: `${size}px` }">
    <svg :width="size" :height="size" :style="{ transform: 'rotate(-90deg)' }">
      <circle :cx="size / 2" :cy="size / 2" :r="r" fill="none" :stroke="track" :stroke-width="thick" />
      <circle
        :cx="size / 2" :cy="size / 2" :r="r" fill="none" :stroke="color" :stroke-width="thick"
        stroke-linecap="round" :stroke-dasharray="c" :stroke-dashoffset="offset"
        style="transition: stroke-dashoffset .7s cubic-bezier(.22,1,.36,1)"
      />
    </svg>
    <div style="position: absolute; inset: 0; display: flex; align-items: center; justify-content: center">
      <slot />
    </div>
  </div>
</template>
