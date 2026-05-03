<script setup>
defineProps({
    title: {
        type: String,
        required: true,
    },
    subtitle: {
        type: String,
        required: true,
    },
    roleBadge: {
        type: String,
        default: 'Admin',
    },
});
</script>

<template>
    <header class="topbar">
        <div class="title-wrap">
            <span class="dot" />
            <div>
                <h1>{{ title }}</h1>
                <p>{{ subtitle }}</p>
            </div>
        </div>
        <span class="role-badge">{{ roleBadge }}</span>
    </header>
</template>

<style scoped src="./styles/AppTopbar.css"></style>
