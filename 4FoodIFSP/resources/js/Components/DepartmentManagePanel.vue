<script setup>
import { router } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import DepartmentSelect from './DepartmentSelect.vue';

const props = defineProps({
    user: {
        type: Object,
        required: true,
    },
    departments: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(['close']);

const selectedDepartmentId = ref('');
const initialDepartmentId = ref('');
const isSaving = ref(false);
const panelError = ref('');

const hasChanges = computed(() => selectedDepartmentId.value !== initialDepartmentId.value);

const canSave = computed(() => (
    hasChanges.value
    && selectedDepartmentId.value !== ''
    && !isSaving.value
));

function syncSelectionFromUser() {
    const currentId = props.user.department_id
        ? String(props.user.department_id)
        : '';
    selectedDepartmentId.value = currentId;
    initialDepartmentId.value = currentId;
    panelError.value = '';
}

function handleCancel() {
    syncSelectionFromUser();
    emit('close');
}

function handleOverlayClick() {
    handleCancel();
}

function handleSave() {
    if (!canSave.value) {
        return;
    }

    isSaving.value = true;
    panelError.value = '';

    router.put(
        `/admin/cadastros/users/${props.user.id}/departments`,
        { department_id: selectedDepartmentId.value },
        {
            preserveScroll: true,
            onError: (errors) => {
                panelError.value = errors.department_id
                    ?? Object.values(errors)[0]
                    ?? 'Nao foi possivel atualizar o departamento.';
            },
            onFinish: () => {
                isSaving.value = false;
            },
            onSuccess: () => {
                emit('close');
            },
        },
    );
}

function onKeydown(event) {
    if (event.key === 'Escape') {
        handleCancel();
    }
}

watch(() => props.user, syncSelectionFromUser, { immediate: true });

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
        class="admin-modal admin-modal--compact"
        role="dialog"
        aria-modal="true"
        aria-labelledby="dept-panel-title"
    >
        <header class="admin-modal-head">
            <h3 id="dept-panel-title">
                Gerir departamento
            </h3>
            <p>{{ props.user.name }}</p>
        </header>

        <p v-if="panelError" class="admin-modal-error">
            {{ panelError }}
        </p>

        <div class="admin-modal-form">
            <div class="admin-modal-field">
                <DepartmentSelect
                    v-model="selectedDepartmentId"
                    :departments="props.departments"
                    label="Departamento"
                />
            </div>

            <footer class="admin-modal-actions">
                <button type="button" class="secondary" :disabled="isSaving" @click="handleCancel">
                    Sair
                </button>
                <button type="button" class="primary" :disabled="!canSave" @click="handleSave">
                    {{ isSaving ? 'Salvando...' : 'Salvar' }}
                </button>
            </footer>
        </div>
    </section>
</template>

<style scoped>
.admin-modal--compact {
    width: min(460px, calc(100vw - 32px));
}
</style>
