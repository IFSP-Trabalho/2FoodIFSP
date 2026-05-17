<script setup>
import { useForm } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted } from 'vue';
import { resolveDepartmentColor } from '../utils/departmentOptions';

const props = defineProps({
    department: {
        type: Object,
        required: true,
    },
});

const emit = defineEmits(['close']);

function normalizeHex(value) {
    const hex = String(value ?? '').trim().toUpperCase();
    return /^#[0-9A-F]{6}$/.test(hex) ? hex : '';
}

const resolvedColor = resolveDepartmentColor(props.department);

const form = useForm({
    color: normalizeHex(props.department.color) || normalizeHex(resolvedColor) || '#5E6B7A',
});

const initialColor = normalizeHex(props.department.color) || normalizeHex(resolvedColor);

const hasChanges = computed(() => normalizeHex(form.color) !== initialColor);

function handleCancel() {
    form.reset();
    form.color = initialColor || '#5E6B7A';
    form.clearErrors();
    emit('close');
}

function handleOverlayClick() {
    handleCancel();
}

function handleSubmit() {
    form.transform((data) => ({
        color: normalizeHex(data.color),
    })).put(`/admin/cadastros/departments/${props.department.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            emit('close');
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
        aria-labelledby="dept-color-panel-title"
    >
        <header class="admin-modal-head">
            <h3 id="dept-color-panel-title">
                Editar cor do departamento
            </h3>
            <p>{{ props.department.label }}</p>
        </header>

        <form class="admin-modal-form" @submit.prevent="handleSubmit">
            <label class="admin-modal-field">
                <div class="admin-modal-input-wrap admin-modal-color-field">
                    <span class="admin-modal-floating-label">Cor do departamento</span>
                    <input
                        v-model="form.color"
                        type="color"
                        :aria-label="`Cor do departamento ${props.department.label}`"
                    >
                    <span
                        class="color-preview-swatch"
                        :style="{ background: form.color }"
                        aria-hidden="true"
                    />
                    <span class="color-preview-hex">{{ form.color }}</span>
                </div>
                <small v-if="form.errors.color">{{ form.errors.color }}</small>
            </label>

            <footer class="admin-modal-actions">
                <button type="button" class="secondary" :disabled="form.processing" @click="handleCancel">
                    Sair
                </button>
                <button
                    type="submit"
                    class="primary"
                    :disabled="!hasChanges || form.processing"
                >
                    {{ form.processing ? 'Salvando...' : 'Salvar' }}
                </button>
            </footer>
        </form>
    </section>
</template>

<style scoped src="./styles/DepartmentColorEditPanel.css"></style>
