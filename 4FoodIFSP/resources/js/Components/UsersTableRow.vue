<script setup>
const props = defineProps({
    user: {
        type: Object,
        required: true,
    },
});

const emit = defineEmits(['manageDepartments', 'edit', 'delete']);

function initials(name) {
    return (name ?? '')
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0]?.toUpperCase())
        .join('');
}

function onDelete() {
    if (props.user.is_root_admin) {
        return;
    }

    emit('delete', props.user);
}
</script>

<template>
    <div class="row">
        <div class="cell user-cell">
            <span class="avatar">{{ initials(props.user.name) }}</span>
            <span class="name">{{ props.user.name }}</span>
        </div>

        <span class="cell">{{ props.user.email }}</span>

        <div class="cell departments-cell">
            <span
                v-for="department in props.user.departments"
                :key="`${props.user.id}-${department}`"
                class="department-badge"
            >
                {{ department }}
            </span>
        </div>

        <div class="cell actions-cell">
            <button type="button" class="icon-btn neutral" title="Gerir departamentos" @click="emit('manageDepartments', props.user)">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M11 3h2v3.1a5.02 5.02 0 0 1 2.6 1.08l2.2-2.2 1.4 1.42-2.2 2.2A5.02 5.02 0 0 1 18.9 11H22v2h-3.1a5.02 5.02 0 0 1-1.08 2.6l2.2 2.2-1.42 1.4-2.2-2.2A5.02 5.02 0 0 1 13 18.9V22h-2v-3.1a5.02 5.02 0 0 1-2.6-1.08l-2.2 2.2-1.4-1.42 2.2-2.2A5.02 5.02 0 0 1 5.1 13H2v-2h3.1a5.02 5.02 0 0 1 1.08-2.6l-2.2-2.2 1.42-1.4 2.2 2.2A5.02 5.02 0 0 1 11 6.1V3Zm1 5a4 4 0 1 0 0 8 4 4 0 0 0 0-8Z" />
                </svg>
            </button>
            <button type="button" class="icon-btn edit" title="Editar usuario" @click="emit('edit', props.user)">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M4 17.25V20h2.75l8.09-8.09-2.75-2.75L4 17.25Zm12.71-9.04a1 1 0 0 0 0-1.42l-1.5-1.5a1 1 0 0 0-1.42 0l-1.17 1.17 2.75 2.75 1.34-1Z" />
                </svg>
            </button>
            <button
                type="button"
                class="icon-btn delete"
                :class="{ disabled: props.user.is_root_admin }"
                :disabled="props.user.is_root_admin"
                :title="props.user.is_root_admin ? 'Admin root nao pode ser removido' : 'Excluir usuario'"
                @click="onDelete"
            >
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M8 4h8l1 2h4v2H3V6h4l1-2Zm1 6h2v8H9v-8Zm4 0h2v8h-2v-8ZM6 8h12l-1 12H7L6 8Z" />
                </svg>
            </button>
        </div>
    </div>
</template>

<style scoped src="./styles/UsersTableRow.css"></style>
