<template>
  <section class="border-r border-slate-200 bg-white overflow-y-auto flex flex-col">
    <div class="px-4 py-3 border-b border-slate-200">
      <input
        v-model="store.search"
        type="search"
        placeholder="Search subject, from, to"
        class="w-full text-sm px-3 py-1.5 border border-slate-300 rounded focus:outline-none focus:ring-2 focus:ring-slate-400"
        @input="debouncedFetch"
      />
    </div>
    <ul class="flex-1 divide-y divide-slate-100">
      <li v-for="email in store.list" :key="email.id">
        <button
          class="w-full text-left px-4 py-3 hover:bg-slate-50"
          :class="[
            email.id === selectedId ? 'bg-slate-100' : '',
            !email.read_at ? 'font-medium' : 'text-slate-500',
          ]"
          @click="$emit('select', email.id)"
        >
          <div class="text-sm truncate">{{ email.from }}</div>
          <div class="text-sm truncate">{{ email.subject || '(no subject)' }}</div>
          <div class="text-xs text-slate-400">{{ formatDate(email.created_at) }}</div>
        </button>
      </li>
    </ul>
    <div v-if="store.loading" class="px-4 py-3 text-xs text-slate-400">Loading…</div>
    <div v-else-if="!store.list.length" class="px-4 py-3 text-xs text-slate-400">No emails yet.</div>
  </section>
</template>

<script setup>
import { onMounted, onUnmounted, ref, toRef, watch } from 'vue'
import { useEmailStore } from '../stores/emails'
import { useInboxRealtime } from '../composables/useInboxRealtime'

const props = defineProps({
  inboxId: { type: Number, required: true },
  selectedId: { type: Number, default: null },
})
defineEmits(['select'])

const store = useEmailStore()
const debounceTimer = ref(null)

function debouncedFetch() {
  clearTimeout(debounceTimer.value)
  debounceTimer.value = setTimeout(() => store.fetchForInbox(props.inboxId), 300)
}

function formatDate(iso) {
  if (!iso) return ''
  return new Date(iso).toLocaleString()
}

watch(() => props.inboxId, id => store.fetchForInbox(id), { immediate: true })

// Realtime: broadcast path subscribes; polling path uses setInterval.
// Both paths are gated by MAILULATOR_CONFIG.realtime.enabled.
const rt = window.MAILULATOR_CONFIG?.realtime ?? {}
const broadcast = useInboxRealtime(toRef(props, 'inboxId'), () => store.fetchForInbox(props.inboxId))

let poll = null
onMounted(() => {
  if (!rt.enabled || rt.mode !== 'polling') return
  const ms = (rt.pollInterval ?? 3) * 1000
  poll = setInterval(() => store.fetchForInbox(props.inboxId), ms)
})
onUnmounted(() => poll && clearInterval(poll))
</script>
