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

const emit = defineEmits([
    'upload',
    'new-folder',
    'rename-folder',
    'delete-folder',
    'reorder-folders',
    'delete',
    'edit',
    'move',
    'move-many',
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

// A — carpetas colapsadas por defecto
const expanded = ref({});
const isOpen = (id) => !!expanded.value[id];
const toggle = (id) => (expanded.value[id] = !expanded.value[id]);

// D — reordenar carpetas (admin) con flechas
const moveFolder = (index, dir) => {
    const ids = folders.value.map((f) => f.id);
    const target = index + dir;
    if (target < 0 || target >= ids.length) return;
    [ids[index], ids[target]] = [ids[target], ids[index]];
    emit('reorder-folders', { tipo: props.tipo, ids });
};

// B — selección múltiple para mover en lote
const selectMode = ref(false);
const selected = ref(new Set());
const isSelected = (m) => selected.value.has(m.id);
const toggleSelect = (m) => {
    const s = new Set(selected.value);
    s.has(m.id) ? s.delete(m.id) : s.add(m.id);
    selected.value = s;
};
const startSelect = () => {
    selectMode.value = true;
    selected.value = new Set();
};
const cancelSelect = () => {
    selectMode.value = false;
    selected.value = new Set();
};
const moveSelected = () => {
    if (!selected.value.size) return;
    emit('move-many', { tipo: props.tipo, ids: [...selected.value] });
    cancelSelect();
};
const movableCount = computed(() => props.materials.filter((m) => m.can_delete).length);
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
                    v-if="movableCount && !selectMode"
                    type="button"
                    @click="startSelect"
                    class="text-sm font-medium text-stone-500 hover:text-brand-700"
                >
                    ☑️ Seleccionar
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

        <!-- B — barra de acciones de selección -->
        <div
            v-if="selectMode"
            class="mb-3 flex flex-wrap items-center justify-between gap-2 rounded-xl border border-brand-200 bg-brand-50 px-3 py-2"
        >
            <span class="text-sm font-medium text-brand-800">
                {{ selected.size }} seleccionado{{ selected.size === 1 ? '' : 's' }}
            </span>
            <div class="flex items-center gap-2">
                <button
                    type="button"
                    @click="cancelSelect"
                    class="rounded-lg px-3 py-1.5 text-sm font-medium text-stone-500 transition hover:bg-white/70"
                >
                    Cancelar
                </button>
                <button
                    type="button"
                    :disabled="selected.size === 0"
                    @click="moveSelected"
                    class="rounded-lg bg-brand-500 px-3 py-1.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-600 disabled:opacity-50"
                >
                    Mover seleccionados
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
                v-for="(f, index) in folders"
                :key="f.id"
                class="overflow-hidden rounded-xl border border-stone-200 bg-stone-50/60"
            >
                <div class="flex items-center gap-1 px-3 py-2.5">
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
                            :disabled="index === 0"
                            @click="moveFolder(index, -1)"
                            aria-label="Subir carpeta"
                            class="rounded-lg px-1.5 py-1 text-stone-400 transition hover:bg-stone-200/60 hover:text-stone-700 disabled:opacity-30 disabled:hover:bg-transparent"
                        >
                            ↑
                        </button>
                        <button
                            type="button"
                            :disabled="index === folders.length - 1"
                            @click="moveFolder(index, 1)"
                            aria-label="Bajar carpeta"
                            class="rounded-lg px-1.5 py-1 text-stone-400 transition hover:bg-stone-200/60 hover:text-stone-700 disabled:opacity-30 disabled:hover:bg-transparent"
                        >
                            ↓
                        </button>
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
                        :selectable="selectMode && m.can_delete"
                        :selected="isSelected(m)"
                        @toggle-select="toggleSelect"
                        @delete="$emit('delete', $event)"
                        @edit="$emit('edit', $event)"
                        @move="$emit('move', $event)"
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
                :selectable="selectMode && m.can_delete"
                :selected="isSelected(m)"
                @toggle-select="toggleSelect"
                @delete="$emit('delete', $event)"
                @edit="$emit('edit', $event)"
                @move="$emit('move', $event)"
                @comments="$emit('comments', $event)"
                @vote="$emit('vote', $event)"
                @favorite="$emit('favorite', $event)"
            />
        </div>
    </section>
</template>
