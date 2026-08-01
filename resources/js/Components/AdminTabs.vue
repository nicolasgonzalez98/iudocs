<script setup>
import { Link } from '@inertiajs/vue3';

const tabClass = (active) =>
    active
        ? 'border-brand-500 text-ink'
        : 'border-transparent text-stone-500 hover:border-stone-300 hover:text-stone-800';
</script>

<template>
    <nav class="mb-8 flex gap-6 border-b border-stone-200">
        <Link
            :href="route('admin.users')"
            class="border-b-2 px-1 pb-3 text-sm font-medium transition"
            :class="tabClass(route().current('admin.users'))"
        >
            Usuarios
        </Link>
        <Link
            :href="route('admin.materias')"
            class="border-b-2 px-1 pb-3 text-sm font-medium transition"
            :class="tabClass(route().current('admin.materias'))"
        >
            Materias
        </Link>
    </nav>
</template>
