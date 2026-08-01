<script setup>
import { fileIcon, formatBytes } from '@/files';

defineProps({
    material: { type: Object, required: true },
});

defineEmits(['delete']);
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
                {{ formatBytes(material.size) }}
            </div>
        </div>

        <div class="flex shrink-0 items-center gap-1">
            <a
                :href="route('materiales.download', material.id)"
                class="rounded-lg px-3 py-1.5 text-sm font-semibold text-amber-600 transition hover:bg-amber-50"
            >
                Descargar
            </a>
            <button
                v-if="material.can_delete"
                type="button"
                @click="$emit('delete', material)"
                class="rounded-lg px-2 py-1.5 text-sm font-medium text-stone-400 transition hover:bg-red-50 hover:text-red-600"
                title="Borrar"
            >
                ✕
            </button>
        </div>
    </div>
</template>
