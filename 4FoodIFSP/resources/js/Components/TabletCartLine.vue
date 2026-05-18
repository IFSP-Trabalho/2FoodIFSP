<script setup>
import './styles/TabletCartLine.css';
import { formatPriceBRL } from '../utils/formatPrice.js';

defineProps({
    line: { type: Object, required: true },
});

defineEmits(['increment', 'decrement', 'edit-note']);
</script>

<template>
    <div class="tablet-cart-line">
        <div class="cart-line-top">
            <span class="cart-line-name">{{ line.name }}</span>
            <span class="cart-line-price">{{ formatPriceBRL(line.unitPrice * line.quantity) }}</span>
        </div>
        <p v-if="line.note" class="cart-line-note">{{ line.note }}</p>
        <div class="cart-line-controls">
            <div class="cart-line-stepper">
                <button
                    class="cart-line-btn"
                    :aria-label="'Remover um ' + line.name"
                    @click="$emit('decrement')"
                >−</button>
                <span class="cart-line-qty" aria-live="polite">{{ line.quantity }}</span>
                <button
                    class="cart-line-btn"
                    :aria-label="'Adicionar mais ' + line.name"
                    @click="$emit('increment')"
                >+</button>
            </div>
            <button
                class="cart-line-edit-note"
                aria-label="Editar observação"
                @click="$emit('edit-note')"
            >
                {{ line.note ? 'Editar obs.' : 'Obs.' }}
            </button>
        </div>
    </div>
</template>
