<script setup>
import { computed } from 'vue';

const props = defineProps({
    order: {
        type: Object,
        required: true,
    },
    status: {
        type: String,
        required: true,
    },
});

const emit = defineEmits(['advance']);

const actionLabel = computed(() => ({
    pending: 'Preparar pedido',
    in_progress: 'Finalizar pedido',
    ready: 'Pedido finalizado',
}[props.status]));

const actionDisabled = computed(() => props.status === 'ready');

const cardClass = computed(() => ({
    'card--pending': props.status === 'pending',
    'card--in-progress': props.status === 'in_progress',
    'card--ready': props.status === 'ready',
}));

const itemClass = computed(() => props.status === 'ready' ? 'item--done' : '');
</script>

<template>
    <div class="order-card" :class="cardClass">
        <div class="card-head">
            <span class="order-id">#{{ order.id }}</span>
            <span v-if="status === 'ready'" class="badge-feito">FEITO</span>
        </div>

        <p class="mesa-label">{{ order.mesa }}</p>

        <ul class="items-list">
            <li
                v-for="item in order.items"
                :key="`${order.id}-${item.name}`"
                class="item-row"
                :class="itemClass"
            >
                <span class="item-qty">{{ item.qty }}x</span>
                <span class="item-name">{{ item.name }}</span>
            </li>
        </ul>

        <p v-if="order.note_summary" class="note" :class="itemClass">
            {{ order.note_summary }}
        </p>

        <button
            type="button"
            class="action-btn"
            :class="`action-btn--${status}`"
            :disabled="actionDisabled"
            @click="!actionDisabled && emit('advance', order.id)"
        >
            {{ actionLabel }}
        </button>
    </div>
</template>

<style scoped src="./styles/OrderCard.css"></style>
