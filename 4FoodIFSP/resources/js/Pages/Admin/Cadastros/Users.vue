<script setup>
import { useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AppSidebar from '../../../Components/AppSidebar.vue';
import UsersTableRow from '../../../Components/UsersTableRow.vue';

const props = defineProps({
    users: {
        type: Array,
        default: () => [],
    },
    departments: {
        type: Array,
        default: () => [],
    },
});

const page = usePage();
const search = ref('');
const isLoading = ref(false);
const isCreateMenuOpen = ref(false);
const isPasswordVisible = ref(false);

const createUserForm = useForm({
    username: '',
    department_id: '',
    email: '',
    password: '',
});

function togglePasswordVisibility() {
    isPasswordVisible.value = !isPasswordVisible.value;
}

const flashSuccess = computed(() => page.props.flash?.success ?? '');

const filteredUsers = computed(() => {
    if (!search.value.trim()) {
        return props.users;
    }

    const query = search.value.toLowerCase();

    return props.users.filter((user) => (
        String(user.name ?? '').toLowerCase().includes(query)
        || String(user.email ?? '').toLowerCase().includes(query)
        || String(user.id ?? '').toLowerCase().includes(query)
    ));
});

const departmentOptions = computed(() => {
    const roleBySlug = {
        admin: 'Admin',
        kitchen: 'Kitchen',
        finance: 'Financeiro',
        waiter: 'Garcom',
    };

    return props.departments.map((department) => {
        if (typeof department === 'string') {
            return {
                id: department,
                label: department,
            };
        }

        const slug = String(department.slug ?? '').toLowerCase();

        return {
            id: department.id,
            label: roleBySlug[slug] ?? department.name ?? 'Departamento',
        };
    });
});

function handleManageDepartments(user) {
    window.alert(`Gestao de departamentos de ${user.name} sera liberada na proxima fase.`);
}

function handleEdit(user) {
    window.alert(`Edicao do usuario ${user.name} sera liberada na proxima fase.`);
}

function handleDelete(user) {
    window.alert(`Exclusao do usuario ${user.name} sera integrada com persistencia na proxima fase.`);
}

function handleAddUser() {
    createUserForm.clearErrors();
    isCreateMenuOpen.value = true;
}

function handleFirstUserCta() {
    handleAddUser();
}

function handleExitCreateMenu() {
    createUserForm.reset();
    createUserForm.clearErrors();
    isCreateMenuOpen.value = false;
    isPasswordVisible.value = false;
}

function handleSaveUser() {
    createUserForm.post('/admin/cadastros/users', {
        preserveScroll: true,
        onSuccess: () => {
            handleExitCreateMenu();
        },
    });
}
</script>

<template>
    <div class="shell">
        <AppSidebar active="cadastros" />

        <div class="main">
            <header class="topbar">
                <h1>Usuarios</h1>
                <div class="head-actions">
                    <input v-model="search" type="text" placeholder="Localize">
                    <button type="button" @click="handleAddUser">
                        Adicionar
                    </button>
                </div>
            </header>

            <div class="content">
                <p v-if="flashSuccess" class="feedback success">
                    {{ flashSuccess }}
                </p>

                <section class="table-card">
                    <div class="table-head">
                        <span>Nome usuario</span>
                        <span>E-mail</span>
                        <span>Departamentos</span>
                        <span>Acoes</span>
                    </div>

                    <div v-if="isLoading" class="skeleton-wrap">
                        <div v-for="line in 4" :key="line" class="skeleton-line" />
                    </div>

                    <template v-else-if="props.users.length === 0">
                        <div class="empty-state">
                            <p>Nenhum usuario cadastrado.</p>
                            <button type="button" @click="handleFirstUserCta">
                                Adicionar primeiro usuario
                            </button>
                        </div>
                    </template>

                    <template v-else-if="filteredUsers.length === 0">
                        <p class="search-empty">Nenhum usuario encontrado.</p>
                    </template>

                    <template v-else>
                        <UsersTableRow
                            v-for="user in filteredUsers"
                            :key="user.id"
                            :user="user"
                            @manage-departments="handleManageDepartments"
                            @edit="handleEdit"
                            @delete="handleDelete"
                        />
                    </template>
                </section>
            </div>
        </div>

        <div v-if="isCreateMenuOpen" class="create-user-overlay" @click.self="handleExitCreateMenu">
            <section class="create-user-panel">
                <header class="create-user-head">
                    <h2>Novo usuario</h2>
                    <p>Preencha os dados para liberar acesso na plataforma.</p>
                </header>

                <form class="create-user-form" @submit.prevent="handleSaveUser">
                    <label>
                        Nome usuario
                        <input
                            v-model="createUserForm.username"
                            type="text"
                            autocomplete="username"
                            placeholder="Nome para login"
                            required
                        >
                        <small v-if="createUserForm.errors.username">{{ createUserForm.errors.username }}</small>
                    </label>

                    <label>
                        Departamento
                        <select v-model="createUserForm.department_id" required>
                            <option disabled value="">
                                Selecione um departamento
                            </option>
                            <option
                                v-for="department in departmentOptions"
                                :key="department.id"
                                :value="department.id"
                            >
                                {{ department.label }}
                            </option>
                        </select>
                        <small v-if="createUserForm.errors.department_id">{{ createUserForm.errors.department_id }}</small>
                    </label>

                    <label>
                        E-mail
                        <input
                            v-model="createUserForm.email"
                            type="email"
                            autocomplete="email"
                            placeholder="usuario@empresa.com"
                            required
                        >
                        <small v-if="createUserForm.errors.email">{{ createUserForm.errors.email }}</small>
                    </label>

                    <label>
                        Senha
                        <div class="password-field">
                            <input
                                v-model="createUserForm.password"
                                :type="isPasswordVisible ? 'text' : 'password'"
                                autocomplete="new-password"
                                placeholder="Senha de acesso"
                                required
                            >
                            <button
                                type="button"
                                class="password-toggle"
                                :aria-label="isPasswordVisible ? 'Ocultar senha' : 'Mostrar senha'"
                                :title="isPasswordVisible ? 'Ocultar senha' : 'Mostrar senha'"
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
                        <small v-if="createUserForm.errors.password">{{ createUserForm.errors.password }}</small>
                    </label>

                    <p v-if="createUserForm.errors.user_limit" class="form-warning">
                        {{ createUserForm.errors.user_limit }}
                    </p>

                    <footer class="create-user-actions">
                        <button type="button" class="secondary" @click="handleExitCreateMenu">
                            Sair
                        </button>
                        <button type="submit" :disabled="createUserForm.processing">
                            {{ createUserForm.processing ? 'Salvando...' : 'Salvar' }}
                        </button>
                    </footer>
                </form>
            </section>
        </div>
    </div>
</template>

<style scoped src="./styles/Users.css"></style>
