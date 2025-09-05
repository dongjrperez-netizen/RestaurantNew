<script setup lang="ts">
import { computed } from 'vue';
import { cn } from '@/lib/utils';
import { badgeVariants } from './index';

interface Props {
  variant?: 'default' | 'secondary' | 'destructive' | 'outline' | 'success' | 'warning';
  class?: string;
}

const props = withDefaults(defineProps<Props>(), {
  variant: 'default'
});

const badgeClasses = computed(() => 
  cn(
    badgeVariants({ variant: props.variant }),
    props.class
  )
);
</script>

<template>
  <div :class="badgeClasses">
    <slot />
  </div>
</template>
