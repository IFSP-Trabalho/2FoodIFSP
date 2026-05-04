<script setup>
import { router } from '@inertiajs/vue3';
import { initializeApp, getApps } from 'firebase/app';
import { getAuth, signInWithEmailAndPassword, updatePassword } from 'firebase/auth';
import { computed, onBeforeUnmount, ref } from 'vue';

const props = defineProps({
    changePassword: {
        type: Boolean,
        default: false,
    },
});

const firebaseConfig = {
    apiKey: import.meta.env.VITE_FIREBASE_API_KEY,
    authDomain: import.meta.env.VITE_FIREBASE_AUTH_DOMAIN,
    projectId: import.meta.env.VITE_FIREBASE_PROJECT_ID,
    storageBucket: import.meta.env.VITE_FIREBASE_STORAGE_BUCKET,
    messagingSenderId: import.meta.env.VITE_FIREBASE_MESSAGING_SENDER_ID,
    appId: import.meta.env.VITE_FIREBASE_APP_ID,
};

const firebaseApp = getApps()[0] ?? initializeApp(firebaseConfig);
const auth = getAuth(firebaseApp);

const email = ref('');
const password = ref('');
const loading = ref(false);
const backgroundImage = new URL('../../../../img/auth.png', import.meta.url).href;
const brandLogo = new URL('../../../../img/4Food.ico', import.meta.url).href;
const loginLogs = ref([]);
const logTimeouts = new Map();
const LOG_DURATION_MS = 5000;
let nextLogId = 1;
let pendingSubmitTimeout = null;

const newPassword = ref('');
const newPasswordConfirmation = ref('');
const showNewPassword = ref(false);
const showConfirmPassword = ref(false);
const passwordChangeLoading = ref(false);
const passwordChangeError = ref('');

const canSubmit = computed(() => {
    return email.value.trim() !== '' && password.value.trim() !== '';
});

function removeLog(logId) {
    const timeoutId = logTimeouts.get(logId);
    if (timeoutId) {
        window.clearTimeout(timeoutId);
        logTimeouts.delete(logId);
    }

    loginLogs.value = loginLogs.value.filter((log) => log.id !== logId);
}

function addLog(type, message) {
    const logId = nextLogId;
    nextLogId += 1;

    loginLogs.value = [...loginLogs.value, {
        id: logId,
        type,
        message,
        duration: LOG_DURATION_MS,
    }];

    const timeoutId = window.setTimeout(() => {
        removeLog(logId);
    }, LOG_DURATION_MS);

    logTimeouts.set(logId, timeoutId);
}

onBeforeUnmount(() => {
    for (const timeoutId of logTimeouts.values()) {
        window.clearTimeout(timeoutId);
    }
    logTimeouts.clear();

    if (pendingSubmitTimeout) {
        window.clearTimeout(pendingSubmitTimeout);
        pendingSubmitTimeout = null;
    }
});

async function handlePasswordChange() {
    passwordChangeError.value = '';

    if (newPassword.value.length < 6) {
        passwordChangeError.value = 'A nova senha deve ter no mínimo 6 caracteres.';
        return;
    }

    if (newPassword.value !== newPasswordConfirmation.value) {
        passwordChangeError.value = 'As senhas não conferem.';
        return;
    }

    const firebaseUser = auth.currentUser;
    if (!firebaseUser) {
        passwordChangeError.value = 'Sessão expirada. Faça login novamente.';
        return;
    }

    passwordChangeLoading.value = true;

    try {
        await updatePassword(firebaseUser, newPassword.value);
        const idToken = await firebaseUser.getIdToken(true);

        router.post('/password/change', {
            idToken,
            new_password: newPassword.value,
            new_password_confirmation: newPasswordConfirmation.value,
        }, {
            onError: (errors) => {
                passwordChangeError.value = errors.new_password ?? 'Erro ao salvar a senha. Tente novamente.';
            },
            onFinish: () => {
                passwordChangeLoading.value = false;
            },
        });
    } catch (_e) {
        passwordChangeError.value = 'Não foi possível atualizar a senha. Faça login novamente.';
        passwordChangeLoading.value = false;
    }
}

async function handleLogin() {
    if (!canSubmit.value || loading.value) {
        return;
    }

    loading.value = true;

    try {
        const credential = await signInWithEmailAndPassword(auth, email.value, password.value);
        const idToken = await credential.user.getIdToken();

        addLog('success', 'Login realizado com sucesso!');
        pendingSubmitTimeout = window.setTimeout(() => {
            router.post('/auth/firebase', { idToken }, {
                onError: (errors) => {
                    addLog('error', errors.email ?? 'Erro de autenticação. Por favor, tente novamente.');
                },
                onFinish: () => {
                    loading.value = false;
                },
            });
            pendingSubmitTimeout = null;
        }, 900);
    } catch (_e) {
        addLog('error', 'E-mail ou senha incorretos.');
        loading.value = false;
    }
}
</script>

<template>
    <main
        class="min-h-screen bg-center bg-cover bg-no-repeat text-white flex items-center p-4 sm:p-8 md:p-10"
        :style="{ backgroundImage: `url(${backgroundImage})` }"
    >
        <transition-group
            name="login-log"
            tag="div"
            class="fixed top-4 left-1/2 -translate-x-1/2 w-[min(90vw,430px)] z-50 space-y-2"
        >
            <div
                v-for="log in loginLogs"
                :key="log.id"
                class="relative overflow-hidden rounded-lg px-4 py-2.5 text-sm font-medium shadow-xl backdrop-blur-sm transition duration-300 hover:translate-y-1"
                :class="log.type === 'success'
                    ? 'bg-[#1a3b26]/95 text-green-50'
                    : 'bg-[#3b1a1f]/95 text-red-50'"
            >
                <div class="flex items-start gap-2.5 pr-2">
                    <span v-if="log.type === 'success'" class="mt-0.5 text-green-300">
                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm3.78-9.72a.75.75 0 0 0-1.06-1.06L9.25 10.69 7.28 8.72a.75.75 0 0 0-1.06 1.06l2.5 2.5a.75.75 0 0 0 1.06 0l4-4Z" clip-rule="evenodd" />
                        </svg>
                    </span>
                    <span v-else class="mt-0.5 text-red-300">
                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M18 10A8 8 0 1 1 2 10a8 8 0 0 1 16 0ZM9.25 6.75a.75.75 0 0 1 1.5 0v3.19l1.28 1.28a.75.75 0 1 1-1.06 1.06l-1.5-1.5a.75.75 0 0 1-.22-.53V6.75Zm.75 8a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" clip-rule="evenodd" />
                        </svg>
                    </span>

                    <p>{{ log.message }}</p>
                </div>

                <div class="absolute bottom-0 left-0 h-1 w-full bg-black/15">
                    <div
                        class="h-full origin-left animate-log-timer"
                        :class="log.type === 'success' ? 'bg-green-300/90' : 'bg-red-300/90'"
                        :style="{ animationDuration: `${log.duration}ms` }"
                    />
                </div>
            </div>
        </transition-group>

        <section class="w-full max-w-[360px] rounded-2xl bg-[#191e26]/95 border border-white/10 shadow-2xl px-7 py-8 sm:ml-8 md:ml-10">
            <div class="mb-8 flex justify-center">
                <img :src="brandLogo" alt="4Food" class="h-20 w-auto object-contain">
            </div>

            <h1 class="text-3xl font-semibold leading-tight mb-1">Entrar</h1>
            <p class="text-xs text-white/70 mb-7">Usar sua conta do sistema</p>

            <form class="space-y-4" @submit.prevent="handleLogin">
                <input
                    v-model="email"
                    type="email"
                    required
                    autocomplete="email"
                    placeholder="Nome do usuário ou e-mail"
                    class="w-full rounded-md bg-white/10 border border-white/20 text-sm text-white placeholder:text-white/45 px-3 py-3 focus:outline-none focus:ring-2 focus:ring-white/40 focus:border-white/40"
                >

                <input
                    v-model="password"
                    type="password"
                    required
                    autocomplete="current-password"
                    placeholder="Senha"
                    class="w-full rounded-md bg-white/10 border border-white/20 text-sm text-white placeholder:text-white/45 px-3 py-3 focus:outline-none focus:ring-2 focus:ring-white/40 focus:border-white/40"
                >

                <button
                    type="submit"
                    :disabled="!canSubmit || loading"
                    class="w-full rounded-md bg-white text-[#1a1f27] py-2.5 text-sm font-semibold tracking-wide uppercase disabled:opacity-50 disabled:cursor-not-allowed transition"
                >
                    {{ loading ? 'Entrando...' : 'Entrar' }}
                </button>
            </form>

            <p class="text-[11px] text-white/55 text-center mt-6">Versão 1.0.0</p>
        </section>

        <transition name="cp-modal">
            <div v-if="props.changePassword" class="cp-backdrop">
                <div class="cp-card">
                    <div class="cp-icon-wrap">
                        <svg viewBox="0 0 24 24" aria-hidden="true" class="cp-lock-icon">
                            <path d="M12 1a5 5 0 0 0-5 5v2H5a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V10a2 2 0 0 0-2-2h-2V6a5 5 0 0 0-5-5Zm-3 7V6a3 3 0 0 1 6 0v2H9Zm3 4a2 2 0 1 1 0 4 2 2 0 0 1 0-4Z" />
                        </svg>
                    </div>

                    <h2 class="cp-title">Defina sua nova senha</h2>
                    <p class="cp-subtitle">Este é seu primeiro acesso. Crie uma senha pessoal para continuar.</p>

                    <p v-if="passwordChangeError" class="cp-error">
                        {{ passwordChangeError }}
                    </p>

                    <form class="cp-form" @submit.prevent="handlePasswordChange">
                        <label class="cp-label">
                            Nova senha
                            <div class="cp-password-field">
                                <input
                                    v-model="newPassword"
                                    :type="showNewPassword ? 'text' : 'password'"
                                    autocomplete="new-password"
                                    placeholder="Mínimo 6 caracteres"
                                    required
                                    minlength="6"
                                >
                                <button
                                    type="button"
                                    class="cp-eye"
                                    :aria-label="showNewPassword ? 'Ocultar' : 'Mostrar'"
                                    @click="showNewPassword = !showNewPassword"
                                >
                                    <svg v-if="showNewPassword" viewBox="0 0 24 24" aria-hidden="true"><path d="M2 4.27 3.28 3l17.49 17.49-1.27 1.27-3.07-3.07A11.86 11.86 0 0 1 12 19.5C5 19.5 1 12 1 12a17.91 17.91 0 0 1 4.32-5.41L2 4.27Zm9.04 4.79 4.9 4.9a3.5 3.5 0 0 0-4.9-4.9Zm5.62 5.62-1.46-1.46a3.5 3.5 0 0 1-4.31-4.31L8.43 6.43A11.92 11.92 0 0 1 12 6c7 0 11 6 11 6a17.74 17.74 0 0 1-4.34 4.93l-1.99-1.99Z" /></svg>
                                    <svg v-else viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5C5 5 1 12 1 12s4 7 11 7 11-7 11-7-4-7-11-7Zm0 12a5 5 0 1 1 5-5 5 5 0 0 1-5 5Zm0-8a3 3 0 1 0 3 3 3 3 0 0 0-3-3Z" /></svg>
                                </button>
                            </div>
                        </label>

                        <label class="cp-label">
                            Confirmar nova senha
                            <div class="cp-password-field">
                                <input
                                    v-model="newPasswordConfirmation"
                                    :type="showConfirmPassword ? 'text' : 'password'"
                                    autocomplete="new-password"
                                    placeholder="Repita a senha"
                                    required
                                >
                                <button
                                    type="button"
                                    class="cp-eye"
                                    :aria-label="showConfirmPassword ? 'Ocultar' : 'Mostrar'"
                                    @click="showConfirmPassword = !showConfirmPassword"
                                >
                                    <svg v-if="showConfirmPassword" viewBox="0 0 24 24" aria-hidden="true"><path d="M2 4.27 3.28 3l17.49 17.49-1.27 1.27-3.07-3.07A11.86 11.86 0 0 1 12 19.5C5 19.5 1 12 1 12a17.91 17.91 0 0 1 4.32-5.41L2 4.27Zm9.04 4.79 4.9 4.9a3.5 3.5 0 0 0-4.9-4.9Zm5.62 5.62-1.46-1.46a3.5 3.5 0 0 1-4.31-4.31L8.43 6.43A11.92 11.92 0 0 1 12 6c7 0 11 6 11 6a17.74 17.74 0 0 1-4.34 4.93l-1.99-1.99Z" /></svg>
                                    <svg v-else viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5C5 5 1 12 1 12s4 7 11 7 11-7 11-7-4-7-11-7Zm0 12a5 5 0 1 1 5-5 5 5 0 0 1-5 5Zm0-8a3 3 0 1 0 3 3 3 3 0 0 0-3-3Z" /></svg>
                                </button>
                            </div>
                        </label>

                        <button type="submit" class="cp-submit" :disabled="passwordChangeLoading">
                            {{ passwordChangeLoading ? 'Salvando...' : 'Salvar senha e continuar' }}
                        </button>
                    </form>
                </div>
            </div>
        </transition>
    </main>
</template>

<style scoped src="./styles/Login.css"></style>
