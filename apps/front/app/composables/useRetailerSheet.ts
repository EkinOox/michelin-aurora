export interface RetailerSheetProduct {
  name: string
}

export function useRetailerSheet() {
  const product = useState<RetailerSheetProduct | null>('aurora-retailer-sheet', () => null)

  function open(p?: RetailerSheetProduct) {
    product.value = p ?? { name: 'Produit Michelin' }
  }

  function close() {
    product.value = null
  }

  return { product, open, close }
}
