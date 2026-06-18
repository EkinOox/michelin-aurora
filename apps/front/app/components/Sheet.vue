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
  <Teleport to="body">
    <div class="sheet-root" :class="{ 'is-open': open }">
      <!-- Backdrop -->
      <div class="sheet-backdrop" @click="emit('close')" />

      <!-- Panel -->
      <div class="sheet-panel" :style="{ height }">
        <div class="sheet-handle" />
        <slot />
      </div>
    </div>
  </Teleport>
  <Teleport to="body">
    <div :style="{ position: 'fixed', inset: 0, zIndex: 60, pointerEvents: open ? 'auto' : 'none' }">
      <!-- backdrop -->
      <div
        @click="emit('close')"
        :style="{
          position: 'absolute', inset: 0, background: 'rgba(15,17,18,.45)',
          opacity: open ? 1 : 0, transition: 'opacity .3s', backdropFilter: 'blur(3px)',
        }"
      />
      <!-- drawer -->
      <div
        :style="{
          position: 'absolute', left: 0, right: 0, bottom: 0,
          background: 'var(--surface)',
          borderRadius: '28px 28px 0 0',
          maxHeight: '88vh',
          display: 'flex', flexDirection: 'column',
          transform: open ? 'translateY(0)' : 'translateY(110%)',
          transition: 'transform .42s cubic-bezier(.22,1,.36,1)',
          boxShadow: '0 -10px 40px rgba(0,0,0,.18)',
        }"
      >
        <!-- drag handle -->
        <div style="padding: 10px 20px 0; flex-shrink: 0">
          <div style="width: 42px; height: 5px; border-radius: 99px; background: #E5E5E5; margin: 0 auto 14px" />
        </div>
        <!-- scrollable content -->
        <div style="overflow-y: auto; padding: 0 20px 28px; flex: 1; -webkit-overflow-scrolling: touch">
          <slot />
        </div>
      </div>
    </div>
  </Teleport>
</template>

<style scoped>
/* Overlay fixé sur le viewport — indépendant du scroll de la page */
.sheet-root {
  position: fixed;
  inset: 0;
  z-index: 200;
  pointer-events: none;
}
.sheet-root.is-open { pointer-events: auto; }

/* Fond semi-transparent */
.sheet-backdrop {
  position: absolute;
  inset: 0;
  background: rgba(15, 17, 18, .45);
  opacity: 0;
  transition: opacity .3s ease;
  backdrop-filter: blur(3px);
  -webkit-backdrop-filter: blur(3px);
  pointer-events: none;
}
.sheet-root.is-open .sheet-backdrop {
  opacity: 1;
  pointer-events: auto;
}

/* Panel bas de page */
.sheet-panel {
  position: absolute;
  left: 0;
  right: 0;
  bottom: 0;
  background: var(--surface);
  border-radius: 28px 28px 0 0;
  /* safe-area pour le home indicator iPhone */
  padding: 10px 20px calc(28px + env(safe-area-inset-bottom, 0px));
  box-sizing: border-box;
  overflow-x: hidden;
  overflow-y: auto;
  /* animation slide-up */
  transform: translateY(100%);
  transition: transform .42s cubic-bezier(.22, 1, .36, 1);
  box-shadow: 0 -12px 40px rgba(0, 0, 0, .20);
  /* évite que le panel aille plus haut que le viewport */
  max-height: 95dvh;
}
.sheet-root.is-open .sheet-panel {
  transform: translateY(0);
}

/* Trait de poignée */
.sheet-handle {
  width: 42px;
  height: 5px;
  border-radius: 99px;
  background: var(--line);
  margin: 0 auto 14px;
  flex-shrink: 0;
}
</style>
