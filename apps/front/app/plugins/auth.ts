export default defineNuxtPlugin(async () => {
  const { token, user, fetchMe, logout } = useAuth()

  if (token.value && !user.value) {
    try {
      await fetchMe()
    } catch {
      logout()
    }
  }
})
