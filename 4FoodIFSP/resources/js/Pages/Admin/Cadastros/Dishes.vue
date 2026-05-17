<script setup>
import { router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AppSidebar from '../../../Components/AppSidebar.vue';
import DishCard from '../../../Components/DishCard.vue';
import DishCategoryChip from '../../../Components/DishCategoryChip.vue';
import DishCreatePanel from '../../../Components/DishCreatePanel.vue';

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

const page = usePage();
const selectedCategoryId = ref(null);
const isCreatePanelOpen = ref(false);
const editingDish = ref(null);
const pendingDeleteDish = ref(null);
const isDeleting = ref(false);

const flashSuccess = computed(() => page.props.flash?.success ?? '');
const deleteError = computed(() => page.props.errors?.delete ?? '');

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

function openCreatePanel() {
    editingDish.value = null;
    isCreatePanelOpen.value = true;
}

function closeCreatePanel() {
    isCreatePanelOpen.value = false;
}

function handleEditDish(dish) {
    isCreatePanelOpen.value = false;
    editingDish.value = dish;
}

function closeEditPanel() {
    editingDish.value = null;
}

function handleDeleteDish(dish) {
    pendingDeleteDish.value = dish;
}

function cancelDelete() {
    pendingDeleteDish.value = null;
}

function confirmDelete() {
    if (!pendingDeleteDish.value || isDeleting.value) {
        return;
    }

    isDeleting.value = true;

    router.delete(`/admin/cadastros/dishes/${pendingDeleteDish.value.id}`, {
        preserveScroll: true,
        onFinish: () => {
            isDeleting.value = false;
            pendingDeleteDish.value = null;
        },
    });
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
                        title="Cadastrar novo prato"
                        @click="openCreatePanel"
                    >
                        Criar prato
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
                            @edit="handleEditDish"
                            @delete="handleDeleteDish"
                        />
                    </div>
                </section>
            </div>
        </div>

        <DishCreatePanel
            v-if="isCreatePanelOpen"
            :categories="props.categories"
            :initial-category-id="selectedCategoryId"
            @close="closeCreatePanel"
        />

        <DishCreatePanel
            v-if="editingDish"
            mode="edit"
            :dish="editingDish"
            :categories="props.categories"
            @close="closeEditPanel"
        />

        <div v-if="pendingDeleteDish" class="confirm-delete-overlay" @click.self="cancelDelete">
            <div class="confirm-delete-dialog">
                <div class="confirm-delete-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24">
                        <path d="M8 4h8l1 2h4v2H3V6h4l1-2Zm1 6h2v8H9v-8Zm4 0h2v8h-2v-8ZM6 8h12l-1 12H7L6 8Z" />
                    </svg>
                </div>
                <h3>Excluir prato</h3>
                <p>
                    Deseja excluir <strong>{{ pendingDeleteDish.name }}</strong>?
                    Esta acao nao pode ser desfeita.
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
    </div>
</template>

<style scoped src="./styles/Dishes.css"></style>
