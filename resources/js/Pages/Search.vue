<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { materiaColor, periodoLabel } from '@/materiaColors';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    q: { type: String, default: '' },
    materias: { type: Array, default: () => [] },
    materiales: { type: Array, default: () => [] },
});

const query = ref(props.q ?? '');

const submit = () => {
    router.get(route('search'), { q: query.value }, { preserveState: true, preserveScroll: true });
};

const TIPO = { apunte: '📝 Apunte', campus: '🏫 Apunte del campus', examen: '🎓 Examen' };

const hasResults = () => props.materias.length > 0 || props.materiales.length > 0;
</script>

<template>
    <Head title="Buscar" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-ink">Buscar</h2>
        </template>

        <div class="mx-auto max-w-3xl px-4 py-8 sm:px-6 lg:px-8">
            <form @submit.prevent="submit" class="mb-8 flex gap-2">
                <input
                    v-model="query"
                    type="search"
                    autofocus
                    placeholder="Buscá una materia, un apunte, un examen…"
                    class="block w-full rounded-lg border-stone-300 shadow-sm focus:border-brand-500 focus:ring-brand-500"
                />
                <button
                    type="submit"
                    class="shrink-0 rounded-lg bg-brand-500 px-5 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-600"
                >
                    Buscar
                </button>
            </form>

            <!-- Prompt inicial -->
            <p v-if="q.length < 2" class="py-10 text-center text-sm text-stone-400">
                Escribí al menos 2 letras para buscar.
            </p>

            <!-- Sin resultados -->
            <div v-else-if="!hasResults()" class="rounded-2xl border border-dashed border-stone-300 bg-white/50 p-10 text-center">
                <div class="text-3xl">🔍</div>
                <p class="mt-2 text-sm text-stone-500">
                    No encontramos nada para “<strong>{{ q }}</strong>”. Probá con otras palabras.
                </p>
            </div>

            <div v-else class="space-y-8">
                <!-- Materias -->
                <section v-if="materias.length">
                    <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-stone-500">
                        Materias ({{ materias.length }})
                    </h3>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <Link
                            v-for="m in materias"
                            :key="m.id"
                            :href="route('materias.show', m.id)"
                            class="flex items-center gap-3 rounded-xl border bg-white p-4 shadow-sm transition hover:shadow-md"
                            :class="materiaColor(m.color).card"
                        >
                            <div
                                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg text-xl"
                                :class="materiaColor(m.color).chip"
                            >
                                {{ m.icon || '📘' }}
                            </div>
                            <div class="min-w-0">
                                <div class="truncate font-medium text-ink">{{ m.nombre }}</div>
                                <div v-if="m.anio || m.cuatrimestre" class="text-xs text-stone-500">
                                    {{ periodoLabel(m.anio, m.cuatrimestre) }}
                                </div>
                            </div>
                        </Link>
                    </div>
                </section>

                <!-- Materiales -->
                <section v-if="materiales.length">
                    <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-stone-500">
                        Apuntes y exámenes ({{ materiales.length }})
                    </h3>
                    <ul class="space-y-3">
                        <li v-for="m in materiales" :key="m.id">
                            <Link
                                :href="route('materias.show', m.materia.id)"
                                class="flex items-start gap-3 rounded-xl border border-stone-200 bg-white p-4 shadow-sm transition hover:shadow-md"
                            >
                                <div
                                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg text-lg"
                                    :class="materiaColor(m.materia.color).chip"
                                >
                                    {{ m.materia.icon || '📘' }}
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div class="truncate font-medium text-ink">{{ m.titulo }}</div>
                                    <p v-if="m.descripcion" class="truncate text-sm text-stone-500">{{ m.descripcion }}</p>
                                    <div class="mt-1 text-xs text-stone-400">
                                        {{ TIPO[m.tipo] }} · {{ m.materia.nombre }} · por {{ m.uploader }}
                                    </div>
                                </div>
                            </Link>
                        </li>
                    </ul>
                </section>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
