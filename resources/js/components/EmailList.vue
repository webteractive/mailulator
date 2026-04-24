<template>
  <section class="border-r bg-card overflow-y-auto flex flex-col">
    <div class="h-14 px-3 border-b flex items-center gap-2">
      <input
        v-model="store.search"
        type="search"
        placeholder="Search subject, from, to"
        class="flex-1 h-9 text-sm px-3 rounded-md border border-input bg-background focus:outline-none focus:ring-2 focus:ring-ring"
        @input="debouncedFetch"
      />
      <Button
        variant="ghost"
        size="icon"
        class="h-8 w-8"
        title="Mark all read"
        :disabled="!store.list.length"
        @click="markAllRead"
      >
        <CheckCheck class="h-4 w-4" />
      </Button>
      <Button
        variant="ghost"
        size="icon"
        class="h-8 w-8 text-destructive hover:text-destructive"
        title="Delete all"
        :disabled="!store.list.length"
        @click="confirmDeleteAll = true"
      >
        <Trash2 class="h-4 w-4" />
      </Button>
    </div>
    <ul class="flex-1 divide-y">
      <li
        v-for="email in store.list"
        :key="email.id"
        class="group relative"
        :class="email.id === selectedId
          ? 'bg-slate-200 dark:bg-slate-700'
          : 'hover:bg-slate-100 dark:hover:bg-slate-800'"
      >
        <button
          type="button"
          class="w-full text-left px-4 py-3 pr-20"
          :class="!email.read_at ? 'font-medium' : 'text-muted-foreground'"
          @click="$emit('select', email.id)"
        >
          <div class="text-sm truncate flex items-center gap-2">
            <span v-if="!email.read_at" class="h-1.5 w-1.5 rounded-full bg-primary shrink-0" />
            <span class="truncate">{{ email.from }}</span>
          </div>
          <div class="text-sm truncate">{{ email.subject || '(no subject)' }}</div>
          <div class="text-xs text-muted-foreground">{{ formatDate(email.created_at) }}</div>
        </button>
        <div
          class="absolute right-2 top-1/2 -translate-y-1/2 flex items-center gap-0.5 opacity-0 group-hover:opacity-100 focus-within:opacity-100 transition-opacity bg-card border rounded-md shadow-sm"
        >
          <Button
            variant="ghost"
            size="icon"
            class="h-7 w-7"
            :title="email.read_at ? 'Mark as unread' : 'Mark as read'"
            @click.stop="toggleRead(email)"
          >
            <MailOpen v-if="email.read_at" class="h-3.5 w-3.5" />
            <Mail v-else class="h-3.5 w-3.5" />
          </Button>
          <Button
            variant="ghost"
            size="icon"
            class="h-7 w-7 text-destructive hover:text-destructive"
            title="Delete"
            @click.stop="askDelete(email)"
          >
            <Trash2 class="h-3.5 w-3.5" />
          </Button>
        </div>
      </li>
    </ul>
    <div v-if="store.loading" class="px-4 py-3 text-xs text-muted-foreground">Loading…</div>
    <div v-else-if="!store.list.length" class="flex-1 flex flex-col items-center justify-center text-muted-foreground py-10 px-4">
      <Inbox class="h-8 w-8 mb-2 opacity-50" />
      <p class="text-sm">No messages yet</p>
    </div>


    <AlertDialog :open="confirmDeleteAll" @update:open="(v) => !v && (confirmDeleteAll = false)">
      <AlertDialogContent>
        <AlertDialogHeader>
          <AlertDialogTitle>Delete all emails?</AlertDialogTitle>
          <AlertDialogDescription>
            All emails and attachments in this inbox will be permanently deleted.
          </AlertDialogDescription>
        </AlertDialogHeader>
        <AlertDialogFooter>
          <Button variant="outline" @click="confirmDeleteAll = false">Cancel</Button>
          <Button variant="destructive" @click="runDeleteAll">Delete all</Button>
        </AlertDialogFooter>
      </AlertDialogContent>
    </AlertDialog>

    <AlertDialog :open="!!deleteTarget" @update:open="(v) => !v && (deleteTarget = null)">
      <AlertDialogContent>
        <AlertDialogHeader>
          <AlertDialogTitle>Delete email?</AlertDialogTitle>
          <AlertDialogDescription>
            "{{ deleteTarget?.subject || '(no subject)' }}" will be permanently removed.
          </AlertDialogDescription>
        </AlertDialogHeader>
        <AlertDialogFooter>
          <Button variant="outline" @click="deleteTarget = null">Cancel</Button>
          <Button variant="destructive" @click="runDelete">Delete</Button>
        </AlertDialogFooter>
      </AlertDialogContent>
    </AlertDialog>
  </section>
</template>

<script setup>
import { onMounted, onUnmounted, ref, toRef, watch } from 'vue'
import { CheckCheck, Trash2, Mail, MailOpen, Inbox } from 'lucide-vue-next'
import { useEmailStore } from '../stores/emails'
import { useInboxStore } from '../stores/inboxes'
import { useInboxRealtime } from '../composables/useInboxRealtime'
import { Button } from '@/components/ui/button'
import {
  AlertDialog,
  AlertDialogContent,
  AlertDialogHeader,
  AlertDialogFooter,
  AlertDialogTitle,
  AlertDialogDescription,
} from '@/components/ui/alert-dialog'

const props = defineProps({
  inboxId: { type: Number, required: true },
  selectedId: { type: Number, default: null },
})
defineEmits(['select'])

const store = useEmailStore()
const inboxes = useInboxStore()
const debounceTimer = ref(null)
const confirmDeleteAll = ref(false)
const deleteTarget = ref(null)

function toggleRead(email) {
  store.toggleRead(email.id)
}

function askDelete(email) {
  deleteTarget.value = email
}

async function runDelete() {
  const target = deleteTarget.value
  deleteTarget.value = null
  if (target) await store.destroy(target.id)
}

function debouncedFetch() {
  clearTimeout(debounceTimer.value)
  debounceTimer.value = setTimeout(() => store.fetchForInbox(props.inboxId), 300)
}

function formatDate(iso) {
  if (!iso) return ''
  return new Date(iso).toLocaleString()
}

async function markAllRead() {
  await store.markAllRead(props.inboxId)
}

async function runDeleteAll() {
  confirmDeleteAll.value = false
  await store.deleteAll(props.inboxId)
}

watch(() => props.inboxId, id => store.fetchForInbox(id), { immediate: true })

const rt = window.MAILULATOR_CONFIG?.realtime ?? {}
async function refreshAll() {
  await Promise.all([
    store.fetchForInbox(props.inboxId),
    inboxes.refresh(),
  ])
}

const broadcast = useInboxRealtime(toRef(props, 'inboxId'), refreshAll)

let poll = null
onMounted(() => {
  if (!rt.enabled || rt.mode !== 'polling') return
  const ms = (rt.pollInterval ?? 3) * 1000
  poll = setInterval(refreshAll, ms)
})
onUnmounted(() => poll && clearInterval(poll))
</script>
