import '../css/app.css'
import { createApp } from 'vue'
import { createPinia } from 'pinia'
import { createRouter, createWebHistory } from 'vue-router'
import App from './App.vue'
import InboxIndex from './views/InboxIndex.vue'
import AdminInboxes from './views/Admin/AdminInboxes.vue'

const config = window.MAILULATOR_CONFIG ?? {}

const router = createRouter({
  history: createWebHistory(config.basePath ?? '/mailulator'),
  routes: [
    { path: '/', name: 'inboxes.index', component: InboxIndex },
    { path: '/inboxes/:inboxId', name: 'inboxes.show', component: InboxIndex, props: true },
    { path: '/inboxes/:inboxId/emails/:emailId', name: 'emails.show', component: InboxIndex, props: true },
    { path: '/admin/inboxes', name: 'admin.inboxes', component: AdminInboxes },
  ],
})

createApp(App)
  .use(createPinia())
  .use(router)
  .mount('#mailulator-app')
