<script setup>
import { nextTick, reactive, ref } from 'vue';

const props = defineProps({
    materiaId: { type: Number, required: true },
    materiaNombre: { type: String, default: '' },
});

const open = ref(false);
const question = ref('');
const streaming = ref(false);
const messages = ref([]); // { role: 'user'|'assistant', text, sources?, confidence?, error?, streaming? }
const scrollEl = ref(null);

const EJEMPLOS = [
    '¿Cuáles son los temas principales?',
    'Explicame el concepto más importante',
    'Hacé un resumen de la unidad 1',
];

function xsrfToken() {
    const m = document.cookie.match(/XSRF-TOKEN=([^;]+)/);
    return m ? decodeURIComponent(m[1]) : '';
}

function scrollDown() {
    nextTick(() => {
        if (scrollEl.value) scrollEl.value.scrollTop = scrollEl.value.scrollHeight;
    });
}

function handleBlock(block, assistant) {
    let event = 'message';
    let data = '';
    for (const line of block.split('\n')) {
        if (line.startsWith('event:')) event = line.slice(6).trim();
        else if (line.startsWith('data:')) data += line.slice(5).trim();
    }
    if (!data) return;

    let payload;
    try {
        payload = JSON.parse(data);
    } catch {
        return;
    }

    if (event === 'sources') {
        assistant.sources = payload.chunks ?? [];
        assistant.confidence = payload.confidence ?? null;
    } else if (event === 'token') {
        assistant.text += payload.text ?? '';
    } else if (event === 'error') {
        assistant.error = payload.message ?? 'Ocurrió un error.';
    }
}

async function send(text) {
    const q = (text ?? question.value).trim();
    if (!q || streaming.value) return;

    question.value = '';
    messages.value.push({ role: 'user', text: q });
    const assistant = reactive({
        role: 'assistant',
        text: '',
        sources: [],
        confidence: null,
        error: null,
        streaming: true,
    });
    messages.value.push(assistant);
    streaming.value = true;
    scrollDown();

    try {
        const res = await fetch(route('materias.chat', props.materiaId), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'text/event-stream',
                'X-Requested-With': 'XMLHttpRequest',
                'X-XSRF-TOKEN': xsrfToken(),
            },
            credentials: 'same-origin',
            body: JSON.stringify({ question: q }),
        });

        if (!res.ok || !res.body) {
            assistant.error =
                res.status === 403
                    ? 'El asistente no está disponible para tu usuario.'
                    : `No se pudo consultar el asistente (error ${res.status}).`;
            return;
        }

        const reader = res.body.getReader();
        const decoder = new TextDecoder();
        let buffer = '';
        for (;;) {
            const { value, done } = await reader.read();
            if (done) break;
            buffer += decoder.decode(value, { stream: true });
            let idx;
            while ((idx = buffer.indexOf('\n\n')) !== -1) {
                handleBlock(buffer.slice(0, idx), assistant);
                buffer = buffer.slice(idx + 2);
                scrollDown();
            }
        }
    } catch {
        assistant.error = 'No se pudo conectar con el asistente.';
    } finally {
        assistant.streaming = false;
        streaming.value = false;
        scrollDown();
    }
}
</script>

<template>
    <!-- Botón flotante -->
    <button
        v-if="!open"
        type="button"
        @click="open = true"
        class="fixed bottom-6 right-6 z-30 inline-flex items-center gap-2 rounded-full bg-brand-500 px-4 py-3 text-sm font-semibold text-white shadow-lg shadow-brand-500/30 transition hover:bg-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 focus:ring-offset-cream"
    >
        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M12 3l1.9 4.6L18.5 9.5 13.9 11.4 12 16l-1.9-4.6L5.5 9.5l4.6-1.9L12 3z" />
            <path d="M19 14l.8 2 2 .8-2 .8-.8 2-.8-2-2-.8 2-.8.8-2z" />
        </svg>
        <span class="hidden sm:inline">Preguntá a los apuntes</span>
    </button>

    <!-- Panel -->
    <Transition
        enter-active-class="transition ease-out duration-200"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition ease-in duration-150"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
    >
        <div v-if="open" class="fixed inset-0 z-40 bg-ink/30" @click="open = false"></div>
    </Transition>

    <Transition
        enter-active-class="transition ease-out duration-300"
        enter-from-class="translate-x-full"
        enter-to-class="translate-x-0"
        leave-active-class="transition ease-in duration-200"
        leave-from-class="translate-x-0"
        leave-to-class="translate-x-full"
    >
        <aside
            v-if="open"
            class="fixed inset-y-0 right-0 z-50 flex w-full flex-col bg-cream shadow-2xl sm:max-w-md"
        >
            <!-- Header -->
            <header class="flex items-start justify-between gap-3 border-b border-stone-200 bg-white px-5 py-4">
                <div class="min-w-0">
                    <h2 class="flex items-center gap-2 text-base font-semibold text-ink">
                        <span class="text-brand-500">✦</span> Asistente
                    </h2>
                    <p class="mt-0.5 truncate text-xs text-stone-500">
                        Responde con los apuntes de {{ materiaNombre || 'la materia' }}, con citas.
                    </p>
                </div>
                <button
                    type="button"
                    @click="open = false"
                    aria-label="Cerrar"
                    class="shrink-0 rounded-lg p-1.5 text-stone-400 transition hover:bg-stone-100 hover:text-stone-700"
                >
                    ✕
                </button>
            </header>

            <!-- Mensajes -->
            <div ref="scrollEl" class="flex-1 space-y-4 overflow-y-auto px-5 py-5">
                <!-- Estado vacío -->
                <div v-if="messages.length === 0" class="pt-6 text-center">
                    <div class="text-3xl">✦</div>
                    <p class="mt-3 text-sm text-stone-600">
                        Preguntá lo que quieras sobre los apuntes de esta materia.
                    </p>
                    <p class="mt-1 text-xs text-stone-400">
                        Las respuestas salen del material subido e incluyen las fuentes.
                    </p>
                    <div class="mt-5 flex flex-col gap-2">
                        <button
                            v-for="ej in EJEMPLOS"
                            :key="ej"
                            type="button"
                            @click="send(ej)"
                            class="rounded-lg border border-stone-200 bg-white px-3 py-2 text-left text-sm text-stone-600 transition hover:border-brand-300 hover:bg-brand-50/50"
                        >
                            {{ ej }}
                        </button>
                    </div>
                </div>

                <!-- Turnos -->
                <template v-for="(m, i) in messages" :key="i">
                    <!-- Usuario -->
                    <div v-if="m.role === 'user'" class="flex justify-end">
                        <div class="max-w-[85%] rounded-2xl rounded-br-sm bg-brand-500 px-4 py-2.5 text-sm text-white">
                            {{ m.text }}
                        </div>
                    </div>

                    <!-- Asistente -->
                    <div v-else class="flex justify-start">
                        <div class="max-w-[90%] space-y-2">
                            <div
                                class="rounded-2xl rounded-bl-sm border border-stone-200 bg-white px-4 py-2.5 text-sm text-ink"
                            >
                                <p v-if="m.error" class="text-red-600">{{ m.error }}</p>
                                <template v-else>
                                    <p class="whitespace-pre-wrap">{{ m.text }}</p>
                                    <span
                                        v-if="m.streaming && !m.text"
                                        class="inline-flex gap-1 text-stone-400"
                                    >
                                        <span class="animate-pulse">escribiendo…</span>
                                    </span>
                                </template>
                            </div>

                            <!-- Fuentes (expandibles: muestran el fragmento citado) -->
                            <div v-if="m.sources && m.sources.length" class="space-y-1.5">
                                <p class="text-[11px] font-semibold uppercase tracking-wide text-stone-400">
                                    Fuentes
                                    <span v-if="m.confidence != null" class="ml-1 font-normal normal-case text-stone-400">
                                        · confianza {{ Math.round(m.confidence * 100) }}%
                                    </span>
                                </p>
                                <details
                                    v-for="s in m.sources"
                                    :key="s.n"
                                    class="group overflow-hidden rounded-lg border border-stone-200 bg-white"
                                >
                                    <summary
                                        class="flex cursor-pointer list-none items-center gap-1.5 px-2.5 py-1.5 text-[11px] text-stone-600 [&::-webkit-details-marker]:hidden"
                                    >
                                        <span class="font-semibold text-brand-600">[{{ s.n }}]</span>
                                        <span class="min-w-0 flex-1 truncate">{{ s.filename }}</span>
                                        <span v-if="s.page" class="shrink-0 text-stone-400">p.{{ s.page }}</span>
                                        <svg
                                            class="h-3.5 w-3.5 shrink-0 text-stone-400 transition group-open:rotate-180"
                                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                        >
                                            <path d="M6 9l6 6 6-6" />
                                        </svg>
                                    </summary>
                                    <p
                                        class="whitespace-pre-wrap border-t border-stone-100 bg-stone-50/60 px-2.5 py-2 text-[11px] leading-relaxed text-stone-600"
                                    >
                                        {{ s.text }}
                                    </p>
                                </details>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            <!-- Input -->
            <footer class="border-t border-stone-200 bg-white px-4 py-3">
                <form class="flex items-end gap-2" @submit.prevent="send()">
                    <textarea
                        v-model="question"
                        rows="1"
                        placeholder="Escribí tu pregunta…"
                        :disabled="streaming"
                        @keydown.enter.exact.prevent="send()"
                        class="max-h-32 flex-1 resize-none rounded-xl border-stone-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500 disabled:opacity-60"
                    ></textarea>
                    <button
                        type="submit"
                        :disabled="streaming || !question.trim()"
                        class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-brand-500 text-white transition hover:bg-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 disabled:opacity-40"
                        aria-label="Enviar"
                    >
                        <svg v-if="!streaming" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M22 2L11 13M22 2l-7 20-4-9-9-4 20-7z" />
                        </svg>
                        <span v-else class="h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white"></span>
                    </button>
                </form>
                <p class="mt-1.5 px-1 text-[11px] text-stone-400">
                    Las respuestas se generan con IA a partir de los apuntes. Verificá lo importante.
                </p>
            </footer>
        </aside>
    </Transition>
</template>
