import { describe, it, expect } from 'vitest'
import { mountSuspended } from '@nuxt/test-utils/runtime'
import { defineComponent } from 'vue'

// Composant d'exemple pour valider la chaîne de tests Vitest + Nuxt.
// À remplacer par des tests de vrais composants au fur et à mesure.
const HelloAurora = defineComponent({
  props: { name: { type: String, default: 'Aurora' } },
  template: '<p>Bonjour {{ name }}</p>',
})

describe('exemple de test unitaire', () => {
  it('monte un composant Vue et rend ses props', async () => {
    const wrapper = await mountSuspended(HelloAurora, { props: { name: 'Michelin' } })
    expect(wrapper.text()).toContain('Bonjour Michelin')
  })
})
