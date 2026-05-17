<script setup>
import { useForm } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import {
    formatPriceInputBRL,
    isPriceInputNavigationKey,
    parsePriceDigitsBRL,
    priceToDigitsFromDecimal,
    sanitizePriceDigits,
} from '../utils/priceInput';
import DishCategorySelect from './DishCategorySelect.vue';

const props = defineProps({
    mode: {
        type: String,
        default: 'create',
    },
    dish: {
        type: Object,
        default: null,
    },
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
const photoInputRef = ref(null);
const selectedPhotoName = ref('');
const priceDigits = ref('');
const existingPhotoUrl = ref(null);

const form = useForm({
    name: '',
    description: '',
    price: '',
    category_id: props.initialCategoryId ?? '',
    active: true,
    photo: null,
});

const isEditMode = computed(() => props.mode === 'edit' && props.dish !== null);
const canSubmit = computed(() => props.categories.length > 0);
const modalTitle = computed(() => (isEditMode.value ? 'Editar prato' : 'Cadastrar prato'));
const submitLabel = computed(() => {
    if (form.processing) {
        return 'Salvando...';
    }

    return isEditMode.value ? 'Salvar alteracoes' : 'Salvar';
});

const priceDisplay = computed(() => formatPriceInputBRL(priceDigits.value));

function isBlobPreview(url) {
    return typeof url === 'string' && url.startsWith('blob:');
}

function revokePreviewIfBlob() {
    if (isBlobPreview(photoPreviewUrl.value)) {
        URL.revokeObjectURL(photoPreviewUrl.value);
    }
}

function populateFromDish(dish) {
    if (!dish) {
        return;
    }

    form.name = dish.name ?? '';
    form.description = dish.description ?? '';
    form.category_id = dish.category_id ?? '';
    form.active = Boolean(dish.active);
    priceDigits.value = priceToDigitsFromDecimal(dish.price);
    existingPhotoUrl.value = dish.photo_url ?? null;
    photoPreviewUrl.value = dish.photo_url ?? null;
    selectedPhotoName.value = '';
    form.photo = null;

    if (photoInputRef.value) {
        photoInputRef.value.value = '';
    }
}

watch(
    () => props.initialCategoryId,
    (value) => {
        if (!isEditMode.value && value) {
            form.category_id = value;
        }
    },
    { immediate: true }
);

watch(
    () => props.dish,
    (dish) => {
        if (isEditMode.value) {
            populateFromDish(dish);
        }
    },
    { immediate: true }
);

function onPhotoChange(event) {
    const file = event.target.files?.[0] ?? null;
    form.photo = file;
    selectedPhotoName.value = file?.name ?? '';

    revokePreviewIfBlob();
    photoPreviewUrl.value = file ? URL.createObjectURL(file) : (existingPhotoUrl.value ?? null);
}

function openPhotoPicker() {
    photoInputRef.value?.click();
}

function clearPhoto() {
    form.photo = null;
    selectedPhotoName.value = '';

    if (photoInputRef.value) {
        photoInputRef.value.value = '';
    }

    revokePreviewIfBlob();
    photoPreviewUrl.value = isEditMode.value ? (existingPhotoUrl.value ?? null) : null;
}

function onPriceInput(event) {
    priceDigits.value = sanitizePriceDigits(event.target.value);
    form.price = priceDisplay.value;
}

function onPriceKeydown(event) {
    if (isPriceInputNavigationKey(event.key) || event.ctrlKey || event.metaKey) {
        return;
    }

    if (!/^\d$/.test(event.key)) {
        event.preventDefault();
    }
}

function handleCancel() {
    revokePreviewIfBlob();
    photoPreviewUrl.value = null;
    existingPhotoUrl.value = null;
    selectedPhotoName.value = '';
    priceDigits.value = '';

    if (photoInputRef.value) {
        photoInputRef.value.value = '';
    }

    form.reset();
    form.clearErrors();
    emit('close');
}

function handleOverlayClick() {
    handleCancel();
}

function handleSubmit() {
    const parsedPrice = parsePriceDigitsBRL(priceDigits.value);

    if (!Number.isFinite(parsedPrice) || parsedPrice < 0.01) {
        form.setError('price', 'Informe um preço válido.');
        return;
    }

    const photoFile = form.photo;

    form.price = parsedPrice;
    form.active = form.active ? '1' : '0';
    form.photo = photoFile;

    if (isEditMode.value) {
        form
            .transform((data) => ({
                ...data,
                _method: 'put',
            }))
            .post(`/admin/cadastros/dishes/${props.dish.id}`, {
                forceFormData: true,
                preserveScroll: true,
                onSuccess: () => handleCancel(),
            });
        return;
    }

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

    if (isEditMode.value) {
        populateFromDish(props.dish);
    }
});

onUnmounted(() => {
    document.removeEventListener('keydown', onKeydown);
    revokePreviewIfBlob();
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
                {{ modalTitle }}
            </h3>
        </header>

        <form class="admin-modal-form" @submit.prevent="handleSubmit">
            <div class="admin-modal-body">
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
                <div
                    class="admin-modal-input-wrap admin-modal-price-wrap"
                    :class="{ 'is-filled': priceDigits.length > 0 }"
                >
                    <span class="admin-modal-floating-label">Preco</span>
                    <input
                        :value="priceDisplay"
                        type="text"
                        inputmode="numeric"
                        autocomplete="off"
                        placeholder="R$ 0,00"
                        required
                        @input="onPriceInput"
                        @keydown="onPriceKeydown"
                    >
                </div>
                <small v-if="form.errors.price">{{ form.errors.price }}</small>
            </label>

            <div class="admin-modal-field admin-modal-photo-field">
                <span class="admin-modal-photo-label">Foto</span>
                <div class="admin-modal-photo-controls">
                    <input
                        ref="photoInputRef"
                        type="file"
                        class="admin-modal-photo-input"
                        accept="image/jpeg,image/png,image/webp"
                        @change="onPhotoChange"
                    >
                    <button
                        type="button"
                        class="admin-modal-photo-btn"
                        @click="openPhotoPicker"
                    >
                        Escolher arquivo
                    </button>
                    <span
                        v-if="selectedPhotoName"
                        class="admin-modal-photo-name"
                        :title="selectedPhotoName"
                    >
                        {{ selectedPhotoName }}
                    </span>
                    <button
                        v-if="selectedPhotoName"
                        type="button"
                        class="admin-modal-photo-clear"
                        @click="clearPhoto"
                    >
                        Remover
                    </button>
                </div>
                <img
                    v-if="photoPreviewUrl"
                    :src="photoPreviewUrl"
                    alt="Preview da foto"
                    class="dish-photo-preview"
                >
                <small v-if="form.errors.photo">{{ form.errors.photo }}</small>
            </div>

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
            </div>

            <footer class="admin-modal-actions">
                <button type="button" class="secondary" :disabled="form.processing" @click="handleCancel">
                    Sair
                </button>
                <button
                    type="submit"
                    class="primary"
                    :disabled="form.processing || !canSubmit"
                >
                    {{ submitLabel }}
                </button>
            </footer>
        </form>
    </section>
</template>

<style scoped src="./styles/DishCreatePanel.css"></style>
