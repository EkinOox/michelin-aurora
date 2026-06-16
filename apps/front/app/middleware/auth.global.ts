const PUBLIC_PATHS = new Set(['/', '/login', '/register'])

export default defineNuxtRouteMiddleware((to) => {
  if (PUBLIC_PATHS.has(to.path)) {
    return
  }

  const token = useAuthToken()
  if (!token.value) {
    return navigateTo('/login')
  }
})
