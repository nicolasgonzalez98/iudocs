<script setup>
import Modal from '@/Components/Modal.vue';
import Spinner from '@/Components/Spinner.vue';
import UserAvatar from '@/Components/UserAvatar.vue';
import { useConfirm } from '@/useConfirm';
import { router, useForm } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';

const props = defineProps({
    material: { type: Object, required: true },
});

defineEmits(['close']);

const { confirm } = useConfirm();

// El Modal (dialog nativo) solo abre cuando `show` pasa de false → true,
// así que arrancamos en false y lo activamos al montar.
const show = ref(false);
onMounted(() => {
    show.value = true;
});

// id del comentario que se está borrando (para el loader)
const busyCommentId = ref(null);

const form = useForm({ body: '' });

const submit = () => {
    if (!form.body.trim() || form.processing) return;
    form.post(route('comentarios.store', props.material.id), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
};

const remove = async (comment) => {
    const ok = await confirm({
        title: 'Borrar comentario',
        message: 'Se va a eliminar este comentario.',
        confirmText: 'Borrar',
        danger: true,
    });
    if (!ok) return;
    router.delete(route('comentarios.destroy', comment.id), {
        preserveScroll: true,
        onStart: () => (busyCommentId.value = comment.id),
        onFinish: () => (busyCommentId.value = null),
    });
};
</script>

<template>
    <Modal :show="show" max-width="lg" @close="$emit('close')">
        <div class="flex max-h-[80vh] flex-col">
            <!-- Header -->
            <div class="flex items-start justify-between gap-3 border-b border-stone-200 p-5">
                <div class="min-w-0">
                    <h3 class="truncate font-semibold text-ink">💬 Comentarios</h3>
                    <p class="truncate text-sm text-stone-500">{{ material.titulo }}</p>
                </div>
                <button
                    type="button"
                    @click="$emit('close')"
                    class="rounded-lg px-2 py-1 text-stone-400 transition hover:bg-stone-100 hover:text-stone-700"
                >
                    ✕
                </button>
            </div>

            <!-- Lista -->
            <div class="flex-1 space-y-4 overflow-y-auto p-5">
                <p v-if="material.comments.length === 0" class="py-6 text-center text-sm text-stone-400">
                    Todavía no hay comentarios. ¡Sé la primera persona en comentar!
                </p>

                <div v-for="c in material.comments" :key="c.id" class="flex gap-3">
                    <UserAvatar :name="c.author.name" :avatar="c.author.avatar" size="sm" tone="stone" />
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-medium text-ink">{{ c.author.name }}</span>
                            <span class="text-xs text-stone-400">{{ c.created_at }}</span>
                            <button
                                v-if="c.can_delete"
                                type="button"
                                :disabled="busyCommentId === c.id"
                                @click="remove(c)"
                                class="ml-auto flex items-center text-xs text-stone-400 transition hover:text-red-500 disabled:opacity-60"
                                title="Borrar"
                            >
                                <Spinner v-if="busyCommentId === c.id" class="h-3 w-3" />
                                <span v-else>✕</span>
                            </button>
                        </div>
                        <p class="mt-0.5 whitespace-pre-wrap break-words text-sm text-stone-700">{{ c.body }}</p>
                    </div>
                </div>
            </div>

            <!-- Form -->
            <form @submit.prevent="submit" class="border-t border-stone-200 p-4">
                <textarea
                    v-model="form.body"
                    rows="2"
                    required
                    maxlength="2000"
                    placeholder="Escribí un comentario… (Ctrl+Enter para enviar)"
                    class="block w-full rounded-lg border-stone-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500"
                    @keydown.ctrl.enter.prevent="submit"
                    @keydown.meta.enter.prevent="submit"
                ></textarea>
                <div class="mt-2 flex justify-end">
                    <button
                        type="submit"
                        :disabled="form.processing || !form.body.trim()"
                        class="inline-flex items-center justify-center rounded-lg bg-brand-500 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 disabled:opacity-50"
                    >
                        <Spinner v-if="form.processing" class="-ms-1 me-2" />
                        Comentar
                    </button>
                </div>
            </form>
        </div>
    </Modal>
</template>
