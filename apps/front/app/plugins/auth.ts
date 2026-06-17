export default defineNuxtPlugin(async () => {
  if (import.meta.server) return

  const { token, user, fetchMe } = useAuth()

  if (token.value && !user.value) {
    try {
      await fetchMe()
    } catch {
      // Échec réseau temporaire — on garde le token, l'utilisateur reste connecté
    }
  }
})
