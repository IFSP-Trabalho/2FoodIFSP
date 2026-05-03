<script setup>
import AppSidebar from '../../../Components/AppSidebar.vue';
</script>

<template>
    <div class="shell">
        <AppSidebar active="cadastros" />

        <main class="main">
            <header class="topbar">
                <h1>Pratos</h1>
            </header>

            <section class="content">
                <p>Pagina dedicada para cadastro de pratos.</p>
                <p>Selecione Cadastros na barra lateral para voltar e escolher outro cadastro.</p>
            </section>
        </main>
    </div>
</template>

<style scoped src="./styles/Dishes.css"></style>
