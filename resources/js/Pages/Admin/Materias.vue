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
import { MATERIA_COLORS, materiaColor, periodoLabel } from '@/materiaColors';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps({
    materias: { type: Array, default: () => [] },
});

const EMOJIS = ['🧪', '🔬', '🧬', '⚗️', '💊', '🩺', '🌱', '🧫', '📐', '📊', '🧠', '📚'];
const COLORS = Object.entries(MATERIA_COLORS).map(([key, val]) => ({ key, ...val }));

const showModal = ref(false);

const form = useForm({
    id: null,
    nombre: '',
    anio: '',
    cuatrimestre: '',
    color: 'amber',
    icon: '',
});

const openCreate = () => {
    form.reset();
    form.clearErrors();
    showModal.value = true;
};

const openEdit = (m) => {
    form.clearErrors();
    form.id = m.id;
    form.nombre = m.nombre;
    form.anio = m.anio ?? '';
    form.cuatrimestre = m.cuatrimestre ?? '';
    form.color = m.color ?? 'amber';
    form.icon = m.icon ?? '';
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
};

const submit = () => {
    const opts = { preserveScroll: true, onSuccess: closeModal };
    if (form.id) {
        form.patch(route('admin.materias.update', form.id), opts);
    } else {
        form.post(route('admin.materias.store'), opts);
    }
};

const destroy = (m) => {
    if (confirm(`¿Borrar la materia "${m.nombre}"? Se van a borrar también sus materiales.`)) {
        router.delete(route('admin.materias.destroy', m.id), { preserveScroll: true });
    }
};
</script>

<template>
    <Head title="Materias · Admin" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-ink">Panel de admin</h2>
        </template>

        <div class="mx-auto max-w-5xl px-4 py-8 sm:px-6 lg:px-8">
            <AdminTabs />

            <div class="mb-6 flex items-center justify-between">
                <h3 class="text-sm font-semibold uppercase tracking-wide text-stone-500">
                    Materias ({{ materias.length }})
                </h3>
                <PrimaryButton @click="openCreate">+ Nueva materia</PrimaryButton>
            </div>

            <!-- Estado vacío -->
            <div
                v-if="materias.length === 0"
                class="rounded-xl border border-dashed border-stone-300 bg-white/50 p-10 text-center"
            >
                <div class="text-3xl">📚</div>
                <p class="mt-2 text-sm text-stone-500">
                    Todavía no hay materias. Creá la primera para empezar a organizar los apuntes.
                </p>
            </div>

            <!-- Lista -->
            <ul v-else class="grid gap-3 sm:grid-cols-2">
                <li
                    v-for="m in materias"
                    :key="m.id"
                    class="flex items-center justify-between gap-3 rounded-xl border bg-white p-4 shadow-sm"
                    :class="materiaColor(m.color).card"
                >
                    <div class="flex items-center gap-3">
                        <div
                            class="flex h-11 w-11 items-center justify-center rounded-lg text-xl"
                            :class="materiaColor(m.color).chip"
                        >
                            {{ m.icon || '📘' }}
                        </div>
                        <div>
                            <div class="font-medium text-ink">{{ m.nombre }}</div>
                            <div v-if="m.anio || m.cuatrimestre" class="text-xs text-stone-500">
                                {{ periodoLabel(m.anio, m.cuatrimestre) }}
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-1">
                        <button
                            type="button"
                            @click="openEdit(m)"
                            class="rounded-lg px-2.5 py-1.5 text-sm font-medium text-stone-500 transition hover:bg-stone-100 hover:text-stone-800"
                        >
                            Editar
                        </button>
                        <button
                            type="button"
                            @click="destroy(m)"
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
                    {{ form.id ? 'Editar materia' : 'Nueva materia' }}
                </h2>

                <div class="mt-5">
                    <InputLabel for="nombre" value="Nombre" />
                    <TextInput
                        id="nombre"
                        v-model="form.nombre"
                        type="text"
                        class="mt-1 block w-full"
                        placeholder="Ej: Química Orgánica"
                        required
                        autofocus
                    />
                    <InputError class="mt-2" :message="form.errors.nombre" />
                </div>

                <div class="mt-4 grid grid-cols-2 gap-4">
                    <div>
                        <InputLabel for="anio" value="Año" />
                        <select
                            id="anio"
                            v-model="form.anio"
                            class="mt-1 block w-full rounded-lg border-stone-300 shadow-sm focus:border-amber-500 focus:ring-amber-500"
                        >
                            <option value="">—</option>
                            <option v-for="a in 7" :key="a" :value="a">{{ a }}º año</option>
                        </select>
                        <InputError class="mt-2" :message="form.errors.anio" />
                    </div>
                    <div>
                        <InputLabel for="cuatri" value="Cuatrimestre" />
                        <select
                            id="cuatri"
                            v-model="form.cuatrimestre"
                            class="mt-1 block w-full rounded-lg border-stone-300 shadow-sm focus:border-amber-500 focus:ring-amber-500"
                        >
                            <option value="">—</option>
                            <option :value="1">1º cuatrimestre</option>
                            <option :value="2">2º cuatrimestre</option>
                        </select>
                        <InputError class="mt-2" :message="form.errors.cuatrimestre" />
                    </div>
                </div>

                <div class="mt-4">
                    <InputLabel value="Color" />
                    <div class="mt-2 flex flex-wrap gap-2">
                        <button
                            v-for="c in COLORS"
                            :key="c.key"
                            type="button"
                            @click="form.color = c.key"
                            :title="c.name"
                            class="h-8 w-8 rounded-full ring-offset-2 transition"
                            :class="[c.swatch, form.color === c.key ? 'ring-2 ring-stone-800' : 'hover:ring-2 hover:ring-stone-300']"
                        ></button>
                    </div>
                </div>

                <div class="mt-4">
                    <InputLabel value="Ícono (opcional)" />
                    <div class="mt-2 flex flex-wrap items-center gap-2">
                        <button
                            v-for="e in EMOJIS"
                            :key="e"
                            type="button"
                            @click="form.icon = form.icon === e ? '' : e"
                            class="flex h-9 w-9 items-center justify-center rounded-lg border text-lg transition"
                            :class="form.icon === e ? 'border-amber-500 bg-amber-50' : 'border-stone-200 hover:bg-stone-50'"
                        >
                            {{ e }}
                        </button>
                    </div>
                    <InputError class="mt-2" :message="form.errors.icon" />
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <SecondaryButton type="button" @click="closeModal">Cancelar</SecondaryButton>
                    <PrimaryButton :disabled="form.processing" :class="{ 'opacity-75': form.processing }">
                        <Spinner v-if="form.processing" class="-ms-1 me-2" />
                        {{ form.id ? 'Guardar cambios' : 'Crear materia' }}
                    </PrimaryButton>
                </div>
            </form>
        </Modal>
    </AuthenticatedLayout>
</template>
