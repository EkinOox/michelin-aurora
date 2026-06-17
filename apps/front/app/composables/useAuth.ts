interface AuthUser {
  id: string
  email: string
  name: string
  city: string
  rewards_level: string
  total_points: number
}

const TOKEN_KEY = 'aurora_token'

export function useAuthToken() {
  return useState<string | null>(TOKEN_KEY, () => null)
}

export function useAuthUser() {
  return useState<AuthUser | null>('auth-user', () => null)
}

export function useAuth() {
  const apiBase = useApiBase()
  const token = useAuthToken()
  const user = useAuthUser()

  const isLoggedIn = computed(() => Boolean(token.value))

  async function register(payload: { email: string, password: string, name: string, city: string }) {
    await $fetch(`${apiBase}/api/auth/register`, { method: 'POST', body: payload })
    await login({ email: payload.email, password: payload.password })
  }

  async function login(payload: { email: string, password: string }) {
    const res = await $fetch<{ token: string }>(`${apiBase}/api/auth/login`, { method: 'POST', body: payload })
    token.value = res.token
    await fetchMe()
  }

  async function fetchMe() {
    if (!token.value) {
      user.value = null
      return
    }
    user.value = await $fetch<AuthUser>(`${apiBase}/api/auth/me`, {
      headers: { Authorization: `Bearer ${token.value}` },
    })
  }

  function logout() {
    token.value = null
    user.value = null
  }

  return { token, user, isLoggedIn, register, login, logout, fetchMe }
}
