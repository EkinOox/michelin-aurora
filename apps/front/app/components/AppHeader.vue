<script setup lang="ts">
withDefaults(defineProps<{
  title?: string
  sub?: string
  onBack?: (() => void) | null
  light?: boolean
}>(), {
  title: '',
  sub: '',
  onBack: null,
  light: false,
})
</script>

<template>
  <div class="pad safe-top" style="padding-bottom: 10px">
    <div class="between" :style="{ marginBottom: title ? '12px' : '0' }">
      <button v-if="onBack" :class="['iconbtn', light ? 'iconbtn-dark' : '']" @click="onBack">
        <Icon name="chevL" :size="20" />
      </button>
      <span v-else style="width: 44px" />
      <slot name="right">
        <span style="width: 44px" />
      </slot>
    </div>
    <div v-if="title" class="h-lg" :style="{ color: light ? '#fff' : 'var(--ink)' }">{{ title }}</div>
    <div v-if="sub" class="small" style="margin-top: 4px">{{ sub }}</div>
  </div>
</template>
