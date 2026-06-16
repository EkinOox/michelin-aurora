export function useApiBase(): string {
  const config = useRuntimeConfig()
  return import.meta.server ? config.apiBaseInternal : config.public.apiBase
}
