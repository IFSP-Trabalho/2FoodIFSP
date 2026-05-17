<script setup>
import { router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AppSidebar from '../../../Components/AppSidebar.vue';
import DepartmentManagePanel from '../../../Components/DepartmentManagePanel.vue';
import UserCreatePanel from '../../../Components/UserCreatePanel.vue';
import UserEditPanel from '../../../Components/UserEditPanel.vue';
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
const pendingDeleteUser = ref(null);
const isDeleting = ref(false);
const departmentPanelUser = ref(null);
const editPanelUser = ref(null);

const flashSuccess = computed(() => page.props.flash?.success ?? '');
const deleteError = computed(() => page.props.errors?.delete ?? '');

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

function handleManageDepartments(user) {
    if (!props.departments.length) {
        console.error('Departamentos indisponíveis');
        return;
    }

    departmentPanelUser.value = user;
}

function closeDepartmentPanel() {
    departmentPanelUser.value = null;
}

function handleEdit(user) {
    editPanelUser.value = user;
}

function closeEditPanel() {
    editPanelUser.value = null;
}

function handleDelete(user) {
    pendingDeleteUser.value = user;
}

function confirmDelete() {
    if (!pendingDeleteUser.value || isDeleting.value) {
        return;
    }

    isDeleting.value = true;

    router.delete(`/admin/cadastros/users/${pendingDeleteUser.value.id}`, {
        preserveScroll: true,
        onFinish: () => {
            isDeleting.value = false;
            pendingDeleteUser.value = null;
        },
    });
}

function cancelDelete() {
    pendingDeleteUser.value = null;
}

function handleAddUser() {
    isCreateMenuOpen.value = true;
}

function handleFirstUserCta() {
    handleAddUser();
}

function closeCreatePanel() {
    isCreateMenuOpen.value = false;
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
                <p v-if="deleteError" class="feedback error">
                    {{ deleteError }}
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

        <div v-if="pendingDeleteUser" class="confirm-delete-overlay" @click.self="cancelDelete">
            <div class="confirm-delete-dialog">
                <div class="confirm-delete-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24">
                        <path d="M8 4h8l1 2h4v2H3V6h4l1-2Zm1 6h2v8H9v-8Zm4 0h2v8h-2v-8ZM6 8h12l-1 12H7L6 8Z" />
                    </svg>
                </div>
                <h3>Excluir usuario</h3>
                <p>
                    Deseja excluir <strong>{{ pendingDeleteUser.name }}</strong>?
                    Esta ação não pode ser desfeita.
                </p>
                <footer class="confirm-delete-actions">
                    <button type="button" class="secondary" :disabled="isDeleting" @click="cancelDelete">
                        Cancelar
                    </button>
                    <button type="button" class="danger" :disabled="isDeleting" @click="confirmDelete">
                        {{ isDeleting ? 'Excluindo...' : 'Excluir' }}
                    </button>
                </footer>
            </div>
        </div>

        <DepartmentManagePanel
            v-if="departmentPanelUser"
            :user="departmentPanelUser"
            :departments="props.departments"
            @close="closeDepartmentPanel"
        />

        <UserCreatePanel
            v-if="isCreateMenuOpen"
            :departments="props.departments"
            @close="closeCreatePanel"
        />

        <UserEditPanel
            v-if="editPanelUser"
            :user="editPanelUser"
            @close="closeEditPanel"
        />
    </div>
</template>

<style scoped src="./styles/Users.css"></style>
