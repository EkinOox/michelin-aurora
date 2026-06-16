<script setup lang="ts">
const router = useRouter()
const { login } = useAuth()

const email = ref('')
const password = ref('')
const loading = ref(false)
const error = ref('')

async function submit() {
  error.value = ''
  loading.value = true
  try {
    await login({ email: email.value, password: password.value })
    router.push('/home')
  } catch (e: any) {
    error.value = e?.data?.error ?? 'Identifiants invalides.'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="screen">
    <AppHeader title="Connexion" sub="Accédez à votre plateforme Aurora." :on-back="() => router.push('/')" />
    <div class="screen-scroll pad" style="padding-top: 8px">
      <form class="rise" style="display: flex; flex-direction: column; gap: 14px" @submit.prevent="submit">
        <label class="small" style="display: flex; flex-direction: column; gap: 6px">
          Email
          <input
            v-model="email"
            type="email"
            required
            autocomplete="email"
            class="input"
            placeholder="vous@exemple.com"
          >
        </label>
        <label class="small" style="display: flex; flex-direction: column; gap: 6px">
          Mot de passe
          <input
            v-model="password"
            type="password"
            required
            autocomplete="current-password"
            class="input"
            placeholder="••••••••"
          >
        </label>

        <div v-if="error" class="small" style="color: var(--red)">{{ error }}</div>

        <button class="btn btn-blue btn-block" type="submit" :disabled="loading">
          {{ loading ? 'Connexion…' : 'Se connecter' }} <Icon v-if="!loading" name="arrow" :size="20" />
        </button>

        <NuxtLink to="/register" class="small" style="text-align: center; color: var(--ink-3)">
          Pas encore de compte ? <b style="color: var(--blue)">Créer mon profil</b>
        </NuxtLink>
      </form>
    </div>
  </div>
</template>
