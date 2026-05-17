<script setup>
const props = defineProps({
    department: {
        type: Object,
        required: true,
    },
});

const emit = defineEmits(['edit']);
</script>

<template>
    <div class="row">
        <span class="cell name-cell">{{ props.department.label }}</span>

        <div class="cell color-cell">
            <span
                class="color-swatch"
                :style="{ background: props.department.color }"
                aria-hidden="true"
            />
            <span class="color-hex">{{ props.department.color }}</span>
        </div>

        <div class="cell actions-cell">
            <button
                type="button"
                class="icon-btn edit"
                title="Editar cor"
                :aria-label="`Editar cor do departamento ${props.department.label}`"
                @click="emit('edit', props.department)"
            >
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M4 17.25V20h2.75l8.09-8.09-2.75-2.75L4 17.25Zm12.71-9.04a1 1 0 0 0 0-1.42l-1.5-1.5a1 1 0 0 0-1.42 0l-1.17 1.17 2.75 2.75 1.34-1Z" />
                </svg>
            </button>
        </div>
    </div>
</template>

<style scoped src="./styles/DepartmentsTableRow.css"></style>
