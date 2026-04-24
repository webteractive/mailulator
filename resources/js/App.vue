<template>
  <div class="h-full flex flex-col">
    <header class="border-b border-slate-200 bg-white px-6 py-3 flex items-center justify-between">
      <router-link to="/" class="text-lg font-semibold text-slate-800">Mailulator</router-link>
      <nav class="text-sm text-slate-600 space-x-4">
        <router-link to="/" class="hover:text-slate-900">Inboxes</router-link>
        <router-link to="/admin/inboxes" class="hover:text-slate-900">Admin</router-link>
      </nav>
    </header>
    <main class="flex-1 overflow-hidden">
      <router-view />
    </main>
  </div>
</template>
