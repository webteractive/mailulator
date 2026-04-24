<template>
  <div
    class="h-full grid transition-[grid-template-columns] duration-200"
    :style="{ gridTemplateColumns: gridCols }"
  >
    <InboxList
      :selected-id="selectedInboxId"
      :collapsed="inboxesCollapsed"
      @select="selectInbox"
      @toggle="toggleInboxes"
    />
    <template v-if="selectedInboxId">
      <EmailList
        :inbox-id="selectedInboxId"
        :selected-id="selectedEmailId"
        @select="selectEmail"
      />
      <EmailDetail v-if="selectedEmailId" :email-id="selectedEmailId" />
      <section v-else class="bg-background border-l flex flex-col">
        <EmptyInbox v-if="!emails.loading && !emails.list.length" />
        <BrandingPlaceholder v-else hint="Select an email to preview" />
      </section>
    </template>
    <BrandingPlaceholder
      v-else
      class="col-span-2 border-l"
      hint="Select an inbox to start browsing"
    />
  </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import InboxList from '@/components/InboxList.vue'
import EmailList from '@/components/EmailList.vue'
import EmailDetail from '@/components/EmailDetail.vue'
import EmptyInbox from '@/components/EmptyInbox.vue'
import BrandingPlaceholder from '@/components/BrandingPlaceholder.vue'
import { useEmailStore } from '@/stores/emails'

const emails = useEmailStore()

const route = useRoute()
const router = useRouter()

const selectedInboxId = computed(() => route.params.inboxId ? Number(route.params.inboxId) : null)
const selectedEmailId = computed(() => route.params.emailId ? Number(route.params.emailId) : null)

const STORAGE_KEY = 'mailulator.inboxes.collapsed'
const inboxesCollapsed = ref(localStorage.getItem(STORAGE_KEY) === '1')

function toggleInboxes() {
  inboxesCollapsed.value = !inboxesCollapsed.value
}

watch(inboxesCollapsed, (v) => {
  localStorage.setItem(STORAGE_KEY, v ? '1' : '0')
})

const gridCols = computed(() => {
  const first = inboxesCollapsed.value ? '56px' : '260px'
  return `${first} 360px 1fr`
})

function selectInbox(id) {
  router.push({ name: 'inboxes.show', params: { inboxId: id } })
}

function selectEmail(id) {
  router.push({ name: 'emails.show', params: { inboxId: selectedInboxId.value, emailId: id } })
}
</script>
