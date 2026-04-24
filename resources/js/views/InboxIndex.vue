<template>
  <div class="h-full grid grid-cols-[260px_360px_1fr]">
    <InboxList :selected-id="selectedInboxId" @select="selectInbox" />
    <EmailList
      v-if="selectedInboxId"
      :inbox-id="selectedInboxId"
      :selected-id="selectedEmailId"
      @select="selectEmail"
    />
    <div v-else class="bg-white border-l border-slate-200 flex items-center justify-center text-slate-400 text-sm">
      Select an inbox
    </div>
    <EmailDetail v-if="selectedEmailId" :email-id="selectedEmailId" />
    <div v-else-if="selectedInboxId" class="bg-white border-l border-slate-200 flex items-center justify-center text-slate-400 text-sm">
      Select an email
    </div>
  </div>
</template>

<script setup>
import { computed, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import InboxList from '../components/InboxList.vue'
import EmailList from '../components/EmailList.vue'
import EmailDetail from '../components/EmailDetail.vue'

const route = useRoute()
const router = useRouter()

const selectedInboxId = computed(() => route.params.inboxId ? Number(route.params.inboxId) : null)
const selectedEmailId = computed(() => route.params.emailId ? Number(route.params.emailId) : null)

function selectInbox(id) {
  router.push({ name: 'inboxes.show', params: { inboxId: id } })
}

function selectEmail(id) {
  router.push({ name: 'emails.show', params: { inboxId: selectedInboxId.value, emailId: id } })
}
</script>
