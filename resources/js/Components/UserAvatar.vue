<script setup>
import { computed, ref } from 'vue';

const props = defineProps({
    name: { type: String, required: true },
    avatar: { type: String, default: null },
    size: { type: String, default: 'md' }, // sm | md
    tone: { type: String, default: 'amber' }, // amber | stone
});

const failed = ref(false);

const initials = computed(() =>
    props.name
        .trim()
        .split(/\s+/)
        .slice(0, 2)
        .map((p) => p[0])
        .join('')
        .toUpperCase(),
);

const sizeClass = computed(
    () => ({ sm: 'h-9 w-9 text-xs', md: 'h-10 w-10 text-sm' }[props.size] ?? 'h-10 w-10 text-sm'),
);

const toneClass = computed(
    () =>
        ({
            amber: 'bg-brand-200 text-brand-800',
            stone: 'bg-stone-200 text-stone-600',
        }[props.tone] ?? 'bg-brand-200 text-brand-800'),
);

const showImg = computed(() => props.avatar && !failed.value);
</script>

<template>
    <img
        v-if="showImg"
        :src="avatar"
        :alt="name"
        referrerpolicy="no-referrer"
        @error="failed = true"
        :class="sizeClass"
        class="shrink-0 rounded-full object-cover"
    />
    <div
        v-else
        :class="[sizeClass, toneClass]"
        class="flex shrink-0 items-center justify-center rounded-full font-semibold"
    >
        {{ initials }}
    </div>
</template>
