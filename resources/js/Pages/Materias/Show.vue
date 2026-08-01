<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import MaterialCard from '@/Components/MaterialCard.vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import Spinner from '@/Components/Spinner.vue';
import TextInput from '@/Components/TextInput.vue';
import { formatBytes } from '@/files';
import { materiaColor, periodoLabel } from '@/materiaColors';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    materia: { type: Object, required: true },
    apuntes: { type: Array, default: () => [] },
    campus: { type: Array, default: () => [] },
    examenes: { type: Array, default: () => [] },
});

const showModal = ref(false);
const fileInput = ref(null);

const form = useForm({
    titulo: '',
    tipo: 'apunte',
    descripcion: '',
    file: null,
});

const openUpload = (tipo = 'apunte') => {
    form.reset();
    form.clearErrors();
    form.tipo = tipo;
    if (fileInput.value) fileInput.value.value = '';
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
};

const onFile = (e) => {
    form.file = e.target.files[0] ?? null;
};

const submit = () => {
    form.post(route('materiales.store', props.materia.id), {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            closeModal();
            form.reset();
            if (fileInput.value) fileInput.value.value = '';
        },
    });
};

const destroy = (material) => {
    if (confirm(`¿Borrar "${material.titulo}"? Esta acción no se puede deshacer.`)) {
        router.delete(route('materiales.destroy', material.id), { preserveScroll: true });
    }
};
</script>

<template>
    <Head :title="materia.nombre" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between gap-3">
                <div class="flex min-w-0 items-center gap-3">
                    <Link
                        :href="route('dashboard')"
                        class="text-stone-400 transition hover:text-stone-700"
                        title="Volver a materias"
                    >
                        ←
                    </Link>
                    <div
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg text-xl"
                        :class="materiaColor(materia.color).chip"
                    >
                        {{ materia.icon || '📘' }}
                    </div>
                    <div class="min-w-0">
                        <h2 class="truncate text-xl font-semibold leading-tight text-ink">
                            {{ materia.nombre }}
                        </h2>
                        <p v-if="materia.anio || materia.cuatrimestre" class="text-xs text-stone-500">
                            {{ periodoLabel(materia.anio, materia.cuatrimestre) }}
                        </p>
                    </div>
                </div>

                <PrimaryButton class="shrink-0" @click="openUpload('apunte')">
                    Subir material
                </PrimaryButton>
            </div>
        </template>

        <div class="mx-auto max-w-4xl space-y-10 px-4 py-8 sm:px-6 lg:px-8">
            <!-- Apuntes -->
            <section>
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-sm font-semibold uppercase tracking-wide text-stone-500">
                        📝 Apuntes ({{ apuntes.length }})
                    </h3>
                    <button
                        type="button"
                        @click="openUpload('apunte')"
                        class="text-sm font-medium text-amber-600 hover:text-amber-700"
                    >
                        + Agregar apunte
                    </button>
                </div>

                <div v-if="apuntes.length === 0" class="rounded-xl border border-dashed border-stone-300 bg-white/50 p-8 text-center text-sm text-stone-500">
                    Todavía no hay apuntes en esta materia.
                </div>
                <div v-else class="space-y-3">
                    <MaterialCard
                        v-for="m in apuntes"
                        :key="m.id"
                        :material="m"
                        @delete="destroy"
                    />
                </div>
            </section>

            <!-- Apuntes del campus -->
            <section>
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-sm font-semibold uppercase tracking-wide text-stone-500">
                        🏫 Apuntes del campus ({{ campus.length }})
                    </h3>
                    <button
                        type="button"
                        @click="openUpload('campus')"
                        class="text-sm font-medium text-amber-600 hover:text-amber-700"
                    >
                        + Agregar del campus
                    </button>
                </div>

                <div v-if="campus.length === 0" class="rounded-xl border border-dashed border-stone-300 bg-white/50 p-8 text-center text-sm text-stone-500">
                    Todavía no hay apuntes del campus en esta materia.
                </div>
                <div v-else class="space-y-3">
                    <MaterialCard
                        v-for="m in campus"
                        :key="m.id"
                        :material="m"
                        @delete="destroy"
                    />
                </div>
            </section>

            <!-- Exámenes -->
            <section>
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-sm font-semibold uppercase tracking-wide text-stone-500">
                        🎓 Exámenes ({{ examenes.length }})
                    </h3>
                    <button
                        type="button"
                        @click="openUpload('examen')"
                        class="text-sm font-medium text-amber-600 hover:text-amber-700"
                    >
                        + Agregar examen
                    </button>
                </div>

                <div v-if="examenes.length === 0" class="rounded-xl border border-dashed border-stone-300 bg-white/50 p-8 text-center text-sm text-stone-500">
                    Todavía no hay exámenes en esta materia.
                </div>
                <div v-else class="space-y-3">
                    <MaterialCard
                        v-for="m in examenes"
                        :key="m.id"
                        :material="m"
                        @delete="destroy"
                    />
                </div>
            </section>
        </div>

        <!-- Modal de subida -->
        <Modal :show="showModal" @close="closeModal">
            <form @submit.prevent="submit" class="p-6">
                <h2 class="text-lg font-semibold text-ink">Subir material</h2>

                <!-- Tipo -->
                <div class="mt-5">
                    <InputLabel value="Tipo" />
                    <div class="mt-2 grid grid-cols-3 gap-2">
                        <button
                            type="button"
                            @click="form.tipo = 'apunte'"
                            class="rounded-lg border px-3 py-2 text-sm font-semibold transition"
                            :class="form.tipo === 'apunte' ? 'border-amber-500 bg-amber-50 text-amber-700' : 'border-stone-300 text-stone-600 hover:bg-stone-50'"
                        >
                            📝 Apunte
                        </button>
                        <button
                            type="button"
                            @click="form.tipo = 'campus'"
                            class="rounded-lg border px-3 py-2 text-sm font-semibold transition"
                            :class="form.tipo === 'campus' ? 'border-amber-500 bg-amber-50 text-amber-700' : 'border-stone-300 text-stone-600 hover:bg-stone-50'"
                        >
                            🏫 Campus
                        </button>
                        <button
                            type="button"
                            @click="form.tipo = 'examen'"
                            class="rounded-lg border px-3 py-2 text-sm font-semibold transition"
                            :class="form.tipo === 'examen' ? 'border-amber-500 bg-amber-50 text-amber-700' : 'border-stone-300 text-stone-600 hover:bg-stone-50'"
                        >
                            🎓 Examen
                        </button>
                    </div>
                    <InputError class="mt-2" :message="form.errors.tipo" />
                </div>

                <!-- Título -->
                <div class="mt-4">
                    <InputLabel for="titulo" value="Título" />
                    <TextInput
                        id="titulo"
                        v-model="form.titulo"
                        type="text"
                        class="mt-1 block w-full"
                        placeholder="Ej: Resumen unidad 3"
                        required
                    />
                    <InputError class="mt-2" :message="form.errors.titulo" />
                </div>

                <!-- Descripción -->
                <div class="mt-4">
                    <InputLabel for="descripcion" value="Descripción (opcional)" />
                    <textarea
                        id="descripcion"
                        v-model="form.descripcion"
                        rows="2"
                        class="mt-1 block w-full rounded-lg border-stone-300 shadow-sm focus:border-amber-500 focus:ring-amber-500"
                        placeholder="Ej: Le falta la última hoja"
                    ></textarea>
                    <InputError class="mt-2" :message="form.errors.descripcion" />
                </div>

                <!-- Archivo -->
                <div class="mt-4">
                    <InputLabel value="Archivo" />
                    <label
                        class="mt-1 flex cursor-pointer flex-col items-center justify-center rounded-lg border-2 border-dashed border-stone-300 bg-stone-50 px-4 py-6 text-center transition hover:border-amber-400 hover:bg-amber-50/40"
                    >
                        <input
                            ref="fileInput"
                            type="file"
                            class="hidden"
                            accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.ppt,.pptx,.xls,.xlsx,image/*"
                            @change="onFile"
                        />
                        <template v-if="form.file">
                            <span class="text-2xl">📎</span>
                            <span class="mt-1 max-w-full truncate text-sm font-medium text-ink">{{ form.file.name }}</span>
                            <span class="text-xs text-stone-500">{{ formatBytes(form.file.size) }} · tocá para cambiar</span>
                        </template>
                        <template v-else>
                            <span class="text-2xl">⬆️</span>
                            <span class="mt-1 text-sm font-medium text-stone-600">Elegí un archivo</span>
                            <span class="text-xs text-stone-400">PDF, imagen u Office · hasta 10 MB</span>
                        </template>
                    </label>
                    <InputError class="mt-2" :message="form.errors.file" />
                </div>

                <!-- Barra de progreso -->
                <div v-if="form.progress" class="mt-4">
                    <div class="h-2 w-full overflow-hidden rounded-full bg-stone-200">
                        <div
                            class="h-full rounded-full bg-amber-500 transition-all"
                            :style="{ width: `${form.progress.percentage}%` }"
                        ></div>
                    </div>
                    <p class="mt-1 text-center text-xs text-stone-500">Subiendo… {{ form.progress.percentage }}%</p>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <SecondaryButton type="button" @click="closeModal">Cancelar</SecondaryButton>
                    <PrimaryButton :disabled="form.processing" :class="{ 'opacity-75': form.processing }">
                        <Spinner v-if="form.processing" class="-ms-1 me-2" />
                        Subir
                    </PrimaryButton>
                </div>
            </form>
        </Modal>
    </AuthenticatedLayout>
</template>
