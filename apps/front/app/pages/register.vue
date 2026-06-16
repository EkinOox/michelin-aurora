<script setup lang="ts">
const router = useRouter()
const { register } = useAuth()

const email = ref('')
const password = ref('')
const name = ref('')
const city = ref('')
const loading = ref(false)
const error = ref('')

async function submit() {
  error.value = ''
  loading.value = true
  try {
    await register({ email: email.value, password: password.value, name: name.value, city: city.value })
    router.push('/onboarding')
  } catch (e: any) {
    error.value = e?.data?.error ?? 'Inscription impossible.'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="screen">
    <AppHeader title="Créer mon compte" sub="Quelques infos pour démarrer votre profil cycliste." :on-back="() => router.push('/')" />
    <div class="screen-scroll pad" style="padding-top: 8px">
      <form class="rise" style="display: flex; flex-direction: column; gap: 14px" @submit.prevent="submit">
        <label class="small" style="display: flex; flex-direction: column; gap: 6px">
          Nom
          <input v-model="name" type="text" required autocomplete="name" class="input" placeholder="Votre nom">
        </label>
        <label class="small" style="display: flex; flex-direction: column; gap: 6px">
          Ville
          <input v-model="city" type="text" autocomplete="address-level2" class="input" placeholder="Clermont-Ferrand">
        </label>
        <label class="small" style="display: flex; flex-direction: column; gap: 6px">
          Email
          <input v-model="email" type="email" required autocomplete="email" class="input" placeholder="vous@exemple.com">
        </label>
        <label class="small" style="display: flex; flex-direction: column; gap: 6px">
          Mot de passe
          <input v-model="password" type="password" required minlength="8" autocomplete="new-password" class="input" placeholder="8 caractères minimum">
        </label>

        <div v-if="error" class="small" style="color: var(--red)">{{ error }}</div>

        <button class="btn btn-blue btn-block" type="submit" :disabled="loading">
          {{ loading ? 'Création…' : 'Créer mon profil cycliste' }} <Icon v-if="!loading" name="arrow" :size="20" />
        </button>

        <NuxtLink to="/login" class="small" style="text-align: center; color: var(--ink-3)">
          Déjà un compte ? <b style="color: var(--blue)">Se connecter</b>
        </NuxtLink>
      </form>
    </div>
  </div>
</template>
