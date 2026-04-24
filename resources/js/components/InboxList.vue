<template>
  <aside class="border-r border-slate-200 bg-white overflow-y-auto">
    <div class="px-4 py-3 text-xs uppercase tracking-wide text-slate-500 font-semibold">Inboxes</div>
    <ul>
      <li v-for="inbox in store.list" :key="inbox.id">
        <button
          class="w-full text-left px-4 py-2 text-sm hover:bg-slate-50 flex items-center justify-between"
          :class="inbox.id === selectedId ? 'bg-slate-100 font-medium' : ''"
          @click="$emit('select', inbox.id)"
        >
          <span class="truncate">{{ inbox.name }}</span>
          <span v-if="inbox.unread_count" class="text-xs bg-slate-800 text-white rounded-full px-2 py-0.5">{{ inbox.unread_count }}</span>
        </button>
      </li>
    </ul>
    <div v-if="store.loading" class="px-4 py-3 text-xs text-slate-400">Loading…</div>
    <div v-if="!store.loading && !store.list.length" class="px-4 py-3 text-xs text-slate-400">No inboxes yet.</div>
  </aside>
</template>

<script setup>
import { onMounted } from 'vue'
import { useInboxStore } from '../stores/inboxes'

defineProps({ selectedId: { type: Number, default: null } })
defineEmits(['select'])

const store = useInboxStore()
onMounted(() => store.fetch())
</script>
