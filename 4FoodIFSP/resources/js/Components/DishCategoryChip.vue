<script setup>
import { resolveCategoryIcon } from '../utils/dishCategoryIcons';

const props = defineProps({
    name: {
        type: String,
        required: true,
    },
    slug: {
        type: String,
        default: 'grid',
    },
    dishesCount: {
        type: Number,
        default: 0,
    },
    active: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['select']);

const iconKey = resolveCategoryIcon(props.slug);

function handleClick() {
    emit('select');
}
</script>

<template>
    <button
        type="button"
        class="category-chip"
        :class="{ active: props.active }"
        @click="handleClick"
    >
        <span class="chip-icon" aria-hidden="true">
            <svg v-if="iconKey === 'grid'" viewBox="0 0 24 24">
                <path d="M4 4h7v7H4V4Zm9 0h7v7h-7V4ZM4 13h7v7H4v-7Zm9 0h7v7h-7v-7Z" />
            </svg>
            <svg v-else-if="iconKey === 'utensils'" viewBox="0 0 24 24">
                <path d="M5 3v8c0 2.2 1.8 4 4 4v10h2V15c2.2 0 4-1.8 4-4V3h-2v8c0 1.1-.9 2-2 2s-2-.9-2-2V3H5Zm14 0v6h-2V3h2Zm0 8v10h-2V11h2Z" />
            </svg>
            <svg v-else-if="iconKey === 'cup'" viewBox="0 0 24 24">
                <path d="M4 7h14v2c0 3.3-2.7 6-6 6H8c-3.3 0-6-2.7-6-6V7Zm16 2h2v1c0 3.9-3.1 7-7 7h-1v2h1c4.4 0 8-3.6 8-8v-2Z" />
            </svg>
            <svg v-else-if="iconKey === 'cake'" viewBox="0 0 24 24">
                <path d="M4 10h16v2H4v-2Zm2 4h12v6H6v-6Zm1-8h2v4H7V6Zm4 0h2v4h-2V6Zm4 0h2v4h-2V6Z" />
            </svg>
            <svg v-else-if="iconKey === 'burger'" viewBox="0 0 24 24">
                <path d="M4 8h16v2H4V8Zm0 4h16v2H4v-2Zm2 4h12v2H6v-2Z" />
            </svg>
            <svg v-else viewBox="0 0 24 24">
                <path d="M12 3 4 9v12h16V9l-8-6Zm0 2.5L18 10v9H6v-9l6-4.5Z" />
            </svg>
        </span>
        <span class="chip-text">
            <span class="chip-name">{{ props.name }}</span>
            <span class="chip-count">{{ props.dishesCount }} itens</span>
        </span>
    </button>
</template>

<style scoped src="./styles/DishCategoryChip.css"></style>
