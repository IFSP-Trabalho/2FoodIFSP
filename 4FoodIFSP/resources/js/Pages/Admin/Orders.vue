<script setup>
import { computed, ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import AppSidebar from '../../Components/AppSidebar.vue';
import AppTopbar from '../../Components/AppTopbar.vue';
import OrderCard from '../../Components/OrderCard.vue';

const props = defineProps({
    orders: {
        type: Object,
        required: true,
    },
    history: {
        type: Array,
        default: () => [],
    },
    filters: {
        type: Object,
        default: () => ({
            date_from: '',
            date_to: '',
        }),
    },
    date: {
        type: String,
        required: true,
    },
});

const activeTab = ref('live');
const search = ref('');
const dateFrom = ref(props.filters.date_from);
const dateTo = ref(props.filters.date_to);
const statusKeys = ['pending', 'in_progress', 'ready'];
const localOrders = ref(cloneOrders(props.orders));
const dragState = ref({ orderId: null, fromStatus: null });
const dragOverStatus = ref(null);

watch(
    () => props.filters,
    (nextFilters) => {
        dateFrom.value = nextFilters.date_from;
        dateTo.value = nextFilters.date_to;
    }
);

function cloneOrders(source) {
    return statusKeys.reduce((accumulator, status) => {
        accumulator[status] = (source[status] ?? []).map((order) => ({
            ...order,
            items: (order.items ?? []).map((item) => ({ ...item })),
        }));
        return accumulator;
    }, {});
}

function filterBySearch(list) {
    if (!search.value) {
        return list;
    }

    const term = search.value.toLowerCase();

    return list.filter((order) => order.items.some((item) => item.name.toLowerCase().includes(term)));
}

const pendente = computed(() => filterBySearch(localOrders.value.pending ?? []));
const preparando = computed(() => filterBySearch(localOrders.value.in_progress ?? []));
const finalizados = computed(() => filterBySearch(localOrders.value.ready ?? []));

function persistStatus(orderId, status) {
    router.patch(`/admin/orders/${orderId}/status`, { status }, {
        preserveState: true,
        preserveScroll: true,
    });
}

function moveOrder(orderId, fromStatus, toStatus) {
    if (fromStatus === toStatus) {
        return false;
    }

    const sourceList = localOrders.value[fromStatus] ?? [];
    const targetList = localOrders.value[toStatus] ?? [];
    const sourceIndex = sourceList.findIndex((order) => order.id === orderId);

    if (sourceIndex === -1) {
        return false;
    }

    const [order] = sourceList.splice(sourceIndex, 1);
    targetList.unshift(order);

    return true;
}

function advanceStatus(orderId, currentStatus) {
    if (currentStatus === 'pending') {
        if (moveOrder(orderId, 'pending', 'in_progress')) {
            persistStatus(orderId, 'in_progress');
        }
        return;
    }

    if (currentStatus === 'in_progress') {
        if (moveOrder(orderId, 'in_progress', 'ready')) {
            persistStatus(orderId, 'ready');
        }
    }
}

function canDropOn(status) {
    return status !== 'ready';
}

function onDragStart(orderId, fromStatus, event) {
    dragState.value = { orderId, fromStatus };
    event.dataTransfer.effectAllowed = 'move';
    event.dataTransfer.setData('text/plain', orderId);
}

function onDragEnd() {
    dragState.value = { orderId: null, fromStatus: null };
    dragOverStatus.value = null;
}

function onColumnDragOver(status, event) {
    if (!canDropOn(status)) {
        return;
    }

    event.preventDefault();
    dragOverStatus.value = status;
}

function onColumnDragLeave(status) {
    if (dragOverStatus.value === status) {
        dragOverStatus.value = null;
    }
}

function onColumnDrop(status, event) {
    if (!canDropOn(status)) {
        return;
    }

    event.preventDefault();

    const { orderId, fromStatus } = dragState.value;
    if (!orderId || !fromStatus) {
        return;
    }

    if (moveOrder(orderId, fromStatus, status)) {
        persistStatus(orderId, status);
    }

    onDragEnd();
}

function filterHistory() {
    router.get('/admin/orders/history', {
        date_from: dateFrom.value,
        date_to: dateTo.value,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
}

function statusLabel(status) {
    if (status === 'ready') {
        return 'Pronto';
    }

    return 'Cancelado';
}
</script>

<template>
    <div class="shell">
        <AppSidebar active="orders" />
        <div class="main">
            <AppTopbar title="Pedidos" :subtitle="props.date" role-badge="Admin" />

            <div class="content">
                <div class="orders-toolbar">
                    <div class="tabs">
                        <button
                            type="button"
                            :class="{ active: activeTab === 'live' }"
                            @click="activeTab = 'live'"
                        >
                            Pedidos ao vivo
                        </button>
                        <button
                            type="button"
                            :class="{ active: activeTab === 'history' }"
                            @click="activeTab = 'history'"
                        >
                            Historico de pedidos
                        </button>
                    </div>

                    <div class="toolbar-right">
                        <input
                            v-model="search"
                            type="text"
                            placeholder="Pesquisar Prato"
                            class="search-input"
                        >
                        <button type="button" class="bell-btn" disabled aria-label="Notificacoes">
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M12 3a6 6 0 0 0-6 6v3.6L4.4 16a1 1 0 0 0 .8 1.6h13.6a1 1 0 0 0 .8-1.6L18 12.6V9a6 6 0 0 0-6-6Zm0 19a3 3 0 0 0 2.82-2h-5.64A3 3 0 0 0 12 22Z" />
                            </svg>
                        </button>
                    </div>
                </div>

                <div v-if="activeTab === 'live'" class="kanban">
                    <div
                        class="kanban-col"
                        :class="{ 'kanban-col--drop-target': dragOverStatus === 'pending' }"
                        @dragover="onColumnDragOver('pending', $event)"
                        @dragleave="onColumnDragLeave('pending')"
                        @drop="onColumnDrop('pending', $event)"
                    >
                        <div class="col-header">
                            <span class="col-title">Pendente</span>
                            <span class="col-badge">{{ pendente.length }}</span>
                        </div>
                        <div
                            v-for="order in pendente"
                            :key="order.id"
                            class="order-drag-wrapper"
                            draggable="true"
                            @dragstart="onDragStart(order.id, 'pending', $event)"
                            @dragend="onDragEnd"
                        >
                            <OrderCard
                                :order="order"
                                status="pending"
                                @advance="advanceStatus(order.id, 'pending')"
                            />
                        </div>
                    </div>

                    <div
                        class="kanban-col"
                        :class="{ 'kanban-col--drop-target': dragOverStatus === 'in_progress' }"
                        @dragover="onColumnDragOver('in_progress', $event)"
                        @dragleave="onColumnDragLeave('in_progress')"
                        @drop="onColumnDrop('in_progress', $event)"
                    >
                        <div class="col-header">
                            <span class="col-title">Preparando</span>
                            <span class="col-badge">{{ preparando.length }}</span>
                        </div>
                        <div
                            v-for="order in preparando"
                            :key="order.id"
                            class="order-drag-wrapper"
                            draggable="true"
                            @dragstart="onDragStart(order.id, 'in_progress', $event)"
                            @dragend="onDragEnd"
                        >
                            <OrderCard
                                :order="order"
                                status="in_progress"
                                @advance="advanceStatus(order.id, 'in_progress')"
                            />
                        </div>
                    </div>

                    <div class="kanban-col">
                        <div class="col-header">
                            <span class="col-title">Finalizados</span>
                            <span class="col-badge">{{ finalizados.length }}</span>
                        </div>
                        <OrderCard
                            v-for="order in finalizados"
                            :key="order.id"
                            :order="order"
                            status="ready"
                            @advance="advanceStatus(order.id, 'ready')"
                        />
                    </div>
                </div>

                <div v-if="activeTab === 'history'" class="history-view">
                    <form class="history-filters" @submit.prevent="filterHistory">
                        <label>
                            De
                            <input v-model="dateFrom" type="date">
                        </label>
                        <label>
                            Ate
                            <input v-model="dateTo" type="date">
                        </label>
                        <button type="submit" class="filter-btn">Filtrar</button>
                    </form>

                    <div class="history-table-wrap">
                        <table class="history-table">
                            <thead>
                                <tr>
                                    <th>Pedido</th>
                                    <th>Mesa</th>
                                    <th>Itens</th>
                                    <th>Status</th>
                                    <th>Horario</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="entry in props.history" :key="entry.id">
                                    <td>#{{ entry.id }}</td>
                                    <td>{{ entry.mesa }}</td>
                                    <td>{{ entry.items.join(', ') }}</td>
                                    <td>
                                        <span
                                            class="status-badge"
                                            :class="entry.status === 'ready' ? 'status-ready' : 'status-cancelled'"
                                        >
                                            {{ statusLabel(entry.status) }}
                                        </span>
                                    </td>
                                    <td>{{ entry.time }}</td>
                                </tr>
                                <tr v-if="props.history.length === 0">
                                    <td colspan="5" class="empty-row">Nenhum pedido no periodo selecionado.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.shell {
    min-height: 100vh;
    background: #f6f7f9;
    display: flex;
}

.main {
    flex: 1;
    min-width: 0;
}

.content {
    padding: 16px;
    display: grid;
    gap: 16px;
}

.orders-toolbar {
    background: #fff;
    border: 1px solid #eceef0;
    border-radius: 12px;
    padding: 12px;
    display: flex;
    gap: 12px;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
}

.tabs {
    display: flex;
    gap: 8px;
}

.tabs button {
    border: 1px solid #eceef0;
    background: #fff;
    color: #4b5160;
    border-radius: 8px;
    padding: 8px 12px;
    cursor: pointer;
    font-size: 13px;
    font-weight: 600;
}

.tabs button.active {
    border-color: #993c1d;
    background: #faece7;
    color: #712b13;
}

.toolbar-right {
    display: flex;
    align-items: center;
    gap: 8px;
}

.search-input {
    border: 1px solid #d9dde3;
    border-radius: 8px;
    height: 36px;
    padding: 0 10px;
    font-size: 13px;
    min-width: 220px;
}

.bell-btn {
    width: 36px;
    height: 36px;
    border: 1px solid #eceef0;
    border-radius: 8px;
    background: #fff;
    opacity: 0.5;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.bell-btn svg {
    width: 18px;
    height: 18px;
    fill: #4b5160;
}

.kanban {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 12px;
}

.kanban-col {
    background: #fff;
    border: 1px solid #eceef0;
    border-radius: 12px;
    padding: 12px;
    min-height: 360px;
    transition: border-color 0.15s ease, box-shadow 0.15s ease;
}

.kanban-col--drop-target {
    border-color: #1d9e75;
    box-shadow: inset 0 0 0 1px #1d9e75;
}

.col-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 12px;
}

.col-title {
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    font-weight: 600;
    color: #70737d;
}

.col-badge {
    border-radius: 999px;
    border: 1px solid #eceef0;
    background: #f8f9fa;
    font-size: 12px;
    padding: 2px 8px;
}

.order-drag-wrapper {
    cursor: grab;
}

.order-drag-wrapper:active {
    cursor: grabbing;
}

.history-view {
    background: #fff;
    border: 1px solid #eceef0;
    border-radius: 12px;
    padding: 12px;
    display: grid;
    gap: 12px;
}

.history-filters {
    display: flex;
    align-items: end;
    gap: 10px;
    flex-wrap: wrap;
}

.history-filters label {
    display: grid;
    gap: 4px;
    font-size: 12px;
    color: #666c79;
}

.history-filters input {
    border: 1px solid #d9dde3;
    border-radius: 8px;
    height: 36px;
    padding: 0 10px;
    font-size: 13px;
}

.filter-btn {
    border: 0;
    border-radius: 8px;
    height: 36px;
    padding: 0 14px;
    background: #1a1a1a;
    color: #fff;
    font-weight: 600;
    cursor: pointer;
}

.history-table-wrap {
    overflow-x: auto;
}

.history-table {
    width: 100%;
    border-collapse: collapse;
    min-width: 700px;
}

.history-table th,
.history-table td {
    padding: 10px 8px;
    border-bottom: 1px solid #eceef0;
    text-align: left;
    font-size: 13px;
    color: #1f242e;
}

.history-table thead th {
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: #7b7f89;
}

.status-badge {
    display: inline-flex;
    border-radius: 999px;
    padding: 2px 8px;
    font-size: 11px;
    font-weight: 600;
}

.status-ready {
    background: #e1f5ee;
    color: #085041;
}

.status-cancelled {
    background: #f0f1f3;
    color: #5d6472;
}

.empty-row {
    text-align: center;
    color: #6e7482;
}

@media (max-width: 1100px) {
    .kanban {
        grid-template-columns: 1fr;
    }
}
</style>
