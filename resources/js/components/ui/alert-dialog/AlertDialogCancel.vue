<script setup>
import { AlertDialogCancel } from 'reka-ui'
import { cn } from '@/lib/utils'
import { buttonVariants } from '@/components/ui/button'

const props = defineProps({
  class: { type: [String, Array, Object], default: '' },
})
</script>

<template>
  <AlertDialogCancel :class="cn(buttonVariants({ variant: 'outline' }), 'mt-2 sm:mt-0', props.class)">
    <slot />
  </AlertDialogCancel>
</template>
