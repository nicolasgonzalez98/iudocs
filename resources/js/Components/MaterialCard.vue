<script setup>
import Tooltip from '@/Components/Tooltip.vue';
import { fileIcon, formatBytes } from '@/files';

defineProps({
    material: { type: Object, required: true },
});

defineEmits(['delete', 'edit', 'comments', 'vote', 'favorite']);
</script>

<template>
    <div class="flex items-start gap-3 rounded-xl border border-stone-200 bg-white p-4 shadow-sm transition hover:shadow-md">
        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-stone-100 text-xl">
            {{ fileIcon(material.mime, material.original_name) }}
        </div>

        <div class="min-w-0 flex-1">
            <div class="truncate font-medium text-ink">{{ material.titulo }}</div>
            <p v-if="material.descripcion" class="mt-0.5 text-sm text-stone-500">
                {{ material.descripcion }}
            </p>
            <div class="mt-2 text-xs text-stone-400">
                Subido por {{ material.uploader.name }} · {{ material.created_at }} ·
                {{ formatBytes(material.size) }} · ⬇ {{ material.downloads }}
            </div>

            <div class="mt-2 flex flex-wrap items-center gap-1">
                <button
                    type="button"
                    @click="$emit('vote', material)"
                    class="inline-flex items-center gap-1 rounded-lg border px-2 py-1 text-xs font-medium transition"
                    :class="material.has_voted
                        ? 'border-brand-300 bg-brand-50 text-brand-700'
                        : 'border-stone-200 text-stone-500 hover:bg-stone-100 hover:text-brand-600'"
                >
                    👍 Me sirvió ({{ material.helpful_count }})
                </button>
                <button
                    type="button"
                    @click="$emit('comments', material)"
                    class="inline-flex items-center gap-1 rounded-lg px-2 py-1 text-xs font-medium text-stone-500 transition hover:bg-stone-100 hover:text-brand-600"
                >
                    💬 Comentarios ({{ material.comments_count }})
                </button>
            </div>
        </div>

        <div class="flex shrink-0 items-center gap-1">
            <Tooltip :text="material.is_favorite ? 'Quitar de guardados' : 'Guardar'">
                <button
                    type="button"
                    @click="$emit('favorite', material)"
                    aria-label="Guardar"
                    class="rounded-lg px-2 py-1.5 text-lg leading-none transition hover:bg-stone-100"
                >
                    {{ material.is_favorite ? '⭐' : '☆' }}
                </button>
            </Tooltip>
            <a
                :href="route('materiales.download', material.id)"
                class="rounded-lg px-3 py-1.5 text-sm font-semibold text-brand-600 transition hover:bg-brand-50"
            >
                Descargar
            </a>
            <Tooltip v-if="material.can_delete" text="Editar">
                <button
                    type="button"
                    @click="$emit('edit', material)"
                    aria-label="Editar"
                    class="rounded-lg px-2 py-1.5 text-sm text-stone-400 transition hover:bg-brand-50 hover:text-brand-600"
                >
                    ✏️
                </button>
            </Tooltip>
            <Tooltip v-if="material.can_delete" text="Borrar">
                <button
                    type="button"
                    @click="$emit('delete', material)"
                    aria-label="Borrar"
                    class="rounded-lg px-2 py-1.5 text-sm font-medium text-stone-400 transition hover:bg-red-50 hover:text-red-600"
                >
                    ✕
                </button>
            </Tooltip>
        </div>
    </div>
</template>
