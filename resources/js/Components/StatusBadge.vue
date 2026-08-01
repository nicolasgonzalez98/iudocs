<script setup>
import { computed } from 'vue';

const props = defineProps({
    status: {
        type: String,
        required: true,
    },
});

const META = {
    active: { label: 'Activo', class: 'bg-green-100 text-green-700', dot: 'bg-green-500' },
    pending: { label: 'Pendiente', class: 'bg-amber-100 text-amber-700', dot: 'bg-amber-500' },
    blocked: { label: 'Bloqueado', class: 'bg-red-100 text-red-700', dot: 'bg-red-500' },
};

const meta = computed(
    () =>
        META[props.status] ?? {
            label: props.status,
            class: 'bg-stone-100 text-stone-600',
            dot: 'bg-stone-400',
        },
);
</script>

<template>
    <span
        :class="meta.class"
        class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-medium"
    >
        <span :class="meta.dot" class="h-1.5 w-1.5 rounded-full"></span>
        {{ meta.label }}
    </span>
</template>
