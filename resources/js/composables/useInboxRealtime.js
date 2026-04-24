import { onBeforeUnmount, watch } from 'vue'
import { getEcho } from '../lib/echo'

/**
 * Subscribes to the `mailulator.inbox.{id}` private channel when realtime
 * broadcasting is enabled and a broadcaster is configured. Calls `onReceived`
 * with the new-email payload. Falls back silently to a no-op when:
 *   - realtime.enabled = false
 *   - realtime.mode = polling
 *   - no echo.key configured (logged warning, polling keeps working)
 */
export function useInboxRealtime(inboxIdRef, onReceived) {
  const rt = window.MAILULATOR_CONFIG?.realtime ?? {}
  if (!rt.enabled || rt.mode !== 'broadcast') return { active: false }

  let channel = null
  let currentId = null

  async function subscribe(id) {
    unsubscribe()
    if (!id) return

    const echo = await getEcho()
    if (!echo) return

    currentId = id
    channel = echo.private(`mailulator.inbox.${id}`)
    channel.listen('.email.received', payload => onReceived(payload))
  }

  function unsubscribe() {
    if (channel && currentId) {
      try {
        channel.stopListening('.email.received')
      } catch {}
    }
    channel = null
    currentId = null
  }

  watch(inboxIdRef, id => subscribe(id), { immediate: true })
  onBeforeUnmount(unsubscribe)

  return { active: true }
}
