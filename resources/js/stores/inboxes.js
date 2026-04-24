import { defineStore } from 'pinia'
import { http } from '../lib/http'

export const useInboxStore = defineStore('inboxes', {
  state: () => ({
    list: [],
    loading: false,
    error: null,
  }),
  actions: {
    async fetch() {
      this.loading = true
      this.error = null
      try {
        const { data } = await http.get('/inboxes')
        this.list = data.data ?? data
      } catch (e) {
        this.error = e.message
      } finally {
        this.loading = false
      }
    },
    async refresh() {
      try {
        const { data } = await http.get('/inboxes')
        const fresh = data.data ?? data
        for (const incoming of fresh) {
          const existing = this.list.find(i => i.id === incoming.id)
          if (existing) Object.assign(existing, incoming)
          else this.list.push(incoming)
        }
        this.list = this.list.filter(i => fresh.some(f => f.id === i.id))
      } catch {}
    },
    adjustUnread(inboxId, delta) {
      const inbox = this.list.find(i => i.id === inboxId)
      if (!inbox) return
      inbox.unread_count = Math.max(0, (inbox.unread_count ?? 0) + delta)
    },
  },
})
