export default defineNuxtPlugin(() => {
  const token = useAuthToken()

  const stored = localStorage.getItem('aurora_token')
  if (stored) token.value = stored

  watch(token, (val) => {
    if (val) localStorage.setItem('aurora_token', val)
    else localStorage.removeItem('aurora_token')
  })
})
