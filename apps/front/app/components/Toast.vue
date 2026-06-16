<script setup lang="ts">
const { toast } = useToast()
</script>

<template>
  <div
    v-if="toast"
    style="position: fixed; left: 0; right: 0; bottom: 96px; z-index: 80; display: flex; justify-content: center; pointer-events: none"
  >
    <div
      class="rise"
      style="background: rgba(20,22,24,.94); color: #fff; border-radius: 999px; padding: 12px 18px;
      display: flex; align-items: center; gap: 9px; box-shadow: 0 12px 30px rgba(0,0,0,.3); max-width: 320px"
    >
      <Icon :name="toast.icon || 'check'" :size="17" color="var(--yellow)" />
      <span class="small" style="color: #fff; font-weight: 600">{{ toast.msg }}</span>
    </div>
  </div>
</template>
