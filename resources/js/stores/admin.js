import { defineStore } from 'pinia'
import { http } from '../lib/http'

export const useAdminStore = defineStore('admin', {
  state: () => ({
    inboxes: [],
    loading: false,
    saving: false,
    error: null,
    revealKey: null, // { inboxId, plaintext } — shown once after create/regenerate
  }),
  actions: {
    async fetch() {
      this.loading = true
      try {
        const { data } = await http.get('/inboxes')
        this.inboxes = data.data ?? data
      } finally {
        this.loading = false
      }
    },
    async create({ name, retention_days }) {
      this.saving = true
      try {
        const { data } = await http.post('/inboxes', { name, retention_days })
        this.inboxes.push(data.inbox)
        this.revealKey = { inboxId: data.inbox.id, plaintext: data.plaintext_key }
      } finally {
        this.saving = false
      }
    },
    async update(id, payload) {
      const { data } = await http.patch(`/inboxes/${id}`, payload)
      const i = this.inboxes.findIndex(x => x.id === id)
      if (i !== -1) this.inboxes[i] = { ...this.inboxes[i], ...data.inbox }
    },
    async regenerate(id) {
      const { data } = await http.post(`/inboxes/${id}/regenerate-key`)
      this.revealKey = { inboxId: id, plaintext: data.plaintext_key }
    },
    async destroy(id) {
      await http.delete(`/inboxes/${id}`)
      this.inboxes = this.inboxes.filter(x => x.id !== id)
    },
    dismissKey() {
      this.revealKey = null
    },
  },
})
