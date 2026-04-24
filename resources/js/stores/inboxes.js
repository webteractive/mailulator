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
  },
})
