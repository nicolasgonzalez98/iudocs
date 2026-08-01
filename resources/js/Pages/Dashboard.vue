<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2
                class="text-xl font-semibold leading-tight text-ink"
            >
                Inicio
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div
                    class="overflow-hidden rounded-xl border border-stone-200 bg-white shadow-sm"
                >
                    <div class="p-6 text-ink">
                        <h3 class="text-lg font-semibold">¡Bienvenido/a a IUDocs! 📚</h3>
                        <p class="mt-1 text-sm text-stone-600">
                            Acá van a vivir los apuntes y exámenes de la cursada, ordenados por materia.
                            Pronto vas a poder navegar las materias y subir tu primer apunte.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
