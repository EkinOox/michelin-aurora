const PUBLIC_PATHS = new Set(['/', '/login', '/register', '/onboarding'])

export default defineNuxtRouteMiddleware((to) => {
  if (import.meta.server) return

  if (PUBLIC_PATHS.has(to.path)) return

  const token = useAuthToken()
  if (!token.value) return navigateTo('/login')
})
