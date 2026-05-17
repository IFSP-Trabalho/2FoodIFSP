<script setup>
import { formatPriceBRL } from '../utils/formatPrice';

const props = defineProps({
    dish: {
        type: Object,
        required: true,
    },
});
</script>

<template>
    <article class="dish-card" :class="{ inactive: !props.dish.active }">
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
