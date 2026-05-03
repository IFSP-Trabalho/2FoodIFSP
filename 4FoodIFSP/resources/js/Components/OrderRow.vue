<script setup>
defineProps({
    mesa: {
        type: String,
        required: true,
    },
    itens: {
        type: String,
        required: true,
    },
    status: {
        type: String,
        required: true,
    },
    statusClass: {
        type: String,
        required: true,
    },
    total: {
        type: String,
        required: true,
    },
});
</script>

<template>
    <div class="order-row">
        <span class="mesa">{{ mesa }}</span>
        <span class="itens">{{ itens }}</span>
        <span class="status" :class="statusClass">{{ status }}</span>
        <strong class="total">{{ total }}</strong>
    </div>
</template>

<style scoped src="./styles/OrderRow.css"></style>
