<template>
  <aside class="border-r bg-card overflow-y-auto flex flex-col">
    <div class="h-14 flex items-center justify-between px-2 border-b">
      <span
        v-if="!collapsed"
        class="px-2 text-xs uppercase tracking-wide text-muted-foreground font-semibold"
      >
        Inboxes
      </span>
      <Button
        variant="ghost"
        size="icon"
        class="h-8 w-8 ml-auto"
        :title="collapsed ? 'Expand' : 'Collapse'"
        @click="$emit('toggle')"
      >
        <PanelLeftClose v-if="!collapsed" class="h-4 w-4" />
        <PanelLeftOpen v-else class="h-4 w-4" />
      </Button>
    </div>
    <ul class="flex-1 p-2 space-y-1">
      <li v-for="inbox in store.list" :key="inbox.id">
        <button
          class="w-full text-left text-sm flex items-center gap-2 rounded-md"
          :class="[
            collapsed ? 'justify-center px-0 py-2' : 'px-3 py-2 justify-between',
            inbox.id === selectedId
              ? 'bg-slate-200 dark:bg-slate-700 text-foreground font-medium'
              : 'text-muted-foreground hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-foreground',
          ]"
          :title="collapsed ? inbox.name : undefined"
          @click="$emit('select', inbox.id)"
        >
          <template v-if="collapsed">
            <div class="relative">
              <div
                class="h-7 w-7 rounded-full flex items-center justify-center text-xs font-semibold uppercase text-white"
                :style="avatarStyle(inbox)"
              >
                {{ initial(inbox.name) }}
              </div>
              <span
                v-if="inbox.unread_count"
                class="absolute -top-1 -right-1.5 text-[10px] leading-none bg-primary text-primary-foreground rounded-full px-1 py-0.5 min-w-[14px] text-center ring-2 ring-card"
              >
                {{ inbox.unread_count }}
              </span>
            </div>
          </template>
          <template v-else>
            <span class="flex items-center gap-2 min-w-0">
              <span
                v-if="inbox.color"
                class="h-2 w-2 rounded-full shrink-0"
                :style="{ background: inbox.color }"
              />
              <span class="truncate">{{ inbox.name }}</span>
            </span>
            <span
              v-if="inbox.unread_count"
              class="text-xs bg-primary text-primary-foreground rounded-full px-2 py-0.5"
            >
              {{ inbox.unread_count }}
            </span>
          </template>
        </button>
      </li>
    </ul>
    <div v-if="store.loading && !collapsed" class="px-3 py-2 text-xs text-muted-foreground">Loading…</div>
    <div v-if="!store.loading && !store.list.length && !collapsed" class="px-3 py-2 text-xs text-muted-foreground">No inboxes yet.</div>
  </aside>
</template>

<script setup>
import { onMounted } from 'vue'
import { PanelLeftClose, PanelLeftOpen } from 'lucide-vue-next'
import { useInboxStore } from '@/stores/inboxes'
import { Button } from '@/components/ui/button'

defineProps({
  selectedId: { type: Number, default: null },
  collapsed: { type: Boolean, default: false },
})
defineEmits(['select', 'toggle'])

const store = useInboxStore()
onMounted(() => store.fetch())

function initial(name) {
  return (name ?? '').trim().charAt(0) || '?'
}

function avatarStyle(inbox) {
  if (inbox.color) return { background: inbox.color }
  return { background: 'hsl(var(--muted))', color: 'hsl(var(--muted-foreground))' }
}
</script>
