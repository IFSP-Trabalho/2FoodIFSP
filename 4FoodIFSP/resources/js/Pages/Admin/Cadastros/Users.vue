<script setup>
import { computed, ref } from 'vue';
import AppSidebar from '../../../Components/AppSidebar.vue';
import UsersTableRow from '../../../Components/UsersTableRow.vue';

const props = defineProps({
    users: {
        type: Array,
        default: () => [],
    },
});

const search = ref('');
const isLoading = ref(false);

const filteredUsers = computed(() => {
    if (!search.value.trim()) {
        return props.users;
    }

    const query = search.value.toLowerCase();

    return props.users.filter((user) => (
        user.name.toLowerCase().includes(query)
        || user.email.toLowerCase().includes(query)
        || user.id.toLowerCase().includes(query)
    ));
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
    window.alert('Cadastro de usuario sera liberado na proxima fase.');
}

function handleFirstUserCta() {
    window.alert('Fluxo de cadastro sera implementado na fase 2.');
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
                <section class="table-card">
                    <div class="table-head">
                        <span>ID</span>
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
    </div>
</template>

<style scoped src="./styles/Users.css"></style>
