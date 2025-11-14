export default defineNuxtRouteMiddleware((to) => {
  const ref = (to.query.ref as string | undefined) || undefined
  if (!ref) return
  // 7 days
  const maxAge = 7 * 24 * 60 * 60
  try {
    document.cookie = `mytheme_ref=${encodeURIComponent(ref)}; path=/; max-age=${maxAge}; SameSite=Lax`
  } catch (e) {
    // no-op
  }
})
