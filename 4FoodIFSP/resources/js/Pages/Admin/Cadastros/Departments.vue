<script setup>
import AppSidebar from '../../../Components/AppSidebar.vue';

const props = defineProps({
    departments: {
        type: Array,
        default: () => [],
    },
});
</script>

<template>
    <div class="shell">
        <AppSidebar active="cadastros" />

        <main class="main">
            <header class="topbar">
                <h1>Departamentos</h1>
            </header>

            <section class="content">
                <p>Pagina dedicada para cadastro de departamentos.</p>
                <ul>
                    <li v-for="department in props.departments" :key="department">{{ department }}</li>
                </ul>
            </section>
        </main>
    </div>
</template>

<style scoped src="./styles/Departments.css"></style>
