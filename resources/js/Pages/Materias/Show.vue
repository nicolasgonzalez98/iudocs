<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { materiaColor, periodoLabel } from '@/materiaColors';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    materia: { type: Object, required: true },
});
</script>

<template>
    <Head :title="materia.nombre" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-3">
                <Link
                    :href="route('dashboard')"
                    class="text-stone-400 transition hover:text-stone-700"
                    title="Volver a materias"
                >
                    ←
                </Link>
                <div
                    class="flex h-10 w-10 items-center justify-center rounded-lg text-xl"
                    :class="materiaColor(materia.color).chip"
                >
                    {{ materia.icon || '📘' }}
                </div>
                <div>
                    <h2 class="text-xl font-semibold leading-tight text-ink">{{ materia.nombre }}</h2>
                    <p v-if="materia.anio || materia.cuatrimestre" class="text-xs text-stone-500">
                        {{ periodoLabel(materia.anio, materia.cuatrimestre) }}
                    </p>
                </div>
            </div>
        </template>

        <div class="mx-auto max-w-4xl px-4 py-8 sm:px-6 lg:px-8">
            <div
                class="rounded-2xl border border-dashed border-stone-300 bg-white/50 p-12 text-center"
            >
                <div class="text-4xl">📎</div>
                <h3 class="mt-3 text-lg font-semibold text-ink">Apuntes y exámenes</h3>
                <p class="mx-auto mt-1 max-w-md text-sm text-stone-500">
                    Muy pronto vas a poder subir y ver los apuntes y exámenes de
                    <strong>{{ materia.nombre }}</strong> acá.
                </p>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
