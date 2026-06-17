import { fetchOverpass, osmToRoute } from '../utils/cyclingRoutes'

export default defineEventHandler(async (event) => {
  const query = getQuery(event)
  const lat = parseFloat(query.lat as string) || 43.5297
  const lon = parseFloat(query.lon as string) || 5.4474

  const overpassQuery = `
    [out:json][timeout:25];
    (
      relation[route=bicycle][name](around:50000,${lat},${lon});
      relation[route=mtb][name](around:50000,${lat},${lon});
    );
    out tags;
  `

  const res = await fetchOverpass(overpassQuery)

  return res.elements
    .filter(el => el.tags?.name)
    .slice(0, 30)
    .map(osmToRoute)
})
