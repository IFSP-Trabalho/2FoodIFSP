<script setup>
import { useForm } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import { parsePriceBRL } from '../utils/parsePrice';
import DishCategorySelect from './DishCategorySelect.vue';

const props = defineProps({
    categories: {
        type: Array,
        default: () => [],
    },
    initialCategoryId: {
        type: String,
        default: null,
    },
});

const emit = defineEmits(['close']);

const photoPreviewUrl = ref(null);

const form = useForm({
    name: '',
    description: '',
    price: '',
    category_id: props.initialCategoryId ?? '',
    active: true,
    photo: null,
});

const canSubmit = computed(() => props.categories.length > 0);

watch(
    () => props.initialCategoryId,
    (value) => {
        if (value) {
            form.category_id = value;
        }
    },
    { immediate: true }
);

function onPhotoChange(event) {
    const file = event.target.files?.[0] ?? null;
    form.photo = file;

    if (photoPreviewUrl.value) {
        URL.revokeObjectURL(photoPreviewUrl.value);
    }

    photoPreviewUrl.value = file ? URL.createObjectURL(file) : null;
}

function handleCancel() {
    if (photoPreviewUrl.value) {
        URL.revokeObjectURL(photoPreviewUrl.value);
    }
    photoPreviewUrl.value = null;
    form.reset();
    form.clearErrors();
    emit('close');
}

function handleOverlayClick() {
    handleCancel();
}

function handleSubmit() {
    const parsedPrice = parsePriceBRL(form.price);

    if (!Number.isFinite(parsedPrice) || parsedPrice < 0.01) {
        form.setError('price', 'Informe um preço válido.');
        return;
    }

    const photoFile = form.photo;

    form.price = parsedPrice;
    form.active = form.active ? '1' : '0';
    form.photo = photoFile;

    form.post('/admin/cadastros/dishes', {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => handleCancel(),
    });
}

function onKeydown(event) {
    if (event.key === 'Escape') {
        handleCancel();
    }
}

onMounted(() => {
    document.addEventListener('keydown', onKeydown);
});

onUnmounted(() => {
    document.removeEventListener('keydown', onKeydown);
    if (photoPreviewUrl.value) {
        URL.revokeObjectURL(photoPreviewUrl.value);
    }
});
</script>

<template>
    <div class="admin-modal-overlay" @click.self="handleOverlayClick"></div>
    <section
        class="admin-modal"
        role="dialog"
        aria-modal="true"
        aria-labelledby="dish-panel-title"
    >
        <header class="admin-modal-head">
            <h3 id="dish-panel-title">
                Cadastrar prato
            </h3>
        </header>

        <form class="admin-modal-form" @submit.prevent="handleSubmit">
            <label class="admin-modal-field">
                <div class="admin-modal-input-wrap">
                    <span class="admin-modal-floating-label">Nome do prato</span>
                    <input
                        v-model="form.name"
                        type="text"
                        autocomplete="off"
                        placeholder=" "
                        required
                    >
                </div>
                <small v-if="form.errors.name">{{ form.errors.name }}</small>
            </label>

            <label class="admin-modal-field">
                <div class="admin-modal-input-wrap admin-modal-textarea">
                    <span class="admin-modal-floating-label">Descricao</span>
                    <textarea
                        v-model="form.description"
                        rows="3"
                        placeholder=" "
                    ></textarea>
                </div>
                <small v-if="form.errors.description">{{ form.errors.description }}</small>
            </label>

            <label class="admin-modal-field">
                <div class="admin-modal-input-wrap">
                    <span class="admin-modal-floating-label">Preco</span>
                    <input
                        v-model="form.price"
                        type="text"
                        inputmode="decimal"
                        placeholder=" "
                        required
                    >
                </div>
                <small v-if="form.errors.price">{{ form.errors.price }}</small>
            </label>

            <label class="admin-modal-field admin-modal-photo-field">
                <span class="admin-modal-floating-label">Foto</span>
                <input
                    type="file"
                    accept="image/jpeg,image/png,image/webp"
                    @change="onPhotoChange"
                >
                <img
                    v-if="photoPreviewUrl"
                    :src="photoPreviewUrl"
                    alt="Preview da foto"
                    class="dish-photo-preview"
                >
                <small v-if="form.errors.photo">{{ form.errors.photo }}</small>
            </label>

            <div class="admin-modal-field">
                <DishCategorySelect
                    :model-value="form.category_id"
                    :categories="props.categories"
                    :error="form.errors.category_id"
                    label="Categoria"
                    @update:model-value="(value) => { form.category_id = value; }"
                />
            </div>

            <label class="admin-modal-field admin-modal-checkbox-field">
                <input
                    v-model="form.active"
                    type="checkbox"
                >
                <span>Prato ativo</span>
            </label>
            <p class="admin-modal-hint">
                Inativos nao aparecem no tablet (em breve).
            </p>

            <footer class="admin-modal-actions">
                <button type="button" class="secondary" :disabled="form.processing" @click="handleCancel">
                    Sair
                </button>
                <button
                    type="submit"
                    class="primary"
                    :disabled="form.processing || !canSubmit"
                >
                    {{ form.processing ? 'Salvando...' : 'Salvar' }}
                </button>
            </footer>
        </form>
    </section>
</template>

<style scoped src="./styles/DishCreatePanel.css"></style>
