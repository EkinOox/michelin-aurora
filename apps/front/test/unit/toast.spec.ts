import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'

// ─────────────────────────────────────────────────────────
// useToast — système de notification éphémère (timer auto)
// ─────────────────────────────────────────────────────────
describe('useToast', () => {
  beforeEach(() => {
    vi.useFakeTimers()
    // Repart d'un état propre entre chaque test (state partagé via useState).
    useToast().toast.value = null
  })

  afterEach(() => {
    vi.useRealTimers()
  })

  // ── Test 1 : show() rend le toast visible avec son message ──
  it('expose le message et l icone passes a show()', () => {
    const { toast, show } = useToast()

    show('Sortie enregistree', 'check')

    expect(toast.value).not.toBeNull()
    expect(toast.value?.msg).toBe('Sortie enregistree')
    expect(toast.value?.icon).toBe('check')
  })

  // ── Test 2 : le toast disparait tout seul apres le delai ──
  it('remet le toast a null apres 2200ms', () => {
    const { toast, show } = useToast()

    show('Message temporaire')
    expect(toast.value).not.toBeNull()

    vi.advanceTimersByTime(2200)

    expect(toast.value).toBeNull()
  })

  // ── Test 3 : un nouveau show() relance le timer (pas de fermeture prematuree) ──
  it('rearme le timer quand show() est rappele avant la fin', () => {
    const { toast, show } = useToast()

    show('Premier')
    vi.advanceTimersByTime(2000) // presque expire

    show('Second') // doit relancer un timer de 2200ms
    vi.advanceTimersByTime(2000) // total 4000ms depuis le 1er, mais 2000ms depuis le 2e
    expect(toast.value?.msg).toBe('Second')

    vi.advanceTimersByTime(200) // complete le 2e timer
    expect(toast.value).toBeNull()
  })
})
