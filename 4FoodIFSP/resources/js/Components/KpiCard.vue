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

<style scoped>
.kpi-card {
    background: #fff;
    border: 1px solid #eceef0;
    border-radius: 14px;
    padding: 12px;
}

.label {
    margin: 0;
    color: #7b7f89;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    font-size: 12px;
    font-weight: 600;
    font-family: 'Segoe UI Variable', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

.value {
    margin: 8px 0 0;
    font-size: 22px;
    font-weight: 700;
    color: #15161a;
    font-family: 'Segoe UI Variable', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}
</style>
