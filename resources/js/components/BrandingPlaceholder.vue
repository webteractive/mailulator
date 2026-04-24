<template>
  <div class="flex-1 flex items-center justify-center p-8">
    <div class="text-center space-y-4 max-w-sm">
      <div class="mx-auto h-16 w-16 rounded-2xl bg-primary text-primary-foreground flex items-center justify-center shadow-sm">
        <Mail class="h-8 w-8" />
      </div>
      <div class="space-y-1">
        <h2 class="text-2xl font-semibold tracking-tight">Mailulator</h2>
        <p class="text-sm text-muted-foreground">Self-hosted inbox for Laravel.</p>
      </div>
      <p v-if="hint" class="text-xs text-muted-foreground/80 pt-2">{{ hint }}</p>
    </div>
  </div>
</template>

<script setup>
import { Mail } from 'lucide-vue-next'

defineProps({
  hint: { type: String, default: '' },
})
</script>
