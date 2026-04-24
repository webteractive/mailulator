<template>
  <section class="bg-background overflow-y-auto flex flex-col border-l">
    <div v-if="email" class="flex-1 flex flex-col">
      <header class="h-14 px-6 border-b flex items-center gap-3">
        <h1 class="text-lg font-semibold truncate flex-1">{{ email.subject || '(no subject)' }}</h1>
        <Button
          variant="ghost"
          size="sm"
          :title="email.read_at ? 'Mark as unread' : 'Mark as read'"
          @click="toggleRead"
        >
          <MailOpen v-if="email.read_at" class="h-4 w-4 mr-1.5" />
          <Mail v-else class="h-4 w-4 mr-1.5" />
          {{ email.read_at ? 'Mark unread' : 'Mark read' }}
        </Button>
        <Button
          variant="ghost"
          size="icon"
          class="h-8 w-8 text-destructive hover:text-destructive"
          title="Delete"
          @click="confirmDelete = true"
        >
          <Trash2 class="h-4 w-4" />
        </Button>
      </header>
      <div class="px-6 py-3 border-b text-sm text-muted-foreground space-y-0.5">
        <div><span class="text-muted-foreground/70">From:</span> {{ email.from }}</div>
        <div><span class="text-muted-foreground/70">To:</span> {{ (email.to ?? []).join(', ') }}</div>
        <div v-if="email.cc?.length"><span class="text-muted-foreground/70">Cc:</span> {{ email.cc.join(', ') }}</div>
      </div>
      <nav class="px-6 border-b flex gap-4 text-sm">
        <button
          v-for="t in tabs"
          :key="t"
          class="py-2 border-b-2"
          :class="tab === t ? 'border-primary font-medium' : 'border-transparent text-muted-foreground hover:text-foreground'"
          @click="tab = t"
        >
          {{ t }}
        </button>
      </nav>

      <div v-if="tab === 'HTML'" class="border-b px-6 py-2 flex items-center gap-1">
        <span class="text-xs text-muted-foreground mr-2">Device</span>
        <Button
          v-for="d in devices"
          :key="d.id"
          :variant="device === d.id ? 'secondary' : 'ghost'"
          size="sm"
          class="h-8 px-2"
          @click="device = d.id"
        >
          <component :is="d.icon" class="h-4 w-4 mr-1.5" />
          {{ d.label }}
        </Button>
        <span v-if="activeDevice.width" class="ml-auto text-xs tabular-nums text-muted-foreground">
          {{ activeDevice.width }}px
        </span>
      </div>

      <div class="flex-1 overflow-auto" :class="tab === 'HTML' ? 'bg-muted/40' : ''">
        <div v-if="tab === 'HTML'" class="min-h-full flex justify-center p-4">
          <iframe
            :src="email.preview_url"
            sandbox=""
            class="border bg-background shadow-sm rounded-md w-full h-full"
            :style="iframeStyle"
          />
        </div>
        <pre v-else-if="tab === 'Text'" class="p-6 text-sm whitespace-pre-wrap">{{ email.text_body || '(no text body)' }}</pre>
        <pre v-else-if="tab === 'Headers'" class="p-6 text-xs whitespace-pre-wrap">{{ JSON.stringify(email.headers ?? {}, null, 2) }}</pre>
        <ul v-else-if="tab === 'Attachments'" class="p-6 space-y-2">
          <li v-for="a in email.attachments" :key="a.id" class="flex items-center justify-between border rounded-md px-3 py-2 text-sm">
            <span>{{ a.filename }} <span class="text-muted-foreground">({{ formatSize(a.size) }})</span></span>
            <a :href="a.download_url" class="text-primary hover:underline text-xs">Download</a>
          </li>
          <li v-if="!email.attachments?.length" class="text-xs text-muted-foreground">No attachments.</li>
        </ul>
      </div>
    </div>
    <div v-else class="flex-1 flex items-center justify-center text-muted-foreground text-sm">Loading…</div>

    <AlertDialog :open="confirmDelete" @update:open="(v) => !v && (confirmDelete = false)">
      <AlertDialogContent>
        <AlertDialogHeader>
          <AlertDialogTitle>Delete this email?</AlertDialogTitle>
          <AlertDialogDescription>
            This email and its attachments will be permanently removed.
          </AlertDialogDescription>
        </AlertDialogHeader>
        <AlertDialogFooter>
          <Button variant="outline" @click="confirmDelete = false">Cancel</Button>
          <Button variant="destructive" @click="runDelete">Delete</Button>
        </AlertDialogFooter>
      </AlertDialogContent>
    </AlertDialog>
  </section>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import { Smartphone, Tablet, Monitor, Mail, MailOpen, Trash2 } from 'lucide-vue-next'
import { useEmailStore } from '@/stores/emails'
import { Button } from '@/components/ui/button'
import {
  AlertDialog,
  AlertDialogContent,
  AlertDialogHeader,
  AlertDialogFooter,
  AlertDialogTitle,
  AlertDialogDescription,
} from '@/components/ui/alert-dialog'

const props = defineProps({ emailId: { type: Number, required: true } })
const store = useEmailStore()
const tab = ref('HTML')
const tabs = ['HTML', 'Text', 'Headers', 'Attachments']
const email = computed(() => store.current)

const devices = [
  { id: 'mobile', label: 'Mobile', icon: Smartphone, width: 375 },
  { id: 'tablet', label: 'Tablet', icon: Tablet, width: 768 },
  { id: 'desktop', label: 'Desktop', icon: Monitor, width: null },
]
const device = ref('desktop')
const activeDevice = computed(() => devices.find(d => d.id === device.value))

const iframeStyle = computed(() => {
  const w = activeDevice.value.width
  return w ? { width: `${w}px`, maxWidth: '100%' } : { width: '100%' }
})

const confirmDelete = ref(false)

async function toggleRead() {
  await store.toggleRead(props.emailId)
}

async function runDelete() {
  confirmDelete.value = false
  await store.destroy(props.emailId)
}

watch(() => props.emailId, id => store.show(id), { immediate: true })

function formatSize(n) {
  if (!n) return '0 B'
  const units = ['B', 'KB', 'MB', 'GB']
  let i = 0, v = n
  while (v >= 1024 && i < units.length - 1) { v /= 1024; i++ }
  return `${v.toFixed(1)} ${units[i]}`
}
</script>
