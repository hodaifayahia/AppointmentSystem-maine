<script setup>
import { defineProps, defineEmits, ref, watch, reactive } from 'vue';

const props = defineProps({
  showModal: {
    type: Boolean,
    default: false
  },
  isEditing: {
    type: Boolean,
    default: false
  },
  formData: { // This will be the form data passed from the parent
    type: Object,
    required: true
  },
  services: { // The list of services for the dropdown
    type: Array,
    default: () => []
  },
  isLoading: { // Prop for loading state (saving/updating)
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
  service_id: null, // Changed from specialty_id to service_id
});

const internalFormErrors = reactive({}); // Internal errors for this modal's form

// Watch for changes in the `formData` prop to update `internalForm`
watch(() => props.formData, (newVal) => {
  Object.assign(internalForm, JSON.parse(JSON.stringify(newVal)));
  Object.keys(internalFormErrors).forEach(key => delete internalFormErrors[key]);
}, { deep: true, immediate: true });

// Watch for showModal becoming false to clear form and errors
watch(() => props.showModal, (newVal) => {
  if (!newVal) { // If modal is closing
    Object.keys(internalFormErrors).forEach(key => delete internalFormErrors[key]); // Clear errors
  }
});

// Client-side validation function for this modal's form
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

  return isValid;
};

const saveAnnex = () => {
  if (validateInternalForm()) {
    emit('save', internalForm);
  }
};

const handleCloseModal = () => {
  Object.keys(internalFormErrors).forEach(key => delete internalFormErrors[key]); // Clear errors
  emit('close-modal');
};
</script>

<template>
                {{ services }}

  <div
    v-if="showModal"
    class="modal fade"
    :class="{ 'show d-block': showModal }"
    id="annexFormModal"
    tabindex="-1"
    aria-labelledby="annexFormModalLabel"
    aria-hidden="true"
    @click.self="handleCloseModal"
  >
    <div class="modal-dialog modal-md">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="annexFormModalLabel">
            {{ isEditing ? 'Edit Annex' : 'Add Annex' }}
          </h5>
          <button
            type="button"
            class="btn-close"
            @click="handleCloseModal"
            aria-label="Close"
          ></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label for="annexName" class="form-label">Name:</label>
            <input
              type="text"
              id="annexName"
              v-model="internalForm.annex_name"
              class="form-control"
              :class="{ 'is-invalid': internalFormErrors.annex_name }"
              :disabled="isLoading"
            />
            <div v-if="internalFormErrors.annex_name" class="invalid-feedback">
              {{ internalFormErrors.annex_name }}
            </div>
          </div>
          <div class="mb-3">
            <label for="service" class="form-label">Service:</label>
            <select
              id="service"
              v-model="internalForm.service_id"
              class="form-select w-100"
              :class="{ 'is-invalid': internalFormErrors.service_id }"
              :disabled="isLoading"
            >
              <option :value="null" disabled>Select Service</option>
              <option
                v-for="service in services"
                :key="service.id"
                :value="service.id"
              >
                {{ service.name }}
              </option>
            </select>
            <div v-if="internalFormErrors.service_id" class="invalid-feedback">
              {{ internalFormErrors.service_id }}
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button
            type="button"
            class="btn btn-secondary"
            @click="handleCloseModal"
            :disabled="isLoading"
          >
            Cancel
          </button>
          <button
            type="button"
            class="btn btn-primary"
            @click="saveAnnex"
            :disabled="isLoading"
          >
            <span v-if="isLoading" class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
            {{ isEditing ? 'Update' : 'Save' }}
          </button>
        </div>
      </div>
    </div>
  </div>
  <div v-if="showModal" class="modal-backdrop fade" :class="{ 'show': showModal }"></div>
</template>

<style scoped>
/* Your existing styles remain the same */
.modal {
  background-color: rgba(0, 0, 0, 0.5);
  overflow-x: hidden;
  overflow-y: auto;
  position: fixed;
  top: 0;
  left: 0;
  z-index: 1050;
  width: 100%;
  height: 100%;
  outline: 0;
  display: none;
}

.modal.show {
  display: block;
}

.modal-dialog {
  position: relative;
  width: auto;
  margin: 1.75rem auto;
  pointer-events: none;
}

.modal.fade .modal-dialog {
  transition: transform 0.3s ease-out;
  transform: translate(0, -50px);
}

.modal.show .modal-dialog {
  transform: none;
}

.modal-backdrop {
    position: fixed;
    top: 0;
    left: 0;
    z-index: 1040;
    width: 100vw;
    height: 100vh;
    background-color: #000;
    opacity: 0.5;
}

.modal-backdrop.fade {
    opacity: 0;
    transition: opacity 0.15s linear;
}

.modal-backdrop.show {
    opacity: 0.5;
}

.description-scrollable {
  max-height: 10rem;
  overflow-y: auto;
}

.gap-2 {
  gap: 0.5rem;
}

.spinner-border-sm {
  width: 1rem;
  height: 1rem;
}

.form-control.is-invalid,
.form-select.is-invalid {
  border-color: #dc3545;
  padding-right: calc(1.5em + 0.75rem);
    width: 100%;
}

.invalid-feedback {
  display: block;
  width: 100%;
  margin-top: 0.25rem;
  font-size: 0.875em;
  color: #dc3545;
}
</style>
