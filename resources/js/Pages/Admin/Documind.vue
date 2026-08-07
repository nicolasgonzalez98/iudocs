<script setup>
import AdminTabs from '@/Components/AdminTabs.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps({
    enabled: { type: Boolean, default: false },
    materials: { type: Array, default: () => [] },
    counts: {
        type: Object,
        default: () => ({ total: 0, synced: 0, pending: 0, error: 0, skipped: 0 }),
    },
});

const busyId = ref(null); // material en proceso de reindexar
const busyAction = ref(null); // 'reconcile' | 'syncAll'

const post = (url, opts) =>
    router.post(url, {}, { preserveScroll: true, ...opts });

const resync = (m) =>
    post(route('admin.documind.resync', m.id), {
        onStart: () => (busyId.value = m.id),
        onFinish: () => (busyId.value = null),
    });

const reconcile = () =>
    post(route('admin.documind.reconcile'), {
        onStart: () => (busyAction.value = 'reconcile'),
        onFinish: () => (busyAction.value = null),
    });

const syncAll = () =>
    post(route('admin.documind.sync-all'), {
        onStart: () => (busyAction.value = 'syncAll'),
        onFinish: () => (busyAction.value = null),
    });

const TIPO_LABEL = { apunte: 'Apunte', examen: 'Examen', campus: 'Campus' };
</script>

<template>
    <Head title="Sincronización · Admin" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-ink">Panel de admin</h2>
        </template>

        <div class="mx-auto max-w-5xl px-4 py-8 sm:px-6 lg:px-8">
            <AdminTabs />

            <div class="mb-6">
                <h3 class="text-lg font-semibold text-ink">Sincronización con DocuMind</h3>
                <p class="mt-1 text-sm text-stone-500">
                    Los apuntes se indexan en el motor de IA para poder consultarlos por chat.
                    Acá ves el estado de cada archivo y podés reindexar.
                </p>
            </div>

            <div
                v-if="!enabled"
                class="mb-6 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800"
            >
                La integración con DocuMind está <strong>deshabilitada</strong>
                (variable <code>DOCUMIND_ENABLED</code>). El estado de abajo puede estar desactualizado.
            </div>

            <!-- Resumen -->
            <div class="mb-6 grid grid-cols-2 gap-4 sm:grid-cols-4">
                <div class="rounded-xl border border-stone-200 bg-white p-4 text-center shadow-sm">
                    <div class="text-2xl font-bold text-green-600">{{ counts.synced }}</div>
                    <div class="mt-1 text-xs font-medium uppercase tracking-wide text-stone-500">Sincronizados</div>
                </div>
                <div class="rounded-xl border border-stone-200 bg-white p-4 text-center shadow-sm">
                    <div class="text-2xl font-bold text-brand-600">{{ counts.pending }}</div>
                    <div class="mt-1 text-xs font-medium uppercase tracking-wide text-stone-500">Pendientes</div>
                </div>
                <div class="rounded-xl border border-stone-200 bg-white p-4 text-center shadow-sm">
                    <div class="text-2xl font-bold text-red-600">{{ counts.error }}</div>
                    <div class="mt-1 text-xs font-medium uppercase tracking-wide text-stone-500">Con error</div>
                </div>
                <div class="rounded-xl border border-stone-200 bg-white p-4 text-center shadow-sm">
                    <div class="text-2xl font-bold text-stone-500">{{ counts.skipped }}</div>
                    <div class="mt-1 text-xs font-medium uppercase tracking-wide text-stone-500">Omitidos</div>
                </div>
            </div>

            <!-- Acciones globales -->
            <div class="mb-6 flex flex-wrap items-center gap-3">
                <button
                    type="button"
                    :disabled="busyAction !== null"
                    @click="reconcile"
                    class="inline-flex items-center justify-center rounded-lg border border-stone-300 bg-white px-4 py-2 text-sm font-semibold text-stone-700 shadow-sm transition hover:bg-stone-50 disabled:opacity-50"
                >
                    {{ busyAction === 'reconcile' ? 'Actualizando…' : 'Actualizar estado' }}
                </button>
                <button
                    type="button"
                    :disabled="busyAction !== null"
                    @click="syncAll"
                    class="inline-flex items-center justify-center rounded-lg bg-brand-500 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 focus:ring-offset-cream disabled:opacity-50"
                >
                    {{ busyAction === 'syncAll' ? 'Encolando…' : 'Sincronizar pendientes' }}
                </button>
                <span class="text-xs text-stone-400">
                    "Actualizar estado" consulta a DocuMind; "Sincronizar pendientes" encola los que faltan (requiere el worker corriendo).
                </span>
            </div>

            <!-- Tabla (scroll horizontal en pantallas chicas para no cortar la acción) -->
            <div class="overflow-x-auto rounded-xl border border-stone-200 bg-white shadow-sm">
                <table class="min-w-full divide-y divide-stone-200">
                    <thead class="bg-stone-50">
                        <tr class="text-left text-xs font-semibold uppercase tracking-wide text-stone-500">
                            <th class="px-4 py-3">Archivo</th>
                            <th class="px-4 py-3">Materia</th>
                            <th class="px-4 py-3">Tipo</th>
                            <th class="px-4 py-3">Estado</th>
                            <th class="px-4 py-3">Última sync</th>
                            <th class="px-4 py-3 text-right">Acción</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-100">
                        <tr v-for="m in materials" :key="m.id" class="text-sm align-top">
                            <td class="px-4 py-3">
                                <div class="font-medium text-ink">{{ m.titulo }}</div>
                                <div class="truncate text-xs text-stone-400" :title="m.original_name">
                                    {{ m.original_name }}
                                </div>
                            </td>
                            <td class="px-4 py-3 text-stone-600">{{ m.materia ?? '—' }}</td>
                            <td class="px-4 py-3 text-stone-600">{{ TIPO_LABEL[m.tipo] ?? m.tipo }}</td>
                            <td class="px-4 py-3">
                                <StatusBadge :status="m.status" />
                                <div
                                    v-if="m.error && (m.status === 'error' || m.status === 'skipped')"
                                    class="mt-1 max-w-xs truncate text-xs text-stone-400"
                                    :title="m.error"
                                >
                                    {{ m.error }}
                                </div>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-stone-500">{{ m.synced_at ?? '—' }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right">
                                <button
                                    type="button"
                                    :disabled="busyId === m.id"
                                    @click="resync(m)"
                                    class="rounded-lg border border-stone-300 bg-white px-3 py-1.5 text-sm font-semibold text-stone-700 transition hover:bg-stone-50 disabled:opacity-50"
                                >
                                    {{ busyId === m.id ? '…' : 'Reindexar' }}
                                </button>
                            </td>
                        </tr>
                        <tr v-if="materials.length === 0">
                            <td colspan="6" class="px-4 py-8 text-center text-sm text-stone-500">
                                Todavía no hay materiales.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
