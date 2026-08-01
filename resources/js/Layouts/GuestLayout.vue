<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div
        class="flex min-h-screen flex-col items-center bg-cream pt-6 sm:justify-center sm:pt-0"
    >
        <div>
            <Link href="/" class="flex flex-col items-center gap-2">
                <ApplicationLogo class="h-16 w-16 text-amber-500" />
                <span class="text-lg font-semibold tracking-tight text-ink">Altillo</span>
            </Link>
        </div>

        <div
            class="mt-6 w-full overflow-hidden border border-stone-200/70 bg-white px-6 py-6 shadow-sm sm:max-w-md sm:rounded-xl"
        >
            <slot />
        </div>
    </div>
</template>
