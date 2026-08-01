<script setup>
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { confirmState, settleConfirm } from '@/useConfirm';
</script>

<template>
    <Modal :show="confirmState.show" max-width="sm" @close="settleConfirm(false)">
        <div class="p-6">
            <h3 class="text-lg font-semibold text-ink">{{ confirmState.title }}</h3>
            <p v-if="confirmState.message" class="mt-2 text-sm text-stone-600">
                {{ confirmState.message }}
            </p>

            <div class="mt-6 flex justify-end gap-3">
                <SecondaryButton type="button" @click="settleConfirm(false)">
                    {{ confirmState.cancelText }}
                </SecondaryButton>
                <button
                    type="button"
                    @click="settleConfirm(true)"
                    class="inline-flex items-center justify-center rounded-lg px-4 py-2 text-sm font-semibold text-white shadow-sm transition focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-cream"
                    :class="confirmState.danger
                        ? 'bg-red-600 hover:bg-red-500 focus:ring-red-500'
                        : 'bg-brand-500 hover:bg-brand-600 focus:ring-brand-500'"
                >
                    {{ confirmState.confirmText }}
                </button>
            </div>
        </div>
    </Modal>
</template>
