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

<style scoped>
.order-card {
    background: #fff;
    border-radius: 8px;
    border-left: 3px solid transparent;
    border: 1px solid #eceef0;
    padding: 14px;
    margin-bottom: 12px;
}

.card--pending {
    border-left-color: #d85a30;
}

.card--in-progress {
    border-left-color: #ef9f27;
}

.card--ready {
    border-left-color: #1d9e75;
    background: #e1f5ee;
}

.card-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 8px;
}

.order-id {
    font-size: 12px;
    font-weight: 700;
    font-family: 'JetBrains Mono', 'DM Mono', monospace;
    color: #17181e;
}

.badge-feito {
    background: #1d9e75;
    color: #fff;
    font-size: 10px;
    font-weight: 700;
    padding: 2px 6px;
    border-radius: 4px;
}

.mesa-label {
    margin: 0 0 10px;
    font-size: 13px;
    font-weight: 600;
    color: #17181e;
}

.items-list {
    margin: 0;
    padding: 0;
    list-style: none;
    display: grid;
    gap: 4px;
}

.item-row {
    font-size: 13px;
    color: #1f242e;
}

.item-qty {
    font-weight: 600;
    margin-right: 4px;
}

.note {
    margin: 8px 0 0;
    font-size: 12px;
    color: #6d727f;
}

.item--done {
    text-decoration: line-through;
    opacity: 0.5;
}

.action-btn {
    width: 100%;
    padding: 10px;
    border-radius: 6px;
    border: none;
    font-weight: 600;
    cursor: pointer;
    margin-top: 12px;
}

.action-btn--pending {
    background: #1a1a1a;
    color: #fff;
}

.action-btn--in_progress {
    background: #1d9e75;
    color: #fff;
}

.action-btn--ready {
    background: #e0e0e0;
    color: #aaa;
    cursor: default;
}
</style>
