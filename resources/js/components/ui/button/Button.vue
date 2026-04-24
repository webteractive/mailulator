<script setup>
import { Primitive } from 'reka-ui'
import { cn } from '@/lib/utils'
import { buttonVariants } from './index.js'

const props = defineProps({
  variant: { type: String, default: 'default' },
  size: { type: String, default: 'default' },
  as: { type: String, default: 'button' },
  asChild: { type: Boolean, default: false },
  class: { type: [String, Array, Object], default: '' },
})
</script>

<template>
  <Primitive
    :as="as"
    :as-child="asChild"
    :class="cn(buttonVariants({ variant, size }), props.class)"
  >
    <slot />
  </Primitive>
</template>
