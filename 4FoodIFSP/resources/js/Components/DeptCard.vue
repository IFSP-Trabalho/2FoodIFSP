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

<style scoped>
.dept-card {
    background: #fff;
    border: 1px solid #eceef0;
    border-radius: 14px;
    padding: 12px;
}

.head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 10px;
    gap: 8px;
}

.title {
    display: flex;
    align-items: center;
    gap: 8px;
}

.title strong {
    font-size: 14px;
    color: #16171c;
}

.dot {
    width: 9px;
    height: 9px;
    border-radius: 50%;
}

.badge {
    padding: 3px 8px;
    border-radius: 999px;
    font-size: 10px;
    font-weight: 600;
    white-space: nowrap;
}

.rows {
    display: grid;
    gap: 8px;
}

.row-line {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 8px;
    font-size: 12px;
    color: #2b2d35;
}

.progress {
    margin-top: 5px;
    width: 100%;
    height: 5px;
    border-radius: 999px;
    background: #eff1f4;
    overflow: hidden;
}

.progress i {
    display: block;
    height: 100%;
}
</style>
