<script setup>
const props = defineProps({
    active: {
        type: String,
        default: 'users',
    },
});

const emit = defineEmits(['select']);

const items = [
    { key: 'users', label: 'Usuarios', available: true },
    { key: 'departments', label: 'Departamentos', available: true },
    { key: 'dishes', label: 'Pratos', available: true },
];

function onSelect(item) {
    emit('select', item.key);
}
</script>

<template>
    <aside class="submenu">
        <p class="submenu-title">Cadastros</p>

        <button
            v-for="item in items"
            :key="item.key"
            type="button"
            class="submenu-item"
            :class="{ active: item.key === props.active }"
            @click="onSelect(item)"
        >
            <span>{{ item.label }}</span>
            <span v-if="!item.available" class="soon-badge">Em breve</span>
        </button>
    </aside>
</template>

<style scoped src="./styles/CadastrosSubmenu.css"></style>
