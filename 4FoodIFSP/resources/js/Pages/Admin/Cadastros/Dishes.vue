<script setup>
import { computed, ref } from 'vue';
import AppSidebar from '../../../Components/AppSidebar.vue';
import DishCard from '../../../Components/DishCard.vue';
import DishCategoryChip from '../../../Components/DishCategoryChip.vue';

const props = defineProps({
    categories: {
        type: Array,
        default: () => [],
    },
    dishes: {
        type: Array,
        default: () => [],
    },
});

const selectedCategoryId = ref(null);

const filteredDishes = computed(() => {
    const list = selectedCategoryId.value === null
        ? props.dishes
        : props.dishes.filter((dish) => dish.category_id === selectedCategoryId.value);

    return [...list].sort((a, b) =>
        String(a.name ?? '').localeCompare(String(b.name ?? ''), 'pt-BR')
    );
});

const itemCount = computed(() => filteredDishes.value.length);

const totalDishCount = computed(() => props.dishes.length);

const emptyMessage = computed(() => {
    if (props.dishes.length === 0) {
        return 'Nenhum prato cadastrado.';
    }

    if (filteredDishes.value.length === 0) {
        return 'Nenhum prato neste menu.';
    }

    return '';
});

function selectCategory(categoryId) {
    selectedCategoryId.value = categoryId;
}
</script>

<template>
    <div class="shell">
        <AppSidebar active="cadastros" />

        <div class="main">
            <header class="topbar">
                <h1>Menu ({{ itemCount }} itens)</h1>
                <div class="head-actions">
                    <button
                        type="button"
                        class="btn-secondary"
                        title="Criar menu (em breve)"
                    >
                        Criar menu
                    </button>
                    <button
                        type="button"
                        class="btn-primary"
                        disabled
                        aria-disabled="true"
                        title="Criar prato (em breve)"
                    >
                        Criar prato
                    </button>
                </div>
            </header>

            <div class="content">
                <section class="category-strip" aria-label="Menus">
                    <button
                        type="button"
                        class="category-chip all-chip"
                        :class="{ active: selectedCategoryId === null }"
                        @click="selectCategory(null)"
                    >
                        <span class="chip-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24">
                                <path d="M4 4h7v7H4V4Zm9 0h7v7h-7V4ZM4 13h7v7H4v-7Zm9 0h7v7h-7v-7Z" />
                            </svg>
                        </span>
                        <span class="chip-text">
                            <span class="chip-name">Todos</span>
                            <span class="chip-count">{{ totalDishCount }} itens</span>
                        </span>
                    </button>

                    <DishCategoryChip
                        v-for="category in props.categories"
                        :key="category.id"
                        :name="category.name"
                        :slug="category.slug"
                        :dishes-count="category.dishes_count"
                        :active="selectedCategoryId === category.id"
                        @select="selectCategory(category.id)"
                    />
                </section>

                <section class="dishes-grid-wrap">
                    <p v-if="emptyMessage" class="empty-state">
                        {{ emptyMessage }}
                    </p>

                    <div v-else class="dishes-grid">
                        <DishCard
                            v-for="dish in filteredDishes"
                            :key="dish.id"
                            :dish="dish"
                        />
                    </div>
                </section>
            </div>
        </div>
    </div>
</template>

<style scoped src="./styles/Dishes.css"></style>
