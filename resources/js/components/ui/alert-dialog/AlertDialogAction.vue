<script setup>
import { AlertDialogAction } from 'reka-ui'
import { cn } from '@/lib/utils'
import { buttonVariants } from '@/components/ui/button'

const props = defineProps({
  variant: { type: String, default: 'default' },
  class: { type: [String, Array, Object], default: '' },
})
</script>

<template>
  <AlertDialogAction :class="cn(buttonVariants({ variant }), props.class)">
    <slot />
  </AlertDialogAction>
</template>
