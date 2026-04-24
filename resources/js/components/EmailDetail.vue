<template>
  <section class="bg-white overflow-y-auto flex flex-col">
    <div v-if="email" class="flex-1 flex flex-col">
      <header class="px-6 py-4 border-b border-slate-200 space-y-1">
        <h1 class="text-lg font-semibold">{{ email.subject || '(no subject)' }}</h1>
        <div class="text-sm text-slate-600">
          <div><span class="text-slate-400">From:</span> {{ email.from }}</div>
          <div><span class="text-slate-400">To:</span> {{ (email.to ?? []).join(', ') }}</div>
          <div v-if="email.cc?.length"><span class="text-slate-400">Cc:</span> {{ email.cc.join(', ') }}</div>
        </div>
      </header>
      <nav class="px-6 border-b border-slate-200 flex gap-4 text-sm">
        <button v-for="t in tabs" :key="t" class="py-2 border-b-2" :class="tab === t ? 'border-slate-800 font-medium' : 'border-transparent text-slate-500'" @click="tab = t">
          {{ t }}
        </button>
      </nav>
      <div class="flex-1 overflow-auto">
        <iframe v-if="tab === 'HTML'" :src="email.preview_url" sandbox="" class="w-full h-full border-0" />
        <pre v-else-if="tab === 'Text'" class="p-6 text-sm whitespace-pre-wrap">{{ email.text_body || '(no text body)' }}</pre>
        <pre v-else-if="tab === 'Headers'" class="p-6 text-xs whitespace-pre-wrap">{{ JSON.stringify(email.headers ?? {}, null, 2) }}</pre>
        <ul v-else-if="tab === 'Attachments'" class="p-6 space-y-2">
          <li v-for="a in email.attachments" :key="a.id" class="flex items-center justify-between border border-slate-200 rounded px-3 py-2 text-sm">
            <span>{{ a.filename }} <span class="text-slate-400">({{ formatSize(a.size) }})</span></span>
            <a :href="a.download_url" class="text-blue-600 hover:underline text-xs">Download</a>
          </li>
          <li v-if="!email.attachments?.length" class="text-xs text-slate-400">No attachments.</li>
        </ul>
      </div>
    </div>
    <div v-else class="flex-1 flex items-center justify-center text-slate-400 text-sm">Loading…</div>
  </section>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import { useEmailStore } from '../stores/emails'

const props = defineProps({ emailId: { type: Number, required: true } })
const store = useEmailStore()
const tab = ref('HTML')
const tabs = ['HTML', 'Text', 'Headers', 'Attachments']
const email = computed(() => store.current)

watch(() => props.emailId, id => store.show(id), { immediate: true })

function formatSize(n) {
  if (!n) return '0 B'
  const units = ['B', 'KB', 'MB', 'GB']
  let i = 0, v = n
  while (v >= 1024 && i < units.length - 1) { v /= 1024; i++ }
  return `${v.toFixed(1)} ${units[i]}`
}
</script>
