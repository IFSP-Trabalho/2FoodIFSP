<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { buildOrderedDepartments } from '../utils/departmentOptions';

const props = defineProps({
    modelValue: {
        type: String,
        default: '',
    },
    departments: {
        type: Array,
        default: () => [],
    },
    label: {
        type: String,
        default: 'Departamento',
    },
    error: {
        type: String,
        default: '',
    },
});

const emit = defineEmits(['update:modelValue']);

const isOpen = ref(false);
const rootRef = ref(null);

const options = computed(() => buildOrderedDepartments(props.departments));

const selectedOption = computed(() => (
    options.value.find((department) => department.id === String(props.modelValue)) ?? null
));

const hasValue = computed(() => props.modelValue !== '' && props.modelValue != null);

function toggleOpen(event) {
    event?.stopPropagation?.();
    isOpen.value = !isOpen.value;
}

function closeMenu() {
    isOpen.value = false;
}

function isSelected(departmentId) {
    return String(props.modelValue) === String(departmentId);
}

function onToggleDepartment(departmentId, event) {
    event.stopPropagation();
    const id = String(departmentId);

    if (props.modelValue === id) {
        emit('update:modelValue', '');
    } else {
        emit('update:modelValue', id);
    }

    closeMenu();
}

function clearSelection(event) {
    event.stopPropagation();
    emit('update:modelValue', '');
}

function onDocumentClick(event) {
    if (!rootRef.value?.contains(event.target)) {
        closeMenu();
    }
}

onMounted(() => {
    document.addEventListener('click', onDocumentClick);
});

onUnmounted(() => {
    document.removeEventListener('click', onDocumentClick);
});
</script>

<template>
    <div
        ref="rootRef"
        class="dept-select"
        :class="{ 'is-open': isOpen, 'is-filled': hasValue }"
    >
        <div
            class="dept-select-trigger"
            role="combobox"
            tabindex="0"
            :aria-expanded="isOpen"
            aria-haspopup="listbox"
            @click="toggleOpen"
            @keydown.enter.prevent="toggleOpen"
            @keydown.space.prevent="toggleOpen"
        >
            <span class="admin-modal-floating-label">{{ label }}</span>

            <span class="dept-select-value">
                <span v-if="selectedOption" class="dept-select-tag">
                    {{ selectedOption.label }}
                    <button
                        type="button"
                        class="dept-select-tag-remove"
                        aria-label="Remover departamento"
                        @click="clearSelection"
                    >
                        ×
                    </button>
                </span>
                <span v-else class="dept-select-placeholder">Selecione</span>
            </span>

            <svg class="dept-select-chevron" viewBox="0 0 24 24" aria-hidden="true">
                <path d="M7 10l5 5 5-5H7Z" />
            </svg>
        </div>

        <div v-if="isOpen" class="dept-select-menu" role="listbox">
            <p v-if="options.length === 0" class="dept-select-empty">
                Nenhum departamento disponivel.
            </p>
            <button
                v-for="department in options"
                :key="department.id"
                type="button"
                class="dept-select-option"
                role="option"
                :aria-selected="isSelected(department.id)"
                @click="onToggleDepartment(department.id, $event)"
            >
                <span
                    class="dept-select-color-swatch"
                    :style="{ background: department.color }"
                    aria-hidden="true"
                />
                <span
                    class="dept-select-checkbox"
                    :class="{ 'is-checked': isSelected(department.id) }"
                    aria-hidden="true"
                >
                    <svg v-if="isSelected(department.id)" viewBox="0 0 16 16">
                        <path d="M6.2 11.4 3.4 8.6l1-1 1.8 1.8 5.4-5.4 1 1-6.4 6.4Z" />
                    </svg>
                </span>
                <span>{{ department.label }}</span>
            </button>
        </div>

        <small v-if="error" class="dept-select-error">{{ error }}</small>
    </div>
</template>

<style scoped src="./styles/DepartmentSelect.css"></style>
