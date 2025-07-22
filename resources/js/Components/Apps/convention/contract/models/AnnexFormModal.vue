<script setup>
import { defineProps, defineEmits, ref, watch, reactive } from 'vue';

// PrimeVue Components
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import Dropdown from 'primevue/dropdown';
import Button from 'primevue/button';

// Utility function to format number as DZD currency
const formatDZD = (value) => {
    if (value === null || value === '' || isNaN(value)) return '';
    // Corrected locale to 'ar-DZ' for Algerian Dinar formatting
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'DZD',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    }).format(value);
};

// Utility function to parse DZD input to number
const parseDZD = (value) => {
    if (!value) return null;
    // This parsing logic is generally robust for removing non-numeric characters
    // It handles decimal points and should work regardless of locale-specific symbols
    const cleaned = value.replace(/[^0-9.]/g, '');
    return isNaN(cleaned) ? null : parseFloat(cleaned);
};

const props = defineProps({
    showModal: {
        type: Boolean,
        default: false
    },
    isEditing: {
        type: Boolean,
        default: false
    },
    formData: {
        type: Object,
        required: true
    },
    services: {
        type: Array,
        default: () => []
    },
    isLoading: {
        type: Boolean,
        default: false
    }
});

const emit = defineEmits(['save', 'close-modal']);

// Internal reactive form state
const internalForm = reactive({
    id: null,
    contract_id: null,
    annex_name: '',
    service_id: null,
    min_price: null,
    prestation_prix_status: 'empty' // Added this field with a default value
});

// Ref for formatted input display
const formattedMinPrice = ref('');

// Internal errors
const internalFormErrors = reactive({});

// Options for the price status dropdown
const prestationPrixStatusOptions = ref([
    { label: 'Empty', value: 'empty' },
    { label: 'Convenience Price', value: 'convenience_prix' },
    { label: 'Public Price', value: 'public_prix' }
]);

// Watch for changes in internalForm prices to update formatted values
watch(() => internalForm.min_price, (newVal) => {
    formattedMinPrice.value = formatDZD(newVal);
});



// Watch for showModal to clear form and errors
watch(() => props.showModal, (newVal) => {
    if (!newVal) {
        Object.keys(internalFormErrors).forEach(key => delete internalFormErrors[key]);
        formattedMinPrice.value = '';
    }
});

// Client-side validation
const validateInternalForm = () => {
    Object.keys(internalFormErrors).forEach(key => delete internalFormErrors[key]);
    let isValid = true;

    if (!internalForm.annex_name || !internalForm.annex_name.trim()) {
        internalFormErrors.annex_name = "Name is required.";
        isValid = false;
    }

    if (internalForm.service_id === null || internalForm.service_id === '') {
        internalFormErrors.service_id = "Service is required.";
        isValid = false;
    }


    if (!internalForm.prestation_prix_status || !['convenience_prix', 'empty', 'public_prix'].includes(internalForm.prestation_prix_status)) {
        internalFormErrors.prestation_prix_status = "Price status is required and must be a valid option.";
        isValid = false;
    }

    return isValid;
};

const saveAnnex = () => {
    if (validateInternalForm()) {
        emit('save', internalForm);
    }
};

const handleCloseModal = () => {
    Object.keys(internalFormErrors).forEach(key => delete internalFormErrors[key]);
    formattedMinPrice.value = '';
    emit('close-modal');
};
</script>

<template>
    <Dialog
        :visible="showModal"
        :modal="true"
        :header="isEditing ? 'Edit Annex' : 'Add Annex'"
        @update:visible="handleCloseModal"
        :style="{ width: '50vw' }"
        :breakpoints="{ '460px': '75vw', '341px': '100vw' }"
        :closable="!isLoading"
    >
        <div class="p-fluid">
            <div class="field mb-3">
                <label for="annexName">Name:</label>
                <InputText
                    id="annexName"
                    v-model="internalForm.annex_name"
                    :class="{ 'p-invalid': internalFormErrors.annex_name }"
                    :disabled="isLoading"
                />
                <small class="p-error" v-if="internalFormErrors.annex_name">
                    {{ internalFormErrors.annex_name }}
                </small>
            </div>

            <div class="field mb-3">
                <label for="service">Service:</label>
                <Dropdown
                    id="service"
                    v-model="internalForm.service_id"
                    :options="services"
                    optionLabel="name"
                    optionValue="id"
                    placeholder="Select Service"
                    :class="{ 'p-invalid': internalFormErrors.service_id }"
                    :disabled="isLoading"
                />
                <small class="p-error" v-if="internalFormErrors.service_id">
                    {{ internalFormErrors.service_id }}
                </small>
            </div>

            <div class="field mb-3">
                <label for="prestationPrixStatus">Price Status:</label>
                <Dropdown
                    id="prestationPrixStatus"
                    v-model="internalForm.prestation_prix_status"
                    :options="prestationPrixStatusOptions"
                    optionLabel="label"
                    optionValue="value"
                    placeholder="Select Price Status"
                    :class="{ 'p-invalid': internalFormErrors.prestation_prix_status }"
                    :disabled="isLoading"
                />
                <small class="p-error" v-if="internalFormErrors.prestation_prix_status">
                    {{ internalFormErrors.prestation_prix_status }}
                </small>
            </div>

            <div class="field mb-3" >
                <label for="minPrice">Minimum Price (DZD):</label>
                <InputText
                    id="minPrice"
                    v-model="formattedMinPrice"
                    @input="internalForm.min_price = parseDZD($event.target.value)"
                    :class="{ 'p-invalid': internalFormErrors.min_price }"
                    :disabled="isLoading"
                    placeholder="د.ج 0.00"
                    style="direction: rtl; text-align: left;"
                />
                <small class="p-error" v-if="internalFormErrors.min_price">
                    {{ internalFormErrors.min_price }}
                </small>
            </div>
        </div>

        <template #footer>
            <Button
                label="Cancel"
                icon="pi pi-times"
                class="p-button-secondary"
                @click="handleCloseModal"
                :disabled="isLoading"
            />
            <Button
                :label="isEditing ? 'Update' : 'Save'"
                icon="pi pi-check"
                class="p-button-primary"
                @click="saveAnnex"
                :loading="isLoading"
            />
        </template>
    </Dialog>
</template>

<style scoped>
/* PrimeVue components come with their own styling. */
/* You might need to add global styles or adjust PrimeVue theme for overall look. */

/* Custom style to ensure right-to-left text alignment for currency inputs */
/* This might be needed if PrimeVue's InputText doesn't handle it by default based on locale */
#minPrice {
    direction: rtl;
    text-align: right;
}

/* Basic spacing for fields within the dialog */
.field {
    margin-bottom: 1.5rem; /* Adjust as needed */
}

.p-error {
    font-size: 0.875em;
    color: var(--red-500); /* PrimeVue's default error color */
    display: block;
    margin-top: 0.25rem;
}
</style>
