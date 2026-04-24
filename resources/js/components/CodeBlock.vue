<template>
  <div class="relative group">
    <pre class="bg-muted text-xs p-3 pr-10 rounded-md border overflow-x-auto font-mono whitespace-pre">{{ code }}</pre>
    <Button
      variant="ghost"
      size="icon"
      class="absolute top-1.5 right-1.5 h-7 w-7 opacity-0 group-hover:opacity-100 focus-within:opacity-100 transition-opacity"
      :title="copied ? 'Copied' : 'Copy'"
      @click="copy"
    >
      <Check v-if="copied" class="h-3.5 w-3.5" />
      <Copy v-else class="h-3.5 w-3.5" />
    </Button>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { Copy, Check } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'

const props = defineProps({ code: { type: String, required: true } })
const copied = ref(false)

async function copy() {
  try {
    await navigator.clipboard.writeText(props.code)
    copied.value = true
    setTimeout(() => (copied.value = false), 1500)
  } catch {}
}
</script>
