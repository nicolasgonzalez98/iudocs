<script setup>
import AdminTabs from '@/Components/AdminTabs.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import UserAvatar from '@/Components/UserAvatar.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

defineProps({
    pending: { type: Array, default: () => [] },
    others: { type: Array, default: () => [] },
    counts: { type: Object, default: () => ({ pending: 0, active: 0, blocked: 0 }) },
});

const meId = computed(() => usePage().props.auth.user.id);

// id del usuario sobre el que se está ejecutando una acción (para deshabilitar botones)
const busyId = ref(null);

const activate = (user) => {
    router.patch(
        route('admin.users.activate', user.id),
        {},
        {
            preserveScroll: true,
            onStart: () => (busyId.value = user.id),
            onFinish: () => (busyId.value = null),
        },
    );
};

const block = (user) => {
    if (!confirm(`¿Seguro que querés bloquear a ${user.name}? No va a poder entrar al Altillo.`)) {
        return;
    }
    router.patch(
        route('admin.users.block', user.id),
        {},
        {
            preserveScroll: true,
            onStart: () => (busyId.value = user.id),
            onFinish: () => (busyId.value = null),
        },
    );
};
</script>

<template>
    <Head title="Panel de admin" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-ink">Panel de admin</h2>
        </template>

        <div class="mx-auto max-w-5xl px-4 py-8 sm:px-6 lg:px-8">
            <AdminTabs />

            <!-- Resumen -->
            <div class="mb-8 grid grid-cols-3 gap-4">
                <div class="rounded-xl border border-stone-200 bg-white p-4 text-center shadow-sm">
                    <div class="text-2xl font-bold text-amber-600">{{ counts.pending }}</div>
                    <div class="mt-1 text-xs font-medium uppercase tracking-wide text-stone-500">Pendientes</div>
                </div>
                <div class="rounded-xl border border-stone-200 bg-white p-4 text-center shadow-sm">
                    <div class="text-2xl font-bold text-green-600">{{ counts.active }}</div>
                    <div class="mt-1 text-xs font-medium uppercase tracking-wide text-stone-500">Activos</div>
                </div>
                <div class="rounded-xl border border-stone-200 bg-white p-4 text-center shadow-sm">
                    <div class="text-2xl font-bold text-red-600">{{ counts.blocked }}</div>
                    <div class="mt-1 text-xs font-medium uppercase tracking-wide text-stone-500">Bloqueados</div>
                </div>
            </div>

            <!-- Solicitudes pendientes -->
            <section class="mb-10">
                <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-stone-500">
                    Solicitudes pendientes
                </h3>

                <div v-if="pending.length === 0" class="rounded-xl border border-dashed border-stone-300 bg-white/50 p-8 text-center">
                    <div class="text-2xl">✅</div>
                    <p class="mt-2 text-sm text-stone-500">No hay solicitudes pendientes. ¡Todo al día!</p>
                </div>

                <ul v-else class="space-y-3">
                    <li
                        v-for="user in pending"
                        :key="user.id"
                        class="flex flex-col gap-3 rounded-xl border border-amber-200 bg-amber-50/40 p-4 sm:flex-row sm:items-center sm:justify-between"
                    >
                        <div class="flex items-center gap-3">
                            <UserAvatar :name="user.name" :avatar="user.avatar" size="md" tone="amber" />
                            <div>
                                <div class="font-medium text-ink">{{ user.name }}</div>
                                <div class="text-sm text-stone-500">{{ user.email }}</div>
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            <button
                                type="button"
                                :disabled="busyId === user.id"
                                @click="activate(user)"
                                class="inline-flex items-center justify-center rounded-lg bg-amber-500 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-amber-600 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 focus:ring-offset-cream disabled:opacity-50"
                            >
                                Aprobar
                            </button>
                            <button
                                type="button"
                                :disabled="busyId === user.id"
                                @click="block(user)"
                                class="inline-flex items-center justify-center rounded-lg border border-stone-300 bg-white px-4 py-2 text-sm font-semibold text-stone-600 transition hover:bg-stone-50 focus:outline-none focus:ring-2 focus:ring-stone-400 focus:ring-offset-2 focus:ring-offset-cream disabled:opacity-50"
                            >
                                Rechazar
                            </button>
                        </div>
                    </li>
                </ul>
            </section>

            <!-- Todos los usuarios -->
            <section>
                <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-stone-500">
                    Todos los usuarios
                </h3>

                <div class="overflow-hidden rounded-xl border border-stone-200 bg-white shadow-sm">
                    <table class="min-w-full divide-y divide-stone-200">
                        <thead class="bg-stone-50">
                            <tr class="text-left text-xs font-semibold uppercase tracking-wide text-stone-500">
                                <th class="px-4 py-3">Usuario</th>
                                <th class="px-4 py-3">Estado</th>
                                <th class="px-4 py-3 text-right">Acción</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-stone-100">
                            <tr v-for="user in others" :key="user.id" class="text-sm">
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <UserAvatar :name="user.name" :avatar="user.avatar" size="sm" tone="stone" />
                                        <div>
                                            <div class="font-medium text-ink">
                                                {{ user.name }}
                                                <span v-if="user.role === 'admin'" class="ml-1 text-xs font-semibold text-amber-600">· admin</span>
                                            </div>
                                            <div class="text-stone-500">{{ user.email }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <StatusBadge :status="user.status" />
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <span v-if="user.id === meId" class="text-xs text-stone-400">vos</span>
                                    <template v-else>
                                        <button
                                            v-if="user.status === 'blocked'"
                                            type="button"
                                            :disabled="busyId === user.id"
                                            @click="activate(user)"
                                            class="rounded-lg border border-stone-300 bg-white px-3 py-1.5 text-sm font-semibold text-stone-700 transition hover:bg-stone-50 disabled:opacity-50"
                                        >
                                            Desbloquear
                                        </button>
                                        <button
                                            v-else
                                            type="button"
                                            :disabled="busyId === user.id"
                                            @click="block(user)"
                                            class="rounded-lg border border-red-200 bg-white px-3 py-1.5 text-sm font-semibold text-red-600 transition hover:bg-red-50 disabled:opacity-50"
                                        >
                                            Bloquear
                                        </button>
                                    </template>
                                </td>
                            </tr>
                            <tr v-if="others.length === 0">
                                <td colspan="3" class="px-4 py-8 text-center text-sm text-stone-500">
                                    Todavía no hay otros usuarios.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </AuthenticatedLayout>
</template>
