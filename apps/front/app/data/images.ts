export const IMAGES: Record<string, string> = {
  bikeWhite: 'https://images.unsplash.com/photo-1485965120184-e220f721d03e?w=900&q=80',
  gravelRide: 'https://images.unsplash.com/photo-1534787238916-9ba6764efd4f?w=900&q=80',
  sunsetRide: 'https://images.unsplash.com/photo-1471506480208-91b3a4cc78be?w=900&q=80',
  gravelBike: 'https://images.unsplash.com/photo-1576435728678-68d0fbf94e91?w=900&q=80',
  peloton: 'https://images.unsplash.com/photo-1517649763962-0c623066013b?w=900&q=80',
  duo: 'https://images.unsplash.com/photo-1541625602330-2277a4c46182?w=900&q=80',
  roadBlack: 'https://images.unsplash.com/photo-1532298229144-0ec0c57515c7?w=900&q=80',
  bikepacking: 'https://images.unsplash.com/photo-1511994298241-608e28f14fde?w=900&q=80',
  yellowBike: 'https://images.unsplash.com/photo-1571333250630-f0230c320b6d?w=900&q=80',
}

export function imageFor(key?: string | null): string {
  if (!key) return IMAGES.bikeWhite
  if (key.startsWith('http://') || key.startsWith('https://')) return key
  return IMAGES[key] ?? IMAGES.bikeWhite
}
