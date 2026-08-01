<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    canLogin: {
        type: Boolean,
    },
    canRegister: {
        type: Boolean,
    },
    laravelVersion: {
        type: String,
        required: true,
    },
    phpVersion: {
        type: String,
        required: true,
    },
});

const features = [
    {
        icon: '📚',
        title: 'Ordenado por materia',
        text: 'Nada de apuntes perdidos en mil chats. Cada materia con sus apuntes y exámenes en un solo lugar.',
    },
    {
        icon: '💬',
        title: 'Colaborativo',
        text: 'Comentá cada material: dudas, correcciones y tips entre compañeros de la cursada.',
    },
    {
        icon: '🔒',
        title: 'Curado y privado',
        text: 'Solo entra quien la admin habilita. Tu material queda entre estudiantes de confianza.',
    },
];
</script>

<template>
    <Head title="IUDocs — apuntes de la cursada" />

    <div class="min-h-screen bg-cream text-ink">
        <!-- Header -->
        <header class="mx-auto flex max-w-6xl items-center justify-between px-6 py-5">
            <div class="flex items-center gap-2">
                <ApplicationLogo class="h-8 w-8 text-brand-500" />
                <span class="text-xl font-semibold tracking-tight">IUDocs</span>
            </div>

            <nav v-if="canLogin" class="flex items-center gap-2">
                <Link
                    v-if="$page.props.auth.user"
                    :href="route('dashboard')"
                    class="rounded-lg px-4 py-2 text-sm font-semibold text-stone-700 transition hover:bg-stone-100"
                >
                    Ir a IUDocs
                </Link>

                <template v-else>
                    <Link
                        :href="route('login')"
                        class="rounded-lg px-4 py-2 text-sm font-semibold text-stone-700 transition hover:bg-stone-100"
                    >
                        Iniciar sesión
                    </Link>
                    <Link
                        v-if="canRegister"
                        :href="route('register')"
                        class="rounded-lg bg-brand-500 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-600"
                    >
                        Registrarme
                    </Link>
                </template>
            </nav>
        </header>

        <!-- Hero -->
        <main class="mx-auto max-w-6xl px-6">
            <section class="py-16 text-center sm:py-24">
                <span
                    class="inline-flex items-center gap-2 rounded-full border border-brand-200 bg-brand-50 px-3 py-1 text-xs font-medium text-brand-700"
                >
                    📖 Apuntes y exámenes de la cursada
                </span>
                <h1 class="mx-auto mt-6 max-w-3xl text-4xl font-bold tracking-tight sm:text-5xl">
                    Todos los apuntes y exámenes,
                    <span class="text-brand-600">en un solo lugar</span>
                </h1>
                <p class="mx-auto mt-5 max-w-xl text-lg text-stone-600">
                    Un portal colaborativo para compartir apuntes y exámenes por materia.
                    Subís, comentás y estudian todos juntos.
                </p>

                <div class="mt-8 flex flex-col items-center justify-center gap-3 sm:flex-row">
                    <Link
                        v-if="canRegister && !$page.props.auth.user"
                        :href="route('register')"
                        class="w-full rounded-lg bg-brand-500 px-6 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-600 sm:w-auto"
                    >
                        Sumarme a IUDocs
                    </Link>
                    <Link
                        v-if="!$page.props.auth.user"
                        :href="route('login')"
                        class="w-full rounded-lg border border-stone-300 bg-white px-6 py-3 text-sm font-semibold text-stone-700 shadow-sm transition hover:bg-stone-50 sm:w-auto"
                    >
                        Ya tengo cuenta
                    </Link>
                    <Link
                        v-if="$page.props.auth.user"
                        :href="route('dashboard')"
                        class="w-full rounded-lg bg-brand-500 px-6 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-600 sm:w-auto"
                    >
                        Entrar a IUDocs
                    </Link>
                </div>
            </section>

            <!-- Features -->
            <section class="grid gap-6 pb-20 sm:grid-cols-3">
                <div
                    v-for="f in features"
                    :key="f.title"
                    class="rounded-2xl border border-stone-200 bg-white p-6 shadow-sm"
                >
                    <div class="text-3xl">{{ f.icon }}</div>
                    <h3 class="mt-4 text-lg font-semibold">{{ f.title }}</h3>
                    <p class="mt-2 text-sm leading-relaxed text-stone-600">{{ f.text }}</p>
                </div>
            </section>
        </main>

        <!-- Footer -->
        <footer class="border-t border-stone-200">
            <div
                class="mx-auto flex max-w-6xl flex-col items-center justify-between gap-2 px-6 py-6 text-sm text-stone-500 sm:flex-row"
            >
                <span>© {{ new Date().getFullYear() }} IUDocs</span>
                <span>Hecho para estudiar en equipo 🧉</span>
            </div>
        </footer>
    </div>
</template>
