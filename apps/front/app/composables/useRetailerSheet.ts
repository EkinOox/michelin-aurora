export interface RetailerSheetProduct {
  name: string
  color_token?: string | null
  bike_type?: string | null
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
