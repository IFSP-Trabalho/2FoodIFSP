<script setup>
import { useForm } from '@inertiajs/vue3';
import { onMounted, onUnmounted, ref } from 'vue';
import DepartmentSelect from './DepartmentSelect.vue';

const props = defineProps({
    departments: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(['close']);

const isPasswordVisible = ref(false);

const form = useForm({
    username: '',
    department_id: '',
    email: '',
    password: '',
});

function togglePasswordVisibility() {
    isPasswordVisible.value = !isPasswordVisible.value;
}

function handleCancel() {
    form.reset();
    form.clearErrors();
    isPasswordVisible.value = false;
    emit('close');
}

function handleOverlayClick() {
    handleCancel();
}

function handleSubmit() {
    form.post('/admin/cadastros/users', {
        preserveScroll: true,
        onSuccess: () => {
            handleCancel();
        },
    });
}

function onKeydown(event) {
    if (event.key === 'Escape') {
        handleCancel();
    }
}

onMounted(() => {
    document.addEventListener('keydown', onKeydown);
});

onUnmounted(() => {
    document.removeEventListener('keydown', onKeydown);
});
</script>

<template>
    <div class="admin-modal-overlay" @click.self="handleOverlayClick"></div>
    <section
        class="admin-modal"
        role="dialog"
        aria-modal="true"
        aria-labelledby="user-panel-title"
    >
        <header class="admin-modal-head">
            <h3 id="user-panel-title">
                Cadastrar usuario
            </h3>
        </header>

        <form class="admin-modal-form" @submit.prevent="handleSubmit">
            <label class="admin-modal-field">
                <div class="admin-modal-input-wrap">
                    <span class="admin-modal-floating-label">Nome usuario</span>
                    <input
                        v-model="form.username"
                        type="text"
                        autocomplete="username"
                        placeholder=" "
                        required
                    >
                </div>
                <small v-if="form.errors.username">{{ form.errors.username }}</small>
            </label>

            <div class="admin-modal-field">
                <DepartmentSelect
                    :model-value="form.department_id"
                    :departments="props.departments"
                    :error="form.errors.department_id"
                    @update:model-value="(value) => { form.department_id = value; }"
                />
            </div>

            <label class="admin-modal-field">
                <div class="admin-modal-input-wrap">
                    <span class="admin-modal-floating-label">E-mail</span>
                    <input
                        v-model="form.email"
                        type="email"
                        autocomplete="email"
                        placeholder=" "
                        required
                    >
                </div>
                <small v-if="form.errors.email">{{ form.errors.email }}</small>
            </label>

            <label class="admin-modal-field">
                <div class="admin-modal-input-wrap admin-modal-password">
                    <span class="admin-modal-floating-label">Senha</span>
                    <input
                        v-model="form.password"
                        :type="isPasswordVisible ? 'text' : 'password'"
                        autocomplete="new-password"
                        placeholder=" "
                        required
                    >
                    <button
                        type="button"
                        class="admin-modal-password-toggle"
                        :aria-label="isPasswordVisible ? 'Ocultar senha' : 'Mostrar senha'"
                        @click="togglePasswordVisibility"
                    >
                        <svg v-if="isPasswordVisible" viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M2 4.27 3.28 3l17.49 17.49-1.27 1.27-3.07-3.07A11.86 11.86 0 0 1 12 19.5C5 19.5 1 12 1 12a17.91 17.91 0 0 1 4.32-5.41L2 4.27Zm9.04 4.79 4.9 4.9a3.5 3.5 0 0 0-4.9-4.9Zm5.62 5.62-1.46-1.46a3.5 3.5 0 0 1-4.31-4.31L8.43 6.43A11.92 11.92 0 0 1 12 6c7 0 11 6 11 6a17.74 17.74 0 0 1-4.34 4.93l-1.99-1.99Z" />
                        </svg>
                        <svg v-else viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M12 5C5 5 1 12 1 12s4 7 11 7 11-7 11-7-4-7-11-7Zm0 12a5 5 0 1 1 5-5 5 5 0 0 1-5 5Zm0-8a3 3 0 1 0 3 3 3 3 0 0 0-3-3Z" />
                        </svg>
                    </button>
                </div>
                <small v-if="form.errors.password">{{ form.errors.password }}</small>
            </label>

            <p v-if="form.errors.user_limit" class="admin-modal-warning">
                {{ form.errors.user_limit }}
            </p>

            <footer class="admin-modal-actions">
                <button type="button" class="secondary" :disabled="form.processing" @click="handleCancel">
                    Sair
                </button>
                <button type="submit" class="primary" :disabled="form.processing">
                    {{ form.processing ? 'Salvando...' : 'Salvar' }}
                </button>
            </footer>
        </form>
    </section>
</template>
