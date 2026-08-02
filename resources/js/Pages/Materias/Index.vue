<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { materiaColor, periodoLabel } from '@/materiaColors';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    materias: { type: Array, default: () => [] },
    carreras: { type: Array, default: () => [] },
    recent: { type: Array, default: () => [] },
    topContributors: { type: Array, default: () => [] },
});

const isAdmin = computed(() => usePage().props.auth.user.role === 'admin');

const TIPO = { apunte: '📝', campus: '🏫', examen: '🎓' };

// Filtro por carrera (null = Todas)
const selectedCarrera = ref(null);
const filteredMaterias = computed(() =>
    selectedCarrera.value === null
        ? props.materias
        : props.materias.filter((m) => m.carrera_ids.includes(selectedCarrera.value)),
);

// Agrupa por año (las materias sin año van al final)
const groups = computed(() => {
    const byYear = {};
    for (const m of filteredMaterias.value) {
        const key = m.anio ?? 0;
        (byYear[key] ??= []).push(m);
    }
    return Object.entries(byYear)
        .sort((a, b) => Number(a[0]) - Number(b[0]))
        .map(([anio, items]) => ({
            key: anio,
            label: Number(anio) ? `${anio}º año` : 'Sin año asignado',
            items,
        }));
});
</script>

<template>
    <Head title="Materias" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-ink">Materias</h2>
                <Link
                    v-if="isAdmin"
                    :href="route('admin.materias')"
                    class="text-sm font-medium text-brand-600 hover:text-brand-700"
                >
                    Gestionar materias →
                </Link>
            </div>
        </template>

        <div class="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:px-8">
            <!-- Estado vacío -->
            <div
                v-if="materias.length === 0"
                class="rounded-2xl border border-dashed border-stone-300 bg-white/50 p-12 text-center"
            >
                <div class="text-4xl">📚</div>
                <h3 class="mt-3 text-lg font-semibold text-ink">Todavía no hay materias</h3>
                <p class="mx-auto mt-1 max-w-sm text-sm text-stone-500">
                    <template v-if="isAdmin">
                        Creá la primera materia para empezar a organizar los apuntes.
                    </template>
                    <template v-else>
                        Cuando la admin cargue las materias, van a aparecer acá.
                    </template>
                </p>
                <Link
                    v-if="isAdmin"
                    :href="route('admin.materias')"
                    class="mt-5 inline-flex items-center justify-center rounded-lg bg-brand-500 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-600"
                >
                    + Crear materia
                </Link>
            </div>

            <!-- Materias con filtro por carrera -->
            <template v-else>
                <!-- Tabs por carrera -->
                <div v-if="carreras.length" class="mb-8 flex flex-wrap gap-2">
                    <button
                        type="button"
                        @click="selectedCarrera = null"
                        class="rounded-full px-4 py-1.5 text-sm font-medium transition"
                        :class="selectedCarrera === null ? 'bg-brand-500 text-white shadow-sm' : 'border border-stone-300 text-stone-600 hover:bg-stone-50'"
                    >
                        Todas
                    </button>
                    <button
                        v-for="c in carreras"
                        :key="c.id"
                        type="button"
                        @click="selectedCarrera = c.id"
                        class="rounded-full px-4 py-1.5 text-sm font-medium transition"
                        :class="selectedCarrera === c.id ? 'bg-brand-500 text-white shadow-sm' : 'border border-stone-300 text-stone-600 hover:bg-stone-50'"
                    >
                        {{ c.nombre }}
                    </button>
                </div>

                <!-- Vacío para la carrera elegida -->
                <div
                    v-if="filteredMaterias.length === 0"
                    class="rounded-2xl border border-dashed border-stone-300 bg-white/50 p-10 text-center text-sm text-stone-500"
                >
                    No hay materias en esta carrera todavía.
                </div>

                <!-- Grupos por año -->
                <div v-else class="space-y-10">
                    <section v-for="group in groups" :key="group.key">
                        <h3 class="mb-4 text-sm font-semibold uppercase tracking-wide text-stone-500">
                            {{ group.label }}
                        </h3>

                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        <Link
                            v-for="m in group.items"
                            :key="m.id"
                            :href="route('materias.show', m.id)"
                            class="group flex items-start gap-4 rounded-2xl border bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md"
                            :class="materiaColor(m.color).card"
                        >
                            <div
                                class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl text-2xl"
                                :class="materiaColor(m.color).chip"
                            >
                                {{ m.icon || '📘' }}
                            </div>
                            <div class="min-w-0">
                                <div class="truncate font-semibold text-ink group-hover:text-brand-700">
                                    {{ m.nombre }}
                                </div>
                                <div v-if="m.anio || m.cuatrimestre" class="mt-0.5 text-xs text-stone-500">
                                    {{ periodoLabel(m.anio, m.cuatrimestre) }}
                                </div>
                            </div>
                        </Link>
                    </div>
                    </section>
                </div>
            </template>

            <!-- Últimas subidas -->
            <section v-if="recent.length" class="mt-12">
                <h3 class="mb-4 text-sm font-semibold uppercase tracking-wide text-stone-500">
                    🆕 Últimas subidas
                </h3>
                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    <Link
                        v-for="m in recent"
                        :key="m.id"
                        :href="route('materias.show', m.materia.id)"
                        class="flex items-center gap-3 rounded-xl border border-stone-200 bg-white p-3 shadow-sm transition hover:shadow-md"
                    >
                        <div
                            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-lg"
                            :class="materiaColor(m.materia.color).chip"
                        >
                            {{ TIPO[m.tipo] }}
                        </div>
                        <div class="min-w-0">
                            <div class="truncate text-sm font-medium text-ink">{{ m.titulo }}</div>
                            <div class="truncate text-xs text-stone-400">
                                {{ m.materia.nombre }}<template v-if="m.uploader"> · por {{ m.uploader }}</template>
                            </div>
                        </div>
                    </Link>
                </div>
            </section>

            <!-- Top colaboradores: oculto por pedido (se puede reactivar mostrando esta sección) -->
        </div>
    </AuthenticatedLayout>
</template>
