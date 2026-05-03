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

<style scoped>
.order-row {
    display: grid;
    grid-template-columns: 110px 1fr 120px 80px;
    gap: 10px;
    align-items: center;
    padding: 10px 12px;
    border-top: 1px solid #eef0f2;
    font-size: 12px;
}

.mesa {
    color: #1a1b21;
    font-weight: 600;
}

.itens {
    color: #5e6270;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.status {
    justify-self: start;
    border-radius: 999px;
    padding: 3px 8px;
    font-size: 10px;
    font-weight: 600;
}

.status.badge-amber {
    background: #faeeda;
    color: #633806;
}

.status.badge-green {
    background: #e1f5ee;
    color: #085041;
}

.status.badge-gray {
    background: #eceef2;
    color: #4c5360;
}

.total {
    justify-self: end;
    font-family: 'JetBrains Mono', 'DM Mono', monospace;
    color: #121318;
    font-weight: 500;
}

@media (max-width: 980px) {
    .order-row {
        grid-template-columns: 1fr;
        gap: 6px;
    }

    .total {
        justify-self: start;
    }
}
</style>
