<script setup>
import './styles/TabletCart.css';
import { formatPriceBRL } from '../utils/formatPrice.js';
import TabletCartLine from './TabletCartLine.vue';

defineProps({
    cart: { type: Array, required: true },
    total: { type: Number, required: true },
});

const emit = defineEmits(['increment', 'decrement', 'edit-note', 'confirm']);
</script>

<template>
    <div class="tablet-cart">
        <div v-if="cart.length === 0" class="tablet-cart-empty">
            <p class="tablet-cart-empty-text">Seu pedido está vazio</p>
            <p class="tablet-cart-empty-hint">Adicione itens do cardápio</p>
        </div>
        <div v-else class="tablet-cart-lines">
            <TabletCartLine
                v-for="line in cart"
                :key="line.dishId"
                :line="line"
                @increment="emit('increment', line.dishId)"
                @decrement="emit('decrement', line.dishId)"
                @edit-note="emit('edit-note', line.dishId)"
            />
        </div>
        <div class="tablet-cart-footer">
            <div class="tablet-cart-total-row">
                <span class="tablet-cart-total-label">Total</span>
                <span class="tablet-cart-total-value">{{ formatPriceBRL(total) }}</span>
            </div>
            <button
                class="tablet-cart-confirm"
                :disabled="cart.length === 0"
                aria-label="Confirmar pedido"
                @click="emit('confirm')"
            >
                Confirmar pedido
            </button>
        </div>
    </div>
</template>
