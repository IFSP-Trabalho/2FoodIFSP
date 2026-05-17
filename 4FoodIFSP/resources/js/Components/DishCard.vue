<script setup>
import { onMounted, onUnmounted, ref } from 'vue';
import { formatPriceBRL } from '../utils/formatPrice';

const props = defineProps({
    dish: {
        type: Object,
        required: true,
    },
});

const emit = defineEmits(['edit', 'delete']);

const isMenuOpen = ref(false);
const actionsRef = ref(null);

function openMenu() {
    isMenuOpen.value = true;
}

function closeMenu() {
    isMenuOpen.value = false;
}

function toggleMenu() {
    if (isMenuOpen.value) {
        closeMenu();
        return;
    }

    openMenu();
}

function onEdit() {
    closeMenu();
    emit('edit', props.dish);
}

function onDelete() {
    closeMenu();
    emit('delete', props.dish);
}

function onDocumentClick(event) {
    if (!isMenuOpen.value) {
        return;
    }

    const root = actionsRef.value;

    if (root && !root.contains(event.target)) {
        closeMenu();
    }
}

function onKeydown(event) {
    if (event.key === 'Escape' && isMenuOpen.value) {
        closeMenu();
    }
}

onMounted(() => {
    document.addEventListener('click', onDocumentClick);
    document.addEventListener('keydown', onKeydown);
});

onUnmounted(() => {
    document.removeEventListener('click', onDocumentClick);
    document.removeEventListener('keydown', onKeydown);
});
</script>

<template>
    <article
        class="dish-card"
        :class="{ inactive: !props.dish.active, 'is-actions-open': isMenuOpen }"
    >
        <div class="dish-photo-wrap">
            <img
                v-if="props.dish.photo_url"
                :src="props.dish.photo_url"
                :alt="props.dish.name"
                class="dish-photo"
            >
            <div v-else class="dish-photo-placeholder" aria-hidden="true">
                <svg viewBox="0 0 24 24">
                    <path d="M8.1 10.5 10 4h4l1.9 6.5H8.1ZM6 13h12l-1.2 7H7.2L6 13Zm2.5-9h7L17 8H7l1.5-4Z" />
                </svg>
            </div>
            <span v-if="!props.dish.active" class="inactive-badge">Inativo</span>

            <div ref="actionsRef" class="dish-card-actions">
                <button
                    type="button"
                    class="dish-card-actions-btn"
                    aria-label="Acoes do prato"
                    :aria-expanded="isMenuOpen ? 'true' : 'false'"
                    aria-haspopup="menu"
                    @click.stop="toggleMenu"
                >
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <circle cx="12" cy="5" r="1.5" />
                        <circle cx="12" cy="12" r="1.5" />
                        <circle cx="12" cy="19" r="1.5" />
                    </svg>
                </button>

                <div
                    v-if="isMenuOpen"
                    class="dish-card-actions-menu"
                    role="menu"
                >
                    <button
                        type="button"
                        class="dish-card-actions-item"
                        role="menuitem"
                        @click="onEdit"
                    >
                        Editar
                    </button>
                    <button
                        type="button"
                        class="dish-card-actions-item danger"
                        role="menuitem"
                        @click="onDelete"
                    >
                        Apagar
                    </button>
                </div>
            </div>
        </div>

        <div class="dish-body">
            <h3 class="dish-name">{{ props.dish.name }}</h3>
            <div class="dish-meta">
                <span class="dish-category-tag">{{ props.dish.category_name }}</span>
                <span class="dish-price">{{ formatPriceBRL(props.dish.price) }}</span>
            </div>
        </div>
    </article>
</template>

<style scoped src="./styles/DishCard.css"></style>
