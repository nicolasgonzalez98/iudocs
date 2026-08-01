<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const user = computed(() => usePage().props.auth.user);
</script>

<template>
    <Head title="Esperando aprobación" />

    <div class="flex min-h-screen flex-col items-center justify-center bg-cream px-6 py-12 text-ink">
        <Link href="/" class="mb-8 flex flex-col items-center gap-2">
            <ApplicationLogo class="h-14 w-14 text-brand-500" />
            <span class="text-lg font-semibold tracking-tight">IUDocs</span>
        </Link>

        <div
            class="w-full max-w-md rounded-2xl border border-stone-200 bg-white p-8 text-center shadow-sm"
        >
            <div
                class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-brand-100 text-3xl"
            >
                ⏳
            </div>
            <h1 class="mt-5 text-xl font-semibold">Esperando aprobación</h1>
            <p class="mt-3 text-sm leading-relaxed text-stone-600">
                ¡Gracias por registrarte<span v-if="user">, {{ user.name.split(' ')[0] }}</span>!
                Tu cuenta quedó pendiente. Una admin tiene que aprobarte antes de que puedas
                entrar a IUDocs. Te avisamos apenas esté lista.
            </p>

            <div class="mt-6">
                <Link
                    :href="route('logout')"
                    method="post"
                    as="button"
                    class="text-sm font-medium text-stone-500 underline-offset-4 transition hover:text-brand-600 hover:underline"
                >
                    Cerrar sesión
                </Link>
            </div>
        </div>
    </div>
</template>
