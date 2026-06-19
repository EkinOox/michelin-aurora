<script setup lang="ts">
const route  = useRoute()
const router = useRouter()
const { logout } = useAuth()

function handleLogout() {
  logout()
  router.push('/')
}

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

    <!-- Déconnexion — visible uniquement en sidebar desktop -->
    <div class="tb-logout">
      <button class="logout-btn" @click="handleLogout">
        <span class="logout-icon">
          <Icon name="logout" :size="18" color="rgba(255,255,255,.7)" />
        </span>
        <span class="logout-label">Se déconnecter</span>
      </button>
    </div>

    <!-- Footer — visible uniquement en sidebar desktop -->
    <div class="tb-footer">
      <span class="tiny tb-copy">© 2025 Michelin</span>
    </div>
  </nav>
</template>

<style scoped>
.logout-btn {
  display: flex;
  align-items: center;
  gap: 10px;
  width: 100%;
  padding: 10px 12px;
  border-radius: 12px;
  border: 1px solid rgba(255,255,255,.1);
  background: rgba(255,255,255,.06);
  color: rgba(255,255,255,.6);
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  transition: background .15s, color .15s, border-color .15s;
  margin-bottom: 10px;
}
.logout-btn:hover {
  background: rgba(200,16,46,.2);
  border-color: rgba(200,16,46,.35);
  color: #ff8a8a;
}
.logout-icon {
  width: 32px;
  height: 32px;
  border-radius: 9px;
  background: rgba(255,255,255,.08);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  transition: background .15s;
}
.logout-btn:hover .logout-icon {
  background: rgba(200,16,46,.25);
}
.logout-label {
  flex: 1;
  text-align: left;
}
</style>
