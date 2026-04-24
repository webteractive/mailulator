// Laravel Echo is dynamically imported so teams running in polling mode pay
// zero bundle cost for the broadcasting stack.

let cachedEcho = null

export async function getEcho() {
  if (cachedEcho) return cachedEcho

  const rt = window.MAILULATOR_CONFIG?.realtime ?? {}
  if (!rt.enabled || rt.mode !== 'broadcast') return null
  if (!rt.echo?.key) {
    console.warn('[mailulator] broadcast mode set but no echo.key configured — falling back to polling')
    return null
  }

  const [{ default: Echo }, Pusher] = await Promise.all([
    import('laravel-echo'),
    import('pusher-js').then(m => m.default),
  ])

  window.Pusher = Pusher

  cachedEcho = new Echo({
    broadcaster: rt.broadcaster === 'reverb' ? 'reverb' : 'pusher',
    key: rt.echo.key,
    cluster: rt.echo.cluster,
    wsHost: rt.echo.host,
    wsPort: rt.echo.port ? Number(rt.echo.port) : undefined,
    wssPort: rt.echo.port ? Number(rt.echo.port) : undefined,
    forceTLS: (rt.echo.scheme ?? 'https') === 'https',
    enabledTransports: ['ws', 'wss'],
    authEndpoint: '/broadcasting/auth',
  })

  return cachedEcho
}
