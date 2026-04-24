<template>
  <div class="max-w-5xl mx-auto p-6 space-y-6">
    <header class="flex items-center justify-between">
      <h1 class="text-xl font-semibold">Inboxes</h1>
      <button
        class="bg-slate-800 text-white text-sm px-3 py-1.5 rounded hover:bg-slate-700"
        @click="showCreate = true"
      >
        + New inbox
      </button>
    </header>

    <div v-if="store.loading" class="text-sm text-slate-400">Loading…</div>

    <table v-else class="w-full text-sm bg-white border border-slate-200 rounded overflow-hidden">
      <thead class="bg-slate-50 text-slate-500 text-xs uppercase tracking-wide">
        <tr>
          <th class="text-left px-4 py-2 font-medium">Name</th>
          <th class="text-left px-4 py-2 font-medium">Retention</th>
          <th class="text-left px-4 py-2 font-medium">Last used</th>
          <th class="w-48"></th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-100">
        <tr v-for="inbox in store.inboxes" :key="inbox.id">
          <td class="px-4 py-2">
            <input
              v-if="editingId === inbox.id"
              v-model="editName"
              class="px-2 py-1 border border-slate-300 rounded text-sm"
              @keyup.enter="saveName(inbox)"
              @keyup.esc="editingId = null"
            />
            <span v-else>{{ inbox.name }}</span>
          </td>
          <td class="px-4 py-2 text-slate-500">
            {{ inbox.retention_days ? `${inbox.retention_days} days` : 'Forever' }}
          </td>
          <td class="px-4 py-2 text-slate-500">
            {{ inbox.last_used_at ? formatDate(inbox.last_used_at) : '—' }}
          </td>
          <td class="px-4 py-2 text-right space-x-2">
            <button v-if="editingId !== inbox.id" class="text-xs text-slate-600 hover:text-slate-900" @click="startEdit(inbox)">
              Rename
            </button>
            <button v-else class="text-xs text-slate-800 font-medium" @click="saveName(inbox)">
              Save
            </button>
            <button class="text-xs text-amber-700 hover:text-amber-900" @click="regenerate(inbox)">
              Regenerate key
            </button>
            <button class="text-xs text-red-600 hover:text-red-800" @click="destroy(inbox)">
              Delete
            </button>
          </td>
        </tr>
        <tr v-if="!store.inboxes.length">
          <td colspan="4" class="px-4 py-6 text-center text-slate-400 text-sm">
            No inboxes yet.
          </td>
        </tr>
      </tbody>
    </table>

    <!-- Create modal -->
    <div v-if="showCreate" class="fixed inset-0 bg-black/40 flex items-center justify-center p-4" @click.self="showCreate = false">
      <div class="bg-white rounded shadow-lg w-full max-w-md p-6 space-y-4">
        <h2 class="text-lg font-semibold">New inbox</h2>
        <label class="block space-y-1">
          <span class="text-xs font-medium text-slate-600">Name</span>
          <input v-model="createName" type="text" class="w-full px-3 py-2 border border-slate-300 rounded text-sm" placeholder="Staging" />
        </label>
        <label class="block space-y-1">
          <span class="text-xs font-medium text-slate-600">Retention (days, blank = forever)</span>
          <input v-model="createRetention" type="number" min="1" class="w-full px-3 py-2 border border-slate-300 rounded text-sm" />
        </label>
        <div class="flex justify-end gap-2 pt-2">
          <button class="text-sm px-3 py-1.5 text-slate-600" @click="showCreate = false">Cancel</button>
          <button
            class="bg-slate-800 text-white text-sm px-3 py-1.5 rounded disabled:opacity-50"
            :disabled="store.saving || !createName"
            @click="submitCreate"
          >
            Create
          </button>
        </div>
      </div>
    </div>

    <!-- One-time key reveal -->
    <div v-if="store.revealKey" class="fixed inset-0 bg-black/40 flex items-center justify-center p-4">
      <div class="bg-white rounded shadow-lg w-full max-w-md p-6 space-y-4">
        <h2 class="text-lg font-semibold">Save this key</h2>
        <p class="text-sm text-slate-600">This is the only time you'll see the key. Copy it somewhere safe.</p>
        <pre class="bg-slate-100 text-xs p-3 rounded border border-slate-200 break-all whitespace-pre-wrap">{{ store.revealKey.plaintext }}</pre>
        <div class="flex justify-end gap-2">
          <button
            class="text-sm px-3 py-1.5 border border-slate-300 rounded"
            @click="copy(store.revealKey.plaintext)"
          >
            {{ copied ? 'Copied' : 'Copy' }}
          </button>
          <button class="bg-slate-800 text-white text-sm px-3 py-1.5 rounded" @click="store.dismissKey()">
            I saved it
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import { useAdminStore } from '../../stores/admin'

const store = useAdminStore()
const showCreate = ref(false)
const createName = ref('')
const createRetention = ref('')
const editingId = ref(null)
const editName = ref('')
const copied = ref(false)

onMounted(() => store.fetch())

async function submitCreate() {
  await store.create({
    name: createName.value,
    retention_days: createRetention.value ? Number(createRetention.value) : null,
  })
  showCreate.value = false
  createName.value = ''
  createRetention.value = ''
}

function startEdit(inbox) {
  editingId.value = inbox.id
  editName.value = inbox.name
}

async function saveName(inbox) {
  if (editName.value && editName.value !== inbox.name) {
    await store.update(inbox.id, { name: editName.value })
  }
  editingId.value = null
}

async function regenerate(inbox) {
  if (!confirm(`Regenerate the API key for "${inbox.name}"? The old key will stop working immediately.`)) return
  await store.regenerate(inbox.id)
}

async function destroy(inbox) {
  if (!confirm(`Delete "${inbox.name}" and all its emails? This cannot be undone.`)) return
  await store.destroy(inbox.id)
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
