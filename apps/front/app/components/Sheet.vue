<script setup lang="ts">
withDefaults(defineProps<{
  open: boolean
  height?: string
}>(), {
  height: 'auto',
})

const emit = defineEmits<{ close: [] }>()
</script>

<template>
  <div :style="{ position: 'absolute', inset: 0, zIndex: 60, pointerEvents: open ? 'auto' : 'none' }">
    <div
      @click="emit('close')"
      :style="{
        position: 'absolute', inset: 0, background: 'rgba(15,17,18,.4)',
        opacity: open ? 1 : 0, transition: 'opacity .3s', backdropFilter: 'blur(2px)',
      }"
    />
    <div
      :style="{
        position: 'absolute', left: 0, right: 0, bottom: 0, background: 'var(--surface)',
        borderRadius: '28px 28px 0 0', padding: '10px 20px 28px', height,
        transform: open ? 'translateY(0)' : 'translateY(110%)', transition: 'transform .42s cubic-bezier(.22,1,.36,1)',
        boxShadow: '0 -10px 40px rgba(0,0,0,.18)',
      }"
    >
      <div style="width: 42px; height: 5px; border-radius: 99px; background: var(--line); margin: 0 auto 14px" />
      <slot />
    </div>
  </div>
</template>
