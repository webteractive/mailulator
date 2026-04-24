<script setup>
import { cn } from '@/lib/utils'
const props = defineProps({ class: { type: [String, Array, Object], default: '' } })
</script>

<template>
  <div :class="cn('py-10 text-center text-sm text-muted-foreground', props.class)">
    <slot />
  </div>
</template>
