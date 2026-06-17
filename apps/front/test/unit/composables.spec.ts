import { describe, it, expect } from 'vitest'

// ─────────────────────────────────────────────────────────
// Test 1 : useRetailerSheet — open() sans argument utilise le fallback
// Le fallback 'Produit Michelin' doit s'afficher si aucun produit n'est passé.
// ─────────────────────────────────────────────────────────
describe('useRetailerSheet - open sans argument', () => {
  it('définit product avec le nom fallback quand open() est appelé sans argument', () => {
    const { product, open, close } = useRetailerSheet()
    close()

    open()

    expect(product.value).not.toBeNull()
    expect(product.value?.name).toBe('Produit Michelin')

    close()
  })
})

// ─────────────────────────────────────────────────────────
// Test 2 : useRetailerSheet — open(product) puis close()
// Vérifie que le state s'ouvre avec les bonnes données et se ferme proprement.
// ─────────────────────────────────────────────────────────
describe('useRetailerSheet - open avec produit puis close', () => {
  it('stocke le produit passé en argument puis close() remet null', () => {
    const { product, open, close } = useRetailerSheet()

    open({ name: 'Michelin Power Road TLR' })
    expect(product.value?.name).toBe('Michelin Power Road TLR')

    close()
    expect(product.value).toBeNull()
  })
})

// ─────────────────────────────────────────────────────────
// Test 3 : useArticleSheet — open(article) puis close()
// L'article sheet suit le même pattern : state à null au départ,
// peuplé par open(), remis à null par close().
// ─────────────────────────────────────────────────────────
describe('useArticleSheet - open/close cycle', () => {
  it('stocke l article puis close() remet item à null', () => {
    const { item, open, close } = useArticleSheet()
    close()

    const article = {
      tag: 'Actualité',
      title: 'Michelin lance un nouveau pneu gravel',
      date: '2025-06-01',
      read: '3 min',
      img: 'gravel.jpg',
      body: 'Contenu de l article...',
    }

    open(article)
    expect(item.value?.title).toBe('Michelin lance un nouveau pneu gravel')
    expect(item.value?.tag).toBe('Actualité')

    close()
    expect(item.value).toBeNull()
  })
})
