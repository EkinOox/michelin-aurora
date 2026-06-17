<script setup lang="ts">
const route = useRoute()

const cur = computed(() => {
  const p = route.path
  if (p === '/home') return 'home'
  if (p.startsWith('/routes')) return 'routes'
  if (p.startsWith('/community')) return 'community'
  if (p === '/rewards') return 'rewards'
  if (p === '/profile') return 'profile'
  if (p === '/events' || p === '/store') return 'home'
  return ''
})

function isActive(key: string) {
  return cur.value === key
}
</script>

<template>
  <nav class="tabbar">
    <!-- Brand — visible uniquement en sidebar desktop -->
    <div class="tb-brand">
      <div class="tb-wordmark">AUR<span class="tb-o">O</span>RA</div>
      <MichelinLogo :height="16" class="tb-mich" />
    </div>

    <!-- Liens de navigation -->
    <div class="tb-links">
      <NuxtLink to="/home" :class="['tab', isActive('home') ? 'is-active' : '']">
        <span class="dot" />
        <Icon name="home" :size="22" :stroke="isActive('home') ? 2.2 : 2" />
        <span class="tab-label">Accueil</span>
      </NuxtLink>
      <NuxtLink to="/routes" :class="['tab', isActive('routes') ? 'is-active' : '']">
        <span class="dot" />
        <Icon name="route" :size="22" :stroke="isActive('routes') ? 2.2 : 2" />
        <span class="tab-label">Routes</span>
      </NuxtLink>
      <NuxtLink to="/ride" class="tab-center" aria-label="Démarrer une sortie">
        <Icon name="play" :size="24" color="#fff" />
      </NuxtLink>
      <NuxtLink to="/community" :class="['tab', isActive('community') ? 'is-active' : '']">
        <span class="dot" />
        <Icon name="users" :size="22" :stroke="isActive('community') ? 2.2 : 2" />
        <span class="tab-label">Riders</span>
      </NuxtLink>
      <NuxtLink to="/profile" :class="['tab', isActive('profile') ? 'is-active' : '']">
        <span class="dot" />
        <Icon name="user" :size="22" :stroke="isActive('profile') ? 2.2 : 2" />
        <span class="tab-label">Profil</span>
      </NuxtLink>
    </div>

    <!-- Footer — visible uniquement en sidebar desktop -->
    <div class="tb-footer">
      <span class="tiny tb-copy">© 2025 Michelin LB 2 Wheels</span>
    </div>
  </nav>
</template>
