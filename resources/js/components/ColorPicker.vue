<template>
  <div class="flex flex-wrap gap-2">
    <button
      type="button"
      class="h-7 w-7 rounded-full border flex items-center justify-center text-xs"
      :class="modelValue === null ? 'ring-2 ring-ring ring-offset-2' : ''"
      title="None"
      @click="$emit('update:modelValue', null)"
    >
      <Ban class="h-3.5 w-3.5 text-muted-foreground" />
    </button>
    <button
      v-for="c in palette"
      :key="c.value"
      type="button"
      class="h-7 w-7 rounded-full border transition-transform hover:scale-110"
      :class="modelValue === c.value ? 'ring-2 ring-ring ring-offset-2' : ''"
      :style="{ background: c.value }"
      :title="c.label"
      @click="$emit('update:modelValue', c.value)"
    />
  </div>
</template>

<script setup>
import { Ban } from 'lucide-vue-next'

defineProps({ modelValue: { type: String, default: null } })
defineEmits(['update:modelValue'])

const palette = [
  { label: 'Slate', value: '#64748b' },
  { label: 'Red', value: '#ef4444' },
  { label: 'Amber', value: '#f59e0b' },
  { label: 'Emerald', value: '#10b981' },
  { label: 'Sky', value: '#0ea5e9' },
  { label: 'Indigo', value: '#6366f1' },
  { label: 'Violet', value: '#8b5cf6' },
  { label: 'Rose', value: '#f43f5e' },
]
</script>
