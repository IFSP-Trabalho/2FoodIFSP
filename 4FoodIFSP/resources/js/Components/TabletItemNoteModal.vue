<script setup>
import './styles/TabletItemNoteModal.css';
import { ref, onMounted } from 'vue';

const props = defineProps({
    initialValue: { type: String, default: '' },
});

const emit = defineEmits(['save', 'close']);

const note = ref(props.initialValue);
const textarea = ref(null);

onMounted(() => {
    textarea.value?.focus();
});

function save() {
    emit('save', note.value.slice(0, 200));
}
</script>

<template>
    <div class="note-overlay" @click.self="emit('close')">
        <div class="note-modal" role="dialog" aria-labelledby="note-modal-title">
            <h3 id="note-modal-title" class="note-title">Observação</h3>
            <textarea
                ref="textarea"
                v-model="note"
                class="note-textarea"
                placeholder="Ex: sem cebola, molho à parte..."
                maxlength="200"
                rows="4"
                aria-label="Observação do item"
                @keydown.esc="emit('close')"
            />
            <div class="note-counter">{{ note.length }}/200</div>
            <div class="note-actions">
                <button class="note-cancel" @click="emit('close')">Cancelar</button>
                <button class="note-save" @click="save">Salvar</button>
            </div>
        </div>
    </div>
</template>
