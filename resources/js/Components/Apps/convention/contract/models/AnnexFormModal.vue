<script setup>
import { defineProps, defineEmits, ref, watch, reactive, computed } from 'vue'; // Import computed

// PrimeVue Components
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import Dropdown from 'primevue/dropdown';
import Button from 'primevue/button';

// Utility function to format number as DZD currency
const formatDZD = (value) => {
    if (value === null || value === '' || isNaN(value) || value === 0) return '';
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
    if (!value || value.trim() === '') return null;

    // Remove all non-numeric characters except decimal point
    const cleaned = value.replace(/[^\d.]/g, '');

    // If the cleaned string is empty, return null
    if (cleaned === '' || cleaned === '.') return null;

    const parsed = parseFloat(cleaned);
    return isNaN(parsed) ? null : parsed;
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
    usedServiceIds: { // NEW PROP
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
const displayMinPrice = ref(''); // Renamed to clearly indicate it's for display
// Track if user is currently editing the price field
const isEditingPrice = ref(false);

// Internal errors
const internalFormErrors = reactive({});

// Options for the price status dropdown
const prestationPrixStatusOptions = ref([
    { label: 'Empty', value: 'empty' },
    { label: 'Convenience Price', value: 'convenience_prix' },
    { label: 'Public Price', value: 'public_prix' }
]);

// Computed property to filter services for the dropdown
const availableServices = computed(() => {
    if (props.isEditing) {
        // When editing, the currently selected service should always be available.
        // Other services should only be available if they are not used by other annexes.
        const currentServiceId = internalForm.service_id;
        return props.services.filter(service =>
            !props.usedServiceIds.includes(service.id) || service.id === currentServiceId
        );
    } else {
        // When adding, only services not already used by any annex are available.
        return props.services.filter(service => !props.usedServiceIds.includes(service.id));
    }
});

// Watch for changes in props.formData to populate internalForm
watch(() => props.formData, (newVal) => {
    if (newVal) {
        Object.assign(internalForm, newVal);
        // Only update formatted display if not currently editing
        if (!isEditingPrice.value) {
            displayMinPrice.value = formatDZD(newVal.min_price);
        }
    }
}, { immediate: true, deep: true });

// Watch for changes in internalForm.min_price to update formatted values for display
watch(() => internalForm.min_price, (newVal) => {
    // Only update formatted display if not currently editing the field
    if (!isEditingPrice.value) {
        displayMinPrice.value = formatDZD(newVal);
    }
});


// Watch for showModal to clear form and errors
watch(() => props.showModal, (newVal) => {
    if (!newVal) {
        Object.keys(internalFormErrors).forEach(key => delete internalFormErrors[key]);
        displayMinPrice.value = ''; // Clear display value on modal close
        isEditingPrice.value = false;
    } else {
        // When modal opens, initialize displayMinPrice based on internalForm.min_price
        displayMinPrice.value = formatDZD(internalForm.min_price);
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

    // Optional: Add validation for min_price if needed
    // if (internalForm.min_price === null || isNaN(internalForm.min_price) || internalForm.min_price < 0) {
    //     internalFormErrors.min_price = "Minimum price must be a non-negative number.";
    //     isValid = false;
    // }

    return isValid;
};

const saveAnnex = () => {
    if (validateInternalForm()) {
        emit('save', internalForm);
    }
};

const handleCloseModal = () => {
    Object.keys(internalFormErrors).forEach(key => delete internalFormErrors[key]);
    displayMinPrice.value = ''; // Clear display value on modal close
    isEditingPrice.value = false;
    emit('close-modal');
};

// Handle price input events
const handlePriceInput = (event) => {
    const value = event.target.value;
    // Update the display value directly from the input
    displayMinPrice.value = value;
    // Parse the value and update the actual numeric model
    internalForm.min_price = parseDZD(value);
};

const handlePriceFocus = () => {
    isEditingPrice.value = true;
    // Show raw numeric value when focused for easier editing
    if (internalForm.min_price !== null && internalForm.min_price !== '') {
        displayMinPrice.value = internalForm.min_price.toString();
    } else {
        displayMinPrice.value = ''; // Clear if null/empty
    }
};

const handlePriceBlur = () => {
    isEditingPrice.value = false;
    // Format the value when focus is lost
    displayMinPrice.value = formatDZD(internalForm.min_price);
};

const handlePriceKeydown = (event) => {
    // Allow special keys: backspace, delete, tab, escape, enter
    if ([8, 9, 27, 13, 46].indexOf(event.keyCode) !== -1 ||
        // Allow Ctrl+A, Ctrl+C, Ctrl+V, Ctrl+X
        (event.keyCode === 65 && event.ctrlKey === true) ||
        (event.keyCode === 67 && event.ctrlKey === true) ||
        (event.keyCode === 86 && event.ctrlKey === true) ||
        (event.keyCode === 88 && event.ctrlKey === true) ||
        // Allow home, end, left, right
        (event.keyCode >= 35 && event.keyCode <= 39)) {
        return;
    }
    // Ensure that it is a number or decimal point and stop the keypress
    if ((event.shiftKey || (event.keyCode < 48 || event.keyCode > 57)) &&
        (event.keyCode < 96 || event.keyCode > 105) &&
        event.keyCode !== 190 && event.keyCode !== 110) { // 190 for '.', 110 for numpad '.'
        event.preventDefault();
    }
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
                    :options="availableServices" optionLabel="name"
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

            <div class="field mb-3">
                <label for="minPrice">Minimum Price:</label>
                <InputText
                    id="minPrice"
                    v-model="displayMinPrice"
                    @input="handlePriceInput"
                    @focus="handlePriceFocus"
                    @blur="handlePriceBlur"
                    @keydown="handlePriceKeydown"
                    :class="{ 'p-invalid': internalFormErrors.min_price }"
                    :disabled="isLoading"
                    placeholder="0 DZD" style="direction: ltr; text-align: left;"
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
    direction: ltr;
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