<template>
  <div class="h-full flex">
    <aside
      :class="[
        'border-r bg-card flex flex-col transition-[width] duration-200',
        collapsed ? 'w-14' : 'w-60',
      ]"
    >
      <div class="h-14 border-b flex items-center px-3 justify-between gap-2">
        <router-link
          v-if="!collapsed"
          to="/"
          class="flex items-center gap-[2px] min-w-0"
        >
          <Mail class="h-5 w-5 shrink-0" />
          <span class="text-lg font-semibold tracking-tight truncate">Mailulator</span>
        </router-link>
        <Button variant="ghost" size="icon" class="h-8 w-8 shrink-0" @click="toggle">
          <PanelLeftClose v-if="!collapsed" class="h-4 w-4" />
          <PanelLeftOpen v-else class="h-4 w-4" />
        </Button>
      </div>
      <nav class="flex-1 p-2 space-y-1 text-sm">
        <router-link
          v-for="link in links"
          :key="link.to"
          :to="link.to"
          :title="collapsed ? link.label : undefined"
          custom
          v-slot="{ href, navigate }"
        >
          <a
            :href="href"
            :class="[
              'flex items-center gap-2 rounded-md px-3 py-2',
              collapsed ? 'justify-center px-0' : '',
              isActive(link)
                ? 'bg-slate-200 dark:bg-slate-700 text-foreground font-medium'
                : 'text-muted-foreground hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-foreground',
            ]"
            @click="navigate"
          >
            <component :is="link.icon" class="h-4 w-4 shrink-0" />
            <span v-if="!collapsed">{{ link.label }}</span>
          </a>
        </router-link>
      </nav>
    </aside>
    <main class="flex-1 overflow-auto">
      <router-view />
    </main>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import { Inbox, Settings, Mail, PanelLeftClose, PanelLeftOpen } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'

const STORAGE_KEY = 'mailulator.sidebar.collapsed'
const collapsed = ref(localStorage.getItem(STORAGE_KEY) === '1')
const route = useRoute()

function toggle() {
  collapsed.value = !collapsed.value
}

watch(collapsed, (v) => {
  localStorage.setItem(STORAGE_KEY, v ? '1' : '0')
})

const links = [
  { to: '/', label: 'Inboxes', icon: Inbox, match: (p) => p === '/' || p.startsWith('/inboxes') || p.startsWith('/emails') },
  { to: '/admin/inboxes', label: 'Settings', icon: Settings, match: (p) => p.startsWith('/admin') },
]

function isActive(link) {
  return link.match(route.path)
}
</script>
