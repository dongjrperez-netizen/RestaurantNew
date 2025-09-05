<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowLeft  } from "lucide-vue-next";

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <!-- Home Button -->
    <div class="p-4">
    <Link :href="route('home')" class="inline-flex items-center">
        <ArrowLeft class="w-6 h-6 text-gray-700 hover:text-gray-900 transition" />
    </Link>
    </div>
   <div class="flex min-h-svh flex-col items-center justify-center gap-6 bg-background p-6 md:p-10">
        <div class="w-full max-w-[440px]">
            <div class="flex flex-col gap-8">
            <div class="flex flex-col items-center gap-4">
                <Link :href="route('home')" class="flex flex-col items-center gap-2 font-medium">
                <!-- <div class="mb-1 flex h-9 w-9 items-center justify-center rounded-md">
                    <AppLogoIcon class="size-9 fill-current text-[var(--foreground)] dark:text-white" />
                </div> -->
                <span class="sr-only">{{ title }}</span>
                </Link>
                <div class="space-y-2 text-center">
                <h1 class="text-xl font-medium">{{ title }}</h1>
                <p class="text-center text-sm text-muted-foreground">{{ description }}</p>
                </div>
            </div>

            <!-- White background wrapper -->
            <div class="bg-white p-6 rounded-2xl shadow-md">
                <slot />
            </div>
            </div>
        </div>
        </div>

</template>
