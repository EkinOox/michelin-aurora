import type { AsyncData } from 'nuxt/app'

function authHeaders(): Record<string, string> {
  const token = useAuthToken()
  return token.value ? { Authorization: `Bearer ${token.value}` } : {}
}

export function useApiFetch<T, DefaultT = T>(path: string | (() => string), opts: Record<string, any> = {}): AsyncData<T | DefaultT, unknown> {
  const apiBase = useApiBase()
  const url = typeof path === 'function' ? () => `${apiBase}${path()}` : () => `${apiBase}${path}`

  return useFetch(url, {
    server: false,
    ...opts,
    headers: { ...authHeaders(), ...opts.headers },
  } as any) as unknown as AsyncData<T | DefaultT, unknown>
}

export async function $apiFetch<T>(path: string, opts: Parameters<typeof $fetch>[1] = {}): Promise<T> {
  const apiBase = useApiBase()
  return $fetch<T>(`${apiBase}${path}`, {
    ...opts,
    headers: { ...authHeaders(), ...(opts?.headers as Record<string, string> | undefined) },
  })
}
