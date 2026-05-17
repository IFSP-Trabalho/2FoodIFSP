<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue';

const props = defineProps({
    modelValue: {
        type: String,
        default: '',
    },
    categories: {
        type: Array,
        default: () => [],
    },
    label: {
        type: String,
        default: 'Categoria',
    },
    error: {
        type: String,
        default: '',
    },
});

const emit = defineEmits(['update:modelValue']);

const isOpen = ref(false);
const rootRef = ref(null);

const options = computed(() =>
    [...props.categories].sort((a, b) =>
        String(a.name ?? '').localeCompare(String(b.name ?? ''), 'pt-BR')
    )
);

const selectedOption = computed(() =>
    options.value.find((category) => category.id === String(props.modelValue)) ?? null
);

const hasValue = computed(() => props.modelValue !== '' && props.modelValue != null);

function toggleOpen(event) {
    event?.stopPropagation?.();
    isOpen.value = !isOpen.value;
}

function closeMenu() {
    isOpen.value = false;
}

function isSelected(categoryId) {
    return String(props.modelValue) === String(categoryId);
}

function onSelectCategory(categoryId, event) {
    event.stopPropagation();
    const id = String(categoryId);

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
        class="category-select"
        :class="{ 'is-open': isOpen, 'is-filled': hasValue }"
    >
        <div
            class="category-select-trigger"
            role="combobox"
            tabindex="0"
            :aria-expanded="isOpen"
            aria-haspopup="listbox"
            @click="toggleOpen"
            @keydown.enter.prevent="toggleOpen"
            @keydown.space.prevent="toggleOpen"
        >
            <span class="admin-modal-floating-label">{{ label }}</span>

            <span class="category-select-value">
                <span v-if="selectedOption" class="category-select-tag">
                    {{ selectedOption.name }}
                    <button
                        type="button"
                        class="category-select-tag-remove"
                        aria-label="Remover categoria"
                        @click="clearSelection"
                    >
                        ×
                    </button>
                </span>
                <span v-else class="category-select-placeholder">Selecione</span>
            </span>

            <svg class="category-select-chevron" viewBox="0 0 24 24" aria-hidden="true">
                <path d="M7 10l5 5 5-5H7Z" />
            </svg>
        </div>

        <div v-if="isOpen" class="category-select-menu" role="listbox">
            <p v-if="options.length === 0" class="category-select-empty">
                Nenhuma categoria disponivel.
            </p>
            <button
                v-for="category in options"
                :key="category.id"
                type="button"
                class="category-select-option"
                role="option"
                :aria-selected="isSelected(category.id)"
                @click="onSelectCategory(category.id, $event)"
            >
                <span
                    class="category-select-checkbox"
                    :class="{ 'is-checked': isSelected(category.id) }"
                    aria-hidden="true"
                >
                    <svg v-if="isSelected(category.id)" viewBox="0 0 16 16">
                        <path d="M6.2 11.4 3.4 8.6l1-1 1.8 1.8 5.4-5.4 1 1-6.4 6.4Z" />
                    </svg>
                </span>
                <span>{{ category.name }}</span>
            </button>
        </div>

        <small v-if="error" class="category-select-error">{{ error }}</small>
    </div>
</template>

<style scoped src="./styles/DishCategorySelect.css"></style>
