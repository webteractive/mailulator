import { defineStore } from 'pinia'
import { http } from '../lib/http'

export const useEmailStore = defineStore('emails', {
  state: () => ({
    list: [],
    current: null,
    loading: false,
    error: null,
    cursor: null,
    search: '',
  }),
  actions: {
    async fetchForInbox(inboxId, { append = false } = {}) {
      this.loading = true
      try {
        const { data } = await http.get(`/inboxes/${inboxId}/emails`, {
          params: { search: this.search, cursor: append ? this.cursor : undefined },
        })
        this.list = append ? [...this.list, ...(data.data ?? [])] : (data.data ?? [])
        this.cursor = data.next_cursor ?? null
      } finally {
        this.loading = false
      }
    },
    async show(emailId) {
      const { data } = await http.get(`/emails/${emailId}`)
      this.current = data.data ?? data
      if (this.current && !this.current.read_at) {
        await http.post(`/emails/${emailId}/read`)
        this.current.read_at = new Date().toISOString()
      }
    },
    async destroy(emailId) {
      await http.delete(`/emails/${emailId}`)
      this.list = this.list.filter(e => e.id !== emailId)
      if (this.current?.id === emailId) this.current = null
    },
  },
})
