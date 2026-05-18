<script setup>
import './styles/TabletDishCard.css';
import { formatPriceBRL } from '../utils/formatPrice.js';

defineProps({
    dish: { type: Object, required: true },
    cartQuantity: { type: Number, default: 0 },
});

defineEmits(['add', 'increment', 'decrement']);
</script>

<template>
    <div class="tablet-dish-card">
        <div class="tablet-dish-photo">
            <img
                v-if="dish.photo_url"
                :src="dish.photo_url"
                :alt="dish.name"
                class="tablet-dish-img"
            />
            <div v-else class="tablet-dish-placeholder">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#d1d5db" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" />
                    <polyline points="9 22 9 12 15 12 15 22" />
                </svg>
            </div>
        </div>
        <div class="tablet-dish-body">
            <p class="tablet-dish-name">{{ dish.name }}</p>
            <p v-if="dish.description" class="tablet-dish-desc">{{ dish.description }}</p>
            <p class="tablet-dish-price">{{ formatPriceBRL(dish.price) }}</p>
        </div>
        <div class="tablet-dish-cta">
            <button
                v-if="cartQuantity === 0"
                class="tablet-dish-add"
                :aria-label="'Adicionar ' + dish.name"
                @click="$emit('add')"
            >
                Adicionar
            </button>
            <div v-else class="tablet-dish-stepper">
                <button
                    class="tablet-dish-stepper-btn"
                    :aria-label="'Remover um ' + dish.name"
                    @click="$emit('decrement')"
                >−</button>
                <span class="tablet-dish-stepper-qty" aria-live="polite">{{ cartQuantity }}</span>
                <button
                    class="tablet-dish-stepper-btn"
                    :aria-label="'Adicionar mais ' + dish.name"
                    @click="$emit('increment')"
                >+</button>
            </div>
        </div>
    </div>
</template>
