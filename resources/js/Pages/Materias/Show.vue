<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import CommentsPanel from '@/Components/CommentsPanel.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import MaterialSection from '@/Components/MaterialSection.vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import Spinner from '@/Components/Spinner.vue';
import TextInput from '@/Components/TextInput.vue';
import { fileIcon, formatBytes } from '@/files';
import { materiaColor, periodoLabel } from '@/materiaColors';
import { useConfirm } from '@/useConfirm';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const { confirm } = useConfirm();

const props = defineProps({
    materia: { type: Object, required: true },
    apuntes: { type: Array, default: () => [] },
    campus: { type: Array, default: () => [] },
    examenes: { type: Array, default: () => [] },
    subApuntes: { type: Array, default: () => [] },
    subCampus: { type: Array, default: () => [] },
    subExamenes: { type: Array, default: () => [] },
    canExamenes: { type: Boolean, default: true },
});

const isAdmin = computed(() => usePage().props.auth.user.role === 'admin');

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

// Orden dentro de la materia (lo aplica cada MaterialSection)
const sortBy = ref('recientes');

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
    subcarpeta_id: null,
});

// Carpetas disponibles para el tipo elegido en el modal de subida
const uploadFolders = computed(() => {
    const t = uploadTipo.value;
    return t === 'apunte' ? props.subApuntes : t === 'campus' ? props.subCampus : props.subExamenes;
});
const setUploadTipo = (t) => {
    uploadTipo.value = t;
    form.subcarpeta_id = null; // la carpeta depende del tipo
};

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
    form.subcarpeta_id = null;
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

// ── Subcarpetas ──────────────────────────────────────────────
const TIPO_LABEL = { apunte: 'Apuntes', campus: 'Apuntes del campus', examen: 'Exámenes' };

// Crear carpeta (solo admin)
const showFolderModal = ref(false);
const folderForm = useForm({ tipo: 'apunte', nombre: '', material_ids: [] });
const folderLoose = computed(() => {
    const src =
        folderForm.tipo === 'apunte' ? props.apuntes : folderForm.tipo === 'campus' ? props.campus : props.examenes;
    return src.filter((m) => !m.subcarpeta_id);
});

const openNewFolder = (tipo) => {
    folderForm.clearErrors();
    folderForm.tipo = tipo;
    folderForm.nombre = '';
    folderForm.material_ids = [];
    showFolderModal.value = true;
};
const closeFolderModal = () => {
    showFolderModal.value = false;
};
const submitFolder = () => {
    folderForm.post(route('subcarpetas.store', props.materia.id), {
        preserveScroll: true,
        onSuccess: () => {
            closeFolderModal();
            folderForm.reset();
        },
    });
};

// Renombrar carpeta (solo admin)
const showRenameModal = ref(false);
const renameForm = useForm({ id: null, nombre: '' });
const openRename = (folder) => {
    renameForm.clearErrors();
    renameForm.id = folder.id;
    renameForm.nombre = folder.nombre;
    showRenameModal.value = true;
};
const closeRename = () => {
    showRenameModal.value = false;
};
const submitRename = () => {
    renameForm.patch(route('subcarpetas.update', renameForm.id), {
        preserveScroll: true,
        onSuccess: () => closeRename(),
    });
};

// Borrar carpeta (solo admin) — los archivos vuelven a "sueltos"
const deleteFolder = async (folder) => {
    const ok = await confirm({
        title: 'Borrar carpeta',
        message: `¿Borrar la carpeta "${folder.nombre}"? Los archivos que tenga adentro NO se borran: vuelven a "Otros archivos" en la sección.`,
        confirmText: 'Borrar carpeta',
        danger: true,
    });
    if (ok) router.delete(route('subcarpetas.destroy', folder.id), { preserveScroll: true });
};

// Mover material(es) a una carpeta / sacarlos (dueño o admin)
const showMoveModal = ref(false);
const moveCtx = ref(null); // { multi, ids, tipo, currentId, label }
const moveForm = useForm({ material_ids: [], subcarpeta_id: null });

const foldersByTipo = (t) =>
    t === 'apunte' ? props.subApuntes : t === 'campus' ? props.subCampus : props.subExamenes;
const moveFolders = computed(() => (moveCtx.value ? foldersByTipo(moveCtx.value.tipo) : []));
const moveChanged = computed(() => {
    if (!moveCtx.value) return false;
    return moveCtx.value.multi
        ? moveCtx.value.ids.length > 0
        : moveForm.subcarpeta_id !== moveCtx.value.currentId;
});

const openMove = (material) => {
    moveForm.clearErrors();
    moveCtx.value = {
        multi: false,
        ids: [material.id],
        tipo: material.tipo,
        currentId: material.subcarpeta_id ?? null,
        label: material.titulo,
    };
    moveForm.subcarpeta_id = material.subcarpeta_id ?? null; // arranca en la ubicación actual
    showMoveModal.value = true;
};
const openMoveMany = ({ tipo, ids }) => {
    moveForm.clearErrors();
    moveCtx.value = {
        multi: true,
        ids,
        tipo,
        currentId: null,
        label: `${ids.length} archivo${ids.length === 1 ? '' : 's'} seleccionado${ids.length === 1 ? '' : 's'}`,
    };
    moveForm.subcarpeta_id = null;
    showMoveModal.value = true;
};
const closeMove = () => {
    showMoveModal.value = false;
    moveCtx.value = null;
};
const submitMove = () => {
    if (!moveCtx.value || !moveChanged.value) return;
    moveForm.material_ids = moveCtx.value.ids;
    moveForm.patch(route('materiales.move-batch', props.materia.id), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => closeMove(),
    });
};

// Reordenar carpetas (admin)
const reorderFolders = ({ tipo, ids }) => {
    router.patch(
        route('subcarpetas.reorder', props.materia.id),
        { tipo, ids },
        { preserveScroll: true, preserveState: true },
    );
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

            <MaterialSection
                titulo="📝 Apuntes"
                tipo="apunte"
                add-label="+ Agregar apunte"
                :materials="apuntes"
                :subcarpetas="subApuntes"
                :sort-by="sortBy"
                :is-admin="isAdmin"
                @upload="openUpload"
                @new-folder="openNewFolder"
                @rename-folder="openRename"
                @delete-folder="deleteFolder"
                @delete="destroy"
                @edit="openEdit"
                @move="openMove"
                @move-many="openMoveMany"
                @reorder-folders="reorderFolders"
                @comments="openMaterialId = $event.id"
                @vote="vote"
                @favorite="favorite"
            />

            <MaterialSection
                titulo="🏫 Apuntes del campus"
                tipo="campus"
                add-label="+ Agregar del campus"
                :materials="campus"
                :subcarpetas="subCampus"
                :sort-by="sortBy"
                :is-admin="isAdmin"
                @upload="openUpload"
                @new-folder="openNewFolder"
                @rename-folder="openRename"
                @delete-folder="deleteFolder"
                @delete="destroy"
                @edit="openEdit"
                @move="openMove"
                @move-many="openMoveMany"
                @reorder-folders="reorderFolders"
                @comments="openMaterialId = $event.id"
                @vote="vote"
                @favorite="favorite"
            />

            <MaterialSection
                v-if="canExamenes"
                titulo="🎓 Exámenes"
                tipo="examen"
                add-label="+ Agregar examen"
                :materials="examenes"
                :subcarpetas="subExamenes"
                :sort-by="sortBy"
                :is-admin="isAdmin"
                @upload="openUpload"
                @new-folder="openNewFolder"
                @rename-folder="openRename"
                @delete-folder="deleteFolder"
                @delete="destroy"
                @edit="openEdit"
                @move="openMove"
                @move-many="openMoveMany"
                @reorder-folders="reorderFolders"
                @comments="openMaterialId = $event.id"
                @vote="vote"
                @favorite="favorite"
            />
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
                    <div class="mt-2 grid gap-2" :class="canExamenes ? 'grid-cols-3' : 'grid-cols-2'">
                        <button
                            type="button"
                            @click="setUploadTipo('apunte')"
                            class="rounded-lg border px-3 py-2 text-sm font-semibold transition"
                            :class="uploadTipo === 'apunte' ? 'border-brand-500 bg-brand-50 text-brand-700' : 'border-stone-300 text-stone-600 hover:bg-stone-50'"
                        >
                            📝 Apunte
                        </button>
                        <button
                            type="button"
                            @click="setUploadTipo('campus')"
                            class="rounded-lg border px-3 py-2 text-sm font-semibold transition"
                            :class="uploadTipo === 'campus' ? 'border-brand-500 bg-brand-50 text-brand-700' : 'border-stone-300 text-stone-600 hover:bg-stone-50'"
                        >
                            🏫 Campus
                        </button>
                        <button
                            v-if="canExamenes"
                            type="button"
                            @click="setUploadTipo('examen')"
                            class="rounded-lg border px-3 py-2 text-sm font-semibold transition"
                            :class="uploadTipo === 'examen' ? 'border-brand-500 bg-brand-50 text-brand-700' : 'border-stone-300 text-stone-600 hover:bg-stone-50'"
                        >
                            🎓 Examen
                        </button>
                    </div>
                    <InputError class="mt-2" :message="form.errors.tipo" />
                </div>

                <!-- Carpeta destino (opcional) -->
                <div v-if="uploadFolders.length" class="mt-4">
                    <InputLabel for="upload-carpeta" value="Carpeta (opcional)" />
                    <select
                        id="upload-carpeta"
                        v-model="form.subcarpeta_id"
                        class="mt-1 block w-full rounded-lg border-stone-300 shadow-sm focus:border-brand-500 focus:ring-brand-500"
                    >
                        <option :value="null">Sin carpeta (Otros archivos)</option>
                        <option v-for="f in uploadFolders" :key="f.id" :value="f.id">📁 {{ f.nombre }}</option>
                    </select>
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

        <!-- Modal nueva carpeta (solo admin) -->
        <Modal :show="showFolderModal" @close="closeFolderModal">
            <form @submit.prevent="submitFolder" class="p-6">
                <h2 class="text-lg font-semibold text-ink">Nueva carpeta</h2>
                <p class="mt-1 text-sm text-stone-500">En {{ TIPO_LABEL[folderForm.tipo] }}</p>

                <div class="mt-5">
                    <InputLabel for="folder-nombre" value="Nombre" />
                    <TextInput
                        id="folder-nombre"
                        v-model="folderForm.nombre"
                        type="text"
                        class="mt-1 block w-full"
                        placeholder="Ej: Unidad 1"
                        required
                        autofocus
                    />
                    <InputError class="mt-2" :message="folderForm.errors.nombre" />
                </div>

                <div class="mt-4">
                    <InputLabel value="Archivos para poner en la carpeta (opcional)" />
                    <p class="mt-0.5 text-xs text-stone-400">
                        Solo se listan los que están sueltos en esta sección. Podés dejarla vacía.
                    </p>
                    <div
                        v-if="folderLoose.length"
                        class="mt-2 max-h-64 space-y-1 overflow-y-auto rounded-lg border border-stone-200 p-2"
                    >
                        <label
                            v-for="m in folderLoose"
                            :key="m.id"
                            class="flex cursor-pointer items-center gap-2 rounded-lg px-2 py-1.5 hover:bg-stone-50"
                        >
                            <input
                                type="checkbox"
                                :value="m.id"
                                v-model="folderForm.material_ids"
                                class="rounded border-stone-300 text-brand-600 focus:ring-brand-500"
                            />
                            <span class="min-w-0 truncate text-sm text-ink">{{ m.titulo }}</span>
                        </label>
                    </div>
                    <p v-else class="mt-2 text-sm text-stone-400">
                        No hay archivos sueltos para agregar. La carpeta se crea vacía.
                    </p>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <SecondaryButton type="button" @click="closeFolderModal">Cancelar</SecondaryButton>
                    <PrimaryButton :disabled="folderForm.processing" :class="{ 'opacity-75': folderForm.processing }">
                        <Spinner v-if="folderForm.processing" class="-ms-1 me-2" />
                        Crear carpeta
                    </PrimaryButton>
                </div>
            </form>
        </Modal>

        <!-- Modal renombrar carpeta (solo admin) -->
        <Modal :show="showRenameModal" @close="closeRename">
            <form @submit.prevent="submitRename" class="p-6">
                <h2 class="text-lg font-semibold text-ink">Renombrar carpeta</h2>

                <div class="mt-5">
                    <InputLabel for="rename-nombre" value="Nombre" />
                    <TextInput
                        id="rename-nombre"
                        v-model="renameForm.nombre"
                        type="text"
                        class="mt-1 block w-full"
                        required
                        autofocus
                    />
                    <InputError class="mt-2" :message="renameForm.errors.nombre" />
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <SecondaryButton type="button" @click="closeRename">Cancelar</SecondaryButton>
                    <PrimaryButton :disabled="renameForm.processing" :class="{ 'opacity-75': renameForm.processing }">
                        <Spinner v-if="renameForm.processing" class="-ms-1 me-2" />
                        Guardar
                    </PrimaryButton>
                </div>
            </form>
        </Modal>

        <!-- Modal mover a carpeta (uno o varios) -->
        <Modal :show="showMoveModal" @close="closeMove">
            <div v-if="moveCtx" class="p-6">
                <h2 class="text-lg font-semibold text-ink">Mover a carpeta</h2>
                <p class="mt-1 truncate text-sm text-stone-500">{{ moveCtx.label }}</p>

                <div class="mt-5 space-y-1">
                    <button
                        type="button"
                        @click="moveForm.subcarpeta_id = null"
                        class="flex w-full items-center justify-between gap-2 rounded-lg border px-3 py-2.5 text-left text-sm transition"
                        :class="moveForm.subcarpeta_id === null ? 'border-brand-500 bg-brand-50 text-brand-700' : 'border-stone-200 text-stone-600 hover:bg-stone-50'"
                    >
                        <span>📄 Otros archivos (sin carpeta)</span>
                        <span class="shrink-0 text-xs">
                            <span v-if="moveForm.subcarpeta_id === null" class="font-semibold text-brand-600">✓</span>
                            <span v-else-if="!moveCtx.multi && moveCtx.currentId === null" class="text-stone-400">Actual</span>
                        </span>
                    </button>

                    <button
                        v-for="f in moveFolders"
                        :key="f.id"
                        type="button"
                        @click="moveForm.subcarpeta_id = f.id"
                        class="flex w-full items-center justify-between gap-2 rounded-lg border px-3 py-2.5 text-left text-sm transition"
                        :class="moveForm.subcarpeta_id === f.id ? 'border-brand-500 bg-brand-50 text-brand-700' : 'border-stone-200 text-stone-600 hover:bg-stone-50'"
                    >
                        <span class="min-w-0 truncate">📁 {{ f.nombre }}</span>
                        <span class="shrink-0 text-xs">
                            <span v-if="moveForm.subcarpeta_id === f.id" class="font-semibold text-brand-600">✓</span>
                            <span v-else-if="!moveCtx.multi && moveCtx.currentId === f.id" class="text-stone-400">Actual</span>
                        </span>
                    </button>
                </div>

                <p v-if="moveFolders.length === 0" class="mt-3 text-sm text-stone-400">
                    Todavía no hay carpetas en esta sección. La admin puede crear una con "📁 Nueva carpeta".
                </p>

                <div class="mt-6 flex justify-end gap-3">
                    <SecondaryButton type="button" @click="closeMove">Cancelar</SecondaryButton>
                    <PrimaryButton
                        type="button"
                        @click="submitMove"
                        :disabled="!moveChanged || moveForm.processing"
                        :class="{ 'opacity-75': !moveChanged || moveForm.processing }"
                    >
                        <Spinner v-if="moveForm.processing" class="-ms-1 me-2" />
                        Mover
                    </PrimaryButton>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
