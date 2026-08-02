<script setup>
defineProps({
    text: { type: String, required: true },
});
</script>

<template>
    <span class="group/tt relative inline-flex">
        <slot />
        <span
            role="tooltip"
            class="pointer-events-none absolute bottom-full left-1/2 z-30 mb-1.5 -translate-x-1/2 whitespace-nowrap rounded-md bg-ink px-2 py-1 text-xs font-medium text-white opacity-0 shadow-md transition-opacity duration-150 group-hover/tt:opacity-100"
        >
            {{ text }}
        </span>
    </span>
</template>
