<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Head, Link } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Cuenta bloqueada" />

    <div class="flex min-h-screen flex-col items-center justify-center bg-cream px-6 py-12 text-ink">
        <Link href="/" class="mb-8 flex flex-col items-center gap-2">
            <ApplicationLogo class="h-14 w-14 text-brand-500" />
            <span class="text-lg font-semibold tracking-tight">IUDocs</span>
        </Link>

        <div
            class="w-full max-w-md rounded-2xl border border-stone-200 bg-white p-8 text-center shadow-sm"
        >
            <div
                class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-red-100 text-3xl"
            >
                🚫
            </div>
            <h1 class="mt-5 text-xl font-semibold">Cuenta bloqueada</h1>
            <p class="mt-3 text-sm leading-relaxed text-stone-600">
                Tu acceso a IUDocs fue deshabilitado por una admin. Si creés que es un error,
                hablá con la persona que administra la página.
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
