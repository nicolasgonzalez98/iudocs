<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import CommentsPanel from '@/Components/CommentsPanel.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import MaterialCard from '@/Components/MaterialCard.vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import Spinner from '@/Components/Spinner.vue';
import TextInput from '@/Components/TextInput.vue';
import { fileIcon, formatBytes } from '@/files';
import { materiaColor, periodoLabel } from '@/materiaColors';
import { useConfirm } from '@/useConfirm';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const { confirm } = useConfirm();

const props = defineProps({
    materia: { type: Object, required: true },
    apuntes: { type: Array, default: () => [] },
    campus: { type: Array, default: () => [] },
    examenes: { type: Array, default: () => [] },
});

const showModal = ref(false);
const uploadTipo = ref('apunte');
const dragging = ref(false);
const uploadError = ref('');

// Archivos elegidos para subir (multi): [{ file, titulo, descripcion, preview }]
const items = ref([]);

const MAX_BYTES = 100 * 1024 * 1024; // 100 MB por archivo
const totalBytes = computed(() => items.value.reduce((sum, it) => sum + it.file.size, 0));

// Panel de comentarios: guardamos el id y buscamos el material actualizado en props
const openMaterialId = ref(null);
const openMaterial = computed(
    () =>
        [...props.apuntes, ...props.campus, ...props.examenes].find(
            (m) => m.id === openMaterialId.value,
        ) ?? null,
);

// Orden dentro de la materia (client-side, sobre lo ya cargado)
const sortBy = ref('recientes');
const SORTERS = {
    recientes: (a, b) => b.id - a.id,
    utiles: (a, b) => b.helpful_count - a.helpful_count || b.id - a.id,
    descargados: (a, b) => b.downloads - a.downloads || b.id - a.id,
};
const sortList = (list) => [...list].sort(SORTERS[sortBy.value]);
const sortedApuntes = computed(() => sortList(props.apuntes));
const sortedCampus = computed(() => sortList(props.campus));
const sortedExamenes = computed(() => sortList(props.examenes));

const vote = (material) => {
    router.post(route('materiales.vote', material.id), {}, { preserveScroll: true });
};

const favorite = (material) => {
    router.post(route('materiales.favorite', material.id), {}, { preserveScroll: true });
};

const form = useForm({
    tipo: 'apunte',
    files: [],
    titulos: [],
    descripciones: [],
});

// Título "lindo" a partir del nombre de archivo: unidad-3.pdf -> "Unidad 3"
const titleFromName = (name) => {
    const base = name.replace(/\.[^/.]+$/, '');
    const clean = base.replace(/[_-]+/g, ' ').replace(/\s+/g, ' ').trim();
    return clean.charAt(0).toUpperCase() + clean.slice(1);
};

const addFiles = (fileList) => {
    const rejected = [];
    for (const file of Array.from(fileList)) {
        if (file.size > MAX_BYTES) {
            rejected.push(`${file.name} (${formatBytes(file.size)})`);
            continue;
        }
        items.value.push({
            file,
            titulo: titleFromName(file.name),
            descripcion: '',
            preview: file.type.startsWith('image/') ? URL.createObjectURL(file) : null,
        });
    }
    uploadError.value = rejected.length
        ? `${rejected.length === 1 ? 'Este archivo supera' : 'Estos archivos superan'} el máximo de 100 MB y no se agregaron: ${rejected.join(', ')}`
        : '';
};

const clearItems = () => {
    items.value.forEach((it) => it.preview && URL.revokeObjectURL(it.preview));
    items.value = [];
};

const removeItem = (i) => {
    const [removed] = items.value.splice(i, 1);
    if (removed?.preview) URL.revokeObjectURL(removed.preview);
};

const onFile = (e) => {
    addFiles(e.target.files);
    e.target.value = ''; // permite volver a elegir el mismo archivo
};

const onDrop = (e) => {
    dragging.value = false;
    if (e.dataTransfer?.files?.length) addFiles(e.dataTransfer.files);
};

const openUpload = (tipo = 'apunte') => {
    form.clearErrors();
    uploadError.value = '';
    uploadTipo.value = tipo;
    clearItems();
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
};

const submit = () => {
    form.tipo = uploadTipo.value;
    form.files = items.value.map((it) => it.file);
    form.titulos = items.value.map((it) => it.titulo);
    form.descripciones = items.value.map((it) => it.descripcion);

    uploadError.value = '';
    form.post(route('materiales.store', props.materia.id), {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            closeModal();
            clearItems();
            form.reset();
        },
        onError: (errors) => {
            // Si el server rechazó sin errores de campo (típico: superó el límite
            // del servidor / post_max_size), mostramos un mensaje claro igual.
            if (!errors || Object.keys(errors).length === 0) {
                uploadError.value =
                    'No se pudieron subir los archivos. Puede que el tamaño total supere el límite del servidor. Probá con menos archivos o más livianos.';
            }
        },
    });
};

const destroy = async (material) => {
    const ok = await confirm({
        title: 'Borrar material',
        message: `¿Borrar "${material.titulo}"? Esta acción no se puede deshacer.`,
        confirmText: 'Borrar',
        danger: true,
    });
    if (ok) router.delete(route('materiales.destroy', material.id), { preserveScroll: true });
};

// Edición de título / descripción
const showEditModal = ref(false);
const editForm = useForm({
    id: null,
    titulo: '',
    descripcion: '',
});

const openEdit = (material) => {
    editForm.clearErrors();
    editForm.id = material.id;
    editForm.titulo = material.titulo;
    editForm.descripcion = material.descripcion ?? '';
    showEditModal.value = true;
};

const closeEdit = () => {
    showEditModal.value = false;
};

const submitEdit = () => {
    editForm.patch(route('materiales.update', editForm.id), {
        preserveScroll: true,
        onSuccess: () => closeEdit(),
    });
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
                        <p v-if="materia.anio || materia.cuatrimestre || materia.catedra" class="text-xs text-stone-500">
                            {{ [periodoLabel(materia.anio, materia.cuatrimestre), materia.catedra].filter(Boolean).join(' · ') }}
                        </p>
                    </div>
                </div>

                <PrimaryButton class="shrink-0" @click="openUpload('apunte')">
                    Subir material
                </PrimaryButton>
            </div>
        </template>

        <div class="mx-auto max-w-4xl space-y-10 px-4 py-8 sm:px-6 lg:px-8">
            <!-- Orden -->
            <div class="flex justify-end">
                <label class="inline-flex items-center gap-2 text-sm text-stone-500">
                    Ordenar por
                    <select
                        v-model="sortBy"
                        class="rounded-lg border-stone-300 py-1.5 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500"
                    >
                        <option value="recientes">Más recientes</option>
                        <option value="utiles">Más útiles</option>
                        <option value="descargados">Más descargados</option>
                    </select>
                </label>
            </div>

            <!-- Apuntes -->
            <section>
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-sm font-semibold uppercase tracking-wide text-stone-500">
                        📝 Apuntes ({{ apuntes.length }})
                    </h3>
                    <button
                        type="button"
                        @click="openUpload('apunte')"
                        class="text-sm font-medium text-brand-600 hover:text-brand-700"
                    >
                        + Agregar apunte
                    </button>
                </div>

                <div v-if="apuntes.length === 0" class="rounded-xl border border-dashed border-stone-300 bg-white/50 p-8 text-center text-sm text-stone-500">
                    Todavía no hay apuntes en esta materia.
                </div>
                <div v-else class="space-y-3">
                    <MaterialCard
                        v-for="m in sortedApuntes"
                        :key="m.id"
                        :material="m"
                        @delete="destroy"
                        @edit="openEdit"
                        @comments="openMaterialId = $event.id"
                        @vote="vote"
                        @favorite="favorite"
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
                        class="text-sm font-medium text-brand-600 hover:text-brand-700"
                    >
                        + Agregar del campus
                    </button>
                </div>

                <div v-if="campus.length === 0" class="rounded-xl border border-dashed border-stone-300 bg-white/50 p-8 text-center text-sm text-stone-500">
                    Todavía no hay apuntes del campus en esta materia.
                </div>
                <div v-else class="space-y-3">
                    <MaterialCard
                        v-for="m in sortedCampus"
                        :key="m.id"
                        :material="m"
                        @delete="destroy"
                        @edit="openEdit"
                        @comments="openMaterialId = $event.id"
                        @vote="vote"
                        @favorite="favorite"
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
                        class="text-sm font-medium text-brand-600 hover:text-brand-700"
                    >
                        + Agregar examen
                    </button>
                </div>

                <div v-if="examenes.length === 0" class="rounded-xl border border-dashed border-stone-300 bg-white/50 p-8 text-center text-sm text-stone-500">
                    Todavía no hay exámenes en esta materia.
                </div>
                <div v-else class="space-y-3">
                    <MaterialCard
                        v-for="m in sortedExamenes"
                        :key="m.id"
                        :material="m"
                        @delete="destroy"
                        @edit="openEdit"
                        @comments="openMaterialId = $event.id"
                        @vote="vote"
                        @favorite="favorite"
                    />
                </div>
            </section>
        </div>

        <!-- Panel de comentarios -->
        <CommentsPanel
            v-if="openMaterial"
            :material="openMaterial"
            @close="openMaterialId = null"
        />

        <!-- Modal de subida (multi-archivo) -->
        <Modal :show="showModal" @close="closeModal">
            <div class="p-6">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="text-lg font-semibold text-ink">Subir material</h2>
                    <span
                        v-if="items.length"
                        class="shrink-0 rounded-full bg-brand-50 px-2.5 py-0.5 text-xs font-semibold text-brand-700"
                    >
                        {{ items.length }} {{ items.length === 1 ? 'archivo' : 'archivos' }} · {{ formatBytes(totalBytes) }}
                    </span>
                </div>

                <!-- Tipo (todo el lote) -->
                <div class="mt-5">
                    <InputLabel value="Tipo" />
                    <div class="mt-2 grid grid-cols-3 gap-2">
                        <button
                            type="button"
                            @click="uploadTipo = 'apunte'"
                            class="rounded-lg border px-3 py-2 text-sm font-semibold transition"
                            :class="uploadTipo === 'apunte' ? 'border-brand-500 bg-brand-50 text-brand-700' : 'border-stone-300 text-stone-600 hover:bg-stone-50'"
                        >
                            📝 Apunte
                        </button>
                        <button
                            type="button"
                            @click="uploadTipo = 'campus'"
                            class="rounded-lg border px-3 py-2 text-sm font-semibold transition"
                            :class="uploadTipo === 'campus' ? 'border-brand-500 bg-brand-50 text-brand-700' : 'border-stone-300 text-stone-600 hover:bg-stone-50'"
                        >
                            🏫 Campus
                        </button>
                        <button
                            type="button"
                            @click="uploadTipo = 'examen'"
                            class="rounded-lg border px-3 py-2 text-sm font-semibold transition"
                            :class="uploadTipo === 'examen' ? 'border-brand-500 bg-brand-50 text-brand-700' : 'border-stone-300 text-stone-600 hover:bg-stone-50'"
                        >
                            🎓 Examen
                        </button>
                    </div>
                    <InputError class="mt-2" :message="form.errors.tipo" />
                </div>

                <!-- Dropzone -->
                <label
                    class="mt-4 flex cursor-pointer flex-col items-center justify-center rounded-lg border-2 border-dashed px-4 py-6 text-center transition"
                    :class="dragging ? 'border-brand-500 bg-brand-50' : 'border-stone-300 bg-stone-50 hover:border-brand-400 hover:bg-brand-50/40'"
                    @dragover.prevent="dragging = true"
                    @dragenter.prevent="dragging = true"
                    @dragleave.prevent="dragging = false"
                    @drop.prevent="onDrop"
                >
                    <input
                        type="file"
                        multiple
                        class="hidden"
                        accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.ppt,.pptx,.xls,.xlsx,image/*"
                        @change="onFile"
                    />
                    <span class="text-2xl">⬆️</span>
                    <span class="mt-1 text-sm font-medium text-stone-600">Arrastrá archivos acá o tocá para elegir</span>
                    <span class="text-xs text-stone-400">PDF, imagen u Office · hasta 100 MB c/u</span>
                </label>
                <InputError class="mt-2" :message="form.errors.files" />

                <!-- Aviso de error (tamaño / rechazo del servidor) -->
                <div
                    v-if="uploadError"
                    class="mt-3 flex items-start gap-2 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700"
                >
                    <span class="shrink-0">⚠️</span>
                    <span>{{ uploadError }}</span>
                </div>

                <!-- Lista de archivos elegidos -->
                <div v-if="items.length" class="mt-4 max-h-80 space-y-3 overflow-y-auto pr-1">
                    <div
                        v-for="(it, i) in items"
                        :key="i"
                        class="rounded-xl border border-stone-200 bg-white p-3"
                    >
                        <div class="flex items-start gap-3">
                            <img
                                v-if="it.preview"
                                :src="it.preview"
                                alt=""
                                class="h-11 w-11 shrink-0 rounded-lg object-cover"
                            />
                            <div
                                v-else
                                class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-stone-100 text-xl"
                            >
                                {{ fileIcon(it.file.type, it.file.name) }}
                            </div>

                            <div class="min-w-0 flex-1">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="min-w-0 truncate text-xs text-stone-400">
                                        {{ it.file.name }} · {{ formatBytes(it.file.size) }}
                                    </span>
                                    <button
                                        type="button"
                                        @click="removeItem(i)"
                                        aria-label="Quitar archivo"
                                        class="shrink-0 rounded-lg px-1.5 py-0.5 text-stone-400 transition hover:bg-red-50 hover:text-red-600"
                                    >
                                        ✕
                                    </button>
                                </div>
                                <TextInput
                                    v-model="it.titulo"
                                    type="text"
                                    class="mt-1 block w-full"
                                    placeholder="Título"
                                />
                                <InputError class="mt-1" :message="form.errors['titulos.' + i]" />
                                <InputError class="mt-1" :message="form.errors['files.' + i]" />
                                <input
                                    v-model="it.descripcion"
                                    type="text"
                                    class="mt-2 block w-full rounded-lg border-stone-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500"
                                    placeholder="¿De qué trata? (opcional)"
                                />
                                <InputError class="mt-1" :message="form.errors['descripciones.' + i]" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Barra de progreso -->
                <div v-if="form.progress" class="mt-4">
                    <div class="h-2 w-full overflow-hidden rounded-full bg-stone-200">
                        <div
                            class="h-full rounded-full bg-brand-500 transition-all"
                            :style="{ width: `${form.progress.percentage}%` }"
                        ></div>
                    </div>
                    <p class="mt-1 text-center text-xs text-stone-500">Subiendo… {{ form.progress.percentage }}%</p>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <SecondaryButton type="button" @click="closeModal">Cancelar</SecondaryButton>
                    <PrimaryButton
                        type="button"
                        @click="submit"
                        :disabled="form.processing || items.length === 0"
                        :class="{ 'opacity-75': form.processing || items.length === 0 }"
                    >
                        <Spinner v-if="form.processing" class="-ms-1 me-2" />
                        {{ items.length > 1 ? `Subir ${items.length}` : 'Subir' }}
                    </PrimaryButton>
                </div>
            </div>
        </Modal>

        <!-- Modal de edición (título / descripción) -->
        <Modal :show="showEditModal" @close="closeEdit">
            <form @submit.prevent="submitEdit" class="p-6">
                <h2 class="text-lg font-semibold text-ink">Editar material</h2>

                <div class="mt-5">
                    <InputLabel for="edit-titulo" value="Título" />
                    <TextInput
                        id="edit-titulo"
                        v-model="editForm.titulo"
                        type="text"
                        class="mt-1 block w-full"
                        required
                        autofocus
                    />
                    <InputError class="mt-2" :message="editForm.errors.titulo" />
                </div>

                <div class="mt-4">
                    <InputLabel for="edit-descripcion" value="Descripción (opcional)" />
                    <textarea
                        id="edit-descripcion"
                        v-model="editForm.descripcion"
                        rows="2"
                        class="mt-1 block w-full rounded-lg border-stone-300 shadow-sm focus:border-brand-500 focus:ring-brand-500"
                        placeholder="Ej: Le falta la última hoja"
                    ></textarea>
                    <InputError class="mt-2" :message="editForm.errors.descripcion" />
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <SecondaryButton type="button" @click="closeEdit">Cancelar</SecondaryButton>
                    <PrimaryButton :disabled="editForm.processing" :class="{ 'opacity-75': editForm.processing }">
                        <Spinner v-if="editForm.processing" class="-ms-1 me-2" />
                        Guardar cambios
                    </PrimaryButton>
                </div>
            </form>
        </Modal>
    </AuthenticatedLayout>
</template>
