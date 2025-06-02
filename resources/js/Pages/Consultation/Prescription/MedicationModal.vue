<script setup>
import { ref, watch } from 'vue';

const props = defineProps({
  isVisible: {
    type: Boolean,
    default: false
  },
  medicationToEdit: {
    type: Object,
    default: null
  }
});

const emit = defineEmits(['close', 'save']);

const medicationForm = ref({});
const isEditing = ref(false);
const validationErrors = ref({}); // New: To store validation error messages

// Helper function to initialize a blank medication object
function initializeMedication() {
  return {
    cdActiveSubstance: '',
    brandName: '',
    pharmaceuticalForm: '',
    dosePerIntake: '',
    numIntakesPerDay: '',
    durationOrBoxes: ''
  };
}

// Watchers for modal visibility and edit state
watch(() => props.medicationToEdit, (newVal) => {
  if (newVal) {
    medicationForm.value = JSON.parse(JSON.stringify(newVal));
    isEditing.value = true;
  } else {
    medicationForm.value = initializeMedication();
    isEditing.value = false;
  }
  validationErrors.value = {}; // Clear errors on form initialization
}, { immediate: true, deep: true });

watch(() => props.isVisible, (newVal) => {
  if (newVal && !props.medicationToEdit) {
    medicationForm.value = initializeMedication();
    isEditing.value = false;
  }
  validationErrors.value = {}; // Clear errors when modal opens
});

const closeModal = () => {
  emit('close');
  validationErrors.value = {}; // Clear errors when closing
};

const validateForm = () => {
  validationErrors.value = {};
  let isValid = true;

  if (!medicationForm.value.cdActiveSubstance.trim()) {
    validationErrors.value.cdActiveSubstance = 'Common Denomination is required.';
    isValid = false;
  }
  if (!medicationForm.value.pharmaceuticalForm.trim()) {
    validationErrors.value.pharmaceuticalForm = 'Pharmaceutical Form is required.';
    isValid = false;
  }
  if (!medicationForm.value.dosePerIntake.trim()) {
    validationErrors.value.dosePerIntake = 'Dose Per Intake is required.';
    isValid = false;
  }
  if (!medicationForm.value.numIntakesPerDay.trim()) {
    validationErrors.value.numIntakesPerDay = 'Number of Intakes is required.';
    isValid = false;
  }
  if (!medicationForm.value.durationOrBoxes.trim()) {
    validationErrors.value.durationOrBoxes = 'Duration or Boxes is required.';
    isValid = false;
  }

  return isValid;
};

const saveMedication = () => {
  if (validateForm()) {
    emit('save', medicationForm.value);
  }
};
</script>
<template>
  <div v-if="isVisible" class="modal-overlay">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">{{ isEditing ? 'Edit Medication' : 'Add New Medication' }}</h5>
          <button type="button" class="btn-close" @click="closeModal">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="modal-body">
          <form @submit.prevent="saveMedication">
            <div class="mb-3">
              <label for="modal-cdActiveSubstance" class="form-label">
                <i class="fas fa-pills me-2 mr-2"></i>Common Denomination (CD) of Active Substance <span class="required-star">*</span>
              </label>
              <input
                type="text"
                class="form-control"
                :class="{ 'is-invalid': validationErrors.cdActiveSubstance }"
                id="modal-cdActiveSubstance"
                v-model="medicationForm.cdActiveSubstance"
                required
              >
              <div class="invalid-feedback" v-if="validationErrors.cdActiveSubstance">
                {{ validationErrors.cdActiveSubstance }}
              </div>
            </div>

            <div class="mb-3">
              <label for="modal-brandName" class="form-label">
                <i class="fas fa-tag me-2 mr-2"></i>Brand Name (optional)
              </label>
              <input type="text" class="form-control" id="modal-brandName" v-model="medicationForm.brandName">
              <div class="form-text text-muted">e.g., Doliprane, Tylenol</div>
            </div>

            <div class="mb-3">
              <label for="modal-pharmaceuticalForm" class="form-label">
                <i class="fas fa-capsules me-2 mr-2"></i>Pharmaceutical Form <span class="required-star">*</span>
              </label>
              <input
                type="text"
                class="form-control"
                :class="{ 'is-invalid': validationErrors.pharmaceuticalForm }"
                id="modal-pharmaceuticalForm"
                v-model="medicationForm.pharmaceuticalForm"
                placeholder="e.g., Tablet, Syrup, Eye Drops"
                required
              >
              <div class="invalid-feedback" v-if="validationErrors.pharmaceuticalForm">
                {{ validationErrors.pharmaceuticalForm }}
              </div>
            </div>

            <div class="row">
              <div class="col-md-6 mb-3">
                <label for="modal-dosePerIntake" class="form-label">
                  <i class="fas fa-prescription-bottle me-2 mr-2"></i>Dose Per Intake <span class="required-star">*</span>
                </label>
                <input
                  type="text"
                  class="form-control"
                  :class="{ 'is-invalid': validationErrors.dosePerIntake }"
                  id="modal-dosePerIntake"
                  v-model="medicationForm.dosePerIntake"
                  placeholder="e.g., 500mg, 1 tablet, 5ml"
                  required
                >
                <div class="invalid-feedback" v-if="validationErrors.dosePerIntake">
                  {{ validationErrors.dosePerIntake }}
                </div>
              </div>
              <div class="col-md-6 mb-3">
                <label for="modal-numIntakesPerDay" class="form-label">
                  <i class="fas fa-redo-alt me-2 mr-2"></i>Number of Intakes Per Day <span class="required-star">*</span>
                </label>
                <input
                  type="text"
                  class="form-control"
                  :class="{ 'is-invalid': validationErrors.numIntakesPerDay }"
                  id="modal-numIntakesPerDay"
                  v-model="medicationForm.numIntakesPerDay"
                  placeholder="e.g., 2 times/day, every 8 hours"
                  required
                >
                <div class="invalid-feedback" v-if="validationErrors.numIntakesPerDay">
                  {{ validationErrors.numIntakesPerDay }}
                </div>
              </div>
            </div>

            <div class="mb-3">
              <label for="modal-durationOrBoxes" class="form-label">
                <i class="fas fa-calendar-alt me-2 mr-2"></i>Duration of Treatment OR Number of Boxes <span class="required-star">*</span>
              </label>
              <input
                type="text"
                class="form-control"
                :class="{ 'is-invalid': validationErrors.durationOrBoxes }"
                id="modal-durationOrBoxes"
                v-model="medicationForm.durationOrBoxes"
                placeholder="e.g., for 7 days, 3 months, 2 boxes"
                required
              >
              <div class="invalid-feedback" v-if="validationErrors.durationOrBoxes">
                {{ validationErrors.durationOrBoxes }}
              </div>
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" @click="closeModal">
            <i class="fas fa-times-circle me-2 mr-2"></i>Cancel
          </button>
          <button type="button" class="btn btn-success" @click="saveMedication">
            <i :class="isEditing ? 'fas fa-save' : 'fas fa-plus-circle'" class="me-2 mr-2"></i>
            {{ isEditing ? 'Save Changes' : 'Add Medication' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
  
 
<style scoped>
/* Modal styles (moved from previous component) */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0, 0, 0, 0.6);
  display: flex;
  justify-content: center;
  align-items: center;
  backdrop-filter: blur(4px);
  z-index: 100000;
  animation: fadeIn 0.3s ease-out; /* Add fade-in animation */
}

.modal-dialog {
  padding: 20px;
  border-radius: 12px; /* Slightly more rounded */
  width: 90%;
  max-width: 650px; /* Slightly wider modal */
  
  transform: translateY(-20px); /* Initial position for animation */
  animation: slideIn 0.3s ease-out forwards; /* Slide-in animation */
}

.modal-content {
  display: flex;
  flex-direction: column;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding-bottom: 15px;
  border-bottom: 1px solid #e0e0e0; /* Lighter border */
  margin-bottom: 25px; /* More space */
}

.modal-title {
  margin: 0;
  font-size: 1.6rem; /* Slightly larger title */
  color: #2c3e50; /* Darker, more professional color */
  font-weight: 600; /* Bolder title */
}

.btn-close {
  background: none;
  border: none;
  font-size: 1.8rem; /* Larger close icon */
  cursor: pointer;
  color: #888;
  transition: color 0.2s ease;
}
.btn-close:hover {
  color: #333;
}

.modal-body {
  padding-bottom: 25px; /* More vertical padding */
}

.form-label {
  font-weight: 500; /* Slightly bolder labels */
  color: #495057;
  margin-bottom: 8px; /* More space below labels */
  display: flex; /* Align icon and text */
  align-items: center;
}

.form-control {
  border-radius: 6px; /* Slightly more rounded inputs */
  padding: 10px 15px; /* More padding inside inputs */
  border: 1px solid #ced4da;
  box-shadow: inset 0 1px 2px rgba(0,0,0,.075); /* Subtle inner shadow */
  transition: border-color 0.2s ease, box-shadow 0.2s ease;
}

.form-control:focus {
  border-color: #80bdff;
  box-shadow: 0 0 0 0.25rem rgba(0, 123, 255, 0.25);
  outline: none;
}

.is-invalid {
  border-color: #dc3545;
  padding-right: calc(1.5em + 0.75rem); /* Space for icon */
  background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12 12' width='12' height='12' fill='none' stroke='%23dc3545'%3e%3ccircle cx='6' cy='6' r='4.5'/%3e%3cpath stroke-linejoin='round' d='M5.8 3.6h.4L6 6.5z'/%3e%3ccircle cx='6' cy='8.2' r='.6' fill='%23dc3545' stroke='none'/%3e%3c/svg%3e"); /* Error icon */
  background-repeat: no-repeat;
  background-position: right calc(0.375em + 0.1875rem) center;
  background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem);
}

.invalid-feedback {
  color: #dc3545;
  font-size: 0.875em;
  margin-top: 0.25rem;
}

.form-text.text-muted {
  font-size: 0.85em;
  color: #6c757d !important; /* Ensure it's muted */
  margin-top: 5px;
}

.required-star {
  color: #dc3545;
  margin-left: 5px;
  font-size: 0.9em;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 15px; /* More space between buttons */
  padding-top: 20px;
  border-top: 1px solid #e0e0e0; /* Lighter border */
  margin-top: 25px;
}

.btn {
  padding: 10px 20px;
  border-radius: 8px; /* Consistent border radius */
  font-weight: 500;
  transition: all 0.2s ease;
  display: flex;
  align-items: center;
  justify-content: center;
}

.btn-secondary {
  background-color: #6c757d;
  border-color: #6c757d;
  color: white;
}
.btn-secondary:hover {
  background-color: #5a6268;
  border-color: #545b62;
  transform: translateY(-1px);
}
.btn-success {
  background-color: #28a745;
  border-color: #28a745;
  color: white;
}
.btn-success:hover {
  background-color: #218838;
  border-color: #1e7e34;
  transform: translateY(-1px);
}

/* Animations */
@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}
@keyframes slideIn {
  from { transform: translateY(-30px); opacity: 0; }
  to { transform: translateY(0); opacity: 1; }
}
</style>