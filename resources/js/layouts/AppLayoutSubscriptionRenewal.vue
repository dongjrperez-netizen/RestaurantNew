<script setup lang="ts">
import AppLayotSuscriptionRenewal from '@/layouts/app/AppSideBarLayoutSubscriptionRenewal.vue';
import type { BreadcrumbItemType } from '@/types';

interface Props {
    breadcrumbs?: BreadcrumbItemType[];
}

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});
</script>

<template>
    <AppLayotSuscriptionRenewal :breadcrumbs="breadcrumbs">
        <slot />
    </AppLayotSuscriptionRenewal>
</template>
