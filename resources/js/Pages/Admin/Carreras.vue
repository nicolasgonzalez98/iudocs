<script setup>
import AdminTabs from '@/Components/AdminTabs.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import Spinner from '@/Components/Spinner.vue';
import TextInput from '@/Components/TextInput.vue';
import { periodoLabel } from '@/materiaColors';
import { useConfirm } from '@/useConfirm';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const { confirm } = useConfirm();

defineProps({
    carreras: { type: Array, default: () => [] },
    materias: { type: Array, default: () => [] },
});

const showModal = ref(false);

const form = useForm({
    id: null,
    nombre: '',
    materia_ids: [],
});

const resetForm = () => {
    form.clearErrors();
    form.id = null;
    form.nombre = '';
    form.materia_ids = [];
};

const openCreate = () => {
    resetForm();
    showModal.value = true;
};

const openEdit = (c) => {
    form.clearErrors();
    form.id = c.id;
    form.nombre = c.nombre;
    form.materia_ids = [...c.materia_ids];
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
};

const submit = () => {
    const opts = {
        preserveScroll: true,
        onSuccess: () => {
            closeModal();
            resetForm();
        },
    };
    if (form.id) {
        form.patch(route('admin.carreras.update', form.id), opts);
    } else {
        form.post(route('admin.carreras.store'), opts);
    }
};

const destroy = async (c) => {
    const ok = await confirm({
        title: 'Borrar carrera',
        message: `¿Borrar la carrera "${c.nombre}"? Las materias NO se borran, solo se desvinculan de esta carrera.`,
        confirmText: 'Borrar',
        danger: true,
    });
    if (ok) router.delete(route('admin.carreras.destroy', c.id), { preserveScroll: true });
};
</script>

<template>
    <Head title="Carreras · Admin" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-ink">Panel de admin</h2>
        </template>

        <div class="mx-auto max-w-5xl px-4 py-8 sm:px-6 lg:px-8">
            <AdminTabs />

            <div class="mb-6 flex items-center justify-between">
                <h3 class="text-sm font-semibold uppercase tracking-wide text-stone-500">
                    Carreras ({{ carreras.length }})
                </h3>
                <PrimaryButton @click="openCreate">+ Nueva carrera</PrimaryButton>
            </div>

            <!-- Estado vacío -->
            <div
                v-if="carreras.length === 0"
                class="rounded-xl border border-dashed border-stone-300 bg-white/50 p-10 text-center"
            >
                <div class="text-3xl">🎓</div>
                <p class="mt-2 text-sm text-stone-500">
                    Todavía no hay carreras. Creá la primera para agrupar materias.
                </p>
            </div>

            <!-- Lista -->
            <ul v-else class="grid gap-3 sm:grid-cols-2">
                <li
                    v-for="c in carreras"
                    :key="c.id"
                    class="flex items-center justify-between gap-3 rounded-xl border border-stone-200 bg-white p-4 shadow-sm"
                >
                    <div class="flex items-center gap-3">
                        <div class="flex h-11 w-11 items-center justify-center rounded-lg bg-brand-50 text-xl">
                            🎓
                        </div>
                        <div>
                            <div class="font-medium text-ink">{{ c.nombre }}</div>
                            <div class="text-xs text-stone-500">
                                {{ c.materias_count }} {{ c.materias_count === 1 ? 'materia' : 'materias' }}
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-1">
                        <button
                            type="button"
                            @click="openEdit(c)"
                            class="rounded-lg px-2.5 py-1.5 text-sm font-medium text-stone-500 transition hover:bg-stone-100 hover:text-stone-800"
                        >
                            Editar
                        </button>
                        <button
                            type="button"
                            @click="destroy(c)"
                            class="rounded-lg px-2.5 py-1.5 text-sm font-medium text-red-500 transition hover:bg-red-50"
                        >
                            Borrar
                        </button>
                    </div>
                </li>
            </ul>
        </div>

        <!-- Modal crear / editar -->
        <Modal :show="showModal" @close="closeModal">
            <form @submit.prevent="submit" class="p-6">
                <h2 class="text-lg font-semibold text-ink">
                    {{ form.id ? 'Editar carrera' : 'Nueva carrera' }}
                </h2>

                <div class="mt-5">
                    <InputLabel for="nombre" value="Nombre" />
                    <TextInput
                        id="nombre"
                        v-model="form.nombre"
                        type="text"
                        class="mt-1 block w-full"
                        placeholder="Ej: Biotecnología"
                        required
                        autofocus
                    />
                    <InputError class="mt-2" :message="form.errors.nombre" />
                </div>

                <div class="mt-5">
                    <InputLabel value="Materias de esta carrera" />
                    <p class="mt-0.5 text-xs text-stone-400">
                        Tildá las materias que pertenecen a esta carrera. Una materia puede estar en varias.
                    </p>
                    <div
                        v-if="materias.length"
                        class="mt-2 max-h-72 space-y-1 overflow-y-auto rounded-lg border border-stone-200 p-2"
                    >
                        <label
                            v-for="m in materias"
                            :key="m.id"
                            class="flex cursor-pointer items-center gap-2 rounded-lg px-2 py-1.5 hover:bg-stone-50"
                        >
                            <input
                                type="checkbox"
                                :value="m.id"
                                v-model="form.materia_ids"
                                class="rounded border-stone-300 text-brand-600 focus:ring-brand-500"
                            />
                            <span class="min-w-0 flex-1 truncate text-sm text-ink">{{ m.nombre }}</span>
                            <span v-if="m.anio || m.cuatrimestre" class="shrink-0 text-xs text-stone-400">
                                {{ periodoLabel(m.anio, m.cuatrimestre) }}
                            </span>
                        </label>
                    </div>
                    <p v-else class="mt-2 text-sm text-stone-400">
                        Todavía no hay materias creadas.
                    </p>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <SecondaryButton type="button" @click="closeModal">Cancelar</SecondaryButton>
                    <PrimaryButton :disabled="form.processing" :class="{ 'opacity-75': form.processing }">
                        <Spinner v-if="form.processing" class="-ms-1 me-2" />
                        {{ form.id ? 'Guardar cambios' : 'Crear carrera' }}
                    </PrimaryButton>
                </div>
            </form>
        </Modal>
    </AuthenticatedLayout>
</template>
