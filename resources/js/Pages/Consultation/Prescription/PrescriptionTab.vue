<script setup>
import { ref, watch, defineEmits, defineProps, onMounted, onUnmounted } from 'vue'; // Added onMounted, onUnmounted for dropdown listener
import axios from 'axios';
import MedicationModal from '../../../Pages/Consultation/Prescription/MedicationModal.vue';
import PrescriptionHistoryTab from '../Prescription/PrescriptionHistory.vue';
import { useToastr } from '../../../Components/toster';

const toaster = useToastr();

const props = defineProps({
  consultationData: {
    type: Object,
    default: () => ({})
  },
  patientId: {
    type: [Number, String],
    required: true
  }
});

const emit = defineEmits(['update:consultation-data']);

// Main prescription data structure
const prescriptionDetails = ref({
  patientAge: null,
  patientWeight: null,
  medications: [],
});

const loading = ref(false); // Used for general loading state of actions
const isDropdownOpen = ref(false); // For controlling the print/pdf dropdown

// Function to save the prescription data locally (emits to parent)
// In PrescriptionTab.vue, modify the savePrescriptionData method:
const savePrescriptionData = async () => {
  try {
    loading.value = true;
    
    const response = await axios.post('/api/prescriptions', {
      patient_id: props.patientId,
      appointment_id: props.appointmentId,
      patient_age: prescriptionDetails.value.patientAge,
      patient_weight: prescriptionDetails.value.patientWeight,
      medications: prescriptionDetails.value.medications.map(med => ({
        cdActiveSubstance: med.cdActiveSubstance,
        brandName: med.brandName,
        pharmaceuticalForm: med.pharmaceuticalForm,
        dosePerIntake: med.dosePerIntake,
        numIntakesPerDay: med.numIntakesPerDay,
        durationOrBoxes: med.durationOrBoxes
      }))
    });

    if (response.data.success) {
      toaster.success('Prescription saved successfully!');
      return response.data;
    }
  } catch (error) {
    console.error('Error saving prescription:', error);
    toaster.error('Failed to save prescription.');
    throw error;
  } finally {
    loading.value = false;
  }
};
// Function to trigger PDF generation (usually a new tab with PDF)
const generatePdf = async () => {
    isDropdownOpen.value = false;
    if (!prescriptionDetails.value.medications.length) {
        toaster.warning('Please add at least one medication.');
        return;
    }

    try {
        const response = await savePrescriptionData();
        if (response.success) {
            // Download the PDF
            window.location.href = `/api/prescriptions/${response.prescription.id}/download-pdf`;
            toaster.success('PDF generated successfully!');
        }
    } catch (error) {
        console.error('Error generating PDF:', error);
        toaster.error('Failed to generate PDF');
    }
};

// Function to trigger direct printing (opens a new tab for printing)
const printCurrentPrescription = async () => {
    isDropdownOpen.value = false;
    if (!prescriptionDetails.value.medications.length) {
        toaster.warning('Please add at least one medication.');
        return;
    }

    try {
        const response = await savePrescriptionData();
        if (response.success) {
            // Open print view in new window
            window.open(`/api/prescriptions/${response.prescription.id}/print`, '_blank');
            toaster.success('Prescription opened for printing!');
        }
    } catch (error) {
        console.error('Error preparing print:', error);
        toaster.error('Failed to prepare prescription for printing');
    }
};

// Modal state
const showModal = ref(false);
const currentMedicationToEdit = ref(null);
const editingIndex = ref(null);

watch(() => props.consultationData, (newVal) => {
  if (newVal.prescription) {
    prescriptionDetails.value.patientAge = newVal.prescription.patientAge ?? null;
    prescriptionDetails.value.patientWeight = newVal.prescription.patientWeight ?? null;
    prescriptionDetails.value.medications = newVal.prescription.medications ?? [];
  }
}, { immediate: true, deep: true });

watch(prescriptionDetails, (newVal) => {
  emit('update:consultation-data', { prescription: newVal });
}, { deep: true });

const showAddMedicationModal = () => {
  currentMedicationToEdit.value = null;
  editingIndex.value = null;
  showModal.value = true;
};

const editMedication = (index) => {
  editingIndex.value = index;
  currentMedicationToEdit.value = prescriptionDetails.value.medications[index]; // Corrected typo here
  showModal.value = true;
};

const handleSaveMedication = (medicationData) => {
  if (editingIndex.value !== null) {
    prescriptionDetails.value.medications[editingIndex.value] = { ...medicationData };
  } else {
    prescriptionDetails.value.medications.push({ ...medicationData });
  }
  closeModal();
};

const removeMedication = (index) => {
  if (confirm('Are you sure you want to remove this medication?')) {
    prescriptionDetails.value.medications.splice(index, 1);
  }
};

const closeModal = () => {
  showModal.value = false;
  currentMedicationToEdit.value = null;
  editingIndex.value = null;
};

// Toggle dropdown visibility
const toggleDropdown = () => {
  isDropdownOpen.value = !isDropdownOpen.value;
};

// Close dropdown if clicking outside
const closeDropdown = (event) => {
  // Check if the click occurred outside the dropdown button itself and the dropdown menu
  if (isDropdownOpen.value && event.target.closest('.dropdown-container') === null) {
    isDropdownOpen.value = false;
  }
};

// Add/remove event listener for closing dropdown on outside click
onMounted(() => {
  document.addEventListener('click', closeDropdown);
});

onUnmounted(() => {
  document.removeEventListener('click', closeDropdown);
});

</script>

<template>
  <div class="prescription-tab-container">
    <div class="header-actions">
      <h2>Prescription</h2>
      <div class="button-group">
        <button type="button" class="btn btn-secondary me-2" @click="savePrescriptionData" :disabled="loading">
          <i class="fas fa-save me-1"></i> Save
        </button>
        <div class="dropdown-container">
          <button 
            type="button" 
            class="btn btn-primary" 
            @click="toggleDropdown" 
            :disabled="loading || prescriptionDetails.medications.length === 0"
          >
            <i class="fas fa-file-export me-1"></i> Export 
            <i :class="['fas', isDropdownOpen ? 'fa-caret-up' : 'fa-caret-down', 'ms-1']"></i>
          </button>
          <div v-if="isDropdownOpen" class="dropdown-menu show">
            <button class="dropdown-item" @click="generatePdf">
              <i class="fas fa-file-pdf me-2"></i>Generate PDF
            </button>
            <button class="dropdown-item" @click="printCurrentPrescription">
              <i class="fas fa-print me-2"></i>Print
            </button>
          </div>
        </div>
      </div>
    </div>

    <div class="card premium-card mb-4">
      <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-user-tag me-2"></i>Patient Specific Details</h5>
      </div>
      <div class="card-body">
        <div class="row">
          <div class="col-md-6 mb-3">
            <label for="patientAge" class="form-label">Patient's Age (optional)</label>
            <input type="number" class="form-control" id="patientAge" v-model.number="prescriptionDetails.patientAge" placeholder="e.g., 5 years old">
          </div>
          <div class="col-md-6 mb-3">
            <label for="patientWeight" class="form-label">Patient's Weight (optional)</label>
            <input type="number" class="form-control" id="patientWeight" v-model.number="prescriptionDetails.patientWeight" placeholder="e.g., 20 kg">
          </div>
        </div>
      </div>
    </div>

    <div class="card premium-card mb-4 ">
      <div class="header-actions p-3 d-flex justify-content-between align-items-center ">
        <h5 class="mb-0"><i class="fas fa-pills me-2"></i>Medication Details</h5>
        <button type="button" class="btn btn-primary btn-sm" @click="showAddMedicationModal">
          <i class="fas fa-plus me-2"></i>Add Medication 
        </button>
      </div>
      <div class="card-body">
        <div v-if="prescriptionDetails.medications.length === 0" class="empty-state-message">
          <i class="fas fa-capsules fa-2x text-muted mb-2"></i>
          <p>No medications added yet. Click "Add Medication" above.</p>
        </div>
        <div v-else class="medication-list-group">
          <div
            v-for="(med, index) in prescriptionDetails.medications"
            :key="index"
            class="medication-list-item d-flex justify-content-between align-items-center"
            @click="editMedication(index)"
          >
            <div class="medication-info">
              <strong>{{ med.cdActiveSubstance }}</strong>
              <span v-if="med.brandName" class="brand-name"> ({{ med.brandName }})</span>
              <div class="dosage-info">
                {{ med.dosePerIntake }} - {{ med.numIntakesPerDay }} for {{ med.durationOrBoxes }}
              </div>
            </div>
            <button type="button" class="btn btn-sm btn-outline-danger remove-btn" @click.stop="removeMedication(index)">
              <i class="fas fa-trash"></i>
            </button>
          </div>
        </div>
      </div>
    </div>

    <MedicationModal
      :is-visible="showModal"
      :medication-to-edit="currentMedicationToEdit"
      @close="closeModal"
      @save="handleSaveMedication"
    />

    <div class="card premium-card">
      <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-history me-2"></i>History of Prescriptions</h5>
      </div>
      <div class="card-body">
        <PrescriptionHistoryTab :patient-id="patientId" />
      </div>
    </div>
  </div>
</template>

<style scoped>
/* Main Container and Header */
.prescription-tab-container {
  padding: 20px;
  background-color: #f9f9f9;
  border-radius: 8px;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
}

.header-actions {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 25px;
  padding-bottom: 15px;
  border-bottom: 1px solid #e0e0e0;
}

.header-actions h2 {
  font-size: 1.8rem;
  color: #333;
  margin: 0;
}

.header-actions h5 {
    color: #007bff;
    font-weight: 600;
    font-size: 1.15rem;
}

.button-group {
  display: flex;
  gap: 10px; /* Spacing between buttons/dropdown */
}

.button-group .btn {
  font-weight: 500;
  padding: 8px 18px;
  border-radius: 6px;
}

/* Dropdown specific styles */
.dropdown-container {
  position: relative;
  display: inline-block; /* Essential for correct positioning */
}

.dropdown-menu {
  position: absolute;
  top: 100%; /* Position below the button */
  right: 0; /* Align to the right of the button */
  z-index: 1000; /* Ensure it appears above other content */
  min-width: 160px;
  padding: 8px 0;
  margin: 2px 0 0;
  font-size: 1rem;
  color: #212529;
  text-align: left;
  list-style: none;
  background-color: #fff;
  background-clip: padding-box;
  border: 1px solid rgba(0,0,0,.15);
  border-radius: 0.25rem;
  box-shadow: 0 0.5rem 1rem rgba(0,0,0,.175);
}

.dropdown-item {
  display: block;
  width: 100%;
  padding: 8px 16px;
  clear: both;
  font-weight: 400;
  color: #212529;
  text-align: inherit;
  white-space: nowrap;
  background-color: transparent;
  border: 0;
  cursor: pointer;
}

.dropdown-item:hover, .dropdown-item:focus {
  color: #16181b;
  text-decoration: none;
  background-color: #f8f9fa;
}


/* Premium Card Styling */
.premium-card {
  border: 1px solid #e0e0e0;
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
}

.card-header {
  background-color: #f8fafd;
  padding: 15px 20px;
  border-bottom: 1px solid #e0e0e0; /* Added border to header */
  border-top-left-radius: 8px; /* Ensure rounded corners */
  border-top-right-radius: 8px; /* Ensure rounded corners */
}

.card-header h5 {
  color: #007bff;
  font-weight: 600;
  font-size: 1.15rem;
}

.card-body {
  padding: 20px;
}

/* Input Fields */
.form-label {
  font-weight: 500;
  color: #555;
  margin-bottom: 5px;
  font-size: 0.95rem;
}

.form-control {
  border-radius: 5px;
  border: 1px solid #ced4da;
  padding: 0.75rem 1rem;
}

/* Medication List */
.medication-list-group {
  border: 1px solid #e0e0e0;
  border-radius: 8px;
  overflow: hidden;
}

.medication-list-item {
  padding: 15px 20px;
  border-bottom: 1px solid #f0f0f0;
  cursor: pointer;
  background-color: #fff;
  transition: background-color 0.2s ease;
}

.medication-list-item:last-child {
  border-bottom: none;
}

.medication-list-item:hover {
  background-color: #f8fafd;
}

.medication-info strong {
  color: #333;
  font-size: 1.1rem;
}

.medication-info .brand-name {
  color: #666;
  font-size: 0.95rem;
}

.medication-info .dosage-info {
  font-size: 0.9rem;
  color: #888;
  margin-top: 3px;
}

.remove-btn {
  font-size: 1rem;
  padding: 8px 12px;
  border-radius: 5px;
  transition: background-color 0.2s ease, color 0.2s ease;
}

.remove-btn:hover {
  background-color: #dc3545;
  color: white;
}

/* Empty State Message */
.empty-state-message {
  text-align: center;
  padding: 30px;
  color: #888;
  background-color: #fefefe;
  border-radius: 5px;
}

.empty-state-message i {
  font-size: 2.5rem;
  margin-bottom: 10px;
  color: #ccc;
}

.empty-state-message p {
  font-size: 1rem;
  margin: 0;
}

/* Buttons */
.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 5px;
  font-weight: 600;
  border-radius: 6px;
  padding: 0.6rem 1.2rem;
  transition: all 0.2s ease-in-out;
}

.btn-primary {
  background-color: #007bff;
  border-color: #007bff;
  color: white;
}
.btn-primary:hover {
  background-color: #0056b3;
  border-color: #0056b3;
}

.btn-success {
  background-color: #28a745;
  border-color: #28a745;
  color: white;
}
.btn-success:hover {
  background-color: #218838;
  border-color: #1e7e34;
}

.btn-secondary {
  background-color: #6c757d;
  border-color: #6c757d;
  color: white;
}
.btn-secondary:hover {
  background-color: #5a6268;
  border-color: #545b62;
}

.btn-outline-danger {
  color: #dc3545;
  border-color: #dc3545;
  background-color: transparent;
}
.btn-outline-danger:hover {
  background-color: #dc3545;
  color: white;
}
</style>