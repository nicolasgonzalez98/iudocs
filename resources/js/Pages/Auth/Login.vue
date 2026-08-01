<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import GoogleButton from '@/Components/GoogleButton.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Iniciar sesión" />

        <div v-if="status" class="mb-4 text-sm font-medium text-green-600">
            {{ status }}
        </div>

        <InputError class="mb-4" :message="form.errors.email" v-if="form.errors.email" />

        <!-- Método principal: Google -->
        <GoogleButton />

        <div class="my-6 flex items-center gap-3">
            <span class="h-px flex-1 bg-stone-200"></span>
            <span class="text-xs font-medium uppercase tracking-wide text-stone-400">o con tu email</span>
            <span class="h-px flex-1 bg-stone-200"></span>
        </div>

        <form @submit.prevent="submit">
            <div>
                <InputLabel for="email" value="Email" />

                <TextInput
                    id="email"
                    type="email"
                    class="mt-1 block w-full"
                    v-model="form.email"
                    required
                    autofocus
                    autocomplete="username"
                />
            </div>

            <div class="mt-4">
                <InputLabel for="password" value="Contraseña" />

                <TextInput
                    id="password"
                    type="password"
                    class="mt-1 block w-full"
                    v-model="form.password"
                    required
                    autocomplete="current-password"
                />

                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div class="mt-4 block">
                <label class="flex items-center">
                    <Checkbox name="remember" v-model:checked="form.remember" />
                    <span class="ms-2 text-sm text-stone-600">Recordarme</span>
                </label>
            </div>

            <div class="mt-6 flex items-center justify-between">
                <Link
                    v-if="canResetPassword"
                    :href="route('password.request')"
                    class="rounded-md text-sm text-stone-500 underline-offset-4 hover:text-amber-600 hover:underline focus:outline-none"
                >
                    ¿Olvidaste tu contraseña?
                </Link>
                <span v-else></span>

                <PrimaryButton
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    Iniciar sesión
                </PrimaryButton>
            </div>
        </form>

        <p class="mt-6 text-center text-sm text-stone-500">
            ¿No tenés cuenta?
            <Link :href="route('register')" class="font-semibold text-amber-600 hover:text-amber-700">
                Registrate
            </Link>
        </p>
    </GuestLayout>
</template>
