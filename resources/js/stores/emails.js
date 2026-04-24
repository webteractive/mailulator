import { defineStore } from 'pinia'
import { http } from '../lib/http'
import { useInboxStore } from './inboxes'

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
        const row = this.list.find(e => e.id === emailId)
        if (row) row.read_at = this.current.read_at
        if (this.current.inbox_id) {
          useInboxStore().adjustUnread(this.current.inbox_id, -1)
        }
      }
    },
    async destroy(emailId) {
      await http.delete(`/emails/${emailId}`)
      const removed = this.list.find(e => e.id === emailId)
      this.list = this.list.filter(e => e.id !== emailId)
      if (this.current?.id === emailId) this.current = null
      if (removed && !removed.read_at && removed.inbox_id) {
        useInboxStore().adjustUnread(removed.inbox_id, -1)
      }
    },
    async markAllRead(inboxId) {
      await http.post(`/inboxes/${inboxId}/mark-read`)
      const now = new Date().toISOString()
      for (const e of this.list) e.read_at ??= now
      if (this.current && !this.current.read_at) this.current.read_at = now
      const inbox = useInboxStore().list.find(i => i.id === inboxId)
      if (inbox) inbox.unread_count = 0
    },
    async deleteAll(inboxId) {
      await http.delete(`/inboxes/${inboxId}/emails`)
      this.list = []
      this.current = null
      const inbox = useInboxStore().list.find(i => i.id === inboxId)
      if (inbox) inbox.unread_count = 0
    },
    async toggleRead(emailId) {
      const email = this.current?.id === emailId ? this.current : this.list.find(e => e.id === emailId)
      if (!email) return
      await http.post(`/emails/${emailId}/read`)
      const wasRead = !!email.read_at
      email.read_at = wasRead ? null : new Date().toISOString()
      const row = this.list.find(e => e.id === emailId)
      if (row && row !== email) row.read_at = email.read_at
      if (email.inbox_id) {
        useInboxStore().adjustUnread(email.inbox_id, wasRead ? 1 : -1)
      }
    },
  },
})
