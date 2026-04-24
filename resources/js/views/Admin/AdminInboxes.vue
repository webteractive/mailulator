<template>
  <div class="max-w-5xl mx-auto p-6 space-y-6">
    <header class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-semibold tracking-tight">Inboxes</h1>
        <p class="text-sm text-muted-foreground">Manage inboxes, API keys, and retention.</p>
      </div>
      <Button @click="openCreate">
        <Plus class="h-4 w-4 mr-2" /> New inbox
      </Button>
    </header>

    <Card>
      <div v-if="store.loading" class="p-6 text-sm text-muted-foreground">Loading…</div>
      <Table v-else>
        <TableHeader>
          <TableRow>
            <TableHead>Name</TableHead>
            <TableHead>Retention</TableHead>
            <TableHead>Last used</TableHead>
            <TableHead class="w-64 text-right">Actions</TableHead>
          </TableRow>
        </TableHeader>
        <TableBody>
          <TableRow v-for="inbox in store.inboxes" :key="inbox.id">
            <TableCell>
              <div class="flex items-center gap-2">
                <span
                  class="h-3 w-3 rounded-full border"
                  :style="{ background: inbox.color ?? 'transparent' }"
                />
                <span class="font-medium">{{ inbox.name }}</span>
                <span v-if="inbox.is_default" class="text-xs text-muted-foreground">(default)</span>
              </div>
            </TableCell>
            <TableCell class="text-muted-foreground">
              {{ inbox.retention_days ? `${inbox.retention_days} days` : 'Forever' }}
            </TableCell>
            <TableCell class="text-muted-foreground">
              {{ inbox.last_used_at ? formatDate(inbox.last_used_at) : '—' }}
            </TableCell>
            <TableCell class="text-right space-x-1">
              <Button variant="ghost" size="sm" @click="openSettings(inbox)">
                <Settings class="h-3.5 w-3.5 mr-1" /> Settings
              </Button>
              <Button variant="ghost" size="sm" @click="regenerate(inbox)">
                <KeyRound class="h-3.5 w-3.5 mr-1" /> Key
              </Button>
              <Button
                variant="ghost"
                size="sm"
                class="text-destructive hover:text-destructive"
                :disabled="inbox.is_default"
                @click="destroy(inbox)"
              >
                <Trash2 class="h-3.5 w-3.5" />
              </Button>
            </TableCell>
          </TableRow>
        </TableBody>
      </Table>
      <TableEmpty v-if="!store.loading && !store.inboxes.length">No inboxes yet.</TableEmpty>
    </Card>

    <Dialog :open="showCreate" @update:open="showCreate = $event">
      <DialogContent>
        <DialogHeader>
          <DialogTitle>New inbox</DialogTitle>
          <DialogDescription>Create an inbox. An API key will be shown once after creation.</DialogDescription>
        </DialogHeader>
        <div class="space-y-4">
          <div class="space-y-1.5">
            <Label for="create-name">Name</Label>
            <Input id="create-name" v-model="createName" placeholder="Staging" />
          </div>
          <div class="space-y-1.5">
            <Label for="create-retention">Retention (days, blank = forever)</Label>
            <Input id="create-retention" v-model="createRetention" type="number" min="1" />
          </div>
          <div class="space-y-1.5">
            <Label>Color</Label>
            <ColorPicker v-model="createColor" />
          </div>
        </div>
        <DialogFooter>
          <Button variant="ghost" @click="showCreate = false">Cancel</Button>
          <Button :disabled="store.saving || !createName" @click="submitCreate">Create</Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>

    <Dialog :open="!!settingsInbox" @update:open="(v) => !v && closeSettings()">
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Inbox settings</DialogTitle>
          <DialogDescription>Update name, retention, and color.</DialogDescription>
        </DialogHeader>
        <div v-if="settingsInbox" class="space-y-4">
          <div class="space-y-1.5">
            <Label for="settings-name">Name</Label>
            <Input
              id="settings-name"
              v-model="settingsName"
              :disabled="settingsInbox.is_default"
            />
            <p v-if="settingsInbox.is_default" class="text-xs text-muted-foreground">
              The Default inbox cannot be renamed.
            </p>
          </div>
          <div class="space-y-1.5">
            <Label for="settings-retention">Retention (days, blank = forever)</Label>
            <Input id="settings-retention" v-model="settingsRetention" type="number" min="1" />
          </div>
          <div class="space-y-1.5">
            <Label>Color</Label>
            <ColorPicker v-model="settingsColor" />
          </div>
        </div>
        <DialogFooter>
          <Button variant="ghost" @click="closeSettings">Cancel</Button>
          <Button :disabled="store.saving" @click="saveSettings">Save</Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>

    <Dialog :open="!!store.revealKey" @update:open="(v) => !v && store.dismissKey()">
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Save this key</DialogTitle>
          <DialogDescription>This is the only time you'll see the key. Copy it somewhere safe.</DialogDescription>
        </DialogHeader>
        <pre class="bg-muted text-xs p-3 rounded-md border break-all whitespace-pre-wrap">{{ store.revealKey?.plaintext }}</pre>
        <DialogFooter>
          <Button variant="outline" @click="copy(store.revealKey.plaintext)">
            {{ copied ? 'Copied' : 'Copy' }}
          </Button>
          <Button @click="store.dismissKey()">I saved it</Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>

    <AlertDialog :open="!!confirmState" @update:open="(v) => !v && cancelConfirm()">
      <AlertDialogContent>
        <AlertDialogHeader>
          <AlertDialogTitle>{{ confirmState?.title }}</AlertDialogTitle>
          <AlertDialogDescription>{{ confirmState?.description }}</AlertDialogDescription>
        </AlertDialogHeader>
        <AlertDialogFooter>
          <Button variant="outline" @click="cancelConfirm">Cancel</Button>
          <Button
            :variant="confirmState?.variant ?? 'default'"
            @click="runConfirm"
          >
            {{ confirmState?.actionLabel ?? 'Confirm' }}
          </Button>
        </AlertDialogFooter>
      </AlertDialogContent>
    </AlertDialog>
  </div>
</template>

<script setup>
import { nextTick, onMounted, ref } from 'vue'
import { Plus, KeyRound, Trash2, Settings } from 'lucide-vue-next'
import { useAdminStore } from '@/stores/admin'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Card } from '@/components/ui/card'
import { Table, TableHeader, TableBody, TableRow, TableHead, TableCell, TableEmpty } from '@/components/ui/table'
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter } from '@/components/ui/dialog'
import {
  AlertDialog,
  AlertDialogContent,
  AlertDialogHeader,
  AlertDialogFooter,
  AlertDialogTitle,
  AlertDialogDescription,
} from '@/components/ui/alert-dialog'
import ColorPicker from '@/components/ColorPicker.vue'

const store = useAdminStore()
const showCreate = ref(false)
const createName = ref('')
const createRetention = ref('')
const createColor = ref(null)
const copied = ref(false)
const confirmState = ref(null)
let pendingAction = null

const settingsInbox = ref(null)
const settingsName = ref('')
const settingsRetention = ref('')
const settingsColor = ref(null)

function ask({ title, description, actionLabel, variant, action }) {
  pendingAction = action
  confirmState.value = { title, description, actionLabel, variant }
}

function cancelConfirm() {
  pendingAction = null
  confirmState.value = null
}

async function runConfirm() {
  const action = pendingAction
  pendingAction = null
  confirmState.value = null
  await nextTick()
  if (action) await action()
}

onMounted(() => store.fetch())

function openCreate() {
  createName.value = ''
  createRetention.value = ''
  createColor.value = null
  showCreate.value = true
}

async function submitCreate() {
  await store.create({
    name: createName.value,
    retention_days: createRetention.value ? Number(createRetention.value) : null,
    color: createColor.value,
  })
  showCreate.value = false
}

function openSettings(inbox) {
  settingsInbox.value = inbox
  settingsName.value = inbox.name
  settingsRetention.value = inbox.retention_days ?? ''
  settingsColor.value = inbox.color ?? null
}

function closeSettings() {
  settingsInbox.value = null
}

async function saveSettings() {
  const id = settingsInbox.value.id
  const payload = {
    retention_days: settingsRetention.value ? Number(settingsRetention.value) : null,
    color: settingsColor.value,
  }
  if (!settingsInbox.value.is_default) {
    payload.name = settingsName.value
  }
  await store.update(id, payload)
  closeSettings()
}

function regenerate(inbox) {
  ask({
    title: `Regenerate key for "${inbox.name}"?`,
    description: 'The old key will stop working immediately. Any sender using it will start failing until you update the token.',
    actionLabel: 'Regenerate key',
    action: () => store.regenerate(inbox.id),
  })
}

function destroy(inbox) {
  ask({
    title: `Delete "${inbox.name}"?`,
    description: 'This permanently removes the inbox and all of its emails and attachments. This cannot be undone.',
    actionLabel: 'Delete inbox',
    variant: 'destructive',
    action: () => store.destroy(inbox.id),
  })
}

async function copy(text) {
  try {
    await navigator.clipboard.writeText(text)
    copied.value = true
    setTimeout(() => (copied.value = false), 1500)
  } catch {}
}

function formatDate(iso) {
  return new Date(iso).toLocaleString()
}
</script>
