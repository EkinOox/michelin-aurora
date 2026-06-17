import { fetchOverpass, osmToRoute } from '../../utils/cyclingRoutes'

export default defineEventHandler(async (event) => {
  const id = getRouterParam(event, 'id')

  const res = await fetchOverpass(`[out:json];relation(${id});out tags;`)
  const el = res.elements[0]

  if (!el) throw createError({ statusCode: 404, statusMessage: 'Route not found' })

  return osmToRoute(el)
})
