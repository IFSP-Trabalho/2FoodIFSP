<script setup>
import { router } from '@inertiajs/vue3';
import { initializeApp, getApps } from 'firebase/app';
import { getAuth, updatePassword } from 'firebase/auth';
import { ref } from 'vue';

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

const newPassword = ref('');
const newPasswordConfirmation = ref('');
const showNewPassword = ref(false);
const showConfirmPassword = ref(false);
const loading = ref(false);
const errorMessage = ref('');

function validate() {
    if (newPassword.value.length < 6) {
        return 'A nova senha deve ter no mínimo 6 caracteres.';
    }

    if (newPassword.value !== newPasswordConfirmation.value) {
        return 'As senhas não conferem.';
    }

    return null;
}

async function handleSubmit() {
    errorMessage.value = '';

    const validationError = validate();
    if (validationError) {
        errorMessage.value = validationError;
        return;
    }

    const firebaseUser = auth.currentUser;
    if (!firebaseUser) {
        errorMessage.value = 'Sessão expirada. Faça login novamente.';
        return;
    }

    loading.value = true;

    try {
        await updatePassword(firebaseUser, newPassword.value);

        const idToken = await firebaseUser.getIdToken(true);

        router.post('/password/change', {
            idToken,
            new_password: newPassword.value,
            new_password_confirmation: newPasswordConfirmation.value,
        }, {
            onError: (errors) => {
                errorMessage.value = errors.new_password ?? 'Erro ao atualizar a senha. Tente novamente.';
            },
            onFinish: () => {
                loading.value = false;
            },
        });
    } catch (_e) {
        errorMessage.value = 'Não foi possível atualizar a senha. Tente fazer login novamente.';
        loading.value = false;
    }
}
</script>

<template>
    <main class="change-password-page">
        <section class="change-password-card">
            <div class="card-icon-wrap">
                <svg viewBox="0 0 24 24" aria-hidden="true" class="lock-icon">
                    <path d="M12 1a5 5 0 0 0-5 5v2H5a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V10a2 2 0 0 0-2-2h-2V6a5 5 0 0 0-5-5Zm-3 7V6a3 3 0 0 1 6 0v2H9Zm3 4a2 2 0 1 1 0 4 2 2 0 0 1 0-4Z" />
                </svg>
            </div>

            <h1 class="card-title">Defina sua nova senha</h1>
            <p class="card-subtitle">Este é seu primeiro acesso. Crie uma senha pessoal para continuar.</p>

            <p v-if="errorMessage" class="form-error">
                {{ errorMessage }}
            </p>

            <form class="change-password-form" @submit.prevent="handleSubmit">
                <label class="field-label">
                    Nova senha
                    <div class="password-field">
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
                            class="eye-btn"
                            :aria-label="showNewPassword ? 'Ocultar senha' : 'Mostrar senha'"
                            @click="showNewPassword = !showNewPassword"
                        >
                            <svg v-if="showNewPassword" viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M2 4.27 3.28 3l17.49 17.49-1.27 1.27-3.07-3.07A11.86 11.86 0 0 1 12 19.5C5 19.5 1 12 1 12a17.91 17.91 0 0 1 4.32-5.41L2 4.27Zm9.04 4.79 4.9 4.9a3.5 3.5 0 0 0-4.9-4.9Zm5.62 5.62-1.46-1.46a3.5 3.5 0 0 1-4.31-4.31L8.43 6.43A11.92 11.92 0 0 1 12 6c7 0 11 6 11 6a17.74 17.74 0 0 1-4.34 4.93l-1.99-1.99Z" />
                            </svg>
                            <svg v-else viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M12 5C5 5 1 12 1 12s4 7 11 7 11-7 11-7-4-7-11-7Zm0 12a5 5 0 1 1 5-5 5 5 0 0 1-5 5Zm0-8a3 3 0 1 0 3 3 3 3 0 0 0-3-3Z" />
                            </svg>
                        </button>
                    </div>
                </label>

                <label class="field-label">
                    Confirmar nova senha
                    <div class="password-field">
                        <input
                            v-model="newPasswordConfirmation"
                            :type="showConfirmPassword ? 'text' : 'password'"
                            autocomplete="new-password"
                            placeholder="Repita a senha"
                            required
                        >
                        <button
                            type="button"
                            class="eye-btn"
                            :aria-label="showConfirmPassword ? 'Ocultar senha' : 'Mostrar senha'"
                            @click="showConfirmPassword = !showConfirmPassword"
                        >
                            <svg v-if="showConfirmPassword" viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M2 4.27 3.28 3l17.49 17.49-1.27 1.27-3.07-3.07A11.86 11.86 0 0 1 12 19.5C5 19.5 1 12 1 12a17.91 17.91 0 0 1 4.32-5.41L2 4.27Zm9.04 4.79 4.9 4.9a3.5 3.5 0 0 0-4.9-4.9Zm5.62 5.62-1.46-1.46a3.5 3.5 0 0 1-4.31-4.31L8.43 6.43A11.92 11.92 0 0 1 12 6c7 0 11 6 11 6a17.74 17.74 0 0 1-4.34 4.93l-1.99-1.99Z" />
                            </svg>
                            <svg v-else viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M12 5C5 5 1 12 1 12s4 7 11 7 11-7 11-7-4-7-11-7Zm0 12a5 5 0 1 1 5-5 5 5 0 0 1-5 5Zm0-8a3 3 0 1 0 3 3 3 3 0 0 0-3-3Z" />
                            </svg>
                        </button>
                    </div>
                </label>

                <button type="submit" class="submit-btn" :disabled="loading">
                    {{ loading ? 'Salvando...' : 'Salvar senha e continuar' }}
                </button>
            </form>
        </section>
    </main>
</template>

<style scoped src="./styles/ChangePassword.css"></style>
