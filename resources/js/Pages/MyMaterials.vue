<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { fileIcon } from '@/files';
import { materiaColor } from '@/materiaColors';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    uploads: { type: Array, default: () => [] },
    favorites: { type: Array, default: () => [] },
});

const TIPO = { apunte: 'Apunte', campus: 'Campus', examen: 'Examen' };
</script>

<template>
    <Head title="Mis apuntes" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-ink">Mis apuntes</h2>
        </template>

        <div class="mx-auto max-w-3xl space-y-10 px-4 py-8 sm:px-6 lg:px-8">
            <!-- Guardados -->
            <section>
                <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-stone-500">
                    ⭐ Guardados ({{ favorites.length }})
                </h3>
                <div v-if="favorites.length === 0" class="rounded-xl border border-dashed border-stone-300 bg-white/50 p-8 text-center text-sm text-stone-500">
                    Todavía no guardaste ningún material. Tocá la ⭐ en cualquier apunte para guardarlo acá.
                </div>
                <ul v-else class="space-y-3">
                    <li v-for="m in favorites" :key="m.id">
                        <Link
                            :href="route('materias.show', m.materia.id)"
                            class="flex items-start gap-3 rounded-xl border border-stone-200 bg-white p-4 shadow-sm transition hover:shadow-md"
                        >
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-stone-100 text-lg">
                                {{ fileIcon(m.mime, m.original_name) }}
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="truncate font-medium text-ink">{{ m.titulo }}</div>
                                <div class="mt-1 flex items-center gap-2 text-xs text-stone-400">
                                    <span
                                        class="inline-flex items-center gap-1 rounded-full px-2 py-0.5"
                                        :class="materiaColor(m.materia.color).chip"
                                    >
                                        {{ m.materia.icon || '📘' }} {{ m.materia.nombre }}
                                    </span>
                                    <span>· {{ TIPO[m.tipo] }} · ⬇ {{ m.downloads }}</span>
                                </div>
                            </div>
                        </Link>
                    </li>
                </ul>
            </section>

            <!-- Mis subidas -->
            <section>
                <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-stone-500">
                    📤 Mis subidas ({{ uploads.length }})
                </h3>
                <div v-if="uploads.length === 0" class="rounded-xl border border-dashed border-stone-300 bg-white/50 p-8 text-center text-sm text-stone-500">
                    Todavía no subiste ningún material. ¡Compartí el primero desde cualquier materia!
                </div>
                <ul v-else class="space-y-3">
                    <li v-for="m in uploads" :key="m.id">
                        <Link
                            :href="route('materias.show', m.materia.id)"
                            class="flex items-start gap-3 rounded-xl border border-stone-200 bg-white p-4 shadow-sm transition hover:shadow-md"
                        >
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-stone-100 text-lg">
                                {{ fileIcon(m.mime, m.original_name) }}
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="truncate font-medium text-ink">{{ m.titulo }}</div>
                                <div class="mt-1 flex items-center gap-2 text-xs text-stone-400">
                                    <span
                                        class="inline-flex items-center gap-1 rounded-full px-2 py-0.5"
                                        :class="materiaColor(m.materia.color).chip"
                                    >
                                        {{ m.materia.icon || '📘' }} {{ m.materia.nombre }}
                                    </span>
                                    <span>· {{ TIPO[m.tipo] }} · ⬇ {{ m.downloads }}</span>
                                </div>
                            </div>
                        </Link>
                    </li>
                </ul>
            </section>
        </div>
    </AuthenticatedLayout>
</template>
