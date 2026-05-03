<script setup>
const props = defineProps({
    name: {
        type: String,
        required: true,
    },
    color: {
        type: String,
        required: true,
    },
    badgeText: {
        type: String,
        required: true,
    },
    badgeBg: {
        type: String,
        required: true,
    },
    badgeColor: {
        type: String,
        required: true,
    },
    rows: {
        type: Array,
        default: () => [],
    },
});

function toPercent(value, max) {
    const normalizedMax = Number(max) > 0 ? Number(max) : 1;
    const numeric = Number(value);

    if (Number.isNaN(numeric)) {
        return 0;
    }

    return Math.min(100, Math.max(0, Math.round((numeric / normalizedMax) * 100)));
}
</script>

<template>
    <article class="dept-card">
        <div class="head">
            <div class="title">
                <span class="dot" :style="{ backgroundColor: color }" />
                <strong>{{ name }}</strong>
            </div>
            <span class="badge" :style="{ backgroundColor: badgeBg, color: badgeColor }">{{ badgeText }}</span>
        </div>

        <div class="rows">
            <div v-for="row in props.rows" :key="row.label" class="row">
                <div class="row-line">
                    <span>{{ row.label }}</span>
                    <span>{{ row.value }}</span>
                </div>
                <div v-if="row.bar" class="progress">
                    <i :style="{ width: `${toPercent(row.value, row.max)}%`, backgroundColor: color }" />
                </div>
            </div>
        </div>
    </article>
</template>

<style scoped src="./styles/DeptCard.css"></style>
