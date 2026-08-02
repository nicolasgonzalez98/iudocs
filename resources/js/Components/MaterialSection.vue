<script setup>
import MaterialCard from '@/Components/MaterialCard.vue';
import { computed, ref } from 'vue';

const props = defineProps({
    titulo: { type: String, required: true },
    tipo: { type: String, required: true },
    addLabel: { type: String, required: true },
    materials: { type: Array, default: () => [] },
    subcarpetas: { type: Array, default: () => [] },
    sortBy: { type: String, default: 'recientes' },
    isAdmin: { type: Boolean, default: false },
});

defineEmits([
    'upload',
    'new-folder',
    'rename-folder',
    'delete-folder',
    'delete',
    'edit',
    'comments',
    'vote',
    'favorite',
]);

const SORTERS = {
    recientes: (a, b) => b.id - a.id,
    utiles: (a, b) => b.helpful_count - a.helpful_count || b.id - a.id,
    descargados: (a, b) => b.downloads - a.downloads || b.id - a.id,
};
const sortList = (list) => [...list].sort(SORTERS[props.sortBy] ?? SORTERS.recientes);

const folders = computed(() =>
    props.subcarpetas.map((s) => ({
        ...s,
        items: sortList(props.materials.filter((m) => m.subcarpeta_id === s.id)),
    })),
);
const loose = computed(() => sortList(props.materials.filter((m) => !m.subcarpeta_id)));

// Estado de expandido por carpeta (abiertas por defecto)
const collapsed = ref({});
const toggle = (id) => (collapsed.value[id] = !collapsed.value[id]);
const isOpen = (id) => !collapsed.value[id];
</script>

<template>
    <section>
        <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
            <h3 class="text-sm font-semibold uppercase tracking-wide text-stone-500">
                {{ titulo }} ({{ materials.length }})
            </h3>
            <div class="flex items-center gap-3">
                <button
                    v-if="isAdmin"
                    type="button"
                    @click="$emit('new-folder', tipo)"
                    class="text-sm font-medium text-stone-500 hover:text-brand-700"
                >
                    📁 Nueva carpeta
                </button>
                <button
                    type="button"
                    @click="$emit('upload', tipo)"
                    class="text-sm font-medium text-brand-600 hover:text-brand-700"
                >
                    {{ addLabel }}
                </button>
            </div>
        </div>

        <!-- Estado vacío total -->
        <div
            v-if="materials.length === 0 && subcarpetas.length === 0"
            class="rounded-xl border border-dashed border-stone-300 bg-white/50 p-8 text-center text-sm text-stone-500"
        >
            Todavía no hay nada en esta sección.
        </div>

        <div v-else class="space-y-3">
            <!-- Carpetas (acordeón) -->
            <div
                v-for="f in folders"
                :key="f.id"
                class="overflow-hidden rounded-xl border border-stone-200 bg-stone-50/60"
            >
                <div class="flex items-center gap-2 px-3 py-2.5">
                    <button
                        type="button"
                        @click="toggle(f.id)"
                        class="flex min-w-0 flex-1 items-center gap-2 text-left"
                    >
                        <span class="text-stone-400 transition-transform" :class="{ 'rotate-90': isOpen(f.id) }">▶</span>
                        <span class="text-lg">📁</span>
                        <span class="truncate font-medium text-ink">{{ f.nombre }}</span>
                        <span class="shrink-0 text-xs text-stone-400">({{ f.items.length }})</span>
                    </button>
                    <template v-if="isAdmin">
                        <button
                            type="button"
                            @click="$emit('rename-folder', f)"
                            class="rounded-lg px-2 py-1 text-xs font-medium text-stone-400 transition hover:bg-stone-200/60 hover:text-stone-700"
                        >
                            Renombrar
                        </button>
                        <button
                            type="button"
                            @click="$emit('delete-folder', f)"
                            class="rounded-lg px-2 py-1 text-xs font-medium text-stone-400 transition hover:bg-red-50 hover:text-red-600"
                        >
                            Borrar
                        </button>
                    </template>
                </div>

                <div v-if="isOpen(f.id)" class="space-y-3 px-3 pb-3">
                    <div
                        v-if="f.items.length === 0"
                        class="rounded-lg border border-dashed border-stone-300 bg-white/60 p-4 text-center text-xs text-stone-400"
                    >
                        Carpeta vacía.
                    </div>
                    <MaterialCard
                        v-for="m in f.items"
                        :key="m.id"
                        :material="m"
                        @delete="$emit('delete', $event)"
                        @edit="$emit('edit', $event)"
                        @comments="$emit('comments', $event)"
                        @vote="$emit('vote', $event)"
                        @favorite="$emit('favorite', $event)"
                    />
                </div>
            </div>

            <!-- Otros archivos (fuera de carpetas) -->
            <p
                v-if="folders.length && loose.length"
                class="pt-1 text-xs font-medium uppercase tracking-wide text-stone-400"
            >
                Otros archivos
            </p>
            <MaterialCard
                v-for="m in loose"
                :key="m.id"
                :material="m"
                @delete="$emit('delete', $event)"
                @edit="$emit('edit', $event)"
                @comments="$emit('comments', $event)"
                @vote="$emit('vote', $event)"
                @favorite="$emit('favorite', $event)"
            />
        </div>
    </section>
</template>
