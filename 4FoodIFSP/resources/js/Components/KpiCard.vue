<script setup>
defineProps({
    label: {
        type: String,
        required: true,
    },
    value: {
        type: [String, Number],
        required: true,
    },
});
</script>

<template>
    <article class="kpi-card">
        <p class="label">{{ label }}</p>
        <p class="value">{{ value }}</p>
    </article>
</template>

<style scoped src="./styles/KpiCard.css"></style>
