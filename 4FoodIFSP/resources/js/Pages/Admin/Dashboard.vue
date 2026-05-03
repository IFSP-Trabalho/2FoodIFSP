<script setup>
import AppSidebar from '../../Components/AppSidebar.vue';
import AppTopbar from '../../Components/AppTopbar.vue';
import DeptCard from '../../Components/DeptCard.vue';
import KpiCard from '../../Components/KpiCard.vue';
import OrderRow from '../../Components/OrderRow.vue';

const props = defineProps({
    stats: {
        type: Object,
        required: true,
    },
    date: {
        type: String,
        required: true,
    },
});

const statusLabel = {
    in_progress: 'Em preparo',
    ready: 'Pronto',
    cancelled: 'Cancelado',
};

const statusClass = {
    in_progress: 'badge-amber',
    ready: 'badge-green',
    cancelled: 'badge-gray',
};
</script>

<template>
    <div class="shell">
        <AppSidebar active="dashboard" />
        <div class="main">
            <AppTopbar title="Visão geral" :subtitle="props.date" role-badge="Admin" />
            <div class="content">
                <section>
                    <p class="section-label">resumo do dia</p>
                    <div class="kpi-grid">
                        <KpiCard label="faturamento" :value="props.stats.faturamento" />
                        <KpiCard label="pedidos totais" :value="props.stats.pedidos_totais" />
                        <KpiCard label="mesas abertas" :value="props.stats.mesas_abertas" />
                        <KpiCard label="ticket médio" :value="props.stats.ticket_medio" />
                    </div>
                </section>

                <section>
                    <p class="section-label">departamentos</p>
                    <div class="dept-grid">
                        <DeptCard
                            name="Cozinha"
                            color="#EF9F27"
                            :badge-text="`${props.stats.cozinha.em_preparo} em preparo`"
                            badge-bg="#FAEEDA"
                            badge-color="#633806"
                            :rows="[
                                { label: 'Em preparo', value: props.stats.cozinha.em_preparo, bar: true, max: 10 },
                                { label: 'Prontos hoje', value: props.stats.cozinha.prontos, bar: true, max: 50 },
                                { label: 'Tempo médio', value: props.stats.cozinha.tempo_medio },
                                { label: 'Cancelados', value: props.stats.cozinha.cancelados },
                            ]"
                        />

                        <DeptCard
                            name="Financeiro"
                            color="#1D9E75"
                            :badge-text="`${props.stats.financeiro.mesas_abertas} mesas abertas`"
                            badge-bg="#E1F5EE"
                            badge-color="#085041"
                            :rows="[
                                { label: 'Mesas abertas', value: props.stats.financeiro.mesas_abertas, bar: true, max: 12 },
                                { label: 'Mesas fechadas', value: props.stats.financeiro.mesas_fechadas, bar: true, max: 12 },
                                { label: 'Faturamento', value: props.stats.financeiro.faturamento },
                                { label: 'Pendente', value: props.stats.financeiro.pendente },
                            ]"
                        />

                        <DeptCard
                            name="Garçom"
                            color="#D85A30"
                            :badge-text="`${props.stats.garcom.contas_fechadas} fechamentos`"
                            badge-bg="#FAECE7"
                            badge-color="#712B13"
                            :rows="[
                                { label: 'Mesas atendidas', value: props.stats.garcom.mesas_atendidas, bar: true, max: 10 },
                                { label: 'Contas fechadas', value: props.stats.garcom.contas_fechadas, bar: true, max: 10 },
                                { label: 'Último fechamento', value: props.stats.garcom.ultimo_fechamento },
                                { label: 'Pedidos atendidos', value: props.stats.garcom.pedidos_atendidos },
                            ]"
                        />

                        <DeptCard
                            name="WhatsApp"
                            color="#378ADD"
                            :badge-text="`${props.stats.whatsapp.em_andamento} em andamento`"
                            badge-bg="#E6F1FB"
                            badge-color="#0C447C"
                            :rows="[
                                { label: 'Triagem', value: props.stats.whatsapp.triagem, bar: true, max: 12 },
                                { label: 'Em andamento', value: props.stats.whatsapp.em_andamento, bar: true, max: 12 },
                                { label: 'Fechados hoje', value: props.stats.whatsapp.fechados_hoje },
                                { label: 'Pedidos delivery', value: props.stats.whatsapp.pedidos_delivery },
                            ]"
                        />
                    </div>
                </section>

                <section>
                    <div class="orders-list">
                        <div class="orders-head">
                            <span>Últimos pedidos</span>
                            <button type="button" class="orders-link is-disabled">ver todos →</button>
                        </div>

                        <div class="orders-columns">
                            <span>Mesa</span>
                            <span>Itens</span>
                            <span>Status</span>
                            <span class="right">Total</span>
                        </div>

                        <OrderRow
                            v-for="order in props.stats.ultimos_pedidos"
                            :key="order.mesa + order.itens"
                            :mesa="order.mesa"
                            :itens="order.itens"
                            :status="statusLabel[order.status]"
                            :status-class="statusClass[order.status]"
                            :total="order.total"
                        />
                    </div>
                </section>
            </div>
        </div>
    </div>
</template>

<style scoped src="./styles/Dashboard.css"></style>
