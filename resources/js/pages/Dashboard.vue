<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import PlaceholderPattern from '../components/PlaceholderPattern.vue';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Welcome Back Admin!!',
        href: '/dashboard',
    },
];
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-6 overflow-x-auto">
            <!-- Larger Grid -->
            <div class="grid gap-6 md:grid-cols-3">
                <div class="relative min-h-[250px] overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border p-4">
                    <PlaceholderPattern />
                </div>
                <div class="relative min-h-[250px] overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border p-4">
                    <PlaceholderPattern />
                </div>
                <div class="relative min-h-[250px] overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border p-4">
                    <PlaceholderPattern />
                </div>
            </div>

            <!-- Big Section -->
            <div class="relative min-h-[500px] flex-1 rounded-xl border border-sidebar-border/70 md:min-h-[400px] dark:border-sidebar-border p-6">
                <PlaceholderPattern />
            </div>
        </div>
    </AppLayout>
</template>
